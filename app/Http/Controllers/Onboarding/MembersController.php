<?php

namespace App\Http\Controllers\Onboarding;

use App\Exports\MembersTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Onboarding\Concerns\AdvancesOnboarding;
use App\Imports\MembersImport;
use App\Mail\MemberAccountCreated;
use App\Models\User;
use App\Support\RosterRowValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class MembersController extends Controller
{
    use AdvancesOnboarding;

    public function show(Request $request): Response
    {
        $organization = $this->draftOrganization($request);

        $members = $organization->members()
            ->with(['user', 'roles'])
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'student_id' => $member->membership_number,
                'roles' => $member->roles->pluck('name'),
            ]);

        $roles = $organization->roles()
            ->where('is_system', false)
            ->get(['id', 'name']);

        return Inertia::render('Onboarding/Members', [
            'organization' => $organization,
            'members' => $members,
            'roles' => $roles,
            'fromReview' => $request->query('from') === 'review',
        ]);
    }

    /**
     * Parses an uploaded Excel file OR a raw `rows` array (manual entry) and
     * validates every row against the organization's current roster —
     * without persisting anything. Plain JSON endpoint (not an Inertia
     * visit) so the wizard page can update its preview table in place.
     */
    public function validateImport(Request $request)
    {
        $organization = $this->draftOrganization($request);

        if ($request->hasFile('file')) {
            $request->validate([
                'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            ]);

            $import = new MembersImport;
            Excel::import($import, $request->file('file'));
            $rows = $import->rows;
        } else {
            $rows = $request->input('rows', []);
        }

        $results = RosterRowValidator::validateBatch($rows, $organization);

        return response()->json(['results' => $results]);
    }

    public function commit(Request $request)
    {
        $organization = $this->draftOrganization($request);

        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.first_name' => ['required', 'string'],
            'rows.*.m_i' => ['nullable', 'string'],
            'rows.*.surname' => ['required', 'string'],
            'rows.*.student_id' => ['required', 'string'],
            'rows.*.email' => ['required', 'email'],
            'rows.*.role_id' => ['nullable', 'string'],
        ]);

        $results = RosterRowValidator::validateBatch($validated['rows'], $organization);

        if (collect($results)->contains(fn ($r) => $r['status'] === 'error')) {
            return back()->withErrors([
                'rows' => 'Some rows still have errors — please fix them before adding to the roster.',
            ])->withInput();
        }

        // Roles are assigned per row, not batch-wide — validate each chosen
        // role actually belongs to this org and isn't the hidden system role.
        $orgRoles = $organization->roles()->where('is_system', false)->get()->keyBy('id');

        $newAccounts = [];

        DB::transaction(function () use ($results, $organization, $orgRoles, &$newAccounts) {
            foreach ($results as $result) {
                $row = $result['row'];
                $fullName = trim($row['first_name'].' '.($row['m_i'] !== '' ? $row['m_i'].'. ' : '').$row['surname']);

                if ($result['status'] === 'new_user') {
                    $password = Str::password(16);

                    $user = User::create([
                        'name' => $fullName,
                        'email' => $row['email'],
                        'password' => Hash::make($password),
                        'email_verified_at' => now(),
                        'must_change_password' => true,
                    ]);

                    $newAccounts[] = ['user' => $user, 'password' => $password];
                } else {
                    $user = User::whereRaw('lower(email) = ?', [strtolower($row['email'])])->firstOrFail();
                }

                $member = $organization->members()->create([
                    'user_id' => $user->id,
                    'membership_number' => $row['student_id'],
                    'is_active' => true,
                ]);

                $role = $orgRoles->get($row['role_id'] ?? null);

                if ($role) {
                    $member->roles()->attach($role->id);
                }
            }
        });

        $failedEmails = [];

        foreach ($newAccounts as $account) {
            try {
                Mail::to($account['user']->email)->send(
                    new MemberAccountCreated($organization, Auth::user(), $account['user']->email, $account['password'])
                );
            } catch (\Throwable $e) {
                report($e);
                $failedEmails[] = $account['user']->email;
            }
        }

        $this->advanceTo($organization, 'features');

        $response = $this->redirectToNextStep($request, 'onboarding.features.show');

        // The accounts and memberships above are real and already committed —
        // a failed email doesn't undo that, but the Adviser needs to know
        // exactly who didn't get their credentials so they can use "Resend
        // Login Email" on the Members page, rather than the wizard silently
        // implying every invite went out.
        if (! empty($failedEmails)) {
            $response->with('warning', 'Accounts were created, but the login-credential email could not be sent to: '.implode(', ', $failedEmails).'. Use "Resend Login Email" on the Members page once mail delivery is working.');
        }

        return $response;
    }

    public function skip(Request $request)
    {
        $organization = $this->draftOrganization($request);

        $this->advanceTo($organization, 'features');

        return $this->redirectToNextStep($request, 'onboarding.features.show');
    }

    public function destroy(Request $request, string $member)
    {
        $organization = $this->draftOrganization($request);

        $organization->members()->where('id', $member)->delete();

        return back();
    }

    public function template()
    {
        return Excel::download(new MembersTemplateExport, 'orgflow-members-template.xlsx');
    }
}
