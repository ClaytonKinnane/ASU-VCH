# Security User Rejection Audit v1 — Approval

Дата: 2026-07-25
Статус: утверждено

## 1. Утвержденные документы

- `docs/design/SECURITY-USER-REJECTION-AUDIT-V1-DESIGN.md`
- `docs/design/SECURITY-USER-REJECTION-AUDIT-V1-REVIEW.md`

## 2. Явное решение владельца проекта

Владелец проекта сообщил:

> Утверждаю Architecture/Specification/Review Security User Rejection Audit v1 и разрешаю реализацию

## 3. Разрешенный объем реализации

Разрешено реализовать только утвержденный объем:

- migration 004 с rejected-аудитом;
- разрешение `security.users.reject`;
- `UserRejectionService`;
- POST-маршрут отклонения;
- интерфейс отклонения pending-пользователя;
- отображение результата и аудита;
- фильтр и счетчик rejected;
- CLI-проверку и регрессию.

## 4. Ограничения

Не разрешены без нового Architecture/Specification/Review/Approval:

- отмена отклонения;
- возврат в pending;
- подтверждение rejected-записи;
- массовые операции;
- удаление или архивирование rejected-записи;
- уведомления;
- история нескольких решений;
- любые функции паролей вне уже утвержденных инкрементов.

## 5. Процесс

После реализации обязательны:

```text
Testing -> Commit -> Push -> PR -> Final Review -> отдельное разрешение Merge
```

Создание PR не является разрешением на merge.
