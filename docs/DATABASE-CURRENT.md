# Текущее состояние базы данных

Дата проверки: `2026-07-27`.

Этот документ описывает фактически реализованный schema baseline. `DATABASE.md`, ERD и доменные migration specifications могут описывать более широкую целевую архитектуру.

## Технологическая база

```text
DBMS: MySQL 8.4.8
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

## Применённые миграции

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

Контрольный installer подтвердил 8 применённых миграций и отсутствие новых миграций.

## Security и пользователи

Реализованы данные для:

- пользователей;
- системных ролей;
- системных permissions;
- назначений ролей;
- связей ролей и permissions;
- глобальных system settings;
- approval audit;
- rejection audit;
- archive/restore audit;
- обязательной смены временного пароля.

Ключевые инварианты:

- username и email канонизируются и проверяются на уникальность;
- пароль хранится только как hash;
- owner определяется системной ролью, а не boolean-флагом пользователя;
- в установке поддерживается один активный owner;
- последний активный owner защищён от блокировки и архивирования;
- rejected и archived records не могут войти;
- восстановление не активирует пользователя автоматически;
- изменяющие lifecycle-операции транзакционны.

## Theme Management

Migration 006 хранит глобальную настройку активной темы и пользователя, последним изменившего настройку. Допустимый theme slug дополнительно ограничен статическим allow-list в `config/themes.php`.

## Справочник воинских званий

Migration 007 создаёт 5 таблиц версионируемого каталога.

Текущая контрольная модель:

```text
current versions: 1
legal sources: 2
compositions: 6
normative rank pairs/levels: 20
system permissions after migration: 19
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

Текущая контрольная модель:

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
system permissions after migration: 19
```

Generated guards и UNIQUE constraints ограничивают текущую версию и основной класс. Композитные FK защищают принадлежность типов, классов, aliases и источников одной версии. Каталог read-only.

## Идемпотентность и recovery

Installer применяет только незарегистрированные migrations. После успешного выполнения повторный запуск должен завершаться без новых migrations и без дубликатов seed-данных.

Migration 008 дополнительно рассчитана на повторный запуск после частичного отказа до регистрации migration. Seed использует стабильные коды, upsert и согласованную collation.

## Backup policy

Перед migration, меняющей схему или данные:

1. создаётся SQL dump;
2. фиксируются размер и SHA-256;
3. сохраняются изменяемые deploy-файлы;
4. проверяется точный GitHub SHA;
5. после применения выполняются installer repeat и integration checker.

Post-merge deploy без новой migration требует backup deploy-файлов, но не нового SQL dump.

## Не реализовано

Целевая архитектура может содержать следующие ещё не реализованные сущности:

- военнослужащие;
- конкретные воинские части и подразделения;
- должности и назначения;
- фактическая структура и подчинённость;
- документы и версии файлов;
- универсальный reference runtime;
- общий audit log всех доменов.

Их описание в архитектурных документах не означает наличие соответствующих таблиц в текущем baseline.
