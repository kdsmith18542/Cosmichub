# CosmicHub Hosting Upload Instructions

## 📦 Compressed Files for Upload

Two ZIP files have been created for easy hosting deployment:

### 1. `cosmichub-public-files.zip`
**Contents**: All files that go in your domain's public directory
- `index.php` (PHP router entry point)
- `assets/` (CSS, JS, images)
- `js/` (JavaScript files)
- `favicon.ico`
- `robots.txt`
- `php-routing-test.html` (test page)
- `.htaccess.optional` (optional security enhancements)

**Upload Location**: Extract to your domain's public directory
- **DirectAdmin**: `public_html/` or `domains/yourdomain.com/public_html/`
- **cPanel**: `public_html/`
- **Other hosts**: Your domain's document root

### 2. `cosmichub-app-files.zip`
**Contents**: Application files that should be outside the public directory
- `app/` (controllers, models, libraries including PHPRouter)
- `config/` (configuration files)
- `database/` (database files and migrations)
- `bootstrap.php` (application bootstrap)
- `.env.example` (environment configuration template)
- `PHPMailer/` (email library)

**Upload Location**: Extract to a directory OUTSIDE your public folder
- **Example**: `/home/username/cosmichub/` (outside public_html)
- **Security**: Keep these files inaccessible from the web

## 🚀 Step-by-Step Upload Process

### Step 1: Upload Public Files
1. Download `cosmichub-public-files.zip`
2. Extract the contents
3. Upload all extracted files to your domain's public directory
4. Verify that `index.php` is in the root of your public directory

### Step 2: Upload Application Files
1. Download `cosmichub-app-files.zip`
2. Extract the contents
3. Upload all extracted files to a directory outside your public folder
4. Note the path where you uploaded these files

### Step 3: Configure Paths
1. If your app files are not in the default location, edit `public_html/index.php`
2. Update the `APP_ROOT` constant to point to your app files location:
   ```php
   // Example: if app files are in /home/username/cosmichub/
   define('APP_ROOT', '/home/username/cosmichub');
   ```

### Step 4: Test the Deployment
1. **Test static files**: Visit `https://yourdomain.com/php-routing-test.html`
2. **Test PHP routing**: Visit `https://yourdomain.com/index.php/`
3. **Test different routes**:
   - `https://yourdomain.com/index.php/login`
   - `https://yourdomain.com/index.php?route=/login`

### Step 5: Configure Environment
1. Copy `.env.example` to `.env` in your app files directory
2. Update database and other settings in `.env`
3. Set proper file permissions (644 for files, 755 for directories)

## 📁 Final Directory Structure on Server

```
/home/username/
├── public_html/ (or domains/yourdomain.com/public_html/)
│   ├── index.php
│   ├── assets/
│   ├── js/
│   ├── favicon.ico
│   ├── robots.txt
│   └── php-routing-test.html
│
└── cosmichub/ (or any name outside public_html)
    ├── app/
    ├── config/
    ├── database/
    ├── bootstrap.php
    ├── .env
    └── PHPMailer/
```

## ✅ Success Indicators

- ✅ Test page loads: `https://yourdomain.com/php-routing-test.html`
- ✅ Home page loads: `https://yourdomain.com/index.php/`
- ✅ Login page loads: `https://yourdomain.com/index.php/login`
- ✅ No 404 errors on main routes
- ✅ Static assets (CSS, JS) load correctly

## 🛠️ Troubleshooting

### Common Issues:

1. **404 on all pages**:
   - Check `index.php` is in the correct public directory
   - Verify `APP_ROOT` path in `index.php`

2. **Static files not loading**:
   - Ensure assets are in the public directory
   - Check file permissions

3. **Application errors**:
   - Verify app files are accessible from the path specified in `APP_ROOT`
   - Check `.env` file configuration
   - Review server error logs

## 🔒 Security Notes

- ✅ Application files are outside the public directory
- ✅ `.env` file is not web-accessible
- ✅ Database files are protected
- ✅ No .htaccess dependency issues

## 📞 Support

If you encounter issues:
1. Check the test page first
2. Verify file structure matches the guide
3. Review server error logs
4. Test with different URL formats
5. Contact your hosting provider for server-specific issues

---

**Note**: This PHP-based routing solution works on ANY hosting provider, regardless of .htaccess support. Perfect for DirectAdmin, cPanel, and other hosting environments!