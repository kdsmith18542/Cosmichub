# AWS Ubuntu 24 Deployment Guide for CosmicHub

This guide will help you deploy CosmicHub on AWS EC2 with Ubuntu 24.04 LTS.

## Prerequisites

- AWS Account with EC2 access
- Basic knowledge of Linux command line
- Domain name (optional but recommended)
- SSL certificate (Let's Encrypt recommended)

## Step 1: Launch EC2 Instance

### 1.1 Create EC2 Instance

1. **Login to AWS Console** and navigate to EC2
2. **Launch Instance** with these specifications:
   - **AMI**: Ubuntu Server 24.04 LTS (HVM)
   - **Instance Type**: t3.micro (free tier) or t3.small for production
   - **Key Pair**: Create or select existing key pair
   - **Security Group**: Create new with these rules:
     - SSH (22) - Your IP only
     - HTTP (80) - Anywhere (0.0.0.0/0)
     - HTTPS (443) - Anywhere (0.0.0.0/0)
   - **Storage**: 20GB gp3 (minimum)

### 1.2 Connect to Instance

```bash
# Connect via SSH (replace with your key and IP)
ssh -i "your-key.pem" ubuntu@your-ec2-public-ip
```

## Step 2: Server Setup

### 2.1 Update System

```bash
# Update package list and upgrade system
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget git unzip software-properties-common
```

### 2.2 Install LAMP Stack

```bash
# Install Apache
sudo apt install -y apache2

# Install PHP 8.2 and required extensions
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-gd php8.2-mbstring php8.2-zip php8.2-sqlite3 php8.2-json php8.2-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
```

### 2.3 Configure Apache

```bash
# Create virtual host configuration
sudo nano /etc/apache2/sites-available/cosmichub.conf
```

Add this configuration:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/cosmichub/public
    
    <Directory /var/www/cosmichub/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    ErrorLog ${APACHE_LOG_DIR}/cosmichub_error.log
    CustomLog ${APACHE_LOG_DIR}/cosmichub_access.log combined
</VirtualHost>
```

```bash
# Enable the site and disable default
sudo a2ensite cosmichub.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

## Step 3: Deploy Application

### 3.1 Create Application Directory

```bash
# Create application directory
sudo mkdir -p /var/www/cosmichub
sudo chown -R ubuntu:www-data /var/www/cosmichub
sudo chmod -R 755 /var/www/cosmichub
```

### 3.2 Upload Application Files

**Option A: Using Git (Recommended)**

```bash
# Clone your repository (if using Git)
cd /var/www
sudo git clone https://github.com/yourusername/cosmichub.git
sudo chown -R ubuntu:www-data cosmichub
```

**Option B: Using SCP/SFTP**

```bash
# From your local machine, upload files
scp -i "your-key.pem" -r /path/to/local/cosmichub/* ubuntu@your-ec2-ip:/var/www/cosmichub/
```

### 3.3 Install Dependencies

```bash
cd /var/www/cosmichub

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Set proper permissions
sudo chown -R ubuntu:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 storage/
sudo chmod -R 775 database/
```

### 3.4 Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit environment configuration
nano .env
```

Update `.env` with your settings:

```env
APP_NAME="CosmicHub"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (SQLite for simplicity)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/cosmichub/database/cosmichub.sqlite

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="CosmicHub"

# API Keys
GEMINI_API_KEY=your-gemini-api-key
STRIPE_PUBLIC_KEY=pk_live_your_stripe_public_key
STRIPE_SECRET_KEY=sk_live_your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret

# Security
APP_KEY=your-32-character-secret-key
```

### 3.5 Setup Database

```bash
# Create SQLite database
touch database/cosmichub.sqlite
sudo chown www-data:www-data database/cosmichub.sqlite
sudo chmod 664 database/cosmichub.sqlite

# Run migrations (if you have a migration script)
# php artisan migrate --force
```

## Step 4: SSL Certificate (Let's Encrypt)

### 4.1 Install Certbot

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache
```

### 4.2 Obtain SSL Certificate

```bash
# Get SSL certificate (replace with your domain)
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# Test automatic renewal
sudo certbot renew --dry-run
```

## Step 5: Security Hardening

### 5.1 Configure Firewall

```bash
# Enable UFW firewall
sudo ufw enable

# Allow necessary ports
sudo ufw allow ssh
sudo ufw allow 'Apache Full'

# Check status
sudo ufw status
```

### 5.2 Secure PHP Configuration

```bash
# Edit PHP configuration
sudo nano /etc/php/8.2/apache2/php.ini
```

Update these settings:

```ini
# Security settings
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

# Performance settings
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 50M
upload_max_filesize = 50M
```

```bash
# Restart Apache
sudo systemctl restart apache2
```

### 5.3 Hide Sensitive Files

```bash
# Create .htaccess in root to protect sensitive files
sudo nano /var/www/cosmichub/.htaccess
```

Add:

```apache
# Deny access to sensitive files
<Files ".env">
    Require all denied
</Files>

<Files "composer.json">
    Require all denied
</Files>

<Files "composer.lock">
    Require all denied
</Files>

<FilesMatch "\.(md|txt|log)$">
    Require all denied
</FilesMatch>
```

## Step 6: Monitoring and Maintenance

### 6.1 Setup Log Rotation

```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/cosmichub
```

Add:

```
/var/www/cosmichub/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 644 www-data www-data
}
```

### 6.2 Setup Cron Jobs

```bash
# Edit crontab
crontab -e
```

Add:

```bash
# CosmicHub maintenance tasks
0 2 * * * /usr/bin/php /var/www/cosmichub/artisan schedule:run >> /dev/null 2>&1

# SSL certificate renewal
0 12 * * * /usr/bin/certbot renew --quiet
```

### 6.3 Backup Script

```bash
# Create backup script
sudo nano /usr/local/bin/cosmichub-backup.sh
```

Add:

```bash
#!/bin/bash
BACKUP_DIR="/home/ubuntu/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
cp /var/www/cosmichub/database/cosmichub.sqlite $BACKUP_DIR/cosmichub_$DATE.sqlite

# Backup application files (excluding vendor)
tar -czf $BACKUP_DIR/cosmichub_files_$DATE.tar.gz \
    --exclude='/var/www/cosmichub/vendor' \
    --exclude='/var/www/cosmichub/node_modules' \
    /var/www/cosmichub

# Keep only last 7 days of backups
find $BACKUP_DIR -name "cosmichub_*" -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# Make executable and add to cron
sudo chmod +x /usr/local/bin/cosmichub-backup.sh

# Add to crontab (daily backup at 3 AM)
0 3 * * * /usr/local/bin/cosmichub-backup.sh >> /var/log/cosmichub-backup.log 2>&1
```

## Step 7: Performance Optimization

### 7.1 Enable Apache Caching

```bash
# Enable caching modules
sudo a2enmod expires
sudo a2enmod headers

# Add caching rules to virtual host
sudo nano /etc/apache2/sites-available/cosmichub.conf
```

Add inside `<VirtualHost>` block:

```apache
# Browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

```bash
# Restart Apache
sudo systemctl restart apache2
```

## Step 8: Testing and Verification

### 8.1 Test Application

1. **Visit your domain**: `https://your-domain.com`
2. **Test key features**:
   - User registration
   - Cosmic snapshot generation
   - Payment processing (test mode)
   - Admin dashboard

### 8.2 Performance Testing

```bash
# Install Apache Bench for testing
sudo apt install -y apache2-utils

# Test performance
ab -n 100 -c 10 https://your-domain.com/
```

### 8.3 Security Testing

```bash
# Check for common vulnerabilities
sudo apt install -y nmap
nmap -sV your-domain.com

# Check SSL configuration
ssl-cert-check -c your-domain.com
```

## Step 9: Monitoring Setup

### 9.1 Install Monitoring Tools

```bash
# Install htop for system monitoring
sudo apt install -y htop iotop

# Install log monitoring
sudo apt install -y logwatch
```

### 9.2 CloudWatch Integration (Optional)

```bash
# Install CloudWatch agent
wget https://s3.amazonaws.com/amazoncloudwatch-agent/ubuntu/amd64/latest/amazon-cloudwatch-agent.deb
sudo dpkg -i amazon-cloudwatch-agent.deb

# Configure CloudWatch (follow AWS documentation)
sudo /opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-config-wizard
```

## Troubleshooting

### Common Issues

1. **Permission Errors**:
   ```bash
   sudo chown -R www-data:www-data /var/www/cosmichub
   sudo chmod -R 755 /var/www/cosmichub
   sudo chmod -R 775 /var/www/cosmichub/storage
   ```

2. **Apache Not Starting**:
   ```bash
   sudo systemctl status apache2
   sudo journalctl -u apache2
   ```

3. **SSL Issues**:
   ```bash
   sudo certbot certificates
   sudo certbot renew --force-renewal
   ```

4. **Database Connection Issues**:
   ```bash
   ls -la /var/www/cosmichub/database/
   sudo chmod 664 /var/www/cosmichub/database/cosmichub.sqlite
   ```

### Log Locations

- **Apache Logs**: `/var/log/apache2/`
- **PHP Logs**: `/var/log/php_errors.log`
- **Application Logs**: `/var/www/cosmichub/storage/logs/`
- **System Logs**: `/var/log/syslog`

## Production Checklist

- [ ] EC2 instance properly sized for traffic
- [ ] SSL certificate installed and auto-renewal configured
- [ ] Environment variables configured for production
- [ ] Database backups automated
- [ ] Monitoring and alerting setup
- [ ] Security groups properly configured
- [ ] Firewall rules in place
- [ ] Log rotation configured
- [ ] Performance optimization enabled
- [ ] Domain DNS properly configured
- [ ] Stripe keys updated to production values
- [ ] Email service configured and tested
- [ ] All API keys configured and tested

## Next Steps

1. **Configure Domain DNS** to point to your EC2 instance
2. **Setup CloudFront CDN** for better performance
3. **Configure RDS** for production database (optional)
4. **Setup Load Balancer** for high availability (if needed)
5. **Configure Auto Scaling** for traffic spikes
6. **Setup CI/CD Pipeline** for automated deployments

---

**Your CosmicHub application is now deployed on AWS Ubuntu 24!**

For support and updates, refer to the project documentation or contact the development team.