# Спецификации и выполнение migrations АСУ-ВЧ

## Назначение и классификация

Каталог `docs/migrations` содержит целевые физические спецификации по доменам. Исполняемые migrations находятся в `database/migrations`.

Этот `README.md` является **living migration index**, поскольку сообщает текущую последовательность executable migrations. Целевые migration specifications могут быть шире реализованной схемы; физический baseline подтверждается `../DATABASE-CURRENT.md`, executable migrations, installer и профильными checker'ами.

```text
Architecture / Domain Specification
→ ERD or Increment Specification
→ Migration Specification
→ Review
→ Approval
→ database/migrations/NNN_description.sql
→ Integration Tests
```

Migration не должна вводить скрытое архитектурное решение, отсутствующее в утверждённой документации.

## Текущая нумерация

Исполняемые migrations используют последовательное имя:

```text
NNN_lowercase_description.sql
```

В текущем functional baseline зарегистрированы migrations `001–011`:

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
| 009 | `009_organizational_structure_v1.sql` | структуры, версии, дерево, metadata документов, change events и DB guards |
| 010 | `010_military_positions_directory.sql` | версионируемый публичный каталог типов воинских должностей |
| 011 | `011_public_military_occupational_specialties_directory.sql` | source-centric каталог публичных сведений о военно-учётных специальностях |

Источник истины для фактического порядка — таблица `migrations` и файлы `database/migrations` в актуальном `main`.

## Migration 009

Migration 009 реализует утверждённые документы:

```text
docs/design/ORGANIZATIONAL-STRUCTURE-V1-DESIGN.md
docs/design/ORGANIZATIONAL-STRUCTURE-V1-REVIEW.md
docs/decisions/ORGANIZATIONAL-STRUCTURE-V1-APPROVAL.md
docs/migrations/ORGANIZATIONAL-STRUCTURE-V1-MIGRATION.md
docs/domains/ORGANIZATION-STRUCTURE-V1-ADDENDUM.md
docs/erd/ERD-020-ORGANIZATIONAL-STRUCTURE-V1-ADDENDUM.md
```

Она создаёт:

```text
7 tables
16 triggers
6 organization.structures.* permissions
```

Итоговый system permission baseline после migration 009 — `25`.

Основные физические решения:

- structure aggregate root;
- stable organizational elements;
- version-scoped tree;
- catalog-version binding;
- metadata документов и version links;
- immutable change events;
- DB-level lifecycle и ownership guards;
- stable codes вместо фиксированных seed ID;
- совместимость с MySQL 8.4.

## Migration 010 — типовые воинские должности

Migration 010 реализует approved public normative scope PR #19:

```text
docs/architecture/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md
docs/specification/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md
docs/review/MILITARY-POSITIONS-DIRECTORY-V1-FORMAL-REVIEW.md
docs/decisions/MILITARY-POSITIONS-DIRECTORY-V1-IMPLEMENTATION-APPROVAL.md
docs/implementation/MILITARY-POSITIONS-DIRECTORY-V1-IMPLEMENTATION.md
```

Compatibility packaging:

```text
database/MilitaryPositionMigrationCompatibility.php
database/migrations/010_military_positions_directory.sql
5 ordered gzip/base64 parts
archive SHA-256 verification
canonical SQL SHA-256 verification
```

Physical outcome:

```text
14 tables
41 triggers
34 canonical position types
35 normative variants
new system permissions: 0
```

Каталог не является штатным расписанием, не содержит фактических назначений и не создаёт автоматических связей с воинскими званиями.

## Migration 011 — публичные сведения о ВУС

Migration 011 реализует approved public-source scope PR #20:

```text
docs/architecture/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-ARCHITECTURE.md
docs/specification/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-SPECIFICATION.md
docs/review/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-FORMAL-REVIEW.md
docs/decisions/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-IMPLEMENTATION-APPROVAL.md
docs/implementation/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-IMPLEMENTATION.md
```

Compatibility packaging:

```text
database/MilitaryOccupationalSpecialtyMigrationCompatibility.php
database/migrations/011_public_military_occupational_specialties_directory.sql
2 ordered gzip/base64 parts
archive SHA-256 verification
canonical SQL SHA-256 verification
```

Physical outcome:

```text
9 tables
26 triggers
17 public disclosure records
new system permissions: 0
```

Source/evidence context сохраняется; полнота закрытого или ведомственного классификатора не заявляется. Каталог не связывается автоматически с должностями, званиями, оборудованием или персональными данными.

## Permission baseline

Migrations 010 и 011 используют owner-only read access через существующий `system.*.*` и не добавляют permissions.

```text
system roles: 4
system permissions after migration 011: 25
```

## Требования к спецификации

Перед реализацией фиксируются:

- порядок создания объектов;
- точные типы и collation;
- nullable/default-правила;
- PK, FK, UNIQUE, CHECK и индексы;
- generated columns;
- допустимые FK actions;
- triggers;
- seed-данные и стабильные коды;
- идемпотентность и восстановление после частичного отказа;
- rollback/recovery policy;
- автоматические проверки;
- необходимость SQL backup;
- packaging и integrity verification для migration, превышающей безопасный размер обычной доставки.

## Общие правила

- MySQL 8.4.x и InnoDB;
- основная кодировка `utf8mb4`;
- migration seed использует `utf8mb4_unicode_ci`;
- MySQL `ENUM` не используется;
- FK по умолчанию используют `ON DELETE RESTRICT ON UPDATE RESTRICT`, если иное не утверждено;
- критические идентификаторы не задаются фиксированными числовыми ID;
- seed работает по стабильным кодам;
- повторный installer не создаёт дубликаты;
- секреты и instance-specific credentials в migration отсутствуют;
- первый владелец не создаётся статической production-migration.

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

Для migration 009 дополнительно используются:

```text
database/OrganizationalStructureMigrationCompatibility.php
tools/check-organizational-structure-migration-compatibility.php
tools/check-organizational-structure.php
database/check-organizational-structure.php
```

Для migrations 010–011 дополнительно проверяются ordered archive parts, archive hash, canonical SQL hash и совместимость с installer/MySQL 8.4.

## Статус целевых доменных спецификаций

Файлы `SECURITY-MIGRATIONS.md`, `REFERENCE-MIGRATIONS.md` и `DOCUMENTS-MIGRATIONS.md` описывают целевые доменные решения и могут быть шире фактического schema baseline. Текущее состояние схемы документируется в `../DATABASE-CURRENT.md`.
