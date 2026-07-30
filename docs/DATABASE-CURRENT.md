# Текущее состояние базы данных

Дата проверки functional baseline: `2026-07-29`.
Дата документационной сверки: `2026-07-30`.

Этот документ описывает фактически реализованный schema baseline. `DATABASE.md`, ERD и доменные migration specifications могут описывать более широкую целевую архитектуру.

## Repository pointer и functional anchors

Актуальный repository HEAD определяется динамически:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

Точный SHA текущего `main` не хранится здесь как самореферентное living-поле. Для schema/runtime используются устойчивые anchors:

```text
last completed documentation PR before reconciliation: #16
last completed documentation merge before reconciliation: 72630757c1a72a6bd971cf819cff9bdd36c148bf
last functional PR: #15
last functional merge commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
applied migrations: 9
system roles: 4
system permissions: 25
```

PR #16 и Post-PR16 Repository Reconciliation являются documentation-only и не создают нового schema/runtime baseline.

## Технологическая база

```text
DBMS: MySQL 8.4.x
engine: InnoDB
charset: utf8mb4
migration seed collation: utf8mb4_unicode_ci
application access: PDO
emulated prepares: disabled
```

Credentials находятся только в deploy-файле `config/local.php` и не хранятся в Git.

## Источник истины

Фактическую схему определяют:

1. `database/migrations/*.sql` в `main`;
2. таблица регистрации `migrations`;
3. `database/install.php`;
4. профильные CLI integration checker'ы;
5. post-migration проверки в целевой MySQL.

Контрольный installer подтвердил 9 зарегистрированных migrations и повторный запуск без новых migrations.

## Применённые migrations

| № | Файл | Реализованная область |
|---:|---|---|
| 001 | `001_starter_security.sql` | базовые users, roles, permissions, owner bootstrap и settings |
| 002 | `002_security_users_management.sql` | управление пользователями и RBAC |
| 003 | `003_security_user_approval.sql` | audit metadata подтверждения |
| 004 | `004_security_user_rejection_audit.sql` | rejection state и audit metadata |
| 005 | `005_security_user_archive_restore.sql` | archive/restore state и audit metadata |
| 006 | `006_theme_management.sql` | глобальная active theme и last actor |
| 007 | `007_military_ranks_directory.sql` | версионируемый нормативный каталог воинских званий |
| 008 | `008_organizational_element_types_directory.sql` | версионируемый каталог типов организационных элементов |
| 009 | `009_organizational_structure_v1.sql` | структуры, версии, дерево, документы, change events и DB guards |

## Security и пользователи

Реализованы users, roles, permissions, назначения ролей, связи role–permission, system settings и lifecycle audit metadata.

Ключевые инварианты:

- username и email канонизируются и проверяются на уникальность;
- пароль хранится только как hash;
- owner определяется системной ролью;
- последний активный owner защищён от блокировки и архивирования;
- rejected и archived records не могут войти;
- восстановление не активирует пользователя автоматически;
- изменяющие lifecycle-операции транзакционны.

## Theme Management

Migration 006 хранит глобальную настройку активной темы и пользователя, последним изменившего настройку. Допустимый theme slug дополнительно ограничен статическим allow-list в `config/themes.php`.

## Справочник воинских званий

Migration 007 создаёт 5 таблиц версионируемого каталога.

```text
current versions: 1
legal sources: 2
compositions: 6
normative rank pairs/levels: 20
```

Каталог read-only; текущая версия и поиск проверяются `tools/check-military-ranks-directory.php`.

## Справочник типов организационных элементов

Migration 008 создаёт 7 таблиц:

```text
organizational_element_catalog_versions
organizational_element_catalog_version_sources
organizational_element_classes
organizational_element_types
organizational_element_type_classes
organizational_element_type_aliases
organizational_element_type_sources
```

```text
current versions: 1
legal sources: 4
classes: 6
types: 28
type-class links: 32
aliases: 0
non_subdivision_only: 12
subdivision_only: 12
mixed: 4
```

Generated guards, UNIQUE constraints и composite FK защищают принадлежность данных одной версии. Каталог read-only.

## Organizational Structure v1

Migration 009 создаёт 7 таблиц:

```text
organizational_structures
organizational_structure_elements
organizational_structure_versions
organizational_structure_documents
organizational_structure_version_documents
organizational_structure_nodes
organizational_structure_change_events
```

Назначение:

- `organizational_structures` — aggregate roots и lifecycle active/archived;
- `organizational_structure_elements` — стабильная identity элементов между версиями;
- `organizational_structure_versions` — версии, основание версии, catalog binding, status и revision;
- `organizational_structure_documents` — metadata документов внутри Organization scope;
- `organizational_structure_version_documents` — связи документов с версиями;
- `organizational_structure_nodes` — version-scoped дерево;
- `organizational_structure_change_events` — immutable история изменений.

### Lifecycle и consistency

```text
draft
approved
active
cancelled
```

Изменение дерева и связей документов разрешено только в draft-версии. Approved-версия может быть активирована либо отменена. Новая draft-версия создаётся на основе действующей или последней отменённой версии и сохраняет stable element identity.

Каждая версия связана с конкретной версией справочника типов организационных элементов. Узлы одной версии должны использовать типы из того же catalog baseline.

### DB-level guards

Migration 009 создаёт 16 triggers. Они защищают lifecycle-переходы, historical records, запрет изменения дерева вне draft, ownership consistency, недопустимые UPDATE/DELETE и сохранность change-event history.

Application layer повторяет критические проверки для понятной ошибки пользователя, а DB остаётся финальной защитой invariant.

### Permissions

Migration 009 добавляет 6 permissions:

```text
organization.structures.view
organization.structures.create
organization.structures.update
organization.structures.publish
organization.structures.archive
organization.structures.history
```

Итоговое количество системных permissions: `25`. Новые permissions не назначаются автоматически ролям `administrator`, `operator` и `viewer`; owner сохраняет полный доступ через `system.*.*`.

### Проверка

```text
organization checks: 58 PASS / 0 FAIL
tables: 7
triggers: 16
organization permissions: 6
system roles: 4
system permissions: 25
```

## Идемпотентность и recovery

Installer применяет только незарегистрированные migrations. После успешного выполнения повторный запуск завершается без новых migrations и без дубликатов seed-данных.

Migration 009 имеет отдельную compatibility-проверку для MySQL 8.4 и контролируемого повторного запуска после частичного DDL-состояния до регистрации migration.

## Backup policy

Перед migration, меняющей schema или данные:

1. создаётся SQL dump;
2. фиксируются размер и SHA-256;
3. сохраняются изменяемые deploy-файлы;
4. проверяется точный GitHub SHA;
5. после применения выполняются installer repeat и integration checker.

Post-merge deploy без новой migration требует backup изменяемых deploy-файлов, но не нового SQL dump.

## Не реализовано

- карточки военнослужащих;
- должности, штатные позиции и кадровые назначения;
- общий Documents domain, document files и universal workflow;
- медицинский учёт, имущество, транспорт и обучение;
- общий audit log всех доменов.

Metadata документов в Organizational Structure v1 принадлежит Organization scope и не означает реализацию общего Documents runtime.
