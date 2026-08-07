# АСУ-ВЧ — handoff активного инкремента

## 1. Обязательный старт следующего чата

1. Прочитать `docs/PROJECT-WORKING-RULES.md` и этот handoff.
2. Проверить live GitHub: `main`, feature head, branches, PR, Issues и Actions.
3. Сопоставить live state с exact base/branch/allowlist ниже.
4. Продолжать с незавершённого validation/commit/push/PR gate; не повторять уже выданное Implementation Approval.
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
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS=0
OPEN_FINDINGS=0
IMPLEMENTATION_APPROVAL=GRANTED
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

Required remaining evidence:

```text
AVAILABLE_STATIC_VALIDATION=PENDING/RE-RUN ON EXACT HEAD
CLEAN_DB_001_014=PENDING
EXISTING_DB_BACKUP_MIGRATION_REPEAT=PENDING
LIFECYCLE_AND_REVISION_TESTS=PENDING
STAFFING_PINNING_REGRESSION=PENDING
HTTP_SMOKE=PENDING
DESKTOP_THREE_THEMES=PENDING
MOBILE_ACCEPTANCE=NOT RUN / OUT OF SCOPE
```

Only actual output may turn a pending item into PASS.

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

If implementation is not yet committed/pushed, finish available validation, inspect exact diff and commit/push only to `feature/military-positions-directory-v1`. If already pushed, verify the live feature head and report results.

Do not create a Pull Request. The next owner-controlled gate after implementation push is separate exact-head PR authorization. Merge, branch deletion and production deployment remain forbidden.
