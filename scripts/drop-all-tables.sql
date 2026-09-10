-- Tables created on this host since the source's last deploy aren't in the restored dump, so
-- they'd otherwise survive the restore as orphaned artifacts and collide with forward migrations.
SET FOREIGN_KEY_CHECKS = 0;
SET @tables = (SELECT GROUP_CONCAT(table_name) FROM information_schema.tables WHERE table_schema = DATABASE());
SET @drop_sql = IF(@tables IS NULL, 'DO 0', CONCAT('DROP TABLE IF EXISTS ', @tables));
PREPARE stmt FROM @drop_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET FOREIGN_KEY_CHECKS = 1;
