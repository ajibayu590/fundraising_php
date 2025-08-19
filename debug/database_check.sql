-- ===== DATABASE VALIDATION SCRIPT =====
-- Run this script to validate database structure and data integrity

-- Check if database exists
SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'fundraising_db';

-- Check all tables exist
SHOW TABLES;

-- Check table structures
DESCRIBE users;
DESCRIBE donatur;
DESCRIBE kunjungan;
DESCRIBE settings;

-- Check foreign key relationships
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = 'fundraising_db'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Check data integrity
-- Users table
SELECT COUNT(*) as total_users FROM users;
SELECT role, COUNT(*) as count FROM users GROUP BY role;
SELECT status, COUNT(*) as count FROM users GROUP BY status;

-- Donatur table
SELECT COUNT(*) as total_donatur FROM donatur;
SELECT COUNT(DISTINCT hp) as unique_phones FROM donatur;

-- Kunjungan table
SELECT COUNT(*) as total_kunjungan FROM kunjungan;
SELECT status, COUNT(*) as count FROM kunjungan GROUP BY status;
SELECT COUNT(DISTINCT fundraiser_id) as active_fundraisers FROM kunjungan;

-- Check for orphaned records
SELECT COUNT(*) as orphaned_kunjungan 
FROM kunjungan k 
LEFT JOIN users u ON k.fundraiser_id = u.id 
WHERE u.id IS NULL;

-- Check for data consistency
SELECT 
    'Users without target' as issue,
    COUNT(*) as count
FROM users 
WHERE role = 'user' AND (target IS NULL OR target = 0)
UNION ALL
SELECT 
    'Kunjungan without nominal' as issue,
    COUNT(*) as count
FROM kunjungan 
WHERE status = 'berhasil' AND (nominal IS NULL OR nominal = 0);

-- Performance check
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    DATA_LENGTH,
    INDEX_LENGTH
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'fundraising_db';

-- Check indexes
SHOW INDEX FROM users;
SHOW INDEX FROM donatur;
SHOW INDEX FROM kunjungan;