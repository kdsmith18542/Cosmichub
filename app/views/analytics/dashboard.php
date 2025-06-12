<?php
/**
 * Analytics Dashboard Redirect
 * 
 * This page now redirects to the unified admin dashboard
 */

// Redirect to unified admin dashboard
header('Location: /admin/dashboard?tab=analytics');
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Admin Dashboard...</title>
    <meta http-equiv="refresh" content="0;url=/admin/dashboard?tab=analytics">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .redirect-message {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="redirect-message">
        <div class="spinner"></div>
        <h2>Redirecting to Unified Admin Dashboard...</h2>
        <p>The analytics dashboard has been merged into a comprehensive admin interface.</p>
        <p>If you are not redirected automatically, <a href="/admin/dashboard?tab=analytics" style="color: #fff; text-decoration: underline;">click here</a>.</p>
    </div>
    
    <script>
        // JavaScript redirect as backup
        setTimeout(function() {
            window.location.href = '/admin/dashboard?tab=analytics';
        }, 2000);
    </script>
</body>
</html>