# Security User Archive & Restore v1 — Approval

Дата: 2026-07-26
Статус: утверждено владельцем проекта
Ветка: `feature/user-archive-restore`
База: `main` (`859a2dc7462a41a4e630b637485bf346437ccdd0`)

## Утвержденные документы

- `docs/design/SECURITY-USER-ARCHIVE-RESTORE-V1-DESIGN.md`;
- `docs/design/SECURITY-USER-ARCHIVE-RESTORE-V1-REVIEW.md`.

## Решение владельца проекта

Владелец проекта явно утвердил Architecture/Specification/Review Security User Archive & Restore v1 и разрешил реализацию.

Точная формулировка утверждения:

```text
Утверждаю Architecture/Specification/Review Security User Archive & Restore v1 и разрешаю реализацию.
```

## Разрешенный объем реализации

Разрешено реализовать:

- migration 005 с аудитом последнего цикла archive/restore;
- переиспользование существующих permissions `security.users.archive` и `security.users.restore`;
- транзакционный сервис архивирования и восстановления;
- запрет самоархивирования;
- защиту последнего active + approved + nonarchived владельца;
- сохранение `approval_status`, ролей, пароля, логина и email;
- восстановление только в inactive-состояние;
- POST + CSRF + PRG маршруты;
- read-only карточку архивированной записи;
- sensitive audit только для system_owner и administrator;
- исключение архива из default list и явный фильтр `archived`;
- CLI integration checker и регрессионные проверки;
- desktop UI в текущей теме АСУ-ВЧ.

## Ограничения

Не разрешены в этом инкременте:

- физическое удаление пользователей;
- массовые archive/restore операции;
- автоматическая активация после restore;
- освобождение логина или email архивированной записи;
- изменение ролей при archive/restore;
- append-only история нескольких циклов;
- изменение approve/reject workflow;
- мобильная приемка как обязательный test gate;
- PR или merge до завершения тестирования и финального review.

## Следующий gate

После реализации обязательны автоматические и ручные desktop-проверки. Pull Request создается только после успешного test report и отдельного final review. Merge выполняется только после отдельного явного разрешения владельца проекта.
