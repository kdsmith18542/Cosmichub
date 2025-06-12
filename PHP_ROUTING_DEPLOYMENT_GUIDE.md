# CosmicHub PHP-Based Routing Deployment Guide

## Overview

This deployment package uses **PHP-based routing** instead of .htaccess files, making it compatible with **any hosting provider** regardless of Apache mod_rewrite support. This is perfect for shared hosting environments where .htaccess might not be supported or configured properly.

## 🎯 Key Benefits

- ✅ **Universal Compatibility**: Works on any hosting provider
- ✅ **No .htaccess Required**: Pure PHP routing solution
- ✅ **DirectAdmin Compatible**: Perfect for your hosting setup
- ✅ **Multiple URL Formats**: Supports both clean URLs and query strings
- ✅ **Easy Debugging**: All routing logic is in PHP
- ✅ **Secure**: Application files stay outside public directory

## 📁 File Structure

```
cosmichub-php-routing-deployment/
├── public_html/                    # Upload to your domain's public folder
│   ├── index.php                   # Main entry point (PHP router)
│   ├── assets/                     # CSS, JS, images
│   ├── js/                         # JavaScript files
│   ├── favicon.ico                 # Site icon
│   ├── robots.txt                  # SEO file
│   └── php-routing-test.html       # Test page
├── app/                            # Upload outside public folder
│   ├── libraries/
│   │   └── PHPRouter.php           # New PHP-based router
│   ├── controllers/                # Application controllers
│   ├── models/                     # Data models
│   ├── routes/
│   │   └── web.php                 # Route definitions
│   └── views/                      # Templates
├── config/                         # Configuration files
├── database/                       # Database files and migrations
├── bootstrap.php                   # Application bootstrap
└── .env.example                    # Environment configuration template
```

## 🚀 Deployment Steps

### Step 1: Upload Files

1. **Upload public_html contents** to your domain's public directory:
   - In DirectAdmin: Upload to `public_html/` or `domains/yourdomain.com/public_html/`
   - In cPanel: Upload to `public_html/`

2. **Upload application files** outside the public directory:
   - Upload `app/`, `config/`, `database/`, `bootstrap.php`, etc. to a folder outside public_html
   - Example: `/home/username/cosmichub/` (outside public_html)

### Step 2: Configure Paths

1. Edit `public_html/index.php` if needed:
   ```php
   // Update this line if your app files are in a different location
   define('APP_ROOT', dirname(__DIR__));
   ```

2. If your app files are in a custom location, update the path:
   ```php
   // Example: if app files are in /home/username/cosmichub/
   define('APP_ROOT', '/home/username/cosmichub');
   ```

### Step 3: Test the Deployment

1. **Test static files**: Visit `https://yourdomain.com/php-routing-test.html`
2. **Test PHP routing**: Visit `https://yourdomain.com/index.php/`
3. **Test different URL formats**:
   - Path Info: `https://yourdomain.com/index.php/login`
   - Query String: `https://yourdomain.com/index.php?route=/login`

### Step 4: Configure Environment

1. Copy `.env.example` to `.env`
2. Update database and other settings in `.env`
3. Ensure proper file permissions (644 for files, 755 for directories)

## 🔗 URL Formats Supported

The PHP router automatically detects and supports multiple URL formats:

### 1. Path Info Style (Recommended)
```
https://yourdomain.com/index.php/
https://yourdomain.com/index.php/login
https://yourdomain.com/index.php/dashboard
https://yourdomain.com/index.php/cosmic-snapshot/abc123
```

### 2. Query String Style (Fallback)
```
https://yourdomain.com/index.php?route=/
https://yourdomain.com/index.php?route=/login
https://yourdomain.com/index.php?route=/dashboard
https://yourdomain.com/index.php?route=/cosmic-snapshot/abc123
```

### 3. Direct Access
```
https://yourdomain.com/index.php  (shows home page)
```

## ⚙️ How PHP Routing Works

### Router Detection Logic
The `PHPRouter` class automatically detects the route using multiple methods:

1. **Query Parameter**: Checks for `?route=/path`
2. **PATH_INFO**: Uses server's PATH_INFO if available
3. **REQUEST_URI Parsing**: Extracts path from full URI
4. **Fallback**: Defaults to home page

### Route Processing
```php
// Example route definition in app/routes/web.php
$router->get('/login', 'AuthController', 'loginForm');
$router->post('/login', 'AuthController', 'login');
$router->get('/cosmic-snapshot/{slug}', 'HomeController', 'showSnapshot');
```

### URL Generation
The router can generate URLs in the appropriate format:
```php
// Generates: /index.php/cosmic-snapshot/abc123 or /index.php?route=/cosmic-snapshot/abc123
$url = $router->generateUrl('/cosmic-snapshot/{slug}', ['slug' => 'abc123']);
```

## 🛠️ Troubleshooting

### Common Issues

1. **404 Errors on All Pages**
   - Check that `index.php` is in the correct public directory
   - Verify `APP_ROOT` path in `index.php`
   - Ensure `bootstrap.php` and app files are accessible

2. **Static Files Not Loading**
   - Verify assets are in `public_html/assets/`
   - Check file permissions (644 for files, 755 for directories)

3. **Database Connection Errors**
   - Copy `.env.example` to `.env`
   - Update database settings in `.env`
   - Ensure database file permissions if using SQLite

4. **PHP Errors**
   - Check server error logs
   - Verify PHP version compatibility (7.4+)
   - Ensure all required PHP extensions are installed

### Debug Mode
To enable debug information, edit `public_html/index.php`:
```php
// Enable for debugging (disable in production)
ini_set('display_errors', '1');
error_reporting(E_ALL);
```

### Testing Router Detection
Add this to `index.php` for debugging:
```php
// Debug: Show detected route
echo "Detected Route: " . $router->getCurrentPath() . "<br>";
echo "Method: " . $router->getCurrentMethod() . "<br>";
```

## 🔒 Security Considerations

1. **Application Files**: Keep outside public directory
2. **Environment File**: Ensure `.env` is not web-accessible
3. **File Permissions**: Use appropriate permissions (644/755)
4. **Error Reporting**: Disable in production
5. **Database**: Use secure credentials and connections

## 📈 Performance Tips

1. **Caching**: Implement route caching for high-traffic sites
2. **Static Files**: Use CDN for assets if needed
3. **Database**: Optimize queries and use indexes
4. **PHP**: Use PHP 8+ for better performance

## 🆚 Comparison with .htaccess Routing

| Feature | PHP Routing | .htaccess Routing |
|---------|-------------|-------------------|
| Hosting Compatibility | ✅ Universal | ❌ Apache only |
| DirectAdmin Support | ✅ Yes | ❌ Limited |
| Debugging | ✅ Easy | ❌ Difficult |
| URL Flexibility | ✅ Multiple formats | ❌ Fixed format |
| Setup Complexity | ✅ Simple | ❌ Complex |
| Performance | ✅ Good | ✅ Slightly better |

## 📞 Support

If you encounter issues:

1. Check the test page: `https://yourdomain.com/php-routing-test.html`
2. Review server error logs
3. Verify file structure and permissions
4. Test with different URL formats
5. Contact your hosting provider if server-specific issues occur

## 🎉 Success Indicators

- ✅ Test page loads: `https://yourdomain.com/php-routing-test.html`
- ✅ Home page loads: `https://yourdomain.com/index.php/`
- ✅ Login page loads: `https://yourdomain.com/index.php/login`
- ✅ No 404 errors on main routes
- ✅ Static assets (CSS, JS) load correctly

Once all indicators are green, your CosmicHub application is successfully deployed with PHP-based routing!