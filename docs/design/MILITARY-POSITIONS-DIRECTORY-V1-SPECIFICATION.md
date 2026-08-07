# Military Positions Directory v1 — Specification

## 1. Статус

```text
DOCUMENT=Specification
VERSION=0.6
INCREMENT=Military Positions Directory v1
CONTOUR=PersonnelServiceAccounting
ARCHITECTURE=docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md
IMPLEMENTATION_BRANCH=feature/military-positions-directory-v1
IMPLEMENTATION_BASE_SHA=9ae05b9928903cc483ce415d7378b546e419264c
MIGRATION=database/migrations/014_military_positions_directory_v1.sql
POST_STAFFING_RECONCILIATION=PASS
ORIGINAL_IMPLEMENTATION=AUTHORIZED
FIRST_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED
SECOND_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
THIRD_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
FOURTH_CORRECTIVE_UI_IMPLEMENTATION=PENDING_OWNER_APPROVAL
DESKTOP_ACCEPTANCE=FAIL
```

## 2. Functional scope

V1 реализует управляемый глобальный версионируемый справочник канонических наименований воинских должностей на существующих `military_position_catalog_versions` и `military_position_types`.

### FR-01. Directory navigation

Плитка `Воинские должности` ведёт на `/admin/directories/military-positions.php` и описывается как `Единый версионируемый справочник канонических наименований воинских должностей.`

Модуль доступен из `/admin/content.php` обладателю view permission. Перечень плиток `/admin/directories.php` permission-aware и не раскрывает недоступный модуль.

### FR-02. Permissions

```text
directories.military_positions.view
directories.military_positions.manage
directories.military_positions.publish
directories.military_positions.history
```

Owner wildcard сохраняется. Migration не добавляет `role_permissions` для non-owner roles.

### FR-03. Version list/current version

Экран показывает current published, optional draft, superseded и cancelled versions: code/name/version number, status, effective dates, revision, entry count и legacy/canonical mode.

Одновременно допускается максимум одна draft и одна current published version.

### FR-04. Initial canonical draft

Migration 014 сохраняет legacy classifier current published и создаёт ровно одну canonical draft version с ровно 24 записями FR-16. Автоматическая публикация migration запрещена.

### FR-05. Create next draft version

После публикации canonical version следующая draft создаётся копированием current published version с сохранением stable keys.

Input:

```text
version_label          required, 1..255
effective_from         required date
change_reason          required, 1..1000
expected_catalog_revision required positive integer
```

Создание draft атомарно копирует canonical entries и добавляет history event.

### FR-06. Position entry model

Editable:

```text
name                     required, 1..255
full_name                nullable, <=255
short_name               nullable, <=128
is_combined              boolean
source_type              official|local|imported
source_reference         nullable, <=1000
note                     nullable, <=5000
status                   active|archived
sort_order               positive integer
```

System-managed:

```text
id
stable_key
code
normalized_name
catalog_version_id
created_at / created_by
updated_at / updated_by
revision
```

No VUS, rank, unit, person, equipment, occupancy or staffing quantity field is accepted or persisted.

### FR-07. Canonical name rules

- trim leading/trailing whitespace;
- collapse repeated Unicode whitespace for normalized comparison;
- compare normalized names case-insensitively;
- unique normalized name inside one version;
- archived entry remains unique and addressable;
- internal IDs/keys are not shown as business codes.

### FR-08. Stable identity across versions

Copy preserves `stable_key`. Rename in draft preserves stable identity. New logical position receives a new random stable key. Stable identity and catalog binding are immutable.

### FR-09. Create/update entry

Manage permission, POST, CSRF, draft only, expected catalog and entry revision. Allowed request fields are exactly FR-06 editable fields plus revision/reason controls.

### FR-10. Archive/restore entry

Manage permission, POST, CSRF, draft only, expected revisions and mandatory reason. Archive sets `status=archived`; restore sets `status=active`. Physical delete is absent.

