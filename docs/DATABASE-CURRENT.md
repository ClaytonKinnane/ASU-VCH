# Текущее состояние базы данных

Дата актуализации repository physical schema: `2026-08-08`.

## Current repository baseline

```text
latest functional merge: PR #36 / Military Positions Directory v1
migrations on main: 001–014
system roles: 4
system permissions: 35
DBMS: MySQL 8.4.x
engine: InnoDB
charset: utf8mb4
application access: PDO / emulated prepares disabled
```

Current HEAD определяется live. This file describes merged physical schema; target `DATABASE.md` may be broader.

## Migration inventory

| № | Файл | Область |
|---:|---|---|
| 001 | `001_starter_security.sql` | starter security/bootstrap |
| 002 | `002_security_users_management.sql` | users/RBAC |
| 003 | `003_security_user_approval.sql` | approval audit |
| 004 | `004_security_user_rejection_audit.sql` | rejection audit |
| 005 | `005_security_user_archive_restore.sql` | archive/restore |
| 006 | `006_theme_management.sql` | active theme |
| 007 | `007_military_ranks_directory.sql` | Military Ranks v1 |
| 008 | `008_organizational_element_types_directory.sql` | organizational element types |
| 009 | `009_organizational_structure_v1.sql` | Organizational Structure v1 |
| 010 | `010_military_positions_directory.sql` | legacy/public military-position classifier |
| 011 | `011_public_military_occupational_specialties_directory.sql` | public VUS |
| 012 | `012_military_ranks_directory_v2.sql` | Military Ranks v2 lifecycle/semantics/sources |
| 013 | `013_lowest_unit_staffing_v1.sql` | Lowest Unit Staffing Structure v1 |
| 014 | `014_military_positions_directory_v1.sql` | Managed Military Positions Directory v1 |

## Permission baseline

Migration 009 added six Organization permissions; migration 013 added six Staffing permissions; migration 014 added four Military Positions Directory permissions. Current total is `35`. Migration 014 does not auto-assign its permissions to non-owner roles; owner wildcard remains `system.*.*`.

## Military Ranks v2 — migrations 007 + 012

```text
v1: superseded / historical
v2: published / current
rank records/version: 20
v2 compositions/categories: 8
v2 semantic records: 8
v2 version sources: 2
v2 composition sources: 8
```

Published/superseded rows are immutable according to the implemented lifecycle guards.

## Organizational Structure v1 — migration 009

```text
tables: 7
DB triggers: 16
permissions added: 6
```

## Legacy Military Positions — migration 010

```text
tables: 14
DB triggers: 41
canonical types: 34
normative variants: 35
```

Migrations 010–011 use marker + gzip/base64 compatibility packaging with hash verification.

## Public VUS — migration 011

```text
tables: 9
DB triggers: 26
searchable records: 17
```

No automatic relations to position/rank/equipment/personnel are inferred.

## Staffing — migration 013

Implemented tables:

```text
staffing_registers
staffing_slot_identities
staffing_versions
staffing_documents
staffing_version_documents
staffing_slots
staffing_slot_vus_requirements
staffing_change_events
```

Staffing stores normative structure, not persons/assignments/occupancy.

## Managed Military Positions Directory — migration 014

Migration 014 is standalone SQL and evolves existing `military_position_catalog_versions` and `military_position_types` in place; it does not create a parallel catalog and does not modify migration-010 marker/loader/payload parts.

Implemented changes:

```text
new history table: military_position_change_events
initial canonical version: draft
initial canonical entries: 24 synthetic names
explicit combined entries: 9
new permissions: 4
automatic non-owner grants: 0
```

Lifecycle: `draft/published/superseded/cancelled`. Stable keys, normalized-name uniqueness, revisions, terminal immutability and append-only change history are guarded. Existing Staffing pins are not remapped. Legacy classifier remains published until explicit canonical publication; migration 014 does not auto-publish the draft.

## Migration 014 validation evidence

Exact runtime head `c647a933011873048866c75978d3f506634011fd`:

```text
pre-migration backup: PASS
PHP lint: 171 PASS
initialization: PASS
migrations: 001–014
repeat initialization: PASS
DB/runtime checker: 167 PASS
HTTP smoke: 200,200,302
three managed desktop themes: PASS
real Staffing data mutation: NONE
mobile: NOT RUN / OUT OF SCOPE
```

This evidence belongs to that runtime head. Later docs/merge/history-only commits are not re-labelled as DB-tested.

## Source of truth

1. `database/migrations/*.sql` in `main`;
2. installer registry/table `migrations`;
3. compatibility loaders/canonical modules where applicable;
4. profile checkers and exact runtime evidence.

Credentials remain local only and are not documented or committed.
