# Military Positions Directory v1 — Formal Review

## 1. Статус

```text
DOCUMENT=Formal Review
VERSION=0.2
INCREMENT=Military Positions Directory v1
ARCHITECTURE_VERSION=0.2
SPECIFICATION_VERSION=0.2
IMPLEMENTATION_BASE=main@9ae05b9928903cc483ce415d7378b546e419264c
IMPLEMENTATION_BRANCH=feature/military-positions-directory-v1
MIGRATION=014_military_positions_directory_v1.sql
POST_STAFFING_RECONCILIATION=PASS
FORMAL_REVIEW=PASS
IMPLEMENTATION=AUTHORIZED
```

## 2. Review scope

Проверены:

- owner-approved conceptual model and the exact 24-entry synthetic dataset;
- design source `bad4057251f9ebf996d83b3e246df24127a5d5cc`;
- live post-Staffing `main@9ae05b9928903cc483ce415d7378b546e419264c`;
- PR #35 merge and successful post-merge Actions;
- migrations 001–013 and absence of migration 014;
- migration-010 marker, loader, payload packaging, schema and trigger dependencies;
- Staffing v1 pinning and position selector behavior;
- runtime navigation/RBAC, theme and living-document dependencies;
- exact 38-path implementation allowlist and prohibited changes.

## 3. Anchor review

```text
MAIN_HEAD=9ae05b9928903cc483ce415d7378b546e419264c
IMPLEMENTATION_BRANCH_CREATED_FROM_EXACT_MAIN=PASS
DESIGN_SOURCE_HEAD=bad4057251f9ebf996d83b3e246df24127a5d5cc
DESIGN_SOURCE_MERGE_BASE=3d8a491ff2433994e8580152f190b298c765c66e
DESIGN_BRANCH_IS_NOT_IMPLEMENTATION_BASE=PASS
MIGRATION_013_PRESENT=PASS
MIGRATION_014_ABSENT_BEFORE_IMPLEMENTATION=PASS
UNEXPECTED_MATERIAL_INCREMENT=NONE
OPEN_PULL_REQUESTS=0
OPEN_ISSUES=0
```

## 4. Review questions

### RQ-01. Is a second catalog needed?

Result: NO. Existing `military_position_catalog_versions` and `military_position_types` evolve in place.

### RQ-02. Can migration 010 or legacy classifier schema be replaced?

Result: NO. Migration 014 is standalone SQL. Marker, compatibility loader and five payload parts stay byte-for-byte untouched. Legacy tables/metadata/history remain.

### RQ-03. Is the initial rollout safe?

Result: YES. Legacy version stays published; migration creates one canonical draft with exactly 24 approved entries. Publication is an explicit later UI action that atomically supersedes legacy without deletion.

### RQ-04. Are Staffing histories remapped?

Result: NO. Existing versions retain exact pinned catalog/type/variant references. Draft-from-active retains its catalog version.

### RQ-05. Are archived canonical entries safe?

Result: YES. They remain readable for historical references and are excluded only from selection for new Staffing slots.

### RQ-06. Are catalog-level rank/org catalog links position properties?

Result: NO. Links remain solely for migration-010 and Staffing compatibility. Canonical entry endpoints/model do not accept VUS, rank, unit, person, equipment or occupancy fields.

### RQ-07. Is RBAC complete?

Result: YES in the approved design. Four permissions are created without non-owner grants; module/content navigation and directory tiles are permission-aware; routes/actions enforce the exact required permission.

### RQ-08. Is the UI scope testable?

Result: YES. Managed version/entry/history/form layouts use theme variables across all three desktop themes. Mobile remains outside scope.

## 5. Architecture/Specification consistency

