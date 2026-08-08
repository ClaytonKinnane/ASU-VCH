# Текущее состояние базы данных

Дата актуализации repository schema inventory: `2026-08-07`.

Документ описывает фактически реализованный physical schema baseline. Target ERD/domain specifications могут быть шире runtime.

## Repository anchors

```text
current main functional PR: #35
current main SHA: 9ae05b9928903cc483ce415d7378b546e419264c
main migration inventory: 001–013
implementation branch target migration: 014_military_positions_directory_v1.sql
local application of migration 014: NOT RUN in this worktree
system roles: 4
main system permissions: 31
target after migration 014: 35
```

Current repository HEAD определяется динамически. Technical PR #25 не менял schema и не создаёт новый database-tested baseline.

## Технологическая база

```text
DBMS: MySQL 8.4.x
engine: InnoDB
charset: utf8mb4
migration seed collation: utf8mb4_unicode_ci
application access: PDO
emulated prepares: disabled
```

Credentials находятся только в local deploy configuration и не хранятся в Git.

## Источник истины

1. `database/migrations/*.sql` в `main`;
2. таблица `migrations`;
3. `database/install.php`;
4. compatibility loaders и canonical implementation;
5. профильные integration checker'ы;
6. post-migration MySQL verification.

## Repository migration inventory

| № | Файл | Реализованная область |
|---:|---|---|
| 001 | `001_starter_security.sql` | starter Security/bootstrap |
| 002 | `002_security_users_management.sql` | users, roles, permissions |
| 003 | `003_security_user_approval.sql` | approval audit metadata |
| 004 | `004_security_user_rejection_audit.sql` | rejection lifecycle/audit |
| 005 | `005_security_user_archive_restore.sql` | archive/restore lifecycle |
| 006 | `006_theme_management.sql` | active theme and actor |
| 007 | `007_military_ranks_directory.sql` | Military Ranks catalog v1 baseline |
| 008 | `008_organizational_element_types_directory.sql` | organizational element types |
| 009 | `009_organizational_structure_v1.sql` | Organization Structure v1 |
| 010 | `010_military_positions_directory.sql` | public military position types |
| 011 | `011_public_military_occupational_specialties_directory.sql` | public VUS disclosures |
| 012 | `012_military_ranks_directory_v2.sql` | Military Ranks lifecycle/semantics/source evolution v2 |
| 013 | `013_lowest_unit_staffing_v1.sql` | Lowest Unit Staffing Structure v1 |
| 014 | `014_military_positions_directory_v1.sql` | managed canonical Military Positions Directory v1; implementation branch, local DB validation pending |

## Security и users

Реализованы users, roles, permissions, role assignments, settings и lifecycle audit metadata. `main` baseline: 4 system roles / 31 permissions. Migration 014 adds four module permissions without role assignments.

## Theme Management

Migration 006 хранит global active theme. Допустимый slug ограничен `config/themes.php`.

## Military Ranks Directory — migrations 007 + 012

Migration 007 создала v1. Migration 012 расширила physical model и опубликовала v2.

### Current lifecycle outcome

```text
v1: superseded / historical
v1 valid_to: 2026-08-02
v2: published / current
v2 valid_from: 2026-08-03
current versions: 1
visible published/superseded versions: 2
rank records per version: 20
v2 compositions/categories: 8
v2 semantic records: 8
v2 version sources: 2
v2 composition sources: 8
```

Rank codes, names и order сохранены. Derived categories не объявляются отдельными нормативными compositions.

### Migration 012 schema additions

- lifecycle fields `lifecycle_status`, `published_at`, `superseded_at`;
- generated guards для единственной current и building version;
- lifecycle CHECK constraints;
- table `military_personnel_composition_semantics`;
- table `military_personnel_composition_sources`;
- version-aware uniqueness and FK guards;
- 18 lifecycle/integrity/immutability triggers.

Published/superseded data immutable. Recovery допускает очистку только exact valid building state и fail closed при contradictory anchors.

### Compatibility mechanism

`database/migrations/012_military_ranks_directory_v2.sql` — marker, который обязан fail closed при обходе `MilitaryRankDirectoryV2MigrationCompatibility.php`.

Migration 012 не использует gzip/base64 packaging migrations 010–011. Compatibility loader собирает canonical DDL/publication/recovery из versioned PHP/SQL modules и проверяет exact state до registration/recovery.

### Access boundary

Route owner-only/read-only. Migration 012 не добавляет permissions. Compatibility service принадлежит Reference domain и не зависит от Organization.

## Organizational Element Types — migration 008

```text
catalog tables: 7
legal sources: 4
classes: 6
types: 28
type-class relations: 32
```

## Organizational Structure v1 — migration 009

```text
tables: 7
DB triggers: 16
permissions added: 6
```

## Military Positions — migration 010

```text
tables: 14
DB triggers: 41
canonical types: 34
normative variants: 35
rank relation tables: 0
```

## Managed Military Positions Directory — migration 014

Migration 014 is standalone SQL and does not modify migration-010 marker, loader or five gzip/base64 payload parts.

Target changes:

```text
existing catalog tables evolved: 2
parallel position catalogs created: 0
new history tables: 1 (military_position_change_events)
initial canonical version: draft
initial canonical entries: 24 synthetic names
explicit combined entries: 9
new permissions: 4
automatic non-owner grants: 0
```

Legacy classifier stays current published until an explicit atomic publication of the canonical draft. Existing Staffing pins and legacy metadata remain unchanged/readable. Lifecycle becomes `draft/published/superseded/cancelled`; stable identity, optimistic revisions, terminal immutability and append-only history are DB/service guarded.

## Public VUS — migration 011

```text
tables: 9
DB triggers: 26
searchable records: 17
```

Relation tables к positions, ranks, equipment и personnel отсутствуют.

## Migration packaging

Migrations 010–011 используют marker + gzip/base64 parts с archive/canonical SQL SHA-256 verification. Migration 012 использует отдельный compatibility-loader/marker mechanism без ложного gzip/base64 claim.

## Последняя functional post-merge проверка

```text
applied migrations: 12
repeat installer: no new migrations
Military Ranks v2 source/loader/service checks: PASS
Military Ranks DB regression: PASS
Organization regression: PASS
deploy/source parity: PASS
HTTP smoke: PASS
working tree: clean
mobile: OUT OF SCOPE / NOT RUN
```

GitHub static CI PR #25 не выполняет MySQL и не заменяет этот functional evidence.

Migration 014 clean/existing DB, repeat installer, deploy, HTTP and desktop evidence remains `NOT RUN` until the PowerShell 5.1 runner is executed on the exact implementation head. Mobile remains `OUT OF SCOPE / NOT RUN`.
