# Матрица трассируемости проекта АСУ-ВЧ

## 1. Статус документа

```text
DOCUMENT=TRACEABILITY
INCREMENT=Lowest Unit Staffing Structure v1
CONTOUR=PersonnelServiceAccounting
CitizenMilitaryAccounting=EXCLUDED
BASE_SHA=d60db94e405979c8f29bdc3dcaae7950362fb13a
FEATURE_BRANCH=feature/lowest-unit-staffing-v1
IMPLEMENTATION_COMMIT=59da90e335f0253a55539fb1697c0c73f17abbad
INTEGRITY_FIX_COMMIT=ab5a5cf935c5dd0cb2a4e8c7578fd24d33b7c367
IMPLEMENTATION_STATUS=IMPLEMENTED
LOCAL_VALIDATION_STATUS=IN_PROGRESS
OPERATIONAL_CONDITION=NO_REAL_STAFFING_DATA_BEFORE_SECURITY_FOUNDATION
```

Документ связывает утвержденные требования Specification 0.2 с реализацией и проверками. Он не является свидетельством прохождения MySQL, deploy, HTTP, browser или mobile acceptance до появления соответствующих фактических результатов.

## 2. Нормативные и проектные основания

- `docs/domains/STAFFING.md` — границы домена и инварианты.
- `docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md` — Architecture 0.2.
- `docs/design/LOWEST-UNIT-STAFFING-V1-SPECIFICATION.md` — Specification 0.2.
- `docs/design/LOWEST-UNIT-STAFFING-V1-REVIEW.md` — Formal Review PASS.
- `database/migrations/013_lowest_unit_staffing_v1.sql` — схема, permissions и DB-level guards.
- `tools/check-lowest-unit-staffing-v1.php` — статическая проверка реализации.
- `tools/Test-LowestUnitStaffingV1.ps1` — локальный validation orchestrator.

## 3. Functional requirements

| Requirement | Реализация | Проверка | Текущий статус |
|---|---|---|---|
| FR-01 Navigation | `public/admin/content.php`, `public/admin/staffing/registers.php` | permission-aware link checks в static checker; HTTP/browser gate | Implemented; runtime validation pending |
| FR-02 Register list | `StaffingRepository.php`, `registers.php`, `views/register-list.php` | PHP lint; static path checks; HTTP/browser gate | Implemented; runtime validation pending |
| FR-03 Create register | `StaffingCreateUpdateTrait.php`, `registers/create.php` | route permission/CSRF/PRG checks; synthetic service tests pending | Implemented; DB/service validation pending |
| FR-04 Update register | `StaffingCreateUpdateTrait.php`, `registers/update.php` | expected token and permission paths; synthetic stale-token test pending | Implemented; DB/service validation pending |
| FR-05 Archive/restore | `StaffingCreateUpdateTrait.php`, `registers/archive.php`, `registers/restore.php` | route checks; lifecycle tests pending | Implemented; DB/service validation pending |
| FR-06 Initial draft | `StaffingCreateUpdateTrait.php`, `versions/create.php` | pinned catalog/Organization checks; clean/current DB tests pending | Implemented; DB/service validation pending |
| FR-07 Draft from active | `StaffingCreateUpdateTrait.php` | copy-on-write and no catalog remapping; synthetic tests pending | Implemented; DB/service validation pending |
| FR-08 Version card | `register.php`, `views/register-card.php`, `views/version-card.php` | PHP lint; HTTP/browser gate | Implemented; runtime validation pending |
| FR-09 Document create/link | `StaffingDocumentTrait.php`, document routes and view | published immutability guards; synthetic tests pending | Implemented; DB/service validation pending |
| FR-10 Slot create | `StaffingSlotTrait.php`, `slots/create.php`, `views/slot-form.php` | Organization/catalog/rank/VUS guards; synthetic tests pending | Implemented; DB/service validation pending |
| FR-11 Slot update/remove | `StaffingSlotTrait.php`, slot update/remove routes | draft-only and stable identity guards; synthetic tests pending | Implemented; DB/service validation pending |
| FR-12 Approve | `StaffingLifecycleTrait.php`, `versions/approve.php` | primary basis/active slot/revision checks; lifecycle tests pending | Implemented; DB/service validation pending |
| FR-13 Cancel | `StaffingLifecycleTrait.php`, `versions/cancel.php` | lifecycle and revision tests pending | Implemented; DB/service validation pending |
| FR-14 Activate | `StaffingLifecycleTrait.php`, `versions/activate.php` | transactional activation/guards; current DB tests pending | Implemented; DB/service validation pending |
| FR-15 Read by organization element | repository queries, `version-card.php` | no occupied/vacant static check; browser gate | Implemented; runtime validation pending |
| FR-16 Compare | `public/admin/staffing/compare.php` | PHP lint; synthetic compare/browser tests pending | Implemented; runtime validation pending |
| FR-17 History | `staffing_change_events`, `history.php` | append-only triggers/static checks; DB tests pending | Implemented; DB validation pending |
| FR-18 Explicit data exclusion | migration, domain service, routes and views | static scan for personnel and CitizenMilitaryAccounting fields | Static PASS; real data prohibited |

