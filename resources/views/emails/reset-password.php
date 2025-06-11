<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eeeeee;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
        }
        .content {
            padding: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #e74c3c;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #c0392b;
        }
        .footer {
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid #eeeeee;
            font-size: 12px;
            color: #7f8c8d;
        }
        .notice {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #e74c3c;
            margin: 15px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reset Your Password</h1>
        </div>
        
        <div class="content">
            <p>Hello <?php echo htmlspecialchars($name); ?>,</p>
            
            <p>We received a request to reset the password for your account. To reset your password, please click the button below:</p>
            
            <p style="text-align: center;">
                <a href="<?php echo $reset_url; ?>" class="button">Reset Password</a>
            </p>
            
            <p>If you did not request a password reset, you can safely ignore this email. Only people with access to your email can reset your account password.</p>
            
            <div class="notice">
                <p><strong>Note:</strong> This password reset link will expire in <?php echo $expiration_minutes; ?> minutes for security reasons.</p>
            </div>
            
            <p>If the button above doesn't work, you can copy and paste this link into your browser:</p>
            <p>
                <a href="<?php echo $reset_url; ?>">
                    <?php echo htmlspecialchars($reset_url); ?>
                </a>
            </p>
            
            <p>Best regards,<br>The <?php echo Config::get('app.name', 'Our Team'); ?></p>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo Config::get('app.name', 'Our App'); ?>. All rights reserved.</p>
            <p>This email was sent in response to a password reset request for your account.</p>
        </div>
    </div>
</body>
</html>
