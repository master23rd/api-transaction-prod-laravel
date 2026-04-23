<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #6F4E37;
            margin: 0;
            font-size: 28px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .code-container {
            background-color: #f8f9fa;
            border: 2px dashed #6F4E37;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #6F4E37;
            font-family: monospace;
        }
        .expiry {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 12px;
            margin-top: 20px;
            font-size: 14px;
            color: #856404;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>KoperasiQu OTP</h1>
        </div>

        <p class="greeting">Hi {{ $userName }},</p>

        <p>You requested a verification code for your KoperasiQu account. Use the code below to complete your verification:</p>

        <div class="code-container">
            <div class="code">{{ $code }}</div>
            <p class="expiry">This code expires in {{ $expiryMinutes }} minutes</p>
        </div>

        <p>If you didn't request this code, you can safely ignore this email. Someone else might have typed your email address by mistake.</p>

        <div class="warning">
            <strong>Security tip:</strong> Never share this code with anyone. KoperasiQu staff will never ask for your verification code.
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} KoperasiQu. All rights reserved.</p>
            <p>This is an automated message, please do not reply.</p>
        </div>
    </div>
</body>
</html>