Archived entry is excluded from new Staffing slot selectors while historical references remain readable.

### FR-11. Publish version

Preconditions:

- draft exists and revision matches;
- at least one active entry;
- no invalid/duplicate normalized names;
- stable identities unique;
- all entry values valid;
- publish permission and mandatory reason.

Atomic effect:

```text
previous published → superseded
previous valid_to = draft effective_from
draft → published
published_at / published_by recorded
history event appended
```

Published content becomes immutable. Initial canonical publication supersede-ит legacy version without destroying it.

### FR-12. Cancel draft

Draft can be cancelled with expected revision and mandatory reason. Cancelled content is immutable and cannot become current.

### FR-13. Search/filter

```text
q
status=active|archived
is_combined=0|1
source_type=official|local|imported
```

Search covers name, full name, short name and source reference. Filters are read-only query parameters and do not bypass version binding.

### FR-14. Entry card

Card shows name, full/short name, combined flag, source, source reference, note, status and read-only Staffing usage count. It never renders VUS/rank/unit/person as position properties.

### FR-15. History

Append-only events:

```text
catalog.version.created
catalog.version.published
catalog.version.cancelled
position.created
position.updated
position.archived
position.restored
```

History stores actor/time/version/target/before/after/reason. UI renders readable Russian field labels and values; raw JSON is never displayed.

### FR-16. Initial synthetic dataset

Initial canonical draft contains exactly:

```text
Командир роты
Заместитель командира роты по военно-политической работе
Старшина
Санитарный инструктор
Командир взвода
Начальник аппаратной-техник
Техник
Оператор
Командир отделения
Старший механик
Механик
Начальник радиостанции
Механик-радиотелефонист
Радиотелеграфист
Водитель-электрик
Радиотелефонист
Водитель-радиотелефонист
Заместитель командира взвода-командир отделения
Регулировщик
Регулировщик-наводчик
Регулировщик-радиотелефонист
Водитель-регулировщик
Водитель
Водитель-гранатометчик
```

Source file is not committed. All records use synthetic/local source metadata; real staffing data is prohibited.

### FR-17. Combined flag seed

Exactly these initial entries have `is_combined=true`:

```text
Начальник аппаратной-техник
Механик-радиотелефонист
Водитель-электрик
Водитель-радиотелефонист
Заместитель командира взвода-командир отделения
Регулировщик-наводчик
Регулировщик-радиотелефонист
Водитель-регулировщик
Водитель-гранатометчик
```

Other initial entries are false. No hyphen parser is used.

## 3. Existing catalog transition

### TR-01. Existing physical schema

Migration 014 alters existing migration-010 tables. It does not create a competing catalog and does not modify migration-010 marker/loader/payloads.

### TR-02. Legacy published version

Legacy version remains published until explicit canonical draft publication, then becomes superseded. Existing rows, variants, families, scopes, org relations and source/legal provenance remain intact and readable.

### TR-03. Canonical version

The first canonical writable version is a new draft, never an in-place mutation of legacy published rows.

### TR-04. Legacy compatibility values

Migration assigns safe canonical defaults to legacy rows only where new non-null schema requires them; it must not rewrite legacy names/codes/classification. New entries do not require legacy variants/families/scopes/org relations.

### TR-05. Legacy UI

Main route is canonical managed UI. Any legacy/superseded version is inspectable read-only on the version page. No historical provenance is destroyed.

## 4. Staffing compatibility

### ST-01. Version pinning

Existing StaffingVersion remains pinned. No migration or service silently remaps `position_catalog_version_id`, `position_type_id` or `position_variant_id`.

### ST-02. New Staffing register

New initial Staffing draft can select only a published position catalog. After canonical publication it can pin that version.

### ST-03. Draft from active

Staffing draft-from-active continues copying the same pinned catalog version. Catalog upgrade/remap is a future increment.

### ST-04. Slot fields

`position_type_id` continues to reference `military_position_types`; `position_variant_id` remains optional. Canonical entries use null variant.

