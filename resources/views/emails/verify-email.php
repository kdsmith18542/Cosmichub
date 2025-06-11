<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
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
            background-color: #3498db;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .footer {
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid #eeeeee;
            font-size: 12px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Verify Your Email Address</h1>
        </div>
        
        <div class="content">
            <p>Hello <?php echo htmlspecialchars($name); ?>,</p>
            
            <p>Thank you for registering with us! To complete your registration, please verify your email address by clicking the button below:</p>
            
            <p style="text-align: center;">
                <a href="<?php echo $verification_url; ?>" class="button">Verify Email Address</a>
            </p>
            
            <p>If the button above doesn't work, you can copy and paste this link into your browser:</p>
            <p>
                <a href="<?php echo $verification_url; ?>">
                    <?php echo htmlspecialchars($verification_url); ?>
                </a>
            </p>
            
            <p>This verification link will expire in <?php echo $expiration_hours; ?> hours.</p>
            
            <p>If you did not create an account, no further action is required.</p>
            
            <p>Best regards,<br>The <?php echo Config::get('app.name', 'Our Team'); ?></p>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo Config::get('app.name', 'Our App'); ?>. All rights reserved.</p>
            <p>This email was sent to you as part of your account registration.</p>
        </div>
    </div>
</body>
</html>
