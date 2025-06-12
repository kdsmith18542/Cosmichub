# Shared Linux Hosting Deployment Guide

This guide will help you deploy CosmicHub on shared Linux hosting with DirectAdmin control panel.

## Prerequisites

- Shared Linux hosting account with DirectAdmin
- PHP 7.4 or higher
- SQLite support enabled
- Apache with mod_rewrite enabled
- SSH access (optional but recommended)

## Deployment Steps

### 1. Upload Files

1. **Via File Manager (DirectAdmin):**
   - Login to DirectAdmin
   - Go to File Manager
   - Navigate to `public_html` directory
   - Upload all files from the `public` folder to `public_html`
   - Upload all other project files (app, database, etc.) to the parent directory of `public_html`

2. **Via FTP/SFTP:**
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

Set the following permissions via DirectAdmin File Manager or SSH:

```bash
# Make directories writable
chmod 750 database/
chmod 750 storage/
chmod 750 storage/logs/

# Make database file writable (if it exists)
chmod 640 database/database.sqlite

# Secure sensitive files
chmod 600 .env
chmod 644 .htaccess
```

### 3. Configure Environment

1. **Copy and edit .env file:**
   ```bash
   cp .env.example .env
   ```

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