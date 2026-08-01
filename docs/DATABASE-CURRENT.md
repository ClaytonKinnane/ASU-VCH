# Текущее состояние базы данных

Дата актуализации functional baseline: `2026-08-01`.

Документ описывает фактически реализованный schema baseline. Целевые ERD и domain specifications могут описывать более широкий будущий scope.

## Repository pointer и anchors

Актуальный repository HEAD определяется динамически:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

```text
latest functional PR: #20
PR #19 merge: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #20 merge / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime: 9db06c4a26066ca25dc36c627c1236089a3c1238
applied migrations: 11
system roles: 4
system permissions: 25
```

Documentation-only commits не создают нового schema/runtime baseline.

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
2. таблица `migrations`;
3. `database/install.php`;
4. compatibility loaders и packaged canonical SQL;
5. профильные CLI integration checker'ы;
6. post-migration проверки в MySQL.

Последний полный runner подтвердил 11 зарегистрированных migrations и repeat installer без новых migrations.

## Применённые migrations

| № | Файл | Реализованная область |
|---:|---|---|
| 001 | `001_starter_security.sql` | users, roles, permissions, owner bootstrap, settings |
| 002 | `002_security_users_management.sql` | управление пользователями и RBAC |
| 003 | `003_security_user_approval.sql` | approval audit metadata |
| 004 | `004_security_user_rejection_audit.sql` | rejection lifecycle и audit |
| 005 | `005_security_user_archive_restore.sql` | archive/restore lifecycle и audit |
| 006 | `006_theme_management.sql` | global active theme и last actor |
| 007 | `007_military_ranks_directory.sql` | нормативный каталог воинских званий |
| 008 | `008_organizational_element_types_directory.sql` | каталог типов организационных элементов |
| 009 | `009_organizational_structure_v1.sql` | structures, versions, tree, documents, events и DB guards |
| 010 | `010_military_positions_directory.sql` | публичный каталог типовых воинских должностей |
| 011 | `011_public_military_occupational_specialties_directory.sql` | публичные сведения о ВУС и программах подготовки |

## Security и пользователи

Реализованы users, roles, permissions, role assignments, settings и lifecycle audit metadata.

Инварианты:

- canonical unique username/email;
- password hash only;
- единственный active system owner;
- защита последнего active owner;
- rejected/archived/inactive records не входят в систему;
- restore не активирует автоматически;
- изменяющие lifecycle-операции транзакционны.

## Theme Management

Migration 006 хранит global active theme и last actor. Допустимый slug ограничен статическим allowlist `config/themes.php`.

## Справочник воинских званий — migration 007

```text
catalog tables: 5
current versions: 1
legal sources: 2
compositions: 6
rank levels: 20
```

Каталог read-only.

## Типы организационных элементов — migration 008

```text
catalog tables: 7
current versions: 1
legal sources: 4
classes: 6
types: 28
type-class relations: 32
```

Каталог read-only и отделяет типы от организационных классов.

## Organizational Structure v1 — migration 009

```text
tables: 7
DB triggers: 16
permissions added: 6
```

Реализованы structure/version lifecycle, draft tree, stable elements, documents metadata, immutable events, history и compare.

## Типовые воинские должности — migration 010

```text
tables: 14
DB triggers: 41
published versions: 1
version sources: 4
source entries: 24
source-entry evidence: 28
families: 4
canonical types: 34
normative variants: 35
composition scopes: 2
organizational relations: 29
rank relation tables: 0
```

Модель whole-catalog versioned и immutable после publication. Она хранит публичные нормативные типы, а не штатные позиции и не кадровые назначения.

Критические rejection paths:

- insert child в published version;
- backward lifecycle transition;
- tariff grade вне 1–50;
- cross-version evidence relations.

## Публичные сведения о ВУС — migration 011

```text
tables: 9
DB triggers: 26
published versions: 1
legal sources: 5
official source snapshots: 4
code segments: 3
public context domains: 6
personnel scopes: 3
direct disclosures: 2
training organizations: 4
training programs: 15
searchable records: 17
```

Модель source-centric. Published data immutable. Identifier kinds:

```text
none
base-specialty-number
full-code-complete
official-program-identifier
```

Шестизначные identifiers программ подготовки не разбиваются семантически без публичного источника. Organization filter относится только к training programs; direct disclosures при выбранной организации исключаются.

Отсутствуют relation-таблицы к:

- воинским должностям;
- воинским званиям;
- ВВСТ;
- пользователям и военнослужащим.

## Migration packaging

Migrations 010 и 011 используют marker SQL и compatibility loader для canonical SQL, упакованного в gzip/base64 parts. Runner проверяет archive SHA-256, canonical SQL SHA-256, порядок частей и точное восстановление SQL до выполнения.

VUS package anchors:

```text
archive SHA-256: 1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39
canonical SQL SHA-256: 26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9
canonical SQL bytes: 88267
parts: 2
```

## Последняя проверка

```text
applied migrations: 11
repeat installer: no new migrations
system roles: 4
system permissions: 25
military positions checker: PASS
VUS integration checker: PASS
VUS UI checker: PASS
organization regression: 58 PASS / 0 FAIL
HTTP smoke: PASS
```

Mobile testing не относится к schema validation и для PR #19/#20 было `OUT OF SCOPE / NOT RUN`.
