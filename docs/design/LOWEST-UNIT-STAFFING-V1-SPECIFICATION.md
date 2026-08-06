# Lowest Unit Staffing Structure v1 — Specification

## 1. Статус

```text
DOCUMENT=Specification
VERSION=0.2
INCREMENT=Lowest Unit Staffing Structure v1
BASE_SHA=d60db94e405979c8f29bdc3dcaae7950362fb13a
FEATURE_BRANCH=feature/lowest-unit-staffing-v1
ARCHITECTURE=docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md
DOMAIN=docs/domains/STAFFING.md
IMPLEMENTATION=NOT STARTED
```

## 2. Цель

System owner ведет версионную нормативную штатную структуру на базе Organization Structure v1 без персональных данных, назначений и утверждений о фактической укомплектованности.

## 3. Actors и permissions

```text
staffing.registers.view
staffing.registers.create
staffing.registers.update
staffing.registers.publish
staffing.registers.archive
staffing.registers.history
```

Owner имеет `system.*.*`. Новые permissions не назначаются автоматически другим ролям. V1 не реализует subtree ACL.

## 4. Functional requirements

### FR-01. Navigation

Permission-aware плитка «Штатная структура» в `public/admin/content.php` ведет на `/admin/staffing/registers.php` и видна при view permission или owner wildcard.

### FR-02. Register list

Отображает code, name, OrganizationalStructure, active/pending version, status и updated_at. Поддерживает search, status и structure filters.

### FR-03. Create register

Input:

```text
code
name
organizational_structure_id
note nullable
```

Validation:

- code `[a-z0-9][a-z0-9._-]{1,63}`;
- globally unique and never reused;
- name 1–255;
- structure exists and is not archived;
- POST, CSRF, permission.

Creates register only; draft is a separate command.

### FR-04. Update register

Name/note only. Code and structure immutable. Requires expected updated token. Archived register is read-only.

### FR-05. Archive/restore

Archive requires archive permission and absence of pending version. Restore preserves all versions/history.

### FR-06. Initial draft

User selects:

- active/superseded version of the register OrganizationalStructure;
- current compatible published position catalog;
- rank catalog pinned by that position catalog;
- published public VUS catalog;
- version label;
- effective_from;
- reason.

Creates empty draft, revision 1.

### FR-07. Draft from active

Copies active slots, requirements and document links by copy-on-write rules. Pinned Organization/position/rank/VUS versions remain exactly the same.

Catalog upgrade/remapping is rejected in v1 and requires a future approved increment.

### FR-08. Version card

Shows status, label/number, effective dates, pinned versions, revision, documents, slot totals by organizational element and lifecycle actions.

### FR-09. Document create/link

Draft-only fields:

```text
document_type=staffing_order|amendment_order|approval_act|other_basis
document_date
document_number
title
note nullable
role=primary_basis|additional_basis|amendment
sort_order
expected_revision
```

No file upload. Published metadata uses copy-on-write.

### FR-10. Slot create

Draft-only fields:

```text
organizational_structure_element_id
position_type_id
position_variant_id nullable
internal_code nullable
display_name
minimum_rank_id nullable
maximum_rank_id nullable
preferred_rank_id nullable
normative_state=active|suspended|closed
note nullable
VUS requirements
sort_order
expected_revision
```

Validation:

- organizational element is present in pinned Organization version;
- root and non-root elements are allowed;
- position type belongs to pinned position version;
- optional variant belongs to selected type/version;
- element type is compatible with position catalog relation when relation is defined;
- ranks belong to pinned rank version and form valid range;
- VUS values belong to pinned VUS version;
- duplicate VUS forbidden;
- internal code unique within version when present;
- sort order unique within organization element.

Creates stable slot identity and its first snapshot.

### FR-11. Slot update/remove

Draft-only and expected-revision protected. Update preserves stable identity. Remove deletes only current draft snapshot; published snapshots and identity remain.

### FR-12. Approve

Preconditions:

- draft;
- exactly one primary basis;
- at least one active slot;
- all validations pass;
- effective_from exists;
- expected revision matches;
- publish permission.

Effect: approved, approved metadata, immutable content, event.

### FR-13. Cancel

Draft/approved only. Requires reason and expected revision. Result immutable and releases pending guard.

### FR-14. Activate

Approved only. In one transaction:

- previous active → superseded;
- previous effective_to = new effective_from;
- approved → active;
- guards and events updated.

Activation checks pinned references still exist and effective periods do not overlap.

### FR-15. Read staffing by organization element

Groups slots by Organization tree order and displays:

- organizational element/type;
- slot code/name;
- position type/variant;
- rank requirement;
- VUS requirements;
- normative state;
- `assignment_state=not-managed-in-v1`.

UI must not label slots `occupied` or `vacant`.

### FR-16. Compare

Compares slot identity presence and changes in organization binding, position, rank, VUS, normative state and documents.

### FR-17. History

Append-only events with actor, time, event, version, target and safe summary.

### FR-18. Explicit data exclusion

No person ID, ФИО, personal number, person document, assignment, reserve/conscription/accounting fields in DB, requests, views, fixtures or reports.

## 5. Lifecycle and concurrency

```text
draft → approved → active → superseded
draft → cancelled
approved → cancelled
```

- one pending and one active per register;
- content mutation only in draft;
- `[effective_from,effective_to)`;
- canonical lock order `register → version → children`;
- every draft mutation requires `expected_revision`;
- success increments revision and appends event;
- stale command rolls back entirely.

## 6. Database specification

Migration:

