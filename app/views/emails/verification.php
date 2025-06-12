<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; }
        .logo { max-width: 150px; height: auto; }
        .content { background-color: #f9f9f9; padding: 30px; border-radius: 5px; }
        .button {
            display: inline-block; 
            padding: 12px 24px; 
            background-color: #4CAF50; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            margin: 20px 0;
        }
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            font-size: 12px; 
            color: #777; 
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to CosmicHub</h1>
    </div>
    
    <div class="content">
        <p>Hello <?php echo htmlspecialchars($name); ?>,</p>
        
        <p>Thank you for registering with CosmicHub! To complete your registration, please verify your email address by clicking the button below:</p>
        
        <p style="text-align: center;">
            <a href="<?php echo htmlspecialchars($verification_url); ?>" class="button">
                Verify Email Address
            </a>
        </p>
        
        <p>Or copy and paste this link into your browser:<br>
        <a href="<?php echo htmlspecialchars($verification_url); ?>"><?php echo htmlspecialchars($verification_url); ?></a></p>
        
        <p>If you did not create an account, no further action is required.</p>
        
        <p>Best regards,<br>The CosmicHub Team</p>
    </div>
    
    <div class="footer">
        <p>© <?php echo date('Y'); ?> CosmicHub. All rights reserved.</p>
        <p>This is an automated message, please do not reply to this email.</p>
    </div>
</body>
</html>
