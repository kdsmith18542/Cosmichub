# Shared Linux Hosting Deployment Guide

This guide will help you deploy CosmicHub on shared Linux hosting with DirectAdmin control panel.

## Prerequisites

- Shared Linux hosting account with DirectAdmin
- PHP 7.4 or higher
- SQLite support enabled
- Apache with mod_rewrite enabled
- File Manager access through DirectAdmin

## Deployment Steps

Understanding and correctly configuring your domain's **DocumentRoot** is crucial for a successful deployment. The DocumentRoot is the web-accessible folder where your domain looks for files to serve. In DirectAdmin, this is typically `public_html` by default for the main domain, or a subdirectory within `public_html` for subdomains.

**The Problem of Incorrect Redirects (e.g., to `/public/public_html/`):**
If you see your site redirecting to a strange path like `yourdomain.com/public/public_html/` and getting a 403 Forbidden error, it usually means:
1. Your domain's DocumentRoot might be set to your project's root directory (e.g., `/home/user/domains/yourdomain.com/`).
2. Your project's root `.htaccess` file is correctly redirecting requests to its `/public/` subdirectory.
3. You have mistakenly created a `public_html` folder *inside* your project's `public` folder on the server (e.g., `/home/user/domains/yourdomain.com/public/public_html/`).

**Choose ONE of the following deployment strategies based on your hosting setup:**

### Strategy 1: Standard Shared Hosting (DocumentRoot is `public_html` - Recommended for Simplicity)

This is the most common setup for shared hosting using DirectAdmin.

1.  **Verify DocumentRoot:** In DirectAdmin, confirm that your domain (`cosmichub.online`) points to `/home/your_username/domains/yourdomain.com/public_html/`. This is usually the default.
2.  **File Upload - Critical Structure:**
    *   **Public Files:** Upload the **contents** of your local `Cosmichub/public/` directory (which includes `index.php`, `assets/`, `favicon.ico`, and the `.htaccess` file from *within* the local `public` folder) directly into the server's `public_html` directory.
        *   Server path example: `/home/your_username/domains/yourdomain.com/public_html/index.php`
        *   Server path example: `/home/your_username/domains/yourdomain.com/public_html/.htaccess` (this is the one from your local `public` folder)
    *   **Application Files:** Create a new directory *outside* (one level above) `public_html` to store the rest of your application code. For example, create `/home/your_username/domains/yourdomain.com/application_files/`.
        *   Upload all other project files and folders from your local `Cosmichub` directory (like `app/`, `bootstrap.php`, `vendor/`, `config/`, `storage/`, etc.) into this `application_files/` directory.
        *   Server path example: `/home/your_username/domains/yourdomain.com/application_files/bootstrap.php`
        *   **Important:** Do NOT upload your local project's root `.htaccess` file (the one designed to redirect to a `/public/` subfolder) into either `public_html` or `application_files` in this strategy. It's not needed and will cause issues.
3.  **Update `index.php` Path:**
    *   Open the `index.php` file that you uploaded to `public_html/index.php`.
    *   Modify the line that requires `bootstrap.php` to point to its new location:
        ```php
        // Change this:
        // require __DIR__ . '/../bootstrap.php'; 

        // To this (assuming you named the folder 'application_files'):
        require __DIR__ . '/../application_files/bootstrap.php';
        ```

### Strategy 2: DocumentRoot is Project Root (Less Common on Shared Hosting)

Use this strategy if your DocumentRoot in DirectAdmin is set to the main project directory (e.g., `/home/your_username/domains/yourdomain.com/`) and you cannot change it to a subdirectory like `public` or `public_html`.

