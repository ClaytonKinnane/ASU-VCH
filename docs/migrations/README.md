# Спецификации и выполнение migrations АСУ-ВЧ

Этот file is the living migration index. Executable source of truth is `database/migrations` plus installer/compatibility loaders and checkers.

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
| 007 | `007_military_ranks_directory.sql` | Military Ranks v1 |
| 008 | `008_organizational_element_types_directory.sql` | organizational element types |
| 009 | `009_organizational_structure_v1.sql` | Organizational Structure v1 |
| 010 | `010_military_positions_directory.sql` | legacy/public military-position classifier |
| 011 | `011_public_military_occupational_specialties_directory.sql` | public VUS |
| 012 | `012_military_ranks_directory_v2.sql` | Military Ranks v2 lifecycle/semantics/sources |
| 013 | `013_lowest_unit_staffing_v1.sql` | Lowest Unit Staffing Structure v1 |
| 014 | `014_military_positions_directory_v1.sql` | Managed Military Positions Directory v1 |

## Permission baseline

```text
system roles: 4
system permissions after migration 014: 35
organization permissions added by 009: 6
staffing permissions added by 013: 6
military-position permissions added by 014: 4
automatic non-owner grants from 014: 0
```

## Compatibility mechanisms

- 010–011: fail-closed marker + gzip/base64 parts with hash/ordering checks;
- 012: dedicated compatibility loader/marker and versioned DDL/publication/recovery modules;
- 013: standalone Staffing migration;
- 014: standalone managed Military Positions migration; migration-010 marker/loader/payload protected from modification.

## Migration 014 current outcome

```text
existing catalog tables evolved: military_position_catalog_versions + military_position_types
new table: military_position_change_events
initial canonical version: draft
initial canonical entries: 24 synthetic
explicit combined entries: 9
lifecycle: draft/published/superseded/cancelled
new permissions: 4
auto publication: NO
Staffing remap: NO
```

Legacy current published classifier is preserved until explicit canonical publication. No destructive legacy DROP is performed.

## Execution evidence

Latest exact runtime validation head for migration 014: `c647a933011873048866c75978d3f506634011fd`.

```text
pre-migration backup: PASS
PHP lint: 171 PASS
initialization: PASS
applied migrations: 14
repeat initialization: PASS / no new migration
DB/runtime checker: 167 PASS
HTTP smoke: 200,200,302
three-theme desktop acceptance: PASS
real Staffing data mutation: NONE
mobile: NOT RUN / OUT OF SCOPE
```

## Common rules

- MySQL 8.4 / InnoDB / utf8mb4;
- no secret/instance credentials in migrations;
- no hidden architecture decisions in migration code;
- backup before material schema/data migration unless an explicit deviation is recorded;
- repeat installer must not create duplicates;
- physical schema claims are validated against executable migrations and DB evidence;
- target migration specifications may be broader than current implementation and must be labelled accordingly.
