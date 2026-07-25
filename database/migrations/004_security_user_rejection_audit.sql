ALTER TABLE users
    ADD COLUMN rejected_by BIGINT UNSIGNED NULL AFTER approved_at,
    ADD COLUMN rejected_at DATETIME NULL AFTER rejected_by,
    ADD COLUMN rejection_reason VARCHAR(500) NULL AFTER rejected_at,
    ADD CONSTRAINT fk_users_rejected_by
        FOREIGN KEY (rejected_by) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    ADD INDEX idx_users_rejected_by (rejected_by),
    ADD INDEX idx_users_rejected_at (rejected_at);

INSERT INTO permissions (code, name, description, is_system, created_at, updated_at) VALUES
    ('security.users.reject', 'Отклонение пользователей', 'Отклонение ожидающей подтверждения учетной записи с фиксацией субъекта, даты и основания.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    is_system = 1,
    updated_at = VALUES(updated_at);

INSERT INTO role_permissions (role_id, permission_id, assigned_at, assigned_by)
SELECT r.id, p.id, NOW(), NULL
FROM roles r
JOIN permissions p ON p.code = 'security.users.reject'
WHERE r.code = 'administrator'
ON DUPLICATE KEY UPDATE assigned_at = role_permissions.assigned_at;
