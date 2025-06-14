<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to CosmicHub</title>
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
            color: white;
        }
        
        .container {
            text-align: center;
            max-width: 800px;
            padding: 2rem;
        }
        
        .logo {
            font-size: 4rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .feature {
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .feature h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #ffd700;
        }
        
        .feature p {
            line-height: 1.6;
            opacity: 0.9;
        }
        
        .version {
            margin-top: 3rem;
            opacity: 0.7;
            font-size: 0.9rem;
        }
        
        .links {
            margin-top: 2rem;
        }
        
        .links a {
            color: #ffd700;
            text-decoration: none;
            margin: 0 1rem;
            padding: 0.5rem 1rem;
            border: 1px solid #ffd700;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .links a:hover {
            background: #ffd700;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🌌 CosmicHub</div>
        <div class="subtitle">A Modern PHP Framework for the Stars</div>
        
        <div class="features">
            <div class="feature">
                <h3>🚀 Fast & Lightweight</h3>
                <p>Built for performance with minimal overhead and maximum efficiency.</p>
            </div>
            
            <div class="feature">
                <h3>🛠️ Developer Friendly</h3>
                <p>Clean, expressive syntax with powerful tools for rapid development.</p>
            </div>
            
            <div class="feature">
                <h3>🔧 Modular Architecture</h3>
                <p>Service providers, dependency injection, and clean separation of concerns.</p>
            </div>
            
            <div class="feature">
                <h3>🌐 Modern Features</h3>
                <p>Routing, middleware, ORM, templating, and everything you need.</p>
            </div>
        </div>
        
        <div class="links">
            <a href="/about">About</a>
            <a href="/contact">Contact</a>
            <a href="/api/v1/health">API Health</a>
        </div>
        
        <div class="version">
            CosmicHub Framework v<?= app()->version() ?>
        </div>
    </div>
</body>
</html>