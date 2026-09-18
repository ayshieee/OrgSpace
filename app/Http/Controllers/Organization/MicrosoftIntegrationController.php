<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Models\OrgFile;
use App\Models\Organization;
use App\Models\OrganizationMicrosoftConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Real Microsoft Graph OAuth integration so an Office document can be opened
 * for editing in Word/Excel/PowerPoint for the web, backed by a copy of the
 * file in a OneDrive folder the connecting manager's Microsoft account owns.
 * There is no automatic two-way sync — pullUpdatedVersion() is the only way
 * an edit made in Office ever reaches OrgSpace's own storage, and it only
 * happens when a user explicitly clicks for it to happen.
 */
class MicrosoftIntegrationController extends Controller
{
    use ChecksOrganizationPermission;

    protected const AUTHORIZE_URL = 'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize';

    protected const TOKEN_URL = 'https://login.microsoftonline.com/%s/oauth2/v2.0/token';

    protected const GRAPH_BASE = 'https://graph.microsoft.com/v1.0';

    protected const SCOPES = 'openid profile email offline_access Files.ReadWrite User.Read';

    public function connect(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_files');

        abort_if(! config('services.microsoft.client_id'), 409, 'Microsoft integration is not configured yet.');

        $state = encrypt(['organization_id' => $organization->id, 'nonce' => Str::random(16)]);
        $request->session()->put('ms_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => route('microsoft.callback'),
            'response_mode' => 'query',
            'scope' => self::SCOPES,
            'state' => $state,
        ]);

        return redirect(sprintf(self::AUTHORIZE_URL, config('services.microsoft.tenant')).'?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = $request->query('state');
        $sessionState = $request->session()->pull('ms_oauth_state');

        abort_unless($state && $state === $sessionState, 403, 'Invalid or expired sign-in attempt.');

        try {
            $payload = decrypt($state);
        } catch (\Throwable) {
            abort(403, 'Invalid or expired sign-in attempt.');
        }

        $organization = Organization::findOrFail($payload['organization_id']);
        $membership = $this->membership($request, $organization);
        abort_unless($membership->hasPermission('manage_files'), 403);

        if ($request->query('error')) {
            return redirect()->route('organizations.files.index', $organization->id)
                ->with('error', 'Microsoft sign-in was cancelled or failed: '.$request->query('error_description', $request->query('error')));
        }

        $tokenResponse = Http::asForm()->post(sprintf(self::TOKEN_URL, config('services.microsoft.tenant')), [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $request->query('code'),
            'redirect_uri' => route('microsoft.callback'),
            'scope' => self::SCOPES,
        ]);

        if ($tokenResponse->failed()) {
            Log::warning('Microsoft token exchange failed', ['body' => $tokenResponse->body()]);

            return redirect()->route('organizations.files.index', $organization->id)
                ->with('error', 'Could not complete Microsoft sign-in. Please try again.');
        }

        $tokens = $tokenResponse->json();

        $me = Http::withToken($tokens['access_token'])->get(self::GRAPH_BASE.'/me')->json();

        OrganizationMicrosoftConnection::updateOrCreate(
            ['organization_id' => $organization->id],
            [
                'connected_by' => $request->user()->id,
                'ms_account_email' => $me['mail'] ?? $me['userPrincipalName'] ?? 'Unknown account',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'token_expires_at' => now()->addSeconds($tokens['expires_in']),
                'drive_folder_id' => null,
            ]
        );

        return redirect()->route('organizations.files.index', $organization->id)
            ->with('success', 'Connected to Microsoft.');
    }

    public function disconnect(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_files');

        $organization->microsoftConnection?->delete();

        return back()->with('success', 'Disconnected from Microsoft. Files already opened in OneDrive remain there.');
    }