### ST-05. Archived entries

Repository selectors for new Staffing slots return only active canonical entries for canonical versions. Existing slot/history reads retain archived entries. Legacy version behavior remains compatible.

## 5. Database requirements

Migration 014 must:

- extend lifecycle from `building/published/superseded` to `draft/published/superseded/cancelled` while handling legacy `building` safely;
- add version revision, dates/audit/cancellation metadata and single-draft guard;
- extend `military_position_types` with stable key, normalized name, full/short names, combined flag, source metadata, note, status, revision and audit fields;
- add unique version/stable and version/normalized-name constraints;
- add append-only history table;
- create four permissions without non-owner grants;
- create one initial canonical draft and exactly 24 approved records;
- preserve all legacy data/FKs and catalog-level compatibility links;
- enforce immutable terminal versions, draft-only entry mutations and valid transitions;
- be repeat-safe for clean/existing installer execution.

No destructive DROP of legacy classifier schema is allowed.

## 6. Service/action behavior

All mutations are POST-only, authenticated, permission-first, CSRF-protected, expected-revision guarded, transaction-bound and PRG redirected. Validation and database errors become safe Russian messages; raw SQL/exception details are not disclosed.

Repository and service methods must bind every entry operation to its catalog version. Expected-revision mismatch rolls back the whole transaction.

## 7. UI requirements

Screens:

```text
/admin/directories/military-positions.php
/admin/directories/military-positions/version.php
/admin/directories/military-positions/history.php
```

Actions/forms:

```text
create draft
create position
update position
archive position
restore position
publish version
cancel version
```

Uses theme assets/variables only. Desktop validation covers all three current themes. Mobile is `NOT RUN / OUT OF SCOPE`.

## 8. Exact implementation boundary

```text
MAX_CHANGED_PATHS=38
EXACT_CHANGED_PATH_ALLOWLIST_COUNT=38
```

The exact path list is the owner-approved allowlist in the 2026-08-07 Implementation Approval. No migration compatibility file, workflow, configuration, repository setting or production deployment path is permitted.

## 9. Tests

### Static

- exact base/branch/merge-base and 38-path allowlist;
- PHP lint and `git diff --check`;
- exact migration filename and no changes to migration 010 compatibility files;
- no personal/real staffing data and no forbidden entity fields;
- no destructive legacy DROP or hidden Staffing remap;
- Russian UI and no raw JSON history.

### Clean DB

- migrations 001–014;
- legacy version remains published;
- exactly one initial canonical draft with exactly 24 approved names/flags;
- four permissions without non-owner grants;
- repeat installer no-op.

### Existing DB with backup

- all legacy catalog counts/data and Staffing references preserved;
- migration repeat no-op;
- draft/lifecycle guards and history work after upgrade.

### Functional/lifecycle

- create/update/archive/restore entry in draft;
- duplicate normalized name and stale revision rejection;
- publish/cancel; terminal mutation rejection;
- stable key survives version copy/rename;
- history is append-only/readable;
- legacy version read-only;
- existing Staffing pin unchanged;
- archived canonical entry unavailable for new slot.

### Desktop and HTTP

Validate list, filters, version/entry cards, editor, history and actions in three themes, then authenticated HTTP smoke. Mobile is not claimed.

## 10. Non-goals

- VUS/rank profiles;
- Excel importer or source workbook in runtime;
- catalog upgrade/remap for an existing StaffingVersion;
- personnel, occupancy, equipment or real staffing data;
- external integration or production deployment;
- mobile acceptance;
- destructive legacy cleanup.

## 11. Implementation gate result

```text
POST_STAFFING_RECONCILIATION=PASS
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS=0
OPEN_FINDINGS=0
OWNER_IMPLEMENTATION_APPROVAL=GRANTED
PR=NOT AUTHORIZED
MERGE=NOT AUTHORIZED
```

## 12. Corrective desktop UI requirements (version 0.3)

### UI-C01. Compact list action

