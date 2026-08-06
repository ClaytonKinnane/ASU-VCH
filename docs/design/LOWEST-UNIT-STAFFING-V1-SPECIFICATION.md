# Lowest Unit Staffing Structure v1 — Specification

## 1. Статус

```text
DOCUMENT=Specification
INCREMENT=Lowest Unit Staffing Structure v1
BASE_SHA=d60db94e405979c8f29bdc3dcaae7950362fb13a
FEATURE_BRANCH=feature/lowest-unit-staffing-v1
ARCHITECTURE=docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md
DOMAIN=docs/domains/STAFFING.md
IMPLEMENTATION=NOT STARTED
```

## 2. Пользовательская цель

Уполномоченный владелец системы должен иметь возможность создать и вести версионную штатную структуру нижних подразделений на базе уже опубликованной Organizational Structure v1, не внося сведения о конкретных военнослужащих.

## 3. Actors

### 3.1. System owner

Имеет `system.*.*` и выполняет полный management lifecycle.

### 3.2. Staffing viewer

Имеет `staffing.registers.view` и просматривает registers, versions, slots и документы.

### 3.3. Staffing editor

Имеет view/create/update и изменяет только draft data.

### 3.4. Staffing publisher

Имеет publish и выполняет approve/activate/cancel.

### 3.5. Staffing historian

Имеет history и просматривает events/compare.

V1 не реализует subtree-level delegation. Permission действует на весь модуль.

## 4. Functional requirements

### FR-01. Module visibility

Плитка «Штатная структура» отображается в разделе «Контент» только пользователю с `staffing.registers.view` либо `system.*.*`.

### FR-02. Register list

Список показывает:

- code;
- name;
- linked organizational structure;
- current active version;
- pending version;
- status;
- updated timestamp.

Фильтры:

- active/archived/all;
- search by code/name;
- organizational structure.

### FR-03. Create register

Input:

```text
code
name
organizational_structure_id
note nullable
```

Validation:

- code: lower ASCII `[a-z0-9][a-z0-9._-]{1,63}`;
- unique forever;
- name length 1–255;
- structure exists and is active;
- CSRF and permission required.

Result:

- register created without automatic draft;
- `StaffingRegisterCreated` event;
- PRG redirect to card.

### FR-04. Update register

Only name/note may change. Code and organizational structure are immutable.

Update requires expected timestamp/revision token. Archive data cannot be edited.

### FR-05. Archive/restore register

Archive allowed when no pending version exists.

Restore returns register to active administrative state. Published versions remain unchanged.

### FR-06. Create initial draft

User selects:

- active or superseded organizational structure version;
- current published position catalog;
- compatible rank catalog;
- current published public VUS catalog;
- version label;
- effective_from;
- change reason.

An empty draft version is created with revision 1.

### FR-07. Create draft from active

If active staffing version exists, user can create a new draft by copying:

- slot identities and slot snapshots;
- VUS requirements;
- linked documents using copy-on-write rules;
- catalog pinning, unless user explicitly selects compatible newer versions.

New version receives new version number and revision 1.

### FR-08. Version card

Shows:

- status;
- version number/label;
- effective interval;
- pinned organizational version;
- pinned catalogs;
- revision;
- documents;
- slot counts by organizational element and normative state;
- lifecycle actions allowed to current actor.

### FR-09. Add document

Draft-only.

Input:

```text
document_type
document_date
document_number
title
note nullable
role
sort_order
expected_revision
```

Document type in v1 is a controlled local enum:

```text
staffing_order
amendment_order
approval_act
other_basis
```

No file upload.

### FR-10. Update/unlink document

Draft-only. Document used by a published version is not updated in place. A new draft copy is created when changed.

Unlinking does not physically delete a document already referenced by published history.

### FR-11. Add individual slot

Draft-only.

Input:

```text
organizational_structure_element_id
position_type_id
position_variant_id nullable
internal_code nullable
display_name
minimum_rank_id nullable
maximum_rank_id nullable
preferred_rank_id nullable
normative_state
note nullable
VUS requirement list
sort_order
expected_revision
```

Validation:

