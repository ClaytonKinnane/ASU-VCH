# Матрица трассируемости проекта АСУ-ВЧ

## 1. Latest completed increment

```text
INCREMENT=Military Positions Directory v1
CONTOUR=PersonnelServiceAccounting
BASE_MAIN=9ae05b9928903cc483ce415d7378b546e419264c
FINAL_FEATURE_HEAD=3756b2ec53a00f68d5c1f5c098d1c274f6b8d769
RUNTIME_VALIDATED_HEAD=c647a933011873048866c75978d3f506634011fd
MERGE_PR=36
MERGE_COMMIT=a6cfceb421fac8d0985e409770bb26a62fac0b14
MIGRATION=014_military_positions_directory_v1.sql
IMPLEMENTATION=MERGED
OPEN_FINDINGS=0
MOBILE=NOT RUN / OUT OF SCOPE
```

## 2. Design / implementation sources

- `docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md`;
- `docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md`;
- `docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md`;
- `database/migrations/014_military_positions_directory_v1.sql`;
- `app/Directory/MilitaryPositionCatalogRepository.php`;
- `app/Directory/MilitaryPositionCatalogService.php`;
- `app/Directory/MilitaryPositionCatalogFunctions.php`;
- `tools/check-military-positions-directory-v1.php`;
- `tools/Test-MilitaryPositionsDirectoryV1.ps1`.

## 3. Functional traceability

| Requirement group | Implementation | Verification |
|---|---|---|
| permission-aware navigation | content/directories/main routes | PASS |
| four permissions / no auto grants | migration 014 | PASS |
| version list/current/draft/history | repository + views | PASS |
| initial 24-entry canonical draft / 9 combined | migration 014 | PASS |
| draft creation/copy | service/routes | PASS |
| canonical stable identity + normalized uniqueness | DB/service | PASS |
| create/update/archive/restore | service + POST routes | PASS |
| atomic publish/cancel | service + DB lifecycle guards | PASS |
| filters/cards/usage | repository/views | PASS |
| readable append-only history | table/triggers/page | PASS |
| no real staffing/person data mutation | runner/checkers | PASS |

## 4. Compatibility traceability

| Contract | Result |
|---|---|
| one catalog / no parallel entity | PASS |
| migration-010 protected files unchanged | PASS |
| legacy data preserved | PASS |
| canonical draft not auto-published | PASS |
| existing Staffing pins unchanged | PASS |
| archived canonical entries excluded from new selectors | PASS |
| canonical position has no VUS/rank/unit/person/equipment/occupancy fields | PASS |

## 5. Validation evidence

```text
TOTAL_ALLOWLIST=38/38
CORRECTIVE_INVENTORIES=12/12,9/9,8/8,9/9
PHP_LINT=171_PASS
MIGRATIONS=001-014
INITIALIZATION_RUNS=2
DB_RUNTIME_CHECKER=167_PASS
HTTP_SMOKE=200,200,302
THREE_THEME_DESKTOP=PASS
MUTUAL_EXCLUSION=PASS
UI_F04=CLOSED
UI_F05=CLOSED
OPEN_FINDINGS=0
REAL_STAFFING_DATA_MUTATION=NONE
MOBILE=NOT_RUN_OUT_OF_SCOPE
```

## 6. Prior dependency

Lowest Unit Staffing Structure v1:

```text
PR=35
MERGE_MAIN=9ae05b9928903cc483ce415d7378b546e419264c
MIGRATION=013_lowest_unit_staffing_v1.sql
STATUS=MERGED
```

## 7. Current stage

```text
ACTIVE_PRODUCT_IMPLEMENTATION=NONE
NEXT_PRODUCT_INCREMENT=NOT_SELECTED
```

Research branch `research/military-accounting-order-700` remains separate/unmerged and is not a current implementation trace.
