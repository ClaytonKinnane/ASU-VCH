# АСУ-ВЧ — текущий handoff для нового чата

## 1. Что сделать в новом чате первым

1. Прочитать `docs/PROJECT-WORKING-RULES.md` и этот файл.
2. Проверить live GitHub: `main`, remote branches, open PR, open Issues, relevant Actions.
3. Сопоставить live state с snapshot ниже; GitHub/Git имеет приоритет для mutable lifecycle.
4. Если существует `design/personnel-core-card-v1`, продолжить с exact active gate этого increment и не перепрыгивать Approval.
5. Не начинать Personnel runtime implementation до explicit owner Approval Architecture/Specification/Review и exact path allowlist.
6. Не повторять уже действующие standing permissions для routine maintenance rules/handoff.

## 2. Repository / local environment

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
selected Personnel design base: dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8
local repository: C:\Project\ASU-VCH
deploy: C:\OSPanel\home\asu-vch.local
URL: https://asu-vch.local
Open Server Panel: 6.5.1
Apache
PHP: 8.5.4
MySQL: 8.4.x
PowerShell: 5.1
```

Exact live `main` must always be re-read before the next material gate.

## 3. Current durable main state

```text
latest functional PR: #36 / Military Positions Directory v1
previous functional PR: #35 / Lowest Unit Staffing Structure v1
latest docs PR: #37 / current-state reconciliation
migrations: 001–014
system roles: 4
system permissions: 35
themes: asu-blue, asu-light-blue, asu-evgeniya-rostova
required CSS assets/theme: 10
open functional findings at baseline: 0
production deployment: NOT PERFORMED
mobile: NOT RUN / OUT OF SCOPE
```

Current durable runtime remains through PR #36. Personnel runtime has not been implemented.

## 4. Completed recent functional increments

### Lowest Unit Staffing Structure v1 — PR #35

```text
migration: 013_lowest_unit_staffing_v1.sql
status: MERGED
```

Implemented Staffing registers, version lifecycle, document metadata, stable individual slots, Organization/catalog pins, rank/VUS requirements, history and compare. Persons, assignments and actual occupancy remain outside Staffing v1.

### Military Positions Directory v1 — PR #36

```text
final feature head: 3756b2ec53a00f68d5c1f5c098d1c274f6b8d769
runtime validated head: c647a933011873048866c75978d3f506634011fd
merge commit: a6cfceb421fac8d0985e409770bb26a62fac0b14
migration: 014_military_positions_directory_v1.sql
status: MERGED
```

Core behavior:

- existing position catalog evolved in place;
- canonical draft lifecycle without auto-publication;
- stable identity and append-only history;
- no automatic non-owner grants;
- existing Staffing pins/history preserved;
- no VUS/rank/unit/person/equipment/occupancy properties inferred from position name.

Validation claims belong only to exact runtime head `c647a933...`; later documentation/merge commits are not relabeled runtime-tested.

## 5. Remote branches / preservation rule

At the start of Personnel design, live remote inventory was:

```text
main @ dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8
research/military-accounting-order-700 @ 69bf9c9e1609a40c7f4c27ff41b0ddeebabe2ffe
```

A new branch was then explicitly created for the selected next task:

```text
design/personnel-core-card-v1
base = main@dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8
```

Branch deletion is not authorized for this branch or the research branch.

## 6. Unique research branch

`research/military-accounting-order-700` remains unique unmerged research for `PersonnelServiceAccounting` and must be preserved until intentional reconciliation/decision.

Research target:

```text
PersonnelServiceAccounting
CitizenMilitaryAccounting = EXCLUDED
```

The Personnel design may reuse/reconcile relevant ideas but must not claim the research branch itself was merged or approved as runtime.

## 7. Owner decision — Personnel target

On 2026-08-08 the owner selected the next product direction: block «Военнослужащие».

Owner provided four document templates without real servicemember values:

```text
Анкета
Индивидуальные данные военнослужащих
Объективка
Контрольно-розыскная карта на военнослужащего
```

Project decision:

- ASU-VCH target Personnel model may contain the full necessary set of active-servicemember data, including medical, physical-identification, legal, financial, digital and special-case data when required by the relevant military process;
- sensitivity does not remove a data category from the target model;
- who may access which data will later be governed by a separately designed role/authority model based on orders/other approved authority documents;
- fine-grained Personnel security is postponed to a future increment;
- the current objective is a working Personnel data/accounting prototype;
- military position is not automatically a Security role;
- paper/report forms are projections of one canonical Personnel model, not independent duplicate stores;
- permanent/temporal person facts are separate from situational case snapshots.

Future access conclusions are stored in:

```text
docs/design/PERSONNEL-ACCESS-FUTURE-DESIGN-NOTES.md
```

## 8. Active design increment

```text
ACTIVE_DESIGN_INCREMENT=Personnel Core Card v1
DESIGN_BRANCH=design/personnel-core-card-v1
BASE_SHA=dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8
REVIEWED_DOCUMENT_HEAD=272eb66184b45e380e92654be90fb8fccd1959a1
FORMAL_REVIEW=PASS
OPEN_REVIEW_FINDINGS=0
ACTIVE_RUNTIME_IMPLEMENTATION=NONE
NEXT_IMPLEMENTATION_APPROVAL=WAITING FOR OWNER
```

Design documents:

```text
docs/domains/PERSONNEL.md
docs/design/PERSONNEL-ACCESS-FUTURE-DESIGN-NOTES.md
docs/design/PERSONNEL-CORE-CARD-V1-ARCHITECTURE.md
docs/design/PERSONNEL-CORE-CARD-V1-SPECIFICATION.md
docs/design/PERSONNEL-CORE-CARD-V1-REVIEW.md
```

Architecture/Specification v0.2 were formally reviewed at exact head `272eb661...`.

## 9. Formal Review corrections

Two pre-Approval findings were found and resolved before PASS:

```text
F-P01: personal number / dog tag historical reuse ambiguity → RESOLVED
F-P02: identifier replace/end effective-date ambiguity → RESOLVED
```

Final rules:

- personal number and dog tag are globally unique across retained history and never reused;
- table number and call sign may be reused according to type policy;
- identifier intervals use `[valid_from, valid_to)`;
- replace/end require explicit `effective_date`.

No open review finding remains.

## 10. Personnel Core Card v1 proposed runtime scope

V1 proposes:

- canonical PersonnelRecord;
- ФИО;
- date/place of birth;
- citizenship, nationality, religion;
- typed identifiers: personal number, dog tag, table number, call sign;
- list/search/card;
- aggregate revision / stale-write protection;
- active/archive/restore card lifecycle;
- append-only change history;
- migration `015_personnel_core_card_v1.sql`;
- owner-only prototype access using existing `system_owner`;
- no new Personnel permissions/grants;
- no real Personnel seeds/fixtures.

V1 explicitly does not implement yet:

- person→Staffing assignment;
- position/unit/occupancy truth;
- rank/VUS/service history/contracts/orders;
- contacts/family;
- files/photos/documents;
- medical/physical-identification tables;
- legal/financial/digital data;
- SpecialCases;
- generated forms;
- fine-grained access model;
- production deployment;
- mobile acceptance.

These remain target Personnel requirements, not rejected functionality.

## 11. Core architecture boundary with Staffing

Target relation:

```text
PersonnelRecord
→ Assignment (future)
→ StaffingSlot
→ OrganizationalElement / MilitaryPosition
```

Therefore Personnel Core v1 must not introduce duplicate current `position_id`/`department_id` fields. Actual occupied/vacant state appears only when Assignments exists.

## 12. Prototype access boundary

Until the separately approved Personnel Security increment:

```text
Personnel routes = system_owner only
new Personnel permissions = 0
new non-owner grants = 0
fine-grained organizational scope = NOT IMPLEMENTED
```

This is a development/prototype boundary, not the final access architecture.

## 13. Proposed implementation allowlist

Formal Review accepted:

```text
MAX_EXPECTED_CHANGED_PATHS=40
```

Scope includes migration 015, new `app/Personnel`, protected Personnel admin UI, validation tools and required living/design docs.

No theme asset, workflow, repository settings, deployment config, migrations 001–014, Organization runtime, Staffing runtime, Reference runtime or fine-grained Security runtime is allowlisted.

Any path expansion requires fail-closed re-approval.

## 14. Permanent operating rules

Ordinary material work uses:

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing/Validation → Commit → Push → Pull Request
→ exact-head Actions → Final PR Review → Merge approval → Merge
→ Post-merge verification → Branch deletion approval → Branch deletion
```

No runtime implementation before Personnel owner Approval.

Routine updates of only `PROJECT-WORKING-RULES.md` and `CHAT-HANDOFF.md` retain standing authorization, but branch deletion always requires separate explicit owner approval.

## 15. Safety / evidence boundaries

- no real personnel/staffing/unit data in migrations/docs/test fixtures;
- runtime prototype tests use synthetic records unless separately authorized otherwise;
- no secret/local config in Git;
- no production deployment claim without execution;
- no mobile PASS without actual mobile testing;
- no branch deletion without separate exact approval;
- no force-push/history rewrite without explicit authorization;
- no silent scope expansion beyond the approved Personnel implementation allowlist.

## 16. Current next gate

```text
CURRENT_GATE=Formal Review PASS → waiting for explicit Owner Approval
IMPLEMENTATION=PROHIBITED UNTIL APPROVAL
REVIEWED_DOCUMENT_HEAD=272eb66184b45e380e92654be90fb8fccd1959a1
MAX_EXPECTED_CHANGED_PATHS=40
```

The next action is to present the reviewed scope and exact anchors to the owner. Only after explicit Approval may an implementation branch be created/used for runtime work.