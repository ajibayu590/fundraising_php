#!/bin/bash

# ========================================
# FUNDRAISING SYSTEM - DEPLOYMENT SCRIPT
# ========================================
# This script handles deployment while preserving config.php

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="fundraising_php"
DEPLOY_PATH="/var/www/html"
BACKUP_PATH="/var/www/backups"
LOG_PATH="/var/log/deployments"

# Environment detection
ENVIRONMENT=${1:-"production"}
BRANCH=${2:-"production"}

echo -e "${BLUE}🚀 Starting deployment for $ENVIRONMENT environment${NC}"
echo -e "${BLUE}Branch: $BRANCH${NC}"
echo -e "${BLUE}Deploy path: $DEPLOY_PATH${NC}"

# ========================================
# PRE-DEPLOYMENT CHECKS
# ========================================

echo -e "${YELLOW}📋 Running pre-deployment checks...${NC}"

# Check if running as root or with sudo
if [[ $EUID -eq 0 ]]; then
   echo -e "${RED}❌ This script should not be run as root${NC}"
   exit 1
fi

# Check if deployment directory exists
if [ ! -d "$DEPLOY_PATH" ]; then
    echo -e "${RED}❌ Deployment directory does not exist: $DEPLOY_PATH${NC}"
    exit 1
fi

# Check if git is available
if ! command -v git &> /dev/null; then
    echo -e "${RED}❌ Git is not installed${NC}"
    exit 1
fi

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo -e "${RED}❌ Not in a git repository${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Pre-deployment checks passed${NC}"

# ========================================
# BACKUP CURRENT VERSION
# ========================================

echo -e "${YELLOW}💾 Creating backup of current version...${NC}"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_PATH"

# Create timestamp for backup
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="${PROJECT_NAME}_${ENVIRONMENT}_${TIMESTAMP}"

# Backup current version
if [ -d "$DEPLOY_PATH" ]; then
    tar -czf "$BACKUP_PATH/$BACKUP_NAME.tar.gz" -C "$DEPLOY_PATH" .
    echo -e "${GREEN}✅ Backup created: $BACKUP_PATH/$BACKUP_NAME.tar.gz${NC}"
else
    echo -e "${YELLOW}⚠️  No existing deployment to backup${NC}"
fi

# ========================================
# GIT OPERATIONS
# ========================================

echo -e "${YELLOW}📥 Updating from git repository...${NC}"

# Fetch latest changes
git fetch origin

# Checkout the specified branch
git checkout $BRANCH

# Pull latest changes
git pull origin $BRANCH

echo -e "${GREEN}✅ Git operations completed${NC}"

# ========================================
# PRESERVE CONFIGURATION FILES
# ========================================

echo -e "${YELLOW}🔒 Preserving configuration files...${NC}"

# Backup current config.php if it exists
if [ -f "$DEPLOY_PATH/config.php" ]; then
    cp "$DEPLOY_PATH/config.php" "$DEPLOY_PATH/config.php.backup"
    echo -e "${GREEN}✅ Current config.php backed up${NC}"
fi

# Backup other sensitive files
SENSITIVE_FILES=(".env" ".htaccess" "robots.txt")

for file in "${SENSITIVE_FILES[@]}"; do
    if [ -f "$DEPLOY_PATH/$file" ]; then
        cp "$DEPLOY_PATH/$file" "$DEPLOY_PATH/$file.backup"
        echo -e "${GREEN}✅ Current $file backed up${NC}"
    fi
done

# ========================================
# DEPLOY FILES
# ========================================

echo -e "${YELLOW}📦 Deploying files...${NC}"

# Create temporary deployment directory
TEMP_DEPLOY="/tmp/${PROJECT_NAME}_deploy_${TIMESTAMP}"
mkdir -p "$TEMP_DEPLOY"

# Copy all files except sensitive ones
rsync -av --exclude='config.php' \
         --exclude='.env*' \
         --exclude='.git' \
         --exclude='node_modules' \
         --exclude='vendor' \
         --exclude='*.log' \
         --exclude='uploads' \
         --exclude='temp' \
         --exclude='cache' \
         ./ "$TEMP_DEPLOY/"

# Copy to deployment directory
rsync -av --delete "$TEMP_DEPLOY/" "$DEPLOY_PATH/"

# Clean up temporary directory
rm -rf "$TEMP_DEPLOY"

echo -e "${GREEN}✅ Files deployed successfully${NC}"

# ========================================
# RESTORE CONFIGURATION FILES
# ========================================

echo -e "${YELLOW}🔧 Restoring configuration files...${NC}"

