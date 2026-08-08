# Спецификации и выполнение migrations АСУ-ВЧ

## Назначение и классификация

Каталог содержит target physical specifications; executable migrations находятся в `database/migrations`. Этот `README.md` — living migration index.

```text
Architecture / Domain Specification
→ ERD or Increment Specification
→ Migration Specification
→ Review → Approval
→ executable migration
→ Integration Tests
```

Migration не вводит hidden architecture decisions.

## Current numbering

Current merged `main` contains migrations `001–014`:

| № | Файл | Назначение |
|---:|---|---|
| 001 | `001_starter_security.sql` | starter Security/bootstrap |
| 002 | `002_security_users_management.sql` | users/RBAC |
| 003 | `003_security_user_approval.sql` | approval audit |
| 004 | `004_security_user_rejection_audit.sql` | rejection audit |
| 005 | `005_security_user_archive_restore.sql` | archive/restore |
| 006 | `006_theme_management.sql` | active theme |
| 007 | `007_military_ranks_directory.sql` | Military Ranks v1 baseline |
| 008 | `008_organizational_element_types_directory.sql` | element types |
| 009 | `009_organizational_structure_v1.sql` | Organization Structure v1 |
| 010 | `010_military_positions_directory.sql` | public/legacy military position types |
| 011 | `011_public_military_occupational_specialties_directory.sql` | public VUS information |
| 012 | `012_military_ranks_directory_v2.sql` | rank catalog lifecycle, semantics, sources and v2 publication |
| 013 | `013_lowest_unit_staffing_v1.sql` | versioned lowest-unit staffing registers, slots and history |
| 014 | `014_military_positions_directory_v1.sql` | managed canonical military-position directory lifecycle |

Source of truth: executable files, compatibility loaders, installer registry and profile checkers.

## Migration 009 — Organizational Structure v1

```text
tables: 7
triggers: 16
permissions added: 6
system permissions after 009: 25
```

## Migration 010 — Military Positions

```text
compatibility loader: database/MilitaryPositionMigrationCompatibility.php
transport: 5 ordered gzip/base64 parts
tables: 14
triggers: 41
canonical types: 34
variants: 35
new permissions: 0
```

## Migration 011 — Public VUS

```text
compatibility loader: database/MilitaryOccupationalSpecialtyMigrationCompatibility.php
transport: 2 ordered gzip/base64 parts
tables: 9
triggers: 26
searchable records: 17
new permissions: 0
```

## Migration 012 — Military Ranks Directory v2

Approved records:

```text
docs/design/MILITARY-RANKS-DIRECTORY-V2-DESIGN.md
docs/review/MILITARY-RANKS-DIRECTORY-V2-FORMAL-REVIEW.md
docs/decisions/MILITARY-RANKS-DIRECTORY-V2-APPROVAL.md
docs/implementation/MILITARY-RANKS-DIRECTORY-V2-IMPLEMENTATION.md
docs/testing/MILITARY-RANKS-DIRECTORY-V2-TEST-REPORT.md
```

Mechanism:

```text
marker: database/migrations/012_military_ranks_directory_v2.sql
loader: database/MilitaryRankDirectoryV2MigrationCompatibility.php
canonical modules: database/MilitaryRankDirectoryV2/*
transport packaging: not gzip/base64
marker bypass: fail closed
```

Migration 012 adds lifecycle columns/guards, two tables for version-scoped semantics and composition sources, exact publication/recovery contracts and 18 lifecycle/integrity/immutability triggers.

Published outcome:

```text
v1: superseded / valid_to 2026-08-02
v2: published/current / valid_from 2026-08-03
compositions/categories: 8
semantic records: 8
rank records: 20 unchanged codes/names/order
version sources: 2
composition sources: 8
new permissions: 0
```

Recovery supports fresh v1, DDL-only partial, exact valid building cleanup/recreate, contradictory building fail-closed and exact published-without-registry recovery.

## Permission baseline

Migrations 010–012 add no permissions. Migration 013 adds six Staffing permissions; migration 014 adds four military-position directory permissions without role assignments:

```text
system roles: 4
system permissions after 013: 31
current system permissions after 014: 35
automatic non-owner grants from 014: 0
```

## Migration 013 — Lowest Unit Staffing Structure v1

Migration 013 introduces versioned Staffing registers, stable individual slots, document metadata, VUS requirements, Organization/catalog pins and append-only history. It does not introduce persons, assignments or occupancy/vacancy facts.

## Migration 014 — Managed Military Positions Directory v1

```text
mechanism: standalone SQL
protected migration-010 marker/loader/payload touch: forbidden
legacy current published version: preserved
initial canonical version: draft
approved synthetic entries: 24
explicit is_combined=true: 9
new table: military_position_change_events
new permissions: 4
automatic non-owner grants: 0
automatic canonical publication: NO
Staffing remap: NO
```

Migration evolves `military_position_catalog_versions` and `military_position_types` in place, adds `draft/published/superseded/cancelled`, stable keys, normalized-name uniqueness, revisions, audit metadata and append-only history. It performs no Staffing remap and no destructive legacy DROP.

## Specification requirements

Before implementation fix:

- object order and types/collation;
- PK/FK/UNIQUE/CHECK/indexes;
- generated columns and triggers;
- seed and stable codes;
- idempotency/recovery;
- rollback and backup policy;
- packaging/loader contract;
- exact verification and rejection scenarios.

## Common rules

- MySQL 8.4 / InnoDB / utf8mb4;
- no MySQL ENUM;
- restrictive FK by default;
- no fixed numeric IDs for critical references;
- repeat installer creates no duplicates;
- no production/instance credentials in migrations;
- first owner is not created by static production migration.

## Execution baseline

Before material migration: clean exact SHA, SQL backup, deploy-file backup, review, deploy preserving `config/local.php`, installer, repeat installer, profile checkers, regressions, parity, HTTP/browser acceptance.

PR #24 had a documented pre-migration-backup deviation; post-migration backup was created and checked. This deviation is not hidden or generalized as normal policy.

Current migration-014 accepted evidence on exact runtime head `c647a933011873048866c75978d3f506634011fd`:

```text
pre-migration backup: PASS
PHP lint: 171 PASS
initialization: PASS
applied migrations: 14
repeat initialization: PASS / no new migration
DB/runtime checker: 167 PASS
HTTP smoke: 200,200,302
three managed desktop themes: PASS
real Staffing data mutation: NONE
mobile: OUT OF SCOPE / NOT RUN
```

For a synchronized current installation, expected repeat installer state is 14 applied migrations and no new migrations.

Target files `SECURITY-MIGRATIONS.md`, `REFERENCE-MIGRATIONS.md` and `DOCUMENTS-MIGRATIONS.md` may be broader than current physical schema.
