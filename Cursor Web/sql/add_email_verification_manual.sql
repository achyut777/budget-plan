-- Run this SQL in phpMyAdmin or MySQL client to add email verification columns
ALTER TABLE users 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER email,
ADD COLUMN verification_token VARCHAR(255) NULL AFTER email_verified,
ADD COLUMN verification_expires DATETIME NULL AFTER verification_token,
ADD COLUMN verified_at DATETIME NULL AFTER verification_expires,
ADD COLUMN verification_attempts INT DEFAULT 0 AFTER verified_at;

ALTER TABLE users 
ADD INDEX idx_verification_token (verification_token),
ADD INDEX idx_email_verified (email_verified);