- organizational element exists in pinned structure version;
- element is not root;
- position type belongs to pinned position catalog;
- optional variant belongs to type/version;
- position catalog permits the organizational element type when such relation exists;
- rank values belong to pinned rank catalog;
- rank range is valid;
- VUS values belong to pinned VUS catalog;
- duplicate VUS forbidden;
- internal code unique in version;
- sort order unique within element;
- normative state is `active`, `suspended` or `closed`.

Creation generates a new `staffing_slot_identity` and one slot snapshot.

### FR-12. Edit slot

Draft-only. Stable identity remains unchanged. All mutable snapshot fields may change with the validations from FR-11.

### FR-13. Remove slot from draft

Removal deletes the slot snapshot from the current draft only. Stable identity and published snapshots remain.

Confirmation page/message must display slot code/name and organizational unit.

### FR-14. Approve version

Preconditions:

- status draft;
- exactly one primary basis;
- at least one active slot;
- no validation errors;
- expected revision matches;
- effective_from set;
- publisher permission.

Effect:

- status approved;
- approved_by/approved_at recorded;
- content becomes immutable;
- `StaffingVersionApproved` event.

### FR-15. Cancel draft/approved version

Requires reason and expected revision.

Effect:

- status cancelled;
- no content mutation afterward;
- register can create a new draft;
- event recorded.

### FR-16. Activate approved version

Preconditions:

- status approved;
- activation date equals effective_from;
- no other pending version;
- current active version, if present, has earlier effective_from;
- all pinned source versions still exist;
- publisher permission.

Effect in one transaction:

- previous active version → superseded;
- its effective_to = new effective_from;
- approved version → active;
- active guard updated;
- events appended.

### FR-17. Read active staffing by unit

Read model groups slots by organizational tree order and shows:

- unit name/type;
- slot code/name;
- position type/variant;
- rank requirement;
- VUS requirements;
- normative state;
- assignment state `not-managed-in-v1`.

UI must not call an active slot «занятым» or «вакантным».

### FR-18. Compare versions

Comparison identifies:

- added slot identities;
- removed identities;
- changed organization binding;
- changed position type/variant;
- changed rank requirement;
- changed VUS list;
- changed normative state;
- changed documents.

### FR-19. History

History shows append-only events ordered newest first, with actor, timestamp, event, reason, version and safe summary.

### FR-20. No personnel fields

No request, table, view or test fixture may contain:

- person/soldier ID;
- ФИО;
- personal number;
- document of a person;
- assignment to a person;
- citizen military-accounting status.

## 5. Database specification

### 5.1. Migration

```text
database/migrations/013_lowest_unit_staffing_v1.sql
```

Migration follows existing runner conventions and MySQL 8.4 syntax.

### 5.2. Permissions seed

Migration creates six permissions:

```text
staffing.registers.view
staffing.registers.create
staffing.registers.update
staffing.registers.publish
staffing.registers.archive
staffing.registers.history
```

Permissions are not granted to non-owner roles automatically.

### 5.3. Required tables

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

### 5.4. Referential integrity

Required FKs include:

- register → organizational structure;
- version → register and organizational structure version;
- version → position/rank/VUS catalog versions;
- slot identity → register;
- slot → version, identity, organization element, position type/variant, rank references;
- VUS requirement → slot and VUS;
- document/version relation → same register.

Where MySQL cannot express cross-table version consistency with a simple FK, composite unique keys and BEFORE triggers are required.

### 5.5. Immutability

Triggers reject UPDATE/DELETE of:

- stable slot identity keys;
- published version content;
- append-only events;
- immutable register code/structure;
- documents referenced by published versions.

### 5.6. Transaction isolation

Services use explicit transactions and `SELECT ... FOR UPDATE` in canonical order.

## 6. UI specification

### 6.1. Location

```text
/admin/content.php
→ tile «Штатная структура»
→ /admin/staffing/registers.php
```

### 6.2. Visual language

- existing glass tile/components;
- dark-blue default presentation with turquoise accents through theme assets;
- no hardcoded theme-specific colors in feature markup;
- all three registered themes supported;
- desktop layout required;
- mobile status explicitly `NOT TESTED`.

### 6.3. Accessibility

- semantic headings;
- labels for all fields;
- keyboard-accessible actions;
- visible focus from themes;
- table headers/scopes;
- errors linked to fields where practical;
- no action represented by color alone.

