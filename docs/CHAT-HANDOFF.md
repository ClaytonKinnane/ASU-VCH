# АСУ-ВЧ — handoff активного инкремента

## 1. Обязательный старт следующего чата

1. Прочитать `docs/PROJECT-WORKING-RULES.md` и этот handoff.
2. Проверить live GitHub: `main`, feature head, branches, PR, Issues и Actions.
3. Сопоставить live state с exact base/branch/allowlist ниже.
4. Продолжать с second corrective Testing/Validation gate; Approval получен и implementation patch опубликован только в feature-ветку.
5. Fail closed при moved base, unexpected material increment, extra path, другой migration mechanism или merge/rebase/force-push.

GitHub/Git — canonical source mutable lifecycle. Feature head всегда получать live: текущий документ входит в implementation diff и не содержит самоссылочный commit SHA.

## 2. Репозиторий и локальная среда

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
exact implementation base: 9ae05b9928903cc483ce415d7378b546e419264c
base merge: PR #35 / Lowest Unit Staffing Structure v1
implementation branch: feature/military-positions-directory-v1
local repository: C:\Project\ASU-VCH
deploy: C:\OSPanel\home\asu-vch.local
domain: https://asu-vch.local
Open Server Panel: 6.5.1
Apache
PHP: 8.5.4
MySQL: 8.4
PowerShell: 5.1
```

## 3. Permanent lifecycle and exclusions

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing/Validation → Commit → Push → Pull Request
→ exact-head Actions → Final PR Review → separate Merge approval
→ Merge → Post-merge verification → separate Branch deletion approval
```

Static checks do not replace MySQL, backup, migrations, deploy, HTTP/browser or visual desktop acceptance. Mobile is `NOT RUN / OUT OF SCOPE`. Production deployment, workflow/settings changes and real staffing/personnel data are prohibited.

```text
TARGET_CONTOUR=PersonnelServiceAccounting
CitizenMilitaryAccounting=EXCLUDED
NO_REAL_STAFFING_DATA_BEFORE_SECURITY_FOUNDATION
```

## 4. Completed dependency

```text
INCREMENT=Lowest Unit Staffing Structure v1
PR=35
MERGED_MAIN=9ae05b9928903cc483ce415d7378b546e419264c
MIGRATION=013_lowest_unit_staffing_v1.sql
POST_MERGE_ACTIONS=SUCCESS
```

Open PR and Issues were both zero at implementation preflight.

## 5. Active increment

```text
NAME=Military Positions Directory v1
CLASSIFICATION=functional
BASE_BRANCH=main
EXPECTED_BASE_SHA=9ae05b9928903cc483ce415d7378b546e419264c
FEATURE_BRANCH=feature/military-positions-directory-v1
MIGRATION=database/migrations/014_military_positions_directory_v1.sql
MAX_CHANGED_PATHS=38
POST_STAFFING_RECONCILIATION=PASS
ORIGINAL_BLOCKING_FINDINGS=0
ORIGINAL_MAJOR_FINDINGS=0
ORIGINAL_MINOR_FINDINGS=0
ORIGINAL_OPEN_FINDINGS=0
ORIGINAL_IMPLEMENTATION_APPROVAL=GRANTED
PRE_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
FIRST_CORRECTIVE_UI_IMPLEMENTATION_APPROVAL=GRANTED
FIRST_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED
FIRST_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=PASS
FIRST_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
OPEN_UI_FINDINGS=4_PENDING_RETEST
SECOND_CORRECTIVE_UI_IMPLEMENTATION_APPROVAL=GRANTED
SECOND_CORRECTIVE_UI_IMPLEMENTATION=IMPLEMENTED_PENDING_VALIDATION
PULL_REQUEST=NOT AUTHORIZED
MERGE=NOT AUTHORIZED
BRANCH_DELETION=NOT AUTHORIZED
```

The branch was created exactly from the expected base after live preflight confirmed main, missing implementation branch, migrations 001–013, absent migration 014 and no unexpected material increment.

## 6. Design source and post-Staffing documents

```text
DESIGN_SOURCE_BRANCH=design/military-positions-directory-v1
DESIGN_SOURCE_HEAD=bad4057251f9ebf996d83b3e246df24127a5d5cc
DESIGN_SOURCE_MERGE_BASE=3d8a491ff2433994e8580152f190b298c765c66e
DESIGN_BRANCH_AHEAD=3
DESIGN_BRANCH_BEHIND=37
```

