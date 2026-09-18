<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827; line-height: 1.6;">
    <p>Hi {{ $recipientName }},</p>

    <p>This is a reminder about an announcement from <strong>{{ $organizationName }}</strong> on OrgSpace that you haven't opened yet.</p>

    <table style="border-left: 4px solid #2E418D; margin: 16px 0;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding: 8px 16px;">
                <p style="margin: 0 0 4px; font-weight: bold;">{{ $title }}</p>
                <p style="margin: 0; color: #4B5563;">{{ \Illuminate\Support\Str::limit($body, 240) }}</p>
                <p style="margin: 8px 0 0; font-size: 12px; color: #6B7280;">Posted by {{ $authorName }}</p>
            </td>
        </tr>
    </table>

    <p><a href="{{ $url }}">View this announcement on OrgSpace</a></p>

    <p>Best regards,<br>OrgSpace</p>
</body>
</html>