### 6.4. Safe output

- all user-entered values escaped;
- no SQL or stack details;
- permission-denied does not reveal register existence;
- change event payload is summarized, not dumped raw.

## 7. HTTP and security requirements

- authentication required for every route;
- GET is read-only;
- POST for every mutation;
- CSRF token required;
- permission check before reading mutation target details;
- integer IDs validated;
- expected revision required for draft mutation/lifecycle;
- PRG redirect after successful POST;
- no mutation through query string;
- session cookie behavior unchanged;
- no new repository settings or branch protection changes.

## 8. Proposed implementation paths

The implementation approval allowlist must be a subset of the following exact paths. Any additional path requires fail-closed re-approval.

### 8.1. Existing files to modify

```text
app/bootstrap.php
public/admin/content.php
docs/domains/README.md
docs/PROJECT-STATUS.md
docs/ROADMAP.md
docs/TRACEABILITY.md
```

### 8.2. New database/domain files

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

### 8.3. New HTTP/UI files

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

### 8.4. New validation files

```text
tools/Test-LowestUnitStaffingV1.ps1
tools/check-lowest-unit-staffing-v1.php
```

### 8.5. Process documents already present

```text
docs/domains/STAFFING.md
docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md
docs/design/LOWEST-UNIT-STAFFING-V1-SPECIFICATION.md
docs/design/LOWEST-UNIT-STAFFING-V1-REVIEW.md
```

Maximum implementation changed paths under this proposal: 42, including four process documents already committed.

## 9. Test specification

### TS-01. Static

- PHP syntax all tracked PHP;
- no BOM/encoding regressions;
- no forbidden real data;
- exact path allowlist;
- documentation references resolve.

### TS-02. Clean database

- migrations 001–013 apply;
- expected permissions = previous count + 6;
- all tables/triggers/indexes exist;
- no seed staffing records.

### TS-03. Current database

- backup before migration;
- apply 013 after current 012;
- existing Organization/Directory/Security data unchanged;
- migration recorded once;
- re-run is no-op through runner.

### TS-04. DB constraints

Synthetic scenarios verify:

- duplicate register code rejected;
- cross-structure version rejected;
- organization element absent in pinned version rejected;
- root element slot rejected;
- wrong position/rank/VUS catalog rejected;
- invalid rank range rejected;
- duplicate slot identity in version rejected;
- duplicate VUS rejected;
- second pending/active version rejected;
- published content mutation rejected;
- stale revision rejected;
- event update/delete rejected.

### TS-05. Service

- all use cases FR-03–FR-19;
- transaction rollback on each failed invariant;
- permission matrix;
- CSRF;
- safe errors;
- no person fields.

### TS-06. HTTP/browser desktop

- owner navigation and lifecycle;
- viewer read-only;
- user without permission denied;
- three themes;
- validation messages;
- compare/history;
- empty register and populated synthetic register;
- no mobile PASS.

### TS-07. Regression

- login/logout;
- content landing;
- Organization Structure v1;
- directories ranks/positions/VUS;
- user management;
- theme activation;
- existing CI-safe checkers.

## 10. Acceptance criteria

Increment is accepted only if:

1. exact base/head/path checks pass;
2. migration passes on clean and current MySQL;
3. all DB invariants pass;
4. no real or personal data is committed;
5. all permissions are fail-closed;
6. published versions are immutable;
7. active version switching is atomic;
8. UI does not claim actual vacancy/occupancy;
9. desktop visual acceptance passes in all themes;
10. mobile remains honestly untested;
11. existing functionality regression passes;
12. documentation matches exact implementation;
13. Final PR Review has no blocking/major findings;
14. merge occurs only after separate explicit approval;
15. branches are not deleted without separate approval.

## 11. Explicit non-requirements

- no Personnel Core;
- no Assignments;
- no CitizenMilitaryAccounting;
- no file upload;
- no external integration;
- no import/export;
- no production deployment;
- no branch protection changes;
- no mobile verification.

## 12. Implementation gate

This Specification does not itself authorize runtime changes. Implementation begins only after the Review document records PASS and the owner approves exact branch, head, scope and changed-path allowlist.