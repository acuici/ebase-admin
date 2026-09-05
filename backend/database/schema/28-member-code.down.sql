-- Rollback for 28-member-code.sql
ALTER TABLE `members` DROP INDEX `uk_member_code`, DROP COLUMN `member_code`;