Never use the old design branch as implementation base. Its Architecture, Specification and Formal Review were transferred and reconciled as version 0.2 on the exact feature branch before runtime edits.

## 7. Frozen product contract

- evolve existing `military_position_catalog_versions` + `military_position_types`; no parallel catalog;
- standalone migration 014; migration-010 marker, loader and five payload parts remain untouched;
- lifecycle `draft → published → superseded` and `draft → cancelled`;
- stable identity, normalized uniqueness, optimistic revisions and append-only history;
- legacy classifier remains current published; migration creates one canonical draft;
- exact 24 approved synthetic names and exact nine explicit combined flags;
- explicit publication atomically supersedes legacy without deletion;
- four permissions, no automatic non-owner grants;
- Russian managed UI and readable history, no raw JSON;
- existing Staffing versions remain pinned; no hidden catalog remap;
- archived canonical entries are excluded from new Staffing selectors;
- desktop layouts for `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova` use theme variables;
- no VUS/rank/unit/person/equipment/occupancy fields in canonical position entity.

## 8. Implementation inventory

Implementation is contained by the exact approved 38-path allowlist. Major components:

```text
database/migrations/014_military_positions_directory_v1.sql
app/Directory/MilitaryPositionCatalogRepository.php
app/Directory/MilitaryPositionCatalogService.php
app/Directory/MilitaryPositionCatalogFunctions.php
app/Staffing/StaffingRepository.php
public/admin/directories/military-positions.php
public/admin/directories/military-positions/version.php
public/admin/directories/military-positions/history.php
public/admin/directories/military-positions/{versions,entries,views}/* approved files
themes/{asu-blue,asu-light-blue,asu-evgeniya-rostova}/assets/css/directories.css
tools/Test-MilitaryPositionsDirectoryV1.ps1
tools/check-military-positions-directory-v1.php
three design 0.2 docs and nine living docs
```

Use the exact list in the PowerShell runner or static checker; do not infer additional paths.

## 9. Validation contract

Prepared command for the target local machine:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\Test-MilitaryPositionsDirectoryV1.ps1 `
  -RepositoryPath C:\Project\ASU-VCH `
  -ExpectedHead <EXACT_FEATURE_HEAD> `
  -RunInitialization `
  -RunHttpSmoke `
  -AllowInvalidCertificate
```

The runner enforces branch/base/head, maximum 38 paths, exact allowlist, clean worktree, `git diff --check`, PHP lint, static checker, protected migration-010 hashes, mandatory pre-migration backup, initialization, repeat installer, DB checker and optional HTTP smoke.

Validation evidence on runtime head `7751430288d2b0669dee4fe14101f809f5828db5`:

```text
PROTECTED_MIGRATION_010_HASHES=PASS
STATIC_CHECKER=PASS (109 passes)
PRE_MIGRATION_BACKUP=PASS
PHP_LINT=PASS (171 files)
MIGRATION_014=APPLIED
REPEAT_INITIALIZER=PASS
DATABASE_RUNTIME_CHECKER=PASS (117 passes)
HTTP_SMOKE=PASS (200, 200, expected 302; local certificate bypass explicitly enabled)
DESKTOP_ACCEPTANCE=FAIL
OPEN_UI_FINDINGS=3
MOBILE_ACCEPTANCE=NOT RUN / OUT OF SCOPE
```

The automated gate is complete for that exact runtime head, but six owner-provided desktop screenshots on 2026-08-07 identified one major and two minor UI findings. A later documentation-only or corrective implementation head is not runtime-tested until the runner and desktop acceptance are repeated on that exact head.

## 10. Remote branch warning

At preflight the repository contained:

```text
main
design/military-positions-directory-v1
docs/handoff-lowest-unit-staffing-design
docs/handoff-military-accounting-research
research/military-accounting-order-700
```

The implementation branch was then added. No branch may be deleted without separate authorization.

## 11. Next gate