```text
ONE_CANONICAL_CATALOG=PASS
NO_PARALLEL_POSITION_ENTITY=PASS
POSITION_HAS_NO_VUS=PASS
POSITION_HAS_NO_RANK_REQUIREMENT=PASS
POSITION_HAS_NO_ORG_BINDING=PASS
POSITION_HAS_NO_PERSON=PASS
VERSION_PINNING=PASS
NO_HIDDEN_STAFFING_REMAP=PASS
PUBLISHED_IMMUTABILITY=PASS
STABLE_IDENTITY=PASS
OPTIMISTIC_REVISIONS=PASS
DRAFT_CONCURRENCY=PASS
APPEND_ONLY_HISTORY=PASS
LEGACY_HISTORY_PRESERVED=PASS
NO_DESTRUCTIVE_DROP_IN_V1=PASS
INITIAL_CANONICAL_DRAFT=PASS
EXACT_24_NAME_INITIAL_SET=PASS
EXACT_9_COMBINED_FLAGS=PASS
ARCHIVED_ENTRY_SELECTION_RULE=PASS
PERMISSION_AWARE_NAVIGATION=PASS
THREE_THEME_DESKTOP_REQUIREMENT=PASS
MOBILE_NOT_CLAIMED=PASS
```

## 6. Security/privacy review

```text
PERSONAL_DATA=NONE
REAL_PERSONNEL=NONE
REAL_STAFFING_DATA=NONE
ASSIGNMENTS=NONE
OCCUPIED_VACANT=NONE
CITIZEN_MILITARY_ACCOUNTING=EXCLUDED
EXCEL_RUNTIME_IMPORT=NONE
```

## 7. Data integrity review

Design 0.2 preserves:

- all legacy catalog rows/metadata/FKs and source provenance;
- existing Staffing foreign-key readability and version pinning;
- stable identity across copied canonical versions;
- normalized per-version name uniqueness;
- no physical deletion;
- draft-only mutations with expected revisions;
- atomic publish/cancel transitions;
- terminal-version immutability;
- append-only catalog history.

## 8. Migration review

### M-01 — numbering

Resolved: migration 013 is current; migration 014 is the exact approved number.

### M-02 — mechanism

Resolved: one standalone SQL file. No change to migration 010 marker/loader/payload mechanism.

### M-03 — lifecycle vocabulary

Resolved: migration adapts legacy `building/published/superseded` to `draft/published/superseded/cancelled` without in-place rewrite of published content. Contradictory/unsupported state fails closed.

### M-04 — rollout

Resolved: legacy stays current published; canonical data is seeded as draft only; publication is explicit and atomic.

### M-05 — repeat/existing DB

Required: clean and existing DB with pre-migration backup, repeat installer, legacy/Staffing preservation checks and lifecycle/revision tests.

## 9. UI and authorization review

Required:

- Russian managed UI and safe errors;
- permission-first object access;
- POST + CSRF + PRG for mutations;
- no raw JSON history;
- no internal IDs as business codes;
- content navigation and directory tile visibility by permission;
- identical layout rules across the three theme CSS files, expressed through theme variables.

## 10. Exact implementation boundary review

```text
ALLOWLIST_PATHS=38
MAX_CHANGED_PATHS=38
MIGRATION_010_FILES_ALLOWED=0
WORKFLOW_OR_SETTINGS_CHANGES_ALLOWED=0
PRODUCTION_DEPLOYMENT_ALLOWED=0
PULL_REQUEST_ALLOWED=0
MERGE_ALLOWED=0
```

Any extra path, different migration mechanism, scope expansion or changed anchor requires a new owner approval.

## 11. Testing review

Critical gates:

- exact branch/base/allowlist and PHP lint/static checker;
- clean DB migrations 001–014 and repeat;
- existing DB backup/migration/repeat with legacy and Staffing preservation;
- exactly 24 names and exact combined flags;
- four permissions without non-owner grants;
- duplicate/stale revision rollback;
- draft create/update/archive/restore;
- publish/cancel and terminal immutability;
- stable identity and append-only readable history;
- legacy read-only and existing Staffing pinned;
- archived entry excluded for new Staffing slot;
- HTTP smoke and three-theme desktop acceptance;
- `MOBILE_ACCEPTANCE=NOT_RUN`.

## 12. Findings summary

```text
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS=0
OPEN_FINDINGS=0
FORMAL_REVIEW=PASS
```

## 13. Approval boundary

Owner Implementation Approval grants implementation, validation, commits and push only to `feature/military-positions-directory-v1`. It does not grant PR, merge, branch deletion, workflow/settings/configuration changes or production deployment.