On `/admin/directories/military-positions.php`, every closed version card shows one content-sized `Открыть` action aligned with the card header. It must not occupy the remaining header width.

### UI-C02. Open version actions

On `/admin/directories/military-positions/version.php?id=<id>`:

- the opened version card contains no `Открыть` action;
- `История этой версии` is in the top-right contextual action group when the user has `directories.military_positions.history`;
- `Закрыть` is in the same group and returns to `/admin/directories/military-positions.php#military-position-version-<id>`;
- the version-list card exposes the matching anchor.

The rule applies equally to canonical draft, published legacy and any superseded/cancelled version.

### UI-C03. Archive/restore reason placement

For a manageable draft entry, `Изменить` and `Архивировать должность` / `Восстановить должность` are distinct disclosure actions. The required reason field is rendered only inside the opened archive/restore disclosure, immediately with its confirmation button. The state action panel is full-width and remains visually independent from the editor whether the editor is closed or open.

### UI-C04. Presentation and exclusions

- controls use Russian labels and native links/details/forms; no new JavaScript;
- all three theme CSS files remain byte-symmetric from the managed marker;
- no hardcoded feature colors;
- no service, repository, migration, route, permission, CSRF, revision or PRG behavior changes;
- mobile remains `NOT RUN / OUT OF SCOPE`.

### UI-C05. Corrective acceptance

Required after implementation on the new exact head:

- PHP lint, `git diff --check`, exact 38-path inventory and updated static assertions;
- full local deploy/initializer runner with backup and HTTP smoke;
- desktop recheck in `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova` for list cards, canonical draft detail, historical detail, collapsed entry, opened editor, archive/restore disclosure and version history navigation;
- `Открыть` absent on detail pages; `Закрыть` returns to the anchored version card; `История этой версии` is visible at the top; reason fields are absent until their lifecycle action is opened.

```text
FIRST_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```
## 13. Corrective implementation record

Corrective UI реализован в пределах exact 12-path allowlist после owner Approval на documentation head `c7d2c08c918ae5f0a3ade569c1b504efc1b54ad1`.

Static implementation mapping:

- UI-C01: list caller задаёт `list`, а action group принудительно content-sized;
- UI-C02: detail caller задаёт `detail`; history и `Закрыть` находятся в card header; close target использует version anchor;
- UI-C03: state transition перенесён в отдельный `details`, где reason и submit находятся вместе;
- UI-C04: JavaScript и backend contracts не менялись; managed CSS идентичен для трёх тем;
- UI-C05: checker дополнен assertions и exact 12-path commit inventory.

```text
CORRECTIVE_IMPLEMENTATION=COMPLETE
FIRST_CORRECTIVE_STATIC_VALIDATION=PASS
FIRST_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=PASS
FIRST_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```


## 14. Second corrective desktop UI requirements (version 0.4)

### UI-C06. Equal adjacent entry actions

For every manageable draft entry:

- `Изменить` and `Архивировать должность` / `Восстановить должность` are content-sized controls in one shared action row;
- the lifecycle control uses the same visual component, height, padding, border, background and typography as `Изменить`;
- no full-width border/background shell is visible around a collapsed lifecycle control;
- controls stay adjacent while both disclosures are closed;
- opening either disclosure renders its form as one full-width row below the shared controls;
- disclosures are mutually exclusive per entry, so edit and lifecycle forms cannot remain open simultaneously;
- the lifecycle reason remains hidden until its control is opened and stays grouped with the confirmation button.

The archive/restore form payload and server behavior remain unchanged. The same rule applies to active and archived entries.

### UI-C07. Second corrective implementation boundary

Exactly nine paths may change: `entry-card.php`, three managed theme CSS files, the static checker and four design/handoff documents. No database, migration, repository, service, route, permission, JavaScript, workflow or configuration change is allowed.

### UI-C08. Second corrective acceptance

Required on the new exact head:

- exact 9-path corrective commit inventory and unchanged 38-path total increment inventory;
- PHP lint, `git diff --check`, static assertions and CSS symmetry;
- full local runner with backup, initialization/repeat DB checks and HTTP smoke;
- desktop verification in all three themes that the two collapsed controls are visually identical and adjacent;
- open `Изменить`, then open lifecycle action and verify mutual exclusion;
- verify that the lifecycle reason and confirmation appear below the shared action row and no operation is submitted;
- mobile remains `NOT RUN / OUT OF SCOPE`.

```text
SECOND_CORRECTIVE_DESIGN_REVIEW=PASS
SECOND_CORRECTIVE_ALLOWLIST_PATHS=9
SECOND_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```

## 15. Second corrective implementation record

Owner Approval was granted against exact documentation head `294cd91e26513217187cbf07447b2e769aa2ff72`.

Static implementation mapping:

- UI-C06: entry-scoped native `details[name]` makes edit and lifecycle disclosures mutually exclusive without JavaScript;
- UI-C06: both summaries are direct visual peers in one content-sized action row;
- UI-C06: opened edit and lifecycle forms span the full row below the controls;
- UI-C06: the lifecycle reason and confirmation remain grouped in the opened form;
- UI-C07: exactly nine approved paths change and all backend contracts remain byte-for-byte outside scope;
- UI-C08: checker assertions and exact nine-path commit inventory are fail-closed.

```text
SECOND_CORRECTIVE_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
SECOND_CORRECTIVE_STATIC_VALIDATION=PASS
SECOND_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=PASS
SECOND_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```


## 16. Third corrective desktop UI requirements (version 0.5)

### UI-C09. Deterministic adjacent entry actions

For every manageable draft entry on normal desktop width:

- `Изменить` occupies the first action column;
- `Архивировать должность` / `Восстановить должность` occupies the immediately adjacent second action column;
- the controls retain identical compact styling and a fixed small gap;
- the browser must not derive their spacing from auto-placement or the width of opened form content;
- native per-entry disclosure mutual exclusion remains unchanged.

### UI-C10. Single-line lifecycle reason form

When the lifecycle disclosure is open on normal desktop width:

- reason label, reason input and confirmation button render in one horizontal row;
- label and input remain one accessible label/input unit;
- input grows into available space and the confirmation button remains content-sized;
- the form remains a full-width row below both action controls;
- no archive, restore, revision, CSRF, PRG or route contract changes;
- below the existing narrow-screen breakpoint the row may stack; mobile acceptance remains out of scope.

### UI-C11. Third corrective boundary and acceptance

Exactly eight paths may change: three managed theme CSS files, the static checker and four design/handoff documents. `entry-card.php`, database, migration, repository, service, routes, permissions, JavaScript, workflow and configuration are excluded.

Required on the future exact implementation head:

- exact 8-path third corrective commit inventory and unchanged 38-path total increment inventory;
- PHP lint, `git diff --check`, static assertions and byte-identical managed CSS across three themes;
- full local runner with backup, initialization/repeat DB checks and HTTP smoke;
- desktop verification in `asu-blue`, `asu-light-blue` and `asu-evgeniya-rostova` that the two controls are adjacent;
- open lifecycle action and verify label, input and confirmation in one horizontal row;
- open edit and lifecycle actions in turn and verify native mutual exclusion;
- submit no edit, archive, restore, publish or cancel action;
- mobile remains `NOT RUN / OUT OF SCOPE`.

```text
SECOND_CORRECTIVE_STATIC_VALIDATION=PASS
SECOND_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=PASS
SECOND_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
THIRD_CORRECTIVE_DESIGN_REVIEW=PASS
THIRD_CORRECTIVE_ALLOWLIST_PATHS=8
THIRD_CORRECTIVE_UI_IMPLEMENTATION=AUTHORIZED_AND_IMPLEMENTED_PENDING_VALIDATION
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```

## 17. Third corrective implementation record

Owner Approval was granted against exact documentation head `b1768ad5ffce5e1da1057096bcf6e02063cea3a1`.

