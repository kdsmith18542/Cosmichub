<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - CosmicHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 90%;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .error-title {
            font-size: 32px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .error-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e9ecef;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .cosmic-animation {
            position: relative;
            overflow: hidden;
        }
        
        .cosmic-animation::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            animation: cosmic-pulse 4s ease-in-out infinite;
        }
        
        @keyframes cosmic-pulse {
            0%, 100% {
                transform: scale(0.8) rotate(0deg);
                opacity: 0.5;
            }
            50% {
                transform: scale(1.2) rotate(180deg);
                opacity: 0.8;
            }
        }
        
        .debug-info {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: left;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #666;
        }
        
        .debug-info h3 {
            margin-bottom: 15px;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .debug-info pre {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="error-container cosmic-animation">
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">
            <?php echo htmlspecialchars($message ?? 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.'); ?>
        </p>
        
        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                🏠 Go Home
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                ← Go Back
            </button>
        </div>
        
        <?php if (isset($debug) && $debug && isset($exception)): ?>
        <div class="debug-info">
            <h3>Debug Information</h3>
            <p><strong>File:</strong> <?php echo htmlspecialchars($exception->getFile()); ?></p>
            <p><strong>Line:</strong> <?php echo htmlspecialchars($exception->getLine()); ?></p>
            <pre><?php echo htmlspecialchars($exception->getTraceAsString()); ?></pre>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>