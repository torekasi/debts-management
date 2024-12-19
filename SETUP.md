# Debt Management System - Setup Guide

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependencies)
- Web server with mod_rewrite enabled

## Installation Steps

1. **Clone the Repository**
   ```bash
   git clone [repository-url]
   cd debt-apps
   ```

2. **Configure Database**
   - Create a new MySQL database
   - Copy `config/config.php` to `config/config.local.php`
   - Update database credentials in `config/config.local.php`

3. **Set File Permissions**
   ```bash
   chmod 755 -R ./
   chmod 777 -R ./uploads
   chmod 777 -R ./logs
   ```

4. **Create First Admin User**
   - Access `http://your-domain/setup.php`
   - Fill in the admin user details
   - Delete `setup.php` after completion

## Setting Up Cron Jobs

### Windows (Using Task Scheduler)

1. Open Task Scheduler
2. Click "Create Basic Task"
3. Set Name: "Debt Management - Payment Check"
4. Set Trigger: Daily
5. Set Action: Start a program
6. Program/script: `C:\path\to\php.exe`
7. Add arguments: `C:\path\to\debt-apps\cron\check_payments.php`
8. Set working directory: `C:\path\to\debt-apps`

### Linux/Unix

1. Open terminal
2. Edit crontab:
   ```bash
   crontab -e
   ```

3. Add the following line:
   ```bash
   0 0 * * * /usr/bin/php /path/to/debt-apps/cron/check_payments.php >> /path/to/debt-apps/logs/cron.log 2>&1
   ```

### XAMPP (Windows)

1. Create a batch file `run_cron.bat`:
   ```batch
   @echo off
   "C:\xampp\php\php.exe" "C:\path\to\debt-apps\cron\check_payments.php"
   ```

2. Create a scheduled task:
   - Open Task Scheduler
   - Create Basic Task
   - Action: Start a program
   - Program/script: `C:\path\to\run_cron.bat`

## Security Recommendations

1. **File Permissions**
   - Set restrictive permissions on configuration files
   - Ensure upload directory is not publicly executable

2. **SSL Configuration**
   - Enable HTTPS
   - Update config.php with secure settings
   - Configure SSL certificate

3. **Database Security**
   - Use strong passwords
   - Limit database user privileges
   - Regular backups

## Additional Configuration

### Email Notifications (Optional)

1. Update `config.php` with SMTP settings:
   ```php
   define('SMTP_HOST', 'smtp.example.com');
   define('SMTP_PORT', 587);
   define('SMTP_USER', 'your-email@example.com');
   define('SMTP_PASS', 'your-password');
   ```

### Session Security

1. Update `php.ini` settings:
   ```ini
   session.cookie_secure = 1
   session.cookie_httponly = 1
   session.gc_maxlifetime = 86400
   ```

## Troubleshooting

### Common Issues

1. **Permission Errors**
   - Check file/folder permissions
   - Ensure web server user has write access to uploads/logs

2. **Database Connection**
   - Verify database credentials
   - Check MySQL service status
   - Confirm database user privileges

3. **Cron Job Issues**
   - Check PHP path in cron command
   - Verify file permissions
   - Check cron log for errors

### Logging

- Application logs: `logs/app.log`
- Cron job logs: `logs/cron.log`
- Error logs: `logs/error.log`

## Maintenance

1. **Regular Tasks**
   - Monitor log files
   - Backup database
   - Check disk space
   - Update dependencies

2. **Database Optimization**
   ```sql
   OPTIMIZE TABLE transactions;
   OPTIMIZE TABLE payments;
   OPTIMIZE TABLE users;
   ```

## Support

For technical support:
1. Check the logs directory
2. Review error messages
3. Contact system administrator

## Updates

To update the system:
1. Backup database and files
2. Pull latest changes
3. Run database migrations
4. Clear cache
5. Test functionality
