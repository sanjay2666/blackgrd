-- Task 1.2B-Part 1: reviewed manual schema repair.
-- EXECUTED on blackgrd on 2026-08-03 after separate approval and backup.
-- DO NOT RE-RUN: the individual_id column is no longer present.
-- Observed on 2026-08-03: 3 total rows and 0 non-null individual_id values.

-- Step 1: capture the pre-change counts and table definition.
SELECT COUNT(*) AS total_rows,
       SUM(`individual_id` IS NOT NULL) AS non_null_individual_id_count
FROM `colours`;

SHOW CREATE TABLE `colours`;

-- Step 2: create and verify a table-only backup outside the MySQL client.
-- Replace placeholders; do not store credentials in this repository.
--
-- mysqldump -u <user> -p --single-transaction --skip-lock-tables --hex-blob blackgrd colours > colours_before_individual_id_removal_<timestamp>.sql
-- PowerShell: Get-Item <backup-path> | Select-Object FullName,Length,LastWriteTime
-- PowerShell: Get-FileHash -Algorithm SHA256 <backup-path>

-- Step 3: execute only after the pre-change count and backup are verified.
ALTER TABLE `colours` DROP COLUMN `individual_id`;

-- Step 4: verify that no rows were lost and the column/index are absent.
SELECT COUNT(*) AS total_rows_after
FROM `colours`;

SELECT `COLUMN_NAME`
FROM `information_schema`.`COLUMNS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'colours'
  AND `COLUMN_NAME` = 'individual_id';

SELECT `INDEX_NAME`, `COLUMN_NAME`
FROM `information_schema`.`STATISTICS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'colours'
  AND `COLUMN_NAME` = 'individual_id';
