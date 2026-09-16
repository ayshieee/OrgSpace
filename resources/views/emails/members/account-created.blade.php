<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827; line-height: 1.6;">
    <p>Hello,</p>

    <p>You have been added to {{ $organizationName }} on OrgSpace by {{ $creatorName }}.</p>

    <p>Your OrgSpace account has already been created, so you do not need to sign up. You can use the account credentials below to log in directly.</p>

    <p><strong>Your Login Information</strong></p>

    <p>
        Email: {{ $memberEmail }}<br>
        Password: {{ $password }}
    </p>

    <p>You can log in to OrgSpace using the credentials provided above.</p>

    <p>For your security, please keep your login information confidential and do not share your password with others.</p>

    <p>Welcome to {{ $organizationName }} on OrgSpace!</p>

    <p>Best regards,<br>OrgSpace Team</p>
</body>
</html>
