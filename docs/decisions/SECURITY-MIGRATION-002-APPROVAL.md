# Approval: Security Migration 002

Статус: **Approved**

Утверждено:

- преобразовать `database/install.php` в последовательный migration runner;
- добавить миграцию `002_security_users_management.sql`;
- добавить `assigned_by` в `user_roles` и `role_permissions`;
- создать системные роли и каталог разрешений Security v1.0;
- назначить начальную матрицу разрешений;
- сохранить существующего владельца, его логин и пароль;
- не выдавать `security.roles.delete` роли `administrator` в первой версии;
- исправить итоговое сообщение `Initialize-Local.ps1`;
- после реализации выполнить локальную инициализацию, проверку структуры RBAC и smoke test.

Основание: `docs/design/SECURITY-MIGRATION-002-DESIGN.md`.
