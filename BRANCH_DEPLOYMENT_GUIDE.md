# 🌿 Branch Management & Deployment Guide

## 📋 **Branch Strategy**

### **Branch Structure:**
```
main (production-ready)
├── production (deployment branch)
└── development (ongoing development)
```

## 🎯 **Branch Purposes**

### **1. `main` Branch** 🏠
- **Purpose:** Source of truth for production code
- **Status:** Always production-ready
- **Access:** Admin only
- **Deployment:** Automatic to production server

### **2. `production` Branch** 🚀
- **Purpose:** Deployment-specific configurations
- **Status:** Staging for production deployment
- **Access:** Admin & DevOps
- **Deployment:** Manual deployment to production

### **3. `development` Branch** 🔧
- **Purpose:** Ongoing development and features
- **Status:** Latest development work
- **Access:** All developers
- **Deployment:** Development/testing environment

## 🔄 **Workflow**

### **Development Workflow:**
```bash
# 1. Start development
git checkout development
git pull origin development

# 2. Create feature branch
git checkout -b feature/new-feature
# ... work on feature ...

# 3. Commit and push
git add .
git commit -m "Add new feature"
git push origin feature/new-feature

# 4. Merge to development
git checkout development
git merge feature/new-feature
git push origin development
```

### **Production Deployment:**
```bash
# 1. Update production branch
git checkout production
git merge development
git push origin production

# 2. Deploy to production
# Use deployment script or manual deployment
```

### **Hotfix Workflow:**
```bash
# 1. Create hotfix branch from main
git checkout main
git checkout -b hotfix/critical-fix
# ... fix critical issue ...

# 2. Merge to all branches
git checkout main
git merge hotfix/critical-fix
git push origin main

git checkout production
git merge hotfix/critical-fix
git push origin production

git checkout development
git merge hotfix/critical-fix
git push origin development
```

## 🚀 **Deployment Commands**

### **Production Deployment:**
```bash
# Switch to production branch
git checkout production

# Pull latest changes
git pull origin production

# Deploy to production server
# (Use your deployment script)
```

### **Development Deployment:**
```bash
# Switch to development branch
git checkout development

# Pull latest changes
git pull origin development

# Deploy to development server
# (Use your deployment script)
```

## 📁 **Environment-Specific Files**

### **Production Environment:**
- `config.php` - Production database settings
- `.env.production` - Production environment variables
- `robots.txt` - Allow search engines
- `sitemap.xml` - Production sitemap

### **Development Environment:**
- `config.php` - Development database settings
- `.env.development` - Development environment variables
- `robots.txt` - Disallow search engines
- Debug mode enabled

## 🔧 **Configuration Management**

### **Database Configuration:**
```php
// config.php
<?php
// Production Database
if ($_SERVER['HTTP_HOST'] === 'your-production-domain.com') {
    $host = 'production-db-host';
    $dbname = 'production_db';
    $username = 'production_user';
    $password = 'production_password';
} else {
    // Development Database
    $host = 'localhost';
    $dbname = 'fundraising_dev';
    $username = 'root';
    $password = '';
}
?>
```

### **Environment Variables:**
```bash
# .env.production
APP_ENV=production
APP_DEBUG=false
DB_HOST=production-db-host
DB_NAME=production_db
DB_USER=production_user
DB_PASS=production_password

# .env.development
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
DB_NAME=fundraising_dev
DB_USER=root
DB_PASS=
```

## 🛡️ **Security Considerations**

### **Production Security:**
- ✅ SSL/HTTPS enabled
- ✅ Error reporting disabled
- ✅ Debug mode disabled
- ✅ Strong passwords
- ✅ Firewall configured
- ✅ Regular backups

### **Development Security:**
- ⚠️ Debug mode enabled
- ⚠️ Error reporting enabled
- ⚠️ Test data allowed
- ✅ Local access only

## 📊 **Monitoring & Logging**

### **Production Monitoring:**
- Error logging to file
- Performance monitoring
- Uptime monitoring
- Security monitoring

### **Development Monitoring:**
- Console logging
- Debug information
- Development tools

## 🔄 **Backup Strategy**

### **Database Backups:**
```bash
# Production backup
mysqldump -h production-host -u user -p production_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Development backup
mysqldump -h localhost -u root -p fundraising_dev > dev_backup_$(date +%Y%m%d_%H%M%S).sql
```

### **File Backups:**
```bash
# Production files backup
tar -czf production_files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/production/

# Development files backup
tar -czf dev_files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/development/
```

## 🚨 **Emergency Procedures**

### **Rollback Procedure:**
```bash
# 1. Identify the issue
git log --oneline -10

# 2. Rollback to previous commit
git checkout production
git reset --hard HEAD~1
git push origin production --force

# 3. Deploy rollback
# (Use your deployment script)
```

### **Emergency Contact:**
- **System Admin:** [Admin Contact]
- **Database Admin:** [DB Admin Contact]
- **Hosting Provider:** [Hosting Contact]

## 📝 **Checklist**

### **Before Production Deployment:**
- [ ] All tests passing
- [ ] Code review completed
- [ ] Security scan passed
- [ ] Performance tested
- [ ] Database migration ready
- [ ] Backup completed
- [ ] SSL certificate valid
- [ ] Environment variables set

### **After Production Deployment:**
- [ ] Website accessible
- [ ] All features working
- [ ] Database connected
- [ ] SSL working
- [ ] Monitoring active
- [ ] Backup scheduled
- [ ] Team notified

## 🎯 **Best Practices**

### **Code Quality:**
- Write clean, documented code
- Follow coding standards
- Use meaningful commit messages
- Review code before merging

### **Security:**
- Never commit sensitive data
- Use environment variables
- Regular security updates
- Monitor for vulnerabilities

### **Performance:**
- Optimize database queries
- Minimize file sizes
- Use caching where appropriate
- Monitor performance metrics

---

## 📞 **Support**

For questions or issues with deployment:
- **Email:** [support@yourcompany.com]
- **Slack:** #deployment-support
- **Documentation:** [Wiki Link]

---

*Last updated: $(date)*