Static implementation mapping:

- UI-C09: explicit first/second summary columns make the two compact controls deterministically adjacent;
- UI-C09: the existing 10px column gap remains the only desktop spacing between the controls;
- UI-C10: the full-width lifecycle form uses a growing label/input area plus a content-sized submit column;
- UI-C10: the label itself uses a text column plus a growing input column, producing one horizontal desktop row;
- UI-C10: the existing 760px breakpoint stacks both form and label without JavaScript;
- UI-C11: exactly eight approved paths change; PHP and all backend contracts stay outside the patch;
- UI-C11: checker assertions and exact eight-path commit inventory are fail-closed.

```text
THIRD_CORRECTIVE_IMPLEMENTATION=COMPLETE_PENDING_VALIDATION
THIRD_CORRECTIVE_STATIC_VALIDATION=PENDING_EXACT_COMMIT
THIRD_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=NOT_RUN
THIRD_CORRECTIVE_DESKTOP_ACCEPTANCE=NOT_RUN
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```

## 18. Fourth corrective desktop UI requirements (version 0.6)

### UI-C12. Stable adjacent action toolbar

For every manageable canonical-draft entry on normal desktop width:

- `Изменить` and `Архивировать должность` / `Восстановить должность` are ordinary neighboring grid items in one toolbar row;
- the controls use identical compact styling and exactly one fixed `10px` gap;
- neither opened form contributes to the width calculation of either control column;
- `display: contents` is not used on native `<details>`;
- each native `details[name]` remains an entry-scoped mutually exclusive disclosure switch;
- each controlled sibling panel has a unique entry-scoped `id`, referenced by its summary through `aria-controls`;
- opening edit closes lifecycle and opening lifecycle closes edit without JavaScript.

### UI-C13. Clear lifecycle reason hierarchy

When lifecycle disclosure is open on normal desktop width:

- `Основание архивирования` / `Основание восстановления` renders above its input, not beside it;
- label text and input remain one semantic `<label>` unit;
- input has an approximately `360px` bounded desktop width and is visibly longer than the failed third-corrective field;
- confirmation remains content-sized to the right and bottom-aligned with the input;
- the whole lifecycle panel remains below both action controls and spans the available action area;
- below the existing `760px` breakpoint, input and confirmation may stack; mobile acceptance remains out of scope;
- archive/restore action, names, payload, CSRF, revisions, PRG and routes remain unchanged.

### UI-C14. Fourth corrective boundary and acceptance

Exactly nine paths may change: `entry-card.php`, three managed theme CSS files, the static checker and four design/handoff documents. Database, migrations, repositories, services, routes, permissions, JavaScript, workflows and configuration are excluded.

Required on the future exact implementation head:

- exact `9/9` fourth-corrective commit inventory and unchanged 38-path total increment inventory;
- PHP lint, `git diff --check`, static assertions and byte-identical managed CSS across three themes;
- checker assertions that panels are siblings of summary-only native disclosures, summaries reference unique panels, and no managed feature CSS uses `display: contents` for entry actions;
- full local runner with backup, initialization/repeat DB checks and HTTP smoke;
- desktop verification in `asu-blue`, `asu-light-blue` and `asu-evgeniya-rostova` with lifecycle closed and open;
- verify two adjacent controls retain the same `10px` gap after either disclosure opens;
- verify lifecycle label is above the longer input and confirmation is aligned to its right;
- submit no edit, archive, restore, publish or cancel action;
- mobile remains `NOT RUN / OUT OF SCOPE`.

```text
THIRD_CORRECTIVE_STATIC_VALIDATION=PASS
THIRD_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=PASS
THIRD_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
FOURTH_CORRECTIVE_DESIGN_REVIEW=PASS
FOURTH_CORRECTIVE_ALLOWLIST_PATHS=9
FOURTH_CORRECTIVE_UI_IMPLEMENTATION=PENDING_OWNER_APPROVAL
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```
