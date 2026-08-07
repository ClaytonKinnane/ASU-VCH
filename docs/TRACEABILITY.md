# Матрица трассируемости проекта АСУ-ВЧ

## 1. Текущий increment

```text
INCREMENT=Military Positions Directory v1
CONTOUR=PersonnelServiceAccounting
BASE_SHA=9ae05b9928903cc483ce415d7378b546e419264c
FEATURE_BRANCH=feature/military-positions-directory-v1
MIGRATION=014_military_positions_directory_v1.sql
ARCHITECTURE=0.2
SPECIFICATION=0.2
FORMAL_REVIEW=PASS
IMPLEMENTATION=PREPARED
LOCAL_DB_HTTP_DESKTOP_VALIDATION=NOT RUN
MOBILE_ACCEPTANCE=NOT RUN / OUT OF SCOPE
REAL_STAFFING_DATA=PROHIBITED
```

GitHub/Git is canonical for the final feature head. This document does not claim MySQL, deploy, HTTP, browser or manual desktop results without actual exact-head output.

## 2. Design and implementation sources

- `docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md`;
- `docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md`;
- `docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md`;
- `database/migrations/014_military_positions_directory_v1.sql`;
- `app/Directory/MilitaryPositionCatalogRepository.php`;
- `app/Directory/MilitaryPositionCatalogService.php`;
- `app/Directory/MilitaryPositionCatalogFunctions.php`;
- `tools/check-military-positions-directory-v1.php`;
- `tools/Test-MilitaryPositionsDirectoryV1.ps1`.

## 3. Functional requirements

| Requirement | Реализация | Verification status |
|---|---|---|
| FR-01 permission-aware navigation | `content.php`, `directories.php`, main route | Static checker prepared; HTTP pending |
| FR-02 four permissions/no grants | migration 014 | Static/DB checks prepared; DB pending |
| FR-03 version list/current/draft/history | repository, main/version views | PHP/browser pending |
| FR-04 initial canonical draft | migration 014 | Exact 24/9 static + DB checks prepared |
| FR-05 create next draft | service + `versions/create.php` | DB/service test pending |
| FR-06 canonical entry model | altered `military_position_types`, service/forms | Static/schema checks prepared |
| FR-07 normalized unique name | normalized column/unique key/service | Duplicate negative test pending |
| FR-08 stable identity | `stable_key`, copy logic, immutable trigger | Cross-version test pending |
| FR-09 create/update entry | service + explicit request-field routes | CSRF/revision/static; runtime pending |
| FR-10 archive/restore | service/routes, no physical delete | Lifecycle and selector tests pending |
| FR-11 atomic publish | service transaction + version trigger | MySQL transaction test pending |
| FR-12 cancel draft | service/route + terminal immutability | MySQL lifecycle test pending |
| FR-13 filters | repository + version page | HTTP/browser pending |
| FR-14 entry card/usage | entry view + Staffing count | Browser/data check pending |
| FR-15 readable append-only history | history table/triggers/functions/page | Static prepared; DB/browser pending |
| FR-16 exact 24 synthetic names | migration seed + checker exact ordered set | Static checker prepared; DB pending |
| FR-17 exact combined flags | nine explicit seed values, no parser | Static checker prepared; DB pending |

## 4. Transition and compatibility

| Contract | Реализация | Verification status |
|---|---|---|
| one catalog, no parallel entity | existing version/type tables altered | Static/schema review prepared |
| migration 010 untouched | protected hashes in checker | Hash check prepared |
| legacy rows/metadata preserved | no destructive DROP or remap | Existing DB backup/regression pending |
| legacy initially stays published | migration creates canonical `draft` only | DB check pending |
| explicit publish supersedes legacy | atomic service transaction | Lifecycle test pending |
| existing Staffing pins unchanged | migration contains no Staffing update | DB referential check pending |
| canonical type uses null optional variant | existing Staffing schema retained | Synthetic slot test pending |
| archived canonical entry not newly selectable | `StaffingRepository::positionTypes()` | Repository/static prepared; browser pending |

## 5. Security and integrity

| Area | Contract |
|---|---|
| Authentication/RBAC | view/manage/publish/history permissions; owner wildcard only |
| Mutations | POST, permission-first, CSRF, transaction, expected revision, PRG |
| Concurrency | version and entry rows locked; stale revisions roll back |
| Published state | published/superseded/cancelled content immutable |
| Deletion | no user or DB physical delete path for versions/entries/history |
| History | append-only events; UI renders Russian field/value pairs, not raw JSON |
| Data boundary | no VUS/rank/unit/person/equipment/occupancy fields in canonical entry requests |
| Test data | exactly 24 approved synthetic names; no real staffing/person data |

## 6. Exact path and test gates

```text
APPROVED_ALLOWLIST_PATHS=38
MAX_CHANGED_PATHS=38
MIGRATION_010_PROTECTED_FILES=7
PHP_LINT=PENDING
STATIC_CHECKER=PENDING
CLEAN_DB=PENDING
EXISTING_DB_BACKUP_AND_MIGRATION=PENDING
REPEAT_INSTALLER=PENDING
STAFFING_REGRESSION=PENDING
HTTP_SMOKE=PENDING
DESKTOP_ASU_BLUE=PENDING
DESKTOP_ASU_LIGHT_BLUE=PENDING
DESKTOP_ASU_EVGENIYA_ROSTOVA=PENDING
MOBILE_ACCEPTANCE=NOT RUN / OUT OF SCOPE
```

The exact runner accepts `-ExpectedHead` after an implementation commit exists. Its actual output is the only authority for changing these statuses.

## 7. Prior increment closure

`Lowest Unit Staffing Structure v1` is no longer active work:

```text
MERGED_PR=35
MERGED_MAIN_SHA=9ae05b9928903cc483ce415d7378b546e419264c
MIGRATION=013_lowest_unit_staffing_v1.sql
POST_MERGE_ACTIONS=SUCCESS
```

Its catalog pinning is preserved by this increment. Personnel assignments, occupied/vacant facts, real staffing data and `CitizenMilitaryAccounting` remain excluded.

## 8. Remaining gates

Implementation Approval authorizes commit and push only to the feature branch. Pull Request, merge, branch deletion, repository/workflow/settings changes and production deployment remain unauthorized.
