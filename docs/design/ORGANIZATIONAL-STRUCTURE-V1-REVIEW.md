# Formal Review: фактическая организационная структура v1

## Результат

```text
FORMAL REVIEW: CORRECTED / PASS
SPECIFICATION: 0.2
BLOCKING FINDINGS AFTER CORRECTION: 0
```

## Исправления

1. Разделены неизменяемое содержимое версии и управляемый lifecycle.
2. Удалён избыточный случайный `identity_key`; стабильная идентичность — PK `organizational_structure_elements.id`.
3. Определены корни агрегатов Structure и Version.
4. Предметный журнал переименован в `organizational_structure_change_events` и отделён от общего Audit.
5. Отменена автоматическая выдача новых permissions обычным системным ролям.
6. Зафиксирована безопасная стратегия reorder с временным offset и последовательными значениями `10, 20, 30...`.
7. Добавлены DB-triggers неизменяемости опубликованных узлов, связей документов и истории.
8. Перечислены candidate keys и композитные FK для принадлежности одной структуре, версии и каталогу.
9. Завершена модель архивирования и восстановления контейнера.
10. ARIA tree допускается только при полной клавиатурной модели; текущий UI использует семантические карточки/списки.

## Проверенные области

```text
ARCHITECTURE: PASS
DATABASE MODEL: PASS
TREE MODEL: PASS
VERSIONING: PASS
RBAC: PASS
SECURITY: PASS
UI: PASS
TESTABILITY: PASS
```

## Обязательные тесты

- migration и повторный installer;
- DB constraints и triggers;
- цикл, self-parent, cross-version parent;
- immutable published data;
- conditional uniqueness незавершённой и активной версии;
- stable element IDs после клонирования;
- stale revision;
- транзакционная активация и supersede;
- append-only change history;
- отсутствие автоматических role-permission назначений;
- все три темы, desktop и mobile;
- regression Security и Directories.
