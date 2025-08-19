# 🗑️ Files to Remove for System Cleanup

## 📋 **DUPLICATE/UNNECESSARY FILES**

### **❌ Old/Backup Files**
```
target.php (replaced by target-fixed.php)
analytics.php (replaced by analytics-fixed.php)
admin-users.php (duplicate of users.php)
target-backup.php
target-new.php
analytics-backup.php
analytics-new.php
fundraiser-backup.php
fundraiser-simple.php
users-new.php
users-new-redirect.php
users-redirect.php
dashboard-new.php
sidebar-admin-new.php
```

### **❌ Test Files**
```
test_*.php (all test files)
quick_test.php
simple_test.php
final_test.php
desktop_test.php
verify_today_data.php
check_*.php (all check files)
insert_today_data.php
test-fundraiser-display.php
test_api.php
test_api_direct.php
test_dashboard_api.php
test_dashboard_normal.php
test_donatur_hybrid.php
test_hybrid_approach.php
test_responsive.php
test_users_hybrid.php
TEST_ALL_PAGES.php
api_test.php
final_test.php
```

### **❌ Debug Files**
```
debug_dashboard.php
desktop_test.php
fundraiser-debug.php
rekomendasi_arsitektur.php
update-pages-template.php
header-template.php
layout_template.php
```

### **❌ Temporary Files**
```
dummy_log.txt
fix-all-headers.php
DEMO_HEADER_FIXED.html
```

### **❌ Documentation Files (Keep only main ones)**
```
ICON_SIZE_FIX.md
ADMIN_FIXES_COMPLETE.md
CRITICAL_FIXES_COMPLETE.md
ALL_FIXES_COMPLETED.md
DEMO_HEADER_FIXED.html
DESKTOP_FIX_FINAL.md
FINAL_IMPLEMENTATION_SUMMARY.md
FINAL_SEPARATION_COMPLETE.md
FUNDRAISER_AUTO_DISPLAY_SUMMARY.md
HEADER_FIXED_SUMMARY.md
HEADER_SIDEBAR_FIX.md
IMPLEMENTASI_HEADER_BARU.md
MANUAL_FIX_DONATUR.md
MENU_STRUCTURE_UPDATE.md
MOBILE_RESPONSIVENESS.md
PEMISAHAN_FUNCTIONALITY.md
SEMUA_MASALAH_FIXED.md
TABEL_FUNDRAISER_FIXED.md
TODO.md
UPDATE_MENU_INSTRUCTIONS.md
analisis_masalah_api_js.php
```

## ✅ **FILES TO KEEP (CORE SYSTEM)**

### **🔧 Core PHP Files**
```
config.php (centralized database connection)
dashboard.php
kunjungan.php
donatur.php
users.php (fundraiser management)
fundraiser-target.php (individual target)
target-fixed.php (global target)
analytics-fixed.php (analytics & reports)
settings.php
login.php
logout.php
index.php
404.php
```

### **📁 Layout Files**
```
sidebar-admin.php
sidebar-user.php
layout-header.php
layout-footer.php
```

### **🎨 Style Files**
```
styles/main.css
styles/icon-fixes.css
```

### **📜 JavaScript Files**
```
js/app.js
js/utils.js
js/config.js
js/kunjungan_api.js
js/donatur_api.js
js/users_api.js
js/mobile-menu.js
js/icon-fixes.js
```

### **🔌 API Files**
```
api/kunjungan.php
api/donatur.php
api/users.php
```

### **🗄️ Database Files**
```
database.sql
database_migration.php
setup_database.php
```

### **📄 Configuration Files**
```
.htaccess
.gitattributes
README.md
SYSTEM_VALIDATION_COMPLETE.md
```

## 🚀 **CLEANUP COMMANDS**

### **For Linux/Mac:**
```bash
# Remove test files
rm test_*.php
rm quick_test.php simple_test.php final_test.php desktop_test.php
rm verify_today_data.php insert_today_data.php
rm check_*.php
rm test-*.php
rm *_test.php

# Remove backup files
rm *-backup.php
rm *-new.php
rm *-debug.php
rm *-simple.php

# Remove documentation (keep main ones)
rm ICON_SIZE_FIX.md
rm ADMIN_FIXES_COMPLETE.md
rm CRITICAL_FIXES_COMPLETE.md
rm ALL_FIXES_COMPLETED.md
rm DEMO_HEADER_FIXED.html
rm DESKTOP_FIX_FINAL.md
rm FINAL_*.md
rm FUNDRAISER_*.md
rm HEADER_*.md
rm IMPLEMENTASI_*.md
rm MANUAL_*.md
rm MENU_*.md
rm MOBILE_*.md
rm PEMISAHAN_*.md
rm SEMUA_*.md
rm TABEL_*.md
rm TODO.md
rm UPDATE_*.md

# Remove temporary files
rm dummy_log.txt
rm fix-all-headers.php
rm header-template.php
rm layout_template.php
rm rekomendasi_arsitektur.php
rm update-pages-template.php
rm analisis_masalah_api_js.php
```

### **For Windows:**
```cmd
# Remove test files
del test_*.php
del quick_test.php simple_test.php final_test.php desktop_test.php
del verify_today_data.php insert_today_data.php
del check_*.php
del test-*.php
del *_test.php

# Remove backup files
del *-backup.php
del *-new.php
del *-debug.php
del *-simple.php

# Remove documentation (keep main ones)
del ICON_SIZE_FIX.md
del ADMIN_FIXES_COMPLETE.md
del CRITICAL_FIXES_COMPLETE.md
del ALL_FIXES_COMPLETED.md
del DEMO_HEADER_FIXED.html
del DESKTOP_FIX_FINAL.md
del FINAL_*.md
del FUNDRAISER_*.md
del HEADER_*.md
del IMPLEMENTASI_*.md
del MANUAL_*.md
del MENU_*.md
del MOBILE_*.md
del PEMISAHAN_*.md
del SEMUA_*.md
del TABEL_*.md
del TODO.md
del UPDATE_*.md

# Remove temporary files
del dummy_log.txt
del fix-all-headers.php
del header-template.php
del layout_template.php
del rekomendasi_arsitektur.php
del update-pages-template.php
del analisis_masalah_api_js.php
```

## 📊 **EXPECTED RESULT**

After cleanup:
- **Before:** ~80+ files
- **After:** ~30-35 core files
- **Reduction:** ~60% file reduction
- **Benefits:** 
  - Cleaner codebase
  - Easier maintenance
  - Better performance
  - Reduced confusion
  - Standardized structure

## ⚠️ **IMPORTANT NOTES**

1. **Backup first** before removing files
2. **Test thoroughly** after cleanup
3. **Keep debug folder** for future debugging
4. **Document any issues** found during cleanup
5. **Update documentation** if needed

## 🎯 **VALIDATION AFTER CLEANUP**

1. Run `debug/connection_test.php` to test database
2. Run `debug/navigation_test.php` to test all links
3. Test all CRUD operations
4. Test export functionality
5. Test responsive design
6. Verify all features work correctly