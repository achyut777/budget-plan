# 🚀 Budget Planner - Production Deployment Checklist

## Pre-Deployment Checklist

### 🔧 Configuration
- [ ] Update `config/database.php` with production database credentials
- [ ] Configure `config/email_config.php` with production email settings
- [ ] Update `APP_URL` in email configuration to production domain
- [ ] Remove or secure `test_auth.php` file
- [ ] Remove `email_viewer.php` in production

### 🔒 Security
- [ ] Change default admin password (`admin@budgetplanner.com`)
- [ ] Remove demo user (`demo@example.com`) from production database
- [ ] Ensure HTTPS is enabled and enforced
- [ ] Set proper file/directory permissions:
  - Files: 644
  - Directories: 755
  - Config files: 600 (readable only by owner)
- [ ] Disable debug mode/error display in PHP
- [ ] Set up proper error logging

### 📁 File Permissions
```bash
# Set proper permissions
chmod 755 /path/to/budget-planner/
chmod -R 644 /path/to/budget-planner/*
chmod -R 755 /path/to/budget-planner/*/
chmod 700 /path/to/budget-planner/logs/
chmod 600 /path/to/budget-planner/config/*.php
```

### 🗄️ Database
- [ ] Run production database setup: `php config/init_db.php`
- [ ] Create database backups schedule
- [ ] Set up database user with minimal required permissions
- [ ] Test database connection from production server

### 📧 Email Setup
- [ ] Configure SMTP server settings
- [ ] Test email verification functionality
- [ ] Verify "From" email address is authorized
- [ ] Set up email templates with production branding

### 🌐 Web Server Configuration

#### Apache (.htaccess)
```apache
# Secure config files
<Files "*.php">
    <FilesMatch "^(database|email_config)\.php$">
        Order Deny,Allow
        Deny from all
    </FilesMatch>
</Files>

# Hide sensitive directories
RedirectMatch 404 ^/logs/.*$
RedirectMatch 404 ^/config/.*$

# Enable HTTPS redirect
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### Nginx
```nginx
# Block access to sensitive files
location ~ ^/(config|logs)/ {
    deny all;
    return 404;
}

# PHP configuration
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## Post-Deployment Testing

### 🧪 Functionality Tests
- [ ] User registration with email verification
- [ ] User login/logout functionality
- [ ] Password reset (if implemented)
- [ ] Transaction CRUD operations
- [ ] Goal management
- [ ] Admin panel access and functions
- [ ] Email sending functionality
- [ ] File upload/download (if applicable)

### 🔒 Security Tests
- [ ] SQL injection attempts
- [ ] XSS vulnerability tests
- [ ] CSRF token validation
- [ ] Direct access to restricted files
- [ ] Session hijacking protection
- [ ] Brute force login protection

### 📱 Compatibility Tests
- [ ] Desktop browsers (Chrome, Firefox, Safari, Edge)
- [ ] Mobile browsers (iOS Safari, Android Chrome)
- [ ] Tablet devices
- [ ] Different screen resolutions

## Monitoring Setup

### 📊 Logging
- [ ] Set up application error logging
- [ ] Configure web server access logs
- [ ] Set up database query logging (if needed)
- [ ] Implement user activity logging

### 🚨 Alerts
- [ ] Set up email alerts for critical errors
- [ ] Configure monitoring for database connectivity
- [ ] Set up disk space monitoring
- [ ] Configure backup verification alerts

### 📈 Analytics (Optional)
- [ ] Google Analytics or similar
- [ ] User behavior tracking
- [ ] Performance monitoring
- [ ] Database performance tracking

## Backup Strategy

### 🗄️ Database Backups
```bash
# Daily database backup script
#!/bin/bash
BACKUP_DIR="/backups/budget-planner"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u backup_user -p budget_planner > $BACKUP_DIR/budget_planner_$DATE.sql
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
```

### 📁 File Backups
- [ ] Set up automated file backups
- [ ] Test backup restoration process
- [ ] Store backups in secure, off-site location
- [ ] Document recovery procedures

## Maintenance Schedule

### 🔄 Regular Maintenance
- [ ] **Daily**: Check error logs
- [ ] **Weekly**: Review user activity logs
- [ ] **Monthly**: Database cleanup and optimization
- [ ] **Quarterly**: Security audit and updates
- [ ] **Annually**: Backup restoration testing

### 🔧 Updates
- [ ] Plan for PHP updates
- [ ] Schedule database updates
- [ ] Set up staging environment for testing updates
- [ ] Document rollback procedures

## Performance Optimization

### ⚡ Server Optimization
- [ ] Enable PHP OPcache
- [ ] Configure database query cache
- [ ] Set up compression (gzip)
- [ ] Optimize images and static assets
- [ ] Enable browser caching

### 🗄️ Database Optimization
- [ ] Add proper indexes to frequently queried columns
- [ ] Optimize database queries
- [ ] Set up query performance monitoring
- [ ] Plan for database growth

## Support Information

### 📞 Emergency Contacts
- **System Administrator**: [email/phone]
- **Database Administrator**: [email/phone]
- **Developer Team**: [email/phone]
- **Hosting Provider**: [contact info]

### 📚 Documentation
- [ ] Create administrator manual
- [ ] Document common issues and solutions
- [ ] Maintain change log
- [ ] Keep deployment procedures updated

## Final Verification

### ✅ Go-Live Checklist
- [ ] All above items completed
- [ ] Performance testing passed
- [ ] Security audit completed
- [ ] Backup systems verified
- [ ] Monitoring systems active
- [ ] Support team briefed
- [ ] Documentation updated
- [ ] Rollback plan ready

---

## 🆘 Emergency Procedures

### System Down
1. Check web server status
2. Verify database connectivity
3. Check disk space
4. Review error logs
5. Contact hosting provider if needed

### Data Recovery
1. Stop application
2. Restore from latest backup
3. Verify data integrity
4. Test all functionality
5. Resume normal operation

### Security Breach
1. Immediate system isolation
2. Change all admin passwords
3. Review access logs
4. Apply security patches
5. Notify affected users

---

**Deployment Date**: ___________  
**Deployed By**: ___________  
**Verified By**: ___________  
**Version**: 1.0.0

---

*Remember: Always test in a staging environment before deploying to production!*