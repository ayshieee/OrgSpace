<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

/**
 * Validates a single roster row (First Name / M.I. / Surname / Student ID /
 * Email) against basic field rules plus organization-specific duplicate
 * checks, without persisting anything. Used identically by the "preview"
 * endpoint (no side effects) and, one more time, right before the "commit"
 * endpoint actually writes to the database.
 */
class RosterRowValidator
{
    /**
     * @param  array<int, array{first_name:string,m_i:string,surname:string,student_id:string,email:string}>  $rows
     * @return array<int, array{row: array, status: string, errors: array}>
     */
    public static function validateBatch(array $rows, Organization $organization): array
    {
        $seenEmails = [];

        return array_map(function (array $row) use ($organization, &$seenEmails) {
            $result = self::validateRow($row, $organization, $seenEmails);

            if ($result['status'] !== 'error' && $row['email'] !== '') {
                $seenEmails[] = strtolower($row['email']);
            }

            return $result;
        }, $rows);
    }

    protected static function validateRow(array $row, Organization $organization, array $seenEmails): array
    {
        $errors = [];

        $validator = Validator::make($row, [
            'first_name' => ['required', 'string', 'max:100'],
            'm_i' => ['nullable', 'string', 'max:5'],
            'surname' => ['required', 'string', 'max:100'],
            'student_id' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors[$field] = $messages[0];
            }

            return ['row' => $row, 'status' => 'error', 'errors' => $errors];
        }

        if (! self::domainAcceptsMail($row['email'])) {
            return [
                'row' => $row,
                'status' => 'error',
                'errors' => ['email' => "This email's domain doesn't appear to accept mail — double-check for typos."],
            ];
        }

        $email = strtolower($row['email']);

        if (in_array($email, $seenEmails, true)) {
            return [
                'row' => $row,
                'status' => 'error',
                'errors' => ['email' => 'This email appears more than once in this batch.'],
            ];
        }

        $existingUser = User::whereRaw('lower(email) = ?', [$email])->first();

        if ($existingUser) {
            $alreadyMember = $organization->members()
                ->where('user_id', $existingUser->id)
                ->exists();

            if ($alreadyMember) {
                return [
                    'row' => $row,
                    'status' => 'error',
                    'errors' => ['email' => 'This person is already a member of this organization.'],
                ];
            }

            return ['row' => $row, 'status' => 'existing_user', 'errors' => []];
        }

        return ['row' => $row, 'status' => 'new_user', 'errors' => []];
    }

    /**
     * A real, honest check: confirms the email's domain has mail-routing
     * DNS records at all (MX, or a bare A record as a legal fallback), which
     * catches typo'd/made-up domains before an account is provisioned for
     * them. This is NOT a mailbox-existence check — that requires an SMTP
     * RCPT TO probe, which is unreliable and widely blocked by mail
     * providers, so we don't pretend to do it. Fails open (treats as
     * acceptable) only if the DNS lookup itself errors — never on a
     * genuine "no such domain" result.
     */
    protected static function domainAcceptsMail(string $email): bool
    {
        $domain = substr(strrchr($email, '@'), 1);

        if (! $domain) {
            return false;
        }

        try {
            return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
        } catch (\Throwable $e) {
            report($e);

            return true;
        }
    }
}