1.  **Verify DocumentRoot:** Confirm in DirectAdmin that your domain points to the root directory where you will upload your entire project (e.g., `/home/your_username/domains/yourdomain.com/`).
2.  **File Upload:**
    *   Upload your **entire** local `Cosmichub` project directory (including the root `.htaccess`, the `public/` folder, `app/`, `bootstrap.php`, etc.) to the server's DocumentRoot path (e.g., `/home/your_username/domains/yourdomain.com/`).
    *   Your file structure on the server will mirror your local structure:
        *   `/home/your_username/domains/yourdomain.com/.htaccess` (this is your project's root .htaccess)
        *   `/home/your_username/domains/yourdomain.com/public/index.php`
        *   `/home/your_username/domains/yourdomain.com/bootstrap.php`
3.  **Check for Nested `public_html`:**
    *   **Crucially, ensure there is NO `public_html` folder mistakenly created *inside* your `public` folder on the server.** The path `/home/your_username/domains/yourdomain.com/public/public_html/` should NOT exist. If it does, delete that inner `public_html` folder.
4.  **`.htaccess` Files:**
    *   The root `.htaccess` file (in `/home/your_username/domains/yourdomain.com/`) with the rule `RewriteRule ^(.*)$ /public/$1 [L,R=301]` will handle redirecting requests to the `public` folder.
    *   The `.htaccess` file inside your `public` folder will handle requests within it.
5.  **`index.php` Path:** The path `require __DIR__ . '/../bootstrap.php';` in `public/index.php` should be correct in this setup.

### Strategy 3: DocumentRoot is Project's `public` Folder (Cleanest, if Host Allows)

This is ideal if DirectAdmin allows you to set the DocumentRoot directly to your project's `public` subfolder.

1.  **File Upload:**
    *   Upload your entire local `Cosmichub` project to a base directory on your server, for example, `/home/your_username/domains/yourdomain.com/my_app/`.
    *   This means `bootstrap.php` would be at `/home/your_username/domains/yourdomain.com/my_app/bootstrap.php` and your public content at `/home/your_username/domains/yourdomain.com/my_app/public/`.
2.  **Set DocumentRoot:**
    *   In DirectAdmin's "Domain Setup" (or similar section), change the DocumentRoot for `cosmichub.online` to point directly to your project's `public` folder: `/home/your_username/domains/yourdomain.com/my_app/public/`.
3.  **Remove Root `.htaccess`:**
    *   You do **not** need the `.htaccess` file that was in your local project root (the one with `RewriteRule ^(.*)$ /public/$1 [L,R=301]`). Delete it from `/home/your_username/domains/yourdomain.com/my_app/.htaccess` if you uploaded it. The server now directly serves from the `public` folder, so this redirect is unnecessary and potentially problematic.
4.  **`public/.htaccess`:** The `.htaccess` file *inside* your `public` directory (`my_app/public/.htaccess`) is still needed and will function correctly.
5.  **`index.php` Path:** The path `require __DIR__ . '/../bootstrap.php';` in `public/index.php` remains correct.

---

### 1. Upload Files

**Via DirectAdmin File Manager:**

1. **Login to DirectAdmin:**
   - Access your hosting control panel
   - Navigate to File Manager

2. **Upload Public Files:**
   - Navigate to `public_html` directory
   - Upload all files from your project's `public` folder to `public_html`
   - This includes: `index.php`, `.htaccess`, `assets/`, `js/`, `favicon.ico`

3. **Upload Application Files:**
   - Navigate back to the parent directory (usually `/home/username/`)
   - Upload the following folders and files outside of `public_html`:
     - `app/` folder
     - `database/` folder
     - `storage/` folder
     - `bootstrap.php`
     - `.env` file
     - `composer.json`

4. **Final Directory Structure:**
   ```
   /home/username/
   ├── public_html/          (contents of public folder)
   │   ├── index.php
   │   ├── .htaccess
   │   ├── assets/
   │   ├── js/
   │   └── favicon.ico
   ├── app/
   ├── database/
   ├── storage/
   ├── bootstrap.php
   ├── .env
   └── composer.json
   ```

### 2. Set File Permissions

**Via DirectAdmin File Manager:**

1. **Navigate to each directory/file**
2. **Right-click and select "Change Permissions" or "Properties"**
3. **Set the following permissions:**

   - **Directories:**
     - `database/` → 750 (Owner: read/write/execute, Group: read/execute)
     - `storage/` → 750 (Owner: read/write/execute, Group: read/execute)
     - `storage/logs/` → 750 (Owner: read/write/execute, Group: read/execute)

   - **Files:**
     - `database/database.sqlite` → 640 (Owner: read/write, Group: read)
     - `.env` → 600 (Owner: read/write only)
     - `.htaccess` → 644 (Owner: read/write, Group/Others: read)

**Permission Numbers Reference:**
- 750 = rwxr-x--- (Owner: full access, Group: read/execute)
- 644 = rw-r--r-- (Owner: read/write, Others: read only)
- 640 = rw-r----- (Owner: read/write, Group: read only)
- 600 = rw------- (Owner: read/write only)

### 3. Configure Environment

1. **Create .env file:**
   - If you don't have a `.env` file, copy the contents from `.env.example`
   - Use DirectAdmin File Manager to create a new file named `.env`

2. **Update .env settings:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   
   # Update Stripe keys to production values
   STRIPE_SECRET_KEY=sk_live_your_production_key_here
   STRIPE_PUBLISHABLE_KEY=pk_live_your_production_key_here
   
   # Email configuration (use your hosting provider's SMTP)
   MAIL_HOST=mail.yourdomain.com
   MAIL_PORT=587
   MAIL_USERNAME=noreply@yourdomain.com
   MAIL_PASSWORD=your_email_password
   MAIL_ENCRYPTION=tls
   ```

### 4. Database Setup

The SQLite database will be created automatically. Ensure the `database` directory has proper write permissions.

### 5. DirectAdmin Specific Configuration

1. **PHP Version:**
   - Go to DirectAdmin → PHP Selector
   - Select PHP 7.4 or higher
   - Enable required extensions: sqlite3, pdo_sqlite, curl, openssl

2. **Domain Setup:**
   - Ensure your domain points to the `public_html` directory
   - The `.htaccess` file will handle URL rewriting

3. **SSL Certificate:**
   - Enable SSL through DirectAdmin → SSL Certificates
   - Use Let's Encrypt for free SSL

### 6. Security Considerations

1. **File Protection:**
   - The `.htaccess` file blocks access to sensitive directories
   - Database and config files are outside the web root

2. **Error Logging:**
   - Errors are logged to `storage/logs/` directory
   - Display errors are disabled in production

3. **Session Security:**
   - Secure session cookies are enabled
   - HTTPS-only cookies when SSL is available

### 7. Testing the Deployment

1. Visit your domain in a web browser
2. Check that the homepage loads correctly
3. Test user registration and login
4. Verify email functionality
5. Test payment processing (in test mode first)

### 8. Common Issues and Solutions

**Issue: 500 Internal Server Error**
- Check file permissions
- Review error logs in DirectAdmin
- Ensure PHP version compatibility

**Issue: Database connection errors**
- Verify `database` directory permissions (750)
- Check if SQLite extension is enabled

**Issue: Email not sending**
- Verify SMTP settings in `.env`
- Check with hosting provider for SMTP restrictions

**Issue: CSS/JS not loading**
- Check `.htaccess` file is uploaded
- Verify mod_rewrite is enabled

### 9. Maintenance

1. **Regular Backups:**
   - Backup database file regularly
   - Use DirectAdmin backup features

2. **Log Monitoring:**
   - Check `storage/logs/` for errors
   - Monitor DirectAdmin error logs

3. **Updates:**
   - Test updates on staging environment first
   - Always backup before updating

### 10. Performance Optimization

1. **Caching:**
   - The `.htaccess` file includes cache headers
   - Consider enabling OPcache if available

2. **Compression:**
   - Gzip compression is enabled in `.htaccess`
   - Reduces bandwidth usage

## Support

If you encounter issues:
1. Check the error logs first
2. Verify all file permissions
3. Contact your hosting provider for server-specific issues
4. Review this deployment guide for missed steps

## Security Checklist

- [ ] `.env` file has secure permissions (600)
- [ ] Database directory is not web-accessible
- [ ] SSL certificate is installed and working
- [ ] Production Stripe keys are configured
- [ ] Error display is disabled (`APP_DEBUG=false`)
- [ ] File permissions are set correctly
- [ ] Sensitive files are blocked by `.htaccess`