-- Add email verification columns to users table
ALTER TABLE users 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER email,
ADD COLUMN verification_token VARCHAR(255) NULL AFTER email_verified,
ADD COLUMN verification_expires DATETIME NULL AFTER verification_token,
ADD COLUMN verified_at DATETIME NULL AFTER verification_expires,
ADD COLUMN verification_attempts INT DEFAULT 0 AFTER verified_at;

-- Add index for faster token lookups
ALTER TABLE users 
ADD INDEX idx_verification_token (verification_token),
ADD INDEX idx_email_verified (email_verified);

-- Set existing users as verified (optional - for backward compatibility)
-- Uncomment the line below if you want existing users to be automatically verified
-- UPDATE users SET email_verified = 1, verified_at = CURRENT_TIMESTAMP WHERE email_verified = 0;