# Restore config.php if backup exists
if [ -f "$DEPLOY_PATH/config.php.backup" ]; then
    mv "$DEPLOY_PATH/config.php.backup" "$DEPLOY_PATH/config.php"
    echo -e "${GREEN}✅ config.php restored${NC}"
else
    echo -e "${YELLOW}⚠️  No config.php backup found. Please create config.php manually.${NC}"
    echo -e "${BLUE}💡 Use config.example.php as a template${NC}"
fi

# Restore other sensitive files
for file in "${SENSITIVE_FILES[@]}"; do
    if [ -f "$DEPLOY_PATH/$file.backup" ]; then
        mv "$DEPLOY_PATH/$file.backup" "$DEPLOY_PATH/$file"
        echo -e "${GREEN}✅ $file restored${NC}"
    fi
done

# ========================================
# SET PERMISSIONS
# ========================================

echo -e "${YELLOW}🔐 Setting file permissions...${NC}"

# Set directory permissions
find "$DEPLOY_PATH" -type d -exec chmod 755 {} \;

# Set file permissions
find "$DEPLOY_PATH" -type f -exec chmod 644 {} \;

# Set executable permissions for scripts
find "$DEPLOY_PATH" -name "*.sh" -exec chmod +x {} \;

# Set special permissions for uploads directory
if [ -d "$DEPLOY_PATH/uploads" ]; then
    chmod 755 "$DEPLOY_PATH/uploads"
    find "$DEPLOY_PATH/uploads" -type d -exec chmod 755 {} \;
    find "$DEPLOY_PATH/uploads" -type f -exec chmod 644 {} \;
fi

echo -e "${GREEN}✅ Permissions set successfully${NC}"

# ========================================
# DATABASE MIGRATION (Optional)
# ========================================

echo -e "${YELLOW}🗄️  Checking for database migrations...${NC}"

if [ -f "$DEPLOY_PATH/database_migration.php" ]; then
    echo -e "${BLUE}💡 Database migration file found. Run manually if needed:${NC}"
    echo -e "${BLUE}   php database_migration.php${NC}"
else
    echo -e "${GREEN}✅ No database migration needed${NC}"
fi

# ========================================
# POST-DEPLOYMENT CHECKS
# ========================================

echo -e "${YELLOW}🔍 Running post-deployment checks...${NC}"

# Check if config.php exists
if [ -f "$DEPLOY_PATH/config.php" ]; then
    echo -e "${GREEN}✅ config.php exists${NC}"
else
    echo -e "${RED}❌ config.php missing - please create from config.example.php${NC}"
fi

# Check if main files exist
MAIN_FILES=("index.php" "login.php" "dashboard.php")

for file in "${MAIN_FILES[@]}"; do
    if [ -f "$DEPLOY_PATH/$file" ]; then
        echo -e "${GREEN}✅ $file exists${NC}"
    else
        echo -e "${RED}❌ $file missing${NC}"
    fi
done

# ========================================
# CLEANUP
# ========================================

echo -e "${YELLOW}🧹 Cleaning up old backups...${NC}"

# Keep only last 5 backups
cd "$BACKUP_PATH"
ls -t ${PROJECT_NAME}_${ENVIRONMENT}_*.tar.gz | tail -n +6 | xargs -r rm

echo -e "${GREEN}✅ Cleanup completed${NC}"

# ========================================
# DEPLOYMENT COMPLETE
# ========================================

echo -e "${GREEN}🎉 Deployment completed successfully!${NC}"
echo -e "${BLUE}📊 Deployment Summary:${NC}"
echo -e "${BLUE}   Environment: $ENVIRONMENT${NC}"
echo -e "${BLUE}   Branch: $BRANCH${NC}"
echo -e "${BLUE}   Deploy Path: $DEPLOY_PATH${NC}"
echo -e "${BLUE}   Backup: $BACKUP_PATH/$BACKUP_NAME.tar.gz${NC}"
echo -e "${BLUE}   Timestamp: $TIMESTAMP${NC}"

# Log deployment
mkdir -p "$LOG_PATH"
echo "$TIMESTAMP - $ENVIRONMENT - $BRANCH - SUCCESS" >> "$LOG_PATH/deployments.log"

echo -e "${GREEN}✅ Deployment logged to $LOG_PATH/deployments.log${NC}"

# ========================================
# NEXT STEPS
# ========================================

echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "${BLUE}   1. Test the application${NC}"
echo -e "${BLUE}   2. Check error logs if needed${NC}"
echo -e "${BLUE}   3. Run database migrations if needed${NC}"
echo -e "${BLUE}   4. Update DNS if deploying to new domain${NC}"

echo -e "${GREEN}🚀 Deployment script completed!${NC}"