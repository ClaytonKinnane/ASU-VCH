# Approval — Security Migration 003

Статус: **Approved**  
Дизайн: `docs/design/SECURITY-MIGRATION-003-DESIGN.md`  
Дополнение: `docs/design/SECURITY-USER-CREATE-V1-AUDIT-METADATA-ADDENDUM.md`

## Утверждено

1. Создается миграция `003_security_user_approval.sql`.
2. В `users` добавляются `created_by`, `creation_reason`, `approval_status`, `approved_by`, `approved_at`.
3. Новые административно создаваемые учетные записи будут иметь `approval_status = 'pending'` и `is_active = 0`.
4. Существующие учетные записи сохраняются как `approved`; существующий владелец остается активным.
5. `created_by` и `approved_by` ссылаются на `users.id` с `ON DELETE SET NULL`.
6. Допустимые статусы: `pending`, `approved`, `rejected`.
7. `creation_reason` допускает `NULL` для исторических, bootstrap- и системных операций; административная форма будет требовать значение от 10 до 500 символов.
8. Создатель может сам подтвердить учетную запись при наличии `security.users.update`.
9. Добавляется отдельная CLI-проверка миграции 003.
10. Реализация формы создания, подтверждения и карточки пользователя выполняется отдельным следующим инкрементом.

## Разрешение на реализацию

Разрешено реализовать:

- `database/migrations/003_security_user_approval.sql`;
- `database/check-security-user-approval.php`;
- необходимые проверки совместимости без изменения текущего интерфейса.
