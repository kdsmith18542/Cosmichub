<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $subject ?? 'Email from CosmicHub' ?></title>
    <style type="text/css">
        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }
        
        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #ffffff;
        }
        
        /* Header */
        .email-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eaeaea;
        }
        
        .email-logo {
            max-width: 150px;
            height: auto;
        }
        
        /* Content */
        .email-content {
            padding: 30px 20px;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4f46e5;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            margin: 15px 0;
        }
        
        .btn:hover {
            background-color: #4338ca;
        }
        
        /* Footer */
        .email-footer {
            text-align: center;
            padding: 20px 0;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eaeaea;
        }
        
        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            
            .email-content {
                padding: 20px 10px !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <img src="<?= url('/images/logo.png') ?>" alt="CosmicHub Logo" class="email-logo">
        </div>
        
        <div class="email-content">
            <?= $this->section('content') ?>
        </div>
        
        <div class="email-footer">
            <p>&copy; <?= date('Y') ?> CosmicHub. All rights reserved.</p>
            <p>
                <a href="<?= url('/privacy') ?>" style="color: #666; text-decoration: none;">Privacy Policy</a> | 
                <a href="<?= url('/terms') ?>" style="color: #666; text-decoration: none;">Terms of Service</a>
            </p>
        </div>
    </div>
</body>
</html>
