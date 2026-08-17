ALTER TABLE users
    ADD COLUMN reset_token_hash VARCHAR(64) NULL AFTER password_hash,
    ADD COLUMN reset_token_expires_at TIMESTAMP NULL AFTER reset_token_hash;
