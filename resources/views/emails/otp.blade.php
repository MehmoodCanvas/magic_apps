<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your OTP Code</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f7;
            padding: 30px;
        }
        .container {
            max-width: 600px;
            background-color: #fff;
            margin: auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .otp {
            font-size: 32px;
            font-weight: bold;
            color: #2F855A;
            margin: 20px 0;
            letter-spacing: 4px;
        }
        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hello {{ $user->name ?? 'User' }},</h2>
        <p>Your One-Time Password (OTP) is:</p>

        <div class="otp">{{ $otp }}</div>

        <p>This OTP is valid for the next 10 minutes. Please do not share it with anyone.</p>

        <p>If you did not request this code, please ignore this email.</p>

        <div class="footer">
            &copy; {{ date('Y') }} YourAppName. All rights reserved.
        </div>
    </div>
</body>
</html>
