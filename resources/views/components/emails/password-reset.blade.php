{{-- resources/views/components/emails/password-reset.blade.php --}}
{{-- Follows same template pattern as components.emails.purchase-order --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">

    <!-- Main Container -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f9; padding: 30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Header Banner -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 40px; border-radius: 8px 8px 0 0; text-align: center;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="text-align: center;">
                                        <div style="width: 60px; height: 60px; margin: 0 auto 15px; background: rgba(255,255,255,0.2); border-radius: 50%; line-height: 60px; text-align: center;">
                                            <span style="font-size: 28px; color: #ffffff;">&#128274;</span>
                                        </div>
                                        <h1 style="color: #ffffff; font-size: 22px; font-weight: 700; margin: 0 0 5px;">
                                            {{ config('app.name', 'TMS') }}
                                        </h1>
                                        <p style="color: rgba(255,255,255,0.85); font-size: 13px; margin: 0;">
                                            Terminal Management System
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 40px;">

                            <!-- Document Title -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px; border-bottom: 2px solid #667eea; padding-bottom: 15px;">
                                <tr>
                                    <td>
                                        <h2 style="color: #1e293b; font-size: 20px; font-weight: 700; margin: 0;">
                                            Password Reset Request
                                        </h2>
                                    </td>
                                    <td style="text-align: right;">
                                        <span style="color: #64748b; font-size: 13px;">
                                            {{ now()->format('d M Y, h:i A') }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Greeting -->
                            <p style="color: #334155; font-size: 15px; line-height: 1.6; margin: 0 0 20px;">
                                Hello <strong>{{ $user->name ?? 'User' }}</strong>,
                            </p>

                            <p style="color: #334155; font-size: 15px; line-height: 1.6; margin: 0 0 25px;">
                                We received a request to reset the password associated with your TMS account
                                (<strong>{{ $user->email }}</strong>). Click the button below to set a new password.
                            </p>

                            <!-- Action Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $resetUrl }}"
                                           style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; font-size: 16px; font-weight: 600; padding: 14px 40px; border-radius: 8px; text-decoration: none; letter-spacing: 0.5px;">
                                            &#128274; Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Details Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 6px 0; color: #64748b; font-size: 13px; width: 40%;">
                                                    <strong>Account:</strong>
                                                </td>
                                                <td style="padding: 6px 0; color: #1e293b; font-size: 13px;">
                                                    {{ $user->email }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 6px 0; color: #64748b; font-size: 13px;">
                                                    <strong>Link Expires In:</strong>
                                                </td>
                                                <td style="padding: 6px 0; color: #1e293b; font-size: 13px;">
                                                    {{ $expirationMinutes }} minutes
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 6px 0; color: #64748b; font-size: 13px;">
                                                    <strong>Requested At:</strong>
                                                </td>
                                                <td style="padding: 6px 0; color: #1e293b; font-size: 13px;">
                                                    {{ now()->format('d M Y, h:i A') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Fallback URL -->
                            <p style="color: #64748b; font-size: 12px; line-height: 1.6; margin: 20px 0 0;">
                                If the button above doesn't work, copy and paste this URL into your browser:
                            </p>
                            <p style="word-break: break-all; font-size: 12px; color: #667eea; margin: 5px 0 20px; padding: 10px; background: #f8fafc; border-radius: 4px; border: 1px solid #e2e8f0;">
                                {{ $resetUrl }}
                            </p>

                            <!-- Security Notice -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 15px 20px;">
                                        <p style="color: #92400e; font-size: 13px; line-height: 1.5; margin: 0;">
                                            <strong>&#9888; Security Notice:</strong>
                                            If you did not request a password reset, no further action is required.
                                            Your password will remain unchanged. If you suspect unauthorized access,
                                            please contact your system administrator immediately.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 25px 40px; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="color: #94a3b8; font-size: 12px; line-height: 1.6; margin: 0 0 8px;">
                                This is an automated email from <strong style="color: #cbd5e1;">{{ config('app.name', 'TMS') }}</strong> - Terminal Management System
                            </p>
                            <p style="color: #64748b; font-size: 11px; margin: 0;">
                                &copy; {{ date('Y') }} GRASP Software Solutions. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