## 4. Cross-cutting requirements

| Область | Реализация | Проверка |
|---|---|---|
| Permissions | six `staffing.registers.*` permissions in migration 013; no non-owner grants | static checker; clean/current DB inspection pending |
| Authentication and authorization | common application authentication plus route-specific permission checks | static route checks; HTTP permission matrix pending |
| CSRF and POST/Redirect/GET | common `staffing_handle_action()` | static checker; HTTP mutation tests pending |
| Optimistic concurrency | expected register token and version revision | synthetic stale-command tests pending |
| Transaction boundaries | repository/service transaction wrappers and canonical locking | DB rollback/concurrency tests pending |
| Published immutability | DB triggers for versions, slots, VUS requirements and documents | static trigger checks; MySQL negative tests pending |
| Stable slot identity | `staffing_slot_identities` and immutable linkage | static schema checks; cross-version tests pending |
| Append-only history | `staffing_change_events` plus update/delete rejection triggers | static checks; MySQL negative tests pending |
| Catalog pinning | Organization, position, rank and public VUS versions on `staffing_versions` | static schema/trigger checks; MySQL mismatch tests pending |
| Output safety | shared escaping helpers and safe error handling | PHP lint/static review; browser inspection pending |
| Theme reuse | existing layout/components without feature-specific theme colors | desktop browser acceptance for three themes pending |
| Personal/restricted data exclusion | no person assignment, ФИО, occupied/vacant facts or real staffing seed | static PASS; operational prohibition remains active |

## 5. Database objects

| Object | Purpose | Verification |
|---|---|---|
| `staffing_registers` | stable staffing register identity | migration static check; MySQL pending |
| `staffing_slot_identities` | stable slot identity across versions | migration static check; MySQL pending |
| `staffing_versions` | versioned lifecycle and pinned catalogs | guards/static check; MySQL pending |
| `staffing_documents` | basis-document metadata without files | immutability/static check; MySQL pending |
| `staffing_version_documents` | version-to-document role and ordering | draft/published guards; MySQL pending |
| `staffing_slots` | normative slot snapshots | catalog/Organization guards; MySQL pending |
| `staffing_slot_vus_requirements` | public VUS requirements per slot | pinned version/delete guards; MySQL pending |
| `staffing_change_events` | append-only domain history | append-only triggers; MySQL pending |

## 6. Acceptance criteria status

| AC | Criterion | Status |
|---|---|---|
| AC-01 | Exact base/head/path checks | PASS before local validation; must repeat on final head |
| AC-02 | Clean/current MySQL migration | NOT RUN |
| AC-03 | DB and service invariants | Static PASS; runtime NOT RUN |
| AC-04 | No real/personal/restricted data committed | PASS by static inspection |
| AC-05 | Permissions fail closed | Implemented; runtime permission matrix NOT RUN |
| AC-06 | Published content immutable | Implemented and statically checked; MySQL negative tests NOT RUN |
| AC-07 | Activation atomic | Implemented; MySQL transaction test NOT RUN |
| AC-08 | Catalog copy rule enforced | Implemented; synthetic test NOT RUN |
| AC-09 | Root and non-root elements supported | Implemented; synthetic/browser test NOT RUN |
| AC-10 | No false vacancy/occupancy statement | Static PASS |
| AC-11 | Desktop acceptance in three themes | NOT RUN |
| AC-12 | Mobile honestly untested | NOT RUN / no PASS claimed |
| AC-13 | Regressions | NOT RUN |
| AC-14 | Documentation matches implementation | IN PROGRESS; this matrix added after implementation |
| AC-15 | Final PR Review without blocking/major findings | NOT STARTED; PR not authorized/created |
| AC-16 | Separate merge and branch deletion control | ENFORCED; neither operation performed |

## 7. Current validation evidence

The first local run on the implementation head completed PHP syntax checks for all changed PHP files and produced 122 static passes. It then stopped before initialization because this traceability document, listed in the approved allowlist, had not yet been created.

After this document is synchronized locally, the complete command must be repeated. Only its actual output may change MySQL, deploy, HTTP and browser statuses in this matrix or other project documentation.

## 8. Explicit exclusions

This increment does not implement:

- `CitizenMilitaryAccounting`;
- personnel records or person-to-slot assignments;
- actual occupied/vacant state;
- real staffing data;
- file or photo storage;
- leave planning;
- medical data;
- import/export or external integration;
- production deployment;
- mobile acceptance.
