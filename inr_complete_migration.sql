-- Run this in phpMyAdmin or MySQL
ALTER TABLE `inr_withdrawals`
  ADD COLUMN `payment_notes` text DEFAULT NULL AFTER `approved_at`,
  ADD COLUMN `completed_at` datetime DEFAULT NULL AFTER `payment_notes`,
  MODIFY COLUMN `status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending';

ALTER TABLE `user_transactions`
  ADD COLUMN IF NOT EXISTS `admin_note` text DEFAULT NULL AFTER `description`;
