ALTER TABLE users
    ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER deleted_at,
    ADD COLUMN creation_reason VARCHAR(500) NULL AFTER created_by,
    ADD COLUMN approval_status VARCHAR(20) NOT NULL DEFAULT 'approved' AFTER creation_reason,
    ADD COLUMN approved_by BIGINT UNSIGNED NULL AFTER approval_status,
    ADD COLUMN approved_at DATETIME NULL AFTER approved_by,
    ADD CONSTRAINT fk_users_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    ADD CONSTRAINT fk_users_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    ADD CONSTRAINT chk_users_approval_status
        CHECK (approval_status IN ('pending', 'approved', 'rejected')),
    ADD INDEX idx_users_approval_status (approval_status),
    ADD INDEX idx_users_created_by (created_by),
    ADD INDEX idx_users_approved_by (approved_by);

UPDATE users
SET approval_status = 'approved'
WHERE approval_status IS NULL OR approval_status = '';
