-- Stable member handles for owner filters; rollback: 28-member-code.down.sql
ALTER TABLE `members`
  ADD COLUMN `member_code` VARCHAR(64) NULL AFTER `name`,
  ADD UNIQUE KEY `uk_member_code` (`member_code`);

UPDATE `members`
SET `member_code` = CONCAT('member_', `id`)
WHERE `member_code` IS NULL OR `member_code` = '';