Owner Approval was granted against exact documentation head `294cd91e26513217187cbf07447b2e769aa2ff72`, and the nine-path second corrective implementation is published only in the feature branch. Next gate is full local validation on the live exact implementation head, followed by desktop acceptance in all three themes. Pull Request, merge, branch deletion and production deployment remain forbidden.

## 12. Corrective desktop UI gate

First corrective design 0.3 implemented the approved version-card and hidden-reason behavior. Architecture/Specification/Review 0.4 add the pending second correction:

- list cards retain compact `Открыть` controls;
- opened version cards show `История этой версии` and functional `Закрыть` in the top action group; `Открыть` is absent;
- `Закрыть` returns to the anchored version card in the list;
- archive/restore reason and confirmation remain hidden until selected and full-width below the entry fields/editor;
- `Изменить` and `Архивировать должность` / `Восстановить должность` become identical compact controls placed next to each other in one shared action row;
- the collapsed lifecycle control has no separate full-width shell;
- edit and lifecycle disclosures are mutually exclusive per entry;
- all three theme CSS files remain symmetric.

```text
FIRST_CORRECTIVE_ALLOWLIST_PATHS=12
FIRST_CORRECTIVE_DESIGN_REVIEW=PASS
FIRST_CORRECTIVE_UI_IMPLEMENTATION_APPROVAL=GRANTED
FIRST_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED
FIRST_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=PASS
FIRST_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
SECOND_CORRECTIVE_DESIGN_REVIEW=PASS
SECOND_CORRECTIVE_ALLOWLIST_PATHS=9
SECOND_CORRECTIVE_UI_IMPLEMENTATION_APPROVAL=GRANTED
SECOND_CORRECTIVE_UI_IMPLEMENTATION=IMPLEMENTED_PENDING_VALIDATION
OPEN_UI_FINDINGS=4_PENDING_RETEST
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
BRANCH_DELETION=NOT_AUTHORIZED
```

Corrective paths:

```text
public/admin/directories/military-positions.php
public/admin/directories/military-positions/version.php
public/admin/directories/military-positions/views/version-card.php
public/admin/directories/military-positions/views/entry-card.php
themes/asu-blue/assets/css/directories.css
themes/asu-light-blue/assets/css/directories.css
themes/asu-evgeniya-rostova/assets/css/directories.css
tools/check-military-positions-directory-v1.php
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md
docs/CHAT-HANDOFF.md
```

Owner Approval was granted against documentation head `c7d2c08c918ae5f0a3ade569c1b504efc1b54ad1`, and the first corrective implementation changed exactly the 12 paths above. Its automatic gate passed on `6b63efd6d3a6e7567cc48106bd8c12bd9371e585`; desktop evidence then opened UI-F04. Second corrective Approval was granted against exact documentation head `294cd91e26513217187cbf07447b2e769aa2ff72`.


## 13. Second corrective action-row gate

UI-F04 is limited to the entry-card action layout. Approved design review 0.4 requires identical adjacent controls and a full-width opened form below them, without changing the archive/restore operation.

```text
SECOND_CORRECTIVE_ALLOWLIST_PATHS=9
SECOND_CORRECTIVE_DESIGN_REVIEW=PASS
SECOND_CORRECTIVE_UI_IMPLEMENTATION_APPROVAL=GRANTED
SECOND_CORRECTIVE_UI_IMPLEMENTATION=IMPLEMENTED_PENDING_VALIDATION
PULL_REQUEST=NOT AUTHORIZED
MERGE=NOT AUTHORIZED
BRANCH_DELETION=NOT AUTHORIZED
```

Second corrective paths:

```text
public/admin/directories/military-positions/views/entry-card.php
themes/asu-blue/assets/css/directories.css
themes/asu-light-blue/assets/css/directories.css
themes/asu-evgeniya-rostova/assets/css/directories.css
tools/check-military-positions-directory-v1.php
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md
docs/CHAT-HANDOFF.md
```

Owner Approval was granted against exact documentation head `294cd91e26513217187cbf07447b2e769aa2ff72`. The implementation changes exactly the nine paths above: both entry actions are adjacent peers, native entry-scoped disclosure grouping enforces mutual exclusion, opened forms span below the controls, and lifecycle reason/confirmation remain grouped. Next gate is exact-head local validation and three-theme desktop acceptance. PR, merge, force-push, branch deletion and scope expansion remain forbidden.