    public function openInOffice(Request $request, Organization $organization, OrgFile $file): JsonResponse
    {
        $membership = $this->membership($request, $organization);

        abort_unless($file->organization_id === $organization->id && ! $file->is_folder, 404);
        abort_unless($file->canBeEditedBy($membership), 403);
        abort_unless(in_array($file->mime_type, FilesController::CONVERTIBLE_MIMES, true), 422);

        $connection = $organization->microsoftConnection;
        abort_if(! $connection, 409, 'This organization is not connected to Microsoft yet.');

        $accessToken = $this->freshAccessToken($connection);

        $folderId = $this->ensureAppFolder($connection, $accessToken);

        // Upload the current bytes as a new copy every time this is opened,
        // so edits always start from OrgSpace's latest stored version — the
        // drive item id is reused (overwritten in place) once one exists.
        $bytes = Storage::disk('local')->get($file->path);
        $itemPath = "{$folderId}:/{$file->name}:";

        $uploadResponse = Http::withToken($accessToken)
            ->withBody($bytes, $file->mime_type)
            ->put(self::GRAPH_BASE."/me/drive/items/{$itemPath}/content");

        if ($uploadResponse->failed()) {
            Log::warning('Microsoft upload failed', ['file_id' => $file->id, 'body' => $uploadResponse->body()]);
            abort(502, 'Could not prepare this document in Microsoft Office.');
        }

        $driveItem = $uploadResponse->json();

        $file->update([
            'ms_drive_item_id' => $driveItem['id'],
            'ms_web_edit_url' => $driveItem['webUrl'],
            'ms_copy_uploaded_at' => now(),
        ]);

        return response()->json(['edit_url' => $driveItem['webUrl']]);
    }

    public function pullUpdatedVersion(Request $request, Organization $organization, OrgFile $file): RedirectResponse
    {
        $membership = $this->membership($request, $organization);

        abort_unless($file->organization_id === $organization->id && ! $file->is_folder, 404);
        abort_unless($file->canBeEditedBy($membership), 403);
        abort_unless($file->ms_drive_item_id, 422, 'This file has never been opened in Office.');

        $connection = $organization->microsoftConnection;
        abort_if(! $connection, 409, 'This organization is not connected to Microsoft anymore.');

        $accessToken = $this->freshAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->get(self::GRAPH_BASE."/me/drive/items/{$file->ms_drive_item_id}/content");

        if ($response->failed()) {
            Log::warning('Microsoft pull failed', ['file_id' => $file->id, 'body' => $response->body()]);

            return back()->with('error', 'Could not pull the latest version from OneDrive.');
        }

        Storage::disk('local')->put($file->path, $response->body());

        $file->update([
            'size' => strlen($response->body()),
            'ms_last_pulled_at' => now(),
        ]);

        return back()->with('success', 'Pulled the latest edits from OneDrive into OrgSpace.');
    }

    protected function freshAccessToken(OrganizationMicrosoftConnection $connection): string
    {
        if (! $connection->isTokenExpired()) {
            return $connection->access_token;
        }

        $response = Http::asForm()->post(sprintf(self::TOKEN_URL, config('services.microsoft.tenant')), [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->refresh_token,
            'scope' => self::SCOPES,
        ]);

        abort_if($response->failed(), 502, 'Microsoft sign-in has expired. Please reconnect from Settings.');

        $tokens = $response->json();

        $connection->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? $connection->refresh_token,
            'token_expires_at' => now()->addSeconds($tokens['expires_in']),
        ]);

        return $connection->access_token;
    }

    protected function ensureAppFolder(OrganizationMicrosoftConnection $connection, string $accessToken): string
    {
        if ($connection->drive_folder_id) {
            return $connection->drive_folder_id;
        }

        $response = Http::withToken($accessToken)->post(self::GRAPH_BASE.'/me/drive/root/children', [
            'name' => 'OrgSpace Files',
            'folder' => new \stdClass,
            '@microsoft.graph.conflictBehavior' => 'rename',
        ]);

        abort_if($response->failed(), 502, 'Could not set up an OrgSpace folder in OneDrive.');

        $folderId = $response->json('id');
        $connection->update(['drive_folder_id' => $folderId]);

        return $folderId;
    }
}
