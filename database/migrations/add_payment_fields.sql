-- Add transaction_id column to transactions table
ALTER TABLE `transactions`
ADD COLUMN `transaction_id` VARCHAR(100) UNIQUE;
