# Спецификации и выполнение миграций АСУ-ВЧ

## Назначение

Каталог `docs/migrations` содержит целевые физические спецификации по доменам. Исполняемые миграции находятся в `database/migrations`.

```text
Architecture / Domain Specification
→ ERD or Increment Specification
→ Migration Specification
→ Review
→ Approval
→ database/migrations/NNN_description.sql
→ Integration Tests
```

Миграция не должна вводить скрытое архитектурное решение, отсутствующее в утверждённой документации.

## Текущая нумерация

Исполняемые миграции используют последовательное имя:

```text
NNN_lowercase_description.sql
```

В текущем baseline зарегистрированы:

| № | Файл | Назначение |
|---:|---|---|
| 001 | `001_starter_security.sql` | минимальная Security-модель и bootstrap владельца |
| 002 | `002_security_users_management.sql` | роли, permissions и управление пользователями |
| 003 | `003_security_user_approval.sql` | audit metadata подтверждения |
| 004 | `004_security_user_rejection_audit.sql` | отклонение пользователя и аудит |
| 005 | `005_security_user_archive_restore.sql` | архивирование и восстановление |
| 006 | `006_theme_management.sql` | глобальная активная тема и audit actor |
| 007 | `007_military_ranks_directory.sql` | версионируемый справочник воинских званий |
| 008 | `008_organizational_element_types_directory.sql` | версионируемый справочник типов организационных элементов |

Источник истины для фактического порядка — таблица `migrations` и файлы `database/migrations` в `main`.

## Требования к спецификации

Перед реализацией фиксируются:

- порядок создания объектов;
- точные типы и collation;
- nullable/default-правила;
- PK, FK, UNIQUE, CHECK и индексы;
- generated columns;
- допустимые FK actions;
- seed-данные и стабильные коды;
- идемпотентность и восстановление после частичного отказа;
- rollback/recovery policy;
- автоматические проверки;
- необходимость SQL backup.

## Общие правила

- MySQL 8.4.8 и InnoDB;
- основная кодировка `utf8mb4`;
- migration seed использует согласованную collation `utf8mb4_unicode_ci`;
- MySQL `ENUM` не используется;
- FK по умолчанию используют `ON DELETE RESTRICT ON UPDATE RESTRICT`, если иное не утверждено;
- критические идентификаторы не задаются фиксированными числовыми ID;
- seed работает по стабильным кодам;
- повторный installer не создаёт дубликаты;
- секреты и локальные credentials в migration отсутствуют;
- первый владелец не создаётся статической production-миграцией.

## Выполнение

Перед новой migration выполняются:

1. проверка чистоты и точного GitHub SHA;
2. SQL backup;
3. backup изменяемых deploy-файлов;
4. PHP/SQL review;
5. deploy с сохранением `config/local.php`;
6. installer;
7. повторный installer с `Новых миграций нет.`;
8. профильный integration checker;
9. регрессионные checker'ы;
10. browser-проверка затронутого интерфейса.

## Статус целевых доменных спецификаций

Файлы `SECURITY-MIGRATIONS.md`, `REFERENCE-MIGRATIONS.md` и `DOCUMENTS-MIGRATIONS.md` описывают целевые доменные решения и могут быть шире фактического schema baseline. Текущее состояние схемы документируется в `../DATABASE-CURRENT.md`.