```text
database/migrations/013_lowest_unit_staffing_v1.sql
```

Tables:

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

Migration creates six permissions without role grants.

Required DB enforcement:

- unique code/version/internal code/sibling sort order;
- pending and active generated guards;
- FKs and composite version-consistency keys;
- triggers for cross-table Organization/catalog consistency;
- published immutability;
- stable identity immutability;
- append-only events;
- prohibited lifecycle transitions;
- no physical deletion of published history.

## 7. HTTP/security

- every route authenticated;
- GET read-only;
- POST mutations;
- CSRF required;
- permission checked before target disclosure;
- integer IDs and enum inputs validated;
- expected revision required;
- PRG after success;
- safe user-facing errors;
- escaped output;
- no query-string mutation;
- no new settings/branch-protection changes.

## 8. UI

Location:

```text
/admin/content.php
→ Штатная структура
→ /admin/staffing/registers.php
```

Screens:

- register list/card;
- version card;
- grouped slot list;
- register, document and slot forms;
- compare;
- history.

Existing theme components/assets are reused; no feature-hardcoded theme colors. Desktop acceptance for all three current themes is required. Mobile status remains `NOT TESTED`.

## 9. Exact proposed implementation path allowlist

Any path outside this list requires fail-closed re-approval.

### Existing files

```text
app/bootstrap.php
public/admin/content.php
docs/domains/README.md
docs/PROJECT-STATUS.md
docs/ROADMAP.md
docs/TRACEABILITY.md
```

### Database/domain

```text
database/migrations/013_lowest_unit_staffing_v1.sql
app/Staffing/StaffingRepository.php
app/Staffing/StaffingCreateUpdateTrait.php
app/Staffing/StaffingDocumentTrait.php
app/Staffing/StaffingLifecycleTrait.php
app/Staffing/StaffingSlotTrait.php
app/Staffing/StaffingSupportTrait.php
app/Staffing/StaffingService.php
app/Staffing/functions.php
```

### HTTP/UI

```text
public/admin/staffing/registers.php
public/admin/staffing/register.php
public/admin/staffing/registers/create.php
public/admin/staffing/registers/update.php
public/admin/staffing/registers/archive.php
public/admin/staffing/registers/restore.php
public/admin/staffing/versions/create.php
public/admin/staffing/versions/approve.php
public/admin/staffing/versions/activate.php
public/admin/staffing/versions/cancel.php
public/admin/staffing/documents/create.php
public/admin/staffing/documents/update.php
public/admin/staffing/documents/unlink.php
public/admin/staffing/slots/create.php
public/admin/staffing/slots/update.php
public/admin/staffing/slots/remove.php
public/admin/staffing/compare.php
public/admin/staffing/history.php
public/admin/staffing/views/register-list.php
public/admin/staffing/views/register-card.php
public/admin/staffing/views/version-card.php
public/admin/staffing/views/slot-form.php
public/admin/staffing/views/document-form.php
```

### Validation

```text
tools/Test-LowestUnitStaffingV1.ps1
tools/check-lowest-unit-staffing-v1.php
```

### Process documents already changed in the branch

```text
docs/domains/STAFFING.md
docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md
docs/design/LOWEST-UNIT-STAFFING-V1-SPECIFICATION.md
docs/design/LOWEST-UNIT-STAFFING-V1-REVIEW.md
```

```text
MAX_EXPECTED_CHANGED_PATHS=44
```

## 10. Test specification

### Static

- PHP syntax;
- exact paths;
- UTF-8/no BOM regressions;
- no real/restricted data;
- documentation links.

### Clean DB

- migrations 001–013;
- six new permissions;
- eight new tables and expected triggers/indexes;
- no staffing seed records.

### Current DB

- backup first;
- migration after 012;
- existing data unchanged;
- migration recorded once;
- runner repeat is no-op.

### Constraints

Synthetic tests reject:

- duplicate register code;
- structure/version mismatch;
- element absent from pinned snapshot;
- wrong position/rank/VUS versions;
- invalid rank range;
- duplicate identity/VUS/internal code/sort order;
- second pending/active;
- published mutation;
- stale revision;
- event update/delete.

Root and non-root element slots must both be accepted when catalog-compatible.

### Service/HTTP

- FR-03–FR-17 success and failure;
- transaction rollback;
- permission matrix;
- CSRF;
- safe errors;
- no personnel fields.

### Browser desktop

- owner lifecycle;
- viewer read-only;
- denied user;
- all three themes;
- empty/populated synthetic states;
- compare/history;
- no mobile PASS.

### Regression

- login/logout;
- content landing;
- Organization Structure v1;
- ranks/positions/VUS directories;
- user management;
- themes;
- existing CI-safe checkers.

## 11. Acceptance criteria

1. exact base/head/path checks pass;
2. migration passes clean/current MySQL;
3. DB and service invariants pass;
4. no real/personal/restricted data committed;
5. permissions fail closed;
6. published content immutable;
7. activation atomic;
8. catalog copy rule enforced;
9. root and non-root elements supported;
10. no false vacancy/occupancy statement;
11. desktop visual acceptance in three themes;
12. mobile honestly untested;
13. regressions pass;
14. docs match implementation;
15. Final PR Review has no blocking/major findings;
16. merge and branch deletion remain separately controlled.

## 12. Non-requirements

No Personnel, Assignments, CitizenMilitaryAccounting, file upload, import/export, external integration, production deployment, branch protection or mobile verification.

## 13. Gate

Implementation begins only after formal Review PASS and owner approval of exact feature head, scope and 44-path allowlist.