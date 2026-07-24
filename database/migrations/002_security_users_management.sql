ALTER TABLE user_roles
    ADD COLUMN assigned_by BIGINT UNSIGNED NULL AFTER assigned_at,
    ADD CONSTRAINT fk_user_roles_assigned_by
        FOREIGN KEY (assigned_by) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE SET NULL;

ALTER TABLE role_permissions
    ADD COLUMN assigned_by BIGINT UNSIGNED NULL AFTER assigned_at,
    ADD CONSTRAINT fk_role_permissions_assigned_by
        FOREIGN KEY (assigned_by) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE SET NULL;

INSERT INTO roles (code, name, description, is_system, created_at, updated_at) VALUES
    ('system_owner', 'Владелец системы', 'Главная системная роль с абсолютными правами.', 1, NOW(), NOW()),
    ('administrator', 'Администратор', 'Администрирование пользователей, ролей и системных параметров.', 1, NOW(), NOW()),
    ('operator', 'Оператор', 'Выполнение рабочих операций в разрешенных модулях.', 1, NOW(), NOW()),
    ('viewer', 'Наблюдатель', 'Просмотр разрешенных данных без административных изменений.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    is_system = 1,
    updated_at = VALUES(updated_at);

INSERT INTO permissions (code, name, description, is_system, created_at, updated_at) VALUES
    ('system.*.*', 'Абсолютный системный доступ', 'Полный доступ ко всем текущим и будущим ресурсам системы.', 1, NOW(), NOW()),
    ('security.users.view', 'Просмотр пользователей', 'Просмотр списка и карточек пользователей.', 1, NOW(), NOW()),
    ('security.users.create', 'Создание пользователей', 'Создание новых учетных записей.', 1, NOW(), NOW()),
    ('security.users.update', 'Изменение пользователей', 'Изменение основных данных учетной записи.', 1, NOW(), NOW()),
    ('security.users.block', 'Блокировка пользователей', 'Блокировка и разблокировка учетных записей.', 1, NOW(), NOW()),
    ('security.users.archive', 'Архивирование пользователей', 'Мягкое удаление учетных записей.', 1, NOW(), NOW()),
    ('security.users.restore', 'Восстановление пользователей', 'Восстановление архивированных учетных записей.', 1, NOW(), NOW()),
    ('security.users.reset_password', 'Сброс пароля', 'Установка нового временного пароля.', 1, NOW(), NOW()),
    ('security.users.assign_roles', 'Назначение ролей', 'Назначение и снятие разрешенных ролей пользователя.', 1, NOW(), NOW()),
    ('security.roles.view', 'Просмотр ролей', 'Просмотр системных и пользовательских ролей.', 1, NOW(), NOW()),
    ('security.roles.create', 'Создание ролей', 'Создание пользовательских ролей.', 1, NOW(), NOW()),
    ('security.roles.update', 'Изменение ролей', 'Изменение пользовательских ролей.', 1, NOW(), NOW()),
    ('security.roles.delete', 'Удаление ролей', 'Удаление пользовательских ролей после проверки зависимостей.', 1, NOW(), NOW()),
    ('security.roles.assign_permissions', 'Назначение разрешений', 'Изменение матрицы разрешений ролей.', 1, NOW(), NOW()),
    ('security.permissions.view', 'Просмотр разрешений', 'Просмотр каталога разрешений.', 1, NOW(), NOW()),
    ('system.settings.view', 'Просмотр настроек', 'Просмотр системных настроек.', 1, NOW(), NOW()),
    ('system.settings.update', 'Изменение настроек', 'Изменение системных настроек.', 1, NOW(), NOW()),
    ('system.diagnostics.view', 'Просмотр диагностики', 'Просмотр технической информации и диагностики.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    is_system = 1,
    updated_at = VALUES(updated_at);

INSERT INTO role_permissions (role_id, permission_id, assigned_at, assigned_by)
SELECT r.id, p.id, NOW(), NULL
FROM roles r
JOIN permissions p ON p.code = 'system.*.*'
WHERE r.code = 'system_owner'
ON DUPLICATE KEY UPDATE assigned_at = role_permissions.assigned_at;

INSERT INTO role_permissions (role_id, permission_id, assigned_at, assigned_by)
SELECT r.id, p.id, NOW(), NULL
FROM roles r
JOIN permissions p ON p.code IN (
    'security.users.view',
    'security.users.create',
    'security.users.update',
    'security.users.block',
    'security.users.archive',
    'security.users.restore',
    'security.users.reset_password',
    'security.users.assign_roles',
    'security.roles.view',
    'security.roles.create',
    'security.roles.update',
    'security.roles.assign_permissions',
    'security.permissions.view',
    'system.settings.view',
    'system.settings.update',
    'system.diagnostics.view'
)
WHERE r.code = 'administrator'
ON DUPLICATE KEY UPDATE assigned_at = role_permissions.assigned_at;

INSERT INTO role_permissions (role_id, permission_id, assigned_at, assigned_by)
SELECT r.id, p.id, NOW(), NULL
FROM roles r
JOIN permissions p ON p.code = 'security.users.view'
WHERE r.code IN ('operator', 'viewer')
ON DUPLICATE KEY UPDATE assigned_at = role_permissions.assigned_at;
