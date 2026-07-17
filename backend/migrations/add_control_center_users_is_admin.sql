ALTER TABLE `control_center_users`
  ADD COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `account_status`;
