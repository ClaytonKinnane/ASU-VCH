ALTER TABLE users
    ADD COLUMN archived_by BIGINT UNSIGNED NULL AFTER rejection_reason,
    ADD COLUMN last_archived_at DATETIME NULL AFTER archived_by,
    ADD COLUMN archive_reason VARCHAR(500) NULL AFTER last_archived_at,
    ADD COLUMN restored_by BIGINT UNSIGNED NULL AFTER archive_reason,
    ADD COLUMN restored_at DATETIME NULL AFTER restored_by,
    ADD COLUMN restore_reason VARCHAR(500) NULL AFTER restored_at,
    ADD CONSTRAINT fk_users_archived_by
        FOREIGN KEY (archived_by) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    ADD CONSTRAINT fk_users_restored_by
        FOREIGN KEY (restored_by) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    ADD INDEX idx_users_deleted_at (deleted_at),
    ADD INDEX idx_users_archived_by (archived_by),
    ADD INDEX idx_users_last_archived_at (last_archived_at),
    ADD INDEX idx_users_restored_by (restored_by),
    ADD INDEX idx_users_restored_at (restored_at);
