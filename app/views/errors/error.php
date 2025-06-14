<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Error'); ?> - CosmicHub</title>
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
        
        .error-details {
            margin-top: 30px;
            padding: 20px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            text-align: left;
        }
        
        .error-details h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .error-details p {
            color: #856404;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="error-container cosmic-animation">
        <div class="error-code"><?php echo htmlspecialchars($code ?? '500'); ?></div>
        <h1 class="error-title"><?php echo htmlspecialchars($title ?? 'Server Error'); ?></h1>
        <p class="error-message">
            <?php echo htmlspecialchars($message ?? 'Something went wrong on our end. Please try again later.'); ?>
        </p>
        
        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                🏠 Go Home
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                ← Go Back
            </button>
            <?php if (isset($retry) && $retry): ?>
            <button onclick="location.reload()" class="btn btn-secondary">
                🔄 Try Again
            </button>
            <?php endif; ?>
        </div>
        
        <?php if (isset($context) && !empty($context)): ?>
        <div class="error-details">
            <h3>Additional Information</h3>
            <?php foreach ($context as $key => $value): ?>
                <p><strong><?php echo htmlspecialchars(ucfirst($key)); ?>:</strong> <?php echo htmlspecialchars($value); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($debug) && $debug && isset($exception)): ?>
        <div class="debug-info">
            <h3>Debug Information</h3>
            <p><strong>Exception:</strong> <?php echo htmlspecialchars(get_class($exception)); ?></p>
            <p><strong>File:</strong> <?php echo htmlspecialchars($exception->getFile()); ?></p>
            <p><strong>Line:</strong> <?php echo htmlspecialchars($exception->getLine()); ?></p>
            <p><strong>Message:</strong> <?php echo htmlspecialchars($exception->getMessage()); ?></p>
            <pre><?php echo htmlspecialchars($exception->getTraceAsString()); ?></pre>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>