# АСУ-ВЧ — текущий handoff для нового чата

## 1. Что сделать в новом чате первым

1. Прочитать `docs/PROJECT-WORKING-RULES.md` и этот файл.
2. Проверить live GitHub: `main`, remote branches, open PR, open Issues, relevant Actions.
3. Сопоставить live state с snapshot ниже; GitHub/Git имеет приоритет для mutable lifecycle.
4. Проверить, появился ли новый approved increment после этого snapshot.
5. Если active implementation отсутствует, не начинать новый material scope без Research → Analysis → Architecture → Specification → Review → Approval.
6. Не повторять уже действующие standing permissions для routine maintenance rules/handoff.

## 2. Repository / local environment

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
main audit snapshot: b3dda6cae88072c1e74c25de28f7023a8d73620d
local repository: C:\Project\ASU-VCH
deploy: C:\OSPanel\home\asu-vch.local
URL: https://asu-vch.local
Open Server Panel: 6.5.1
Apache
PHP: 8.5.4
MySQL: 8.4.x
PowerShell: 5.1
```

`main` SHA выше — snapshot 2026-08-08; всегда получить live value заново.

## 3. Current durable state

```text
latest functional PR: #36 / Military Positions Directory v1
previous functional PR: #35 / Lowest Unit Staffing Structure v1
migrations: 001–014
system roles: 4
system permissions: 35
themes: asu-blue, asu-light-blue, asu-evgeniya-rostova
required CSS assets/theme: 10
active product implementation increment: NONE
open functional findings: 0
production deployment: NOT PERFORMED
mobile: NOT RUN / OUT OF SCOPE
```

Current `main` tree equals PR #36 merge tree. После merge PR #36 были history-only noop/revert commits, которые восстановили exact tree; не переписывать `main`/history без отдельного explicit authorization.

## 4. Completed recent functional increments

### Lowest Unit Staffing Structure v1 — PR #35

```text
merge main anchor: 9ae05b9928903cc483ce415d7378b546e419264c
migration: 013_lowest_unit_staffing_v1.sql
status: MERGED
post-merge Actions: SUCCESS
```

Реализованы Staffing registers, version lifecycle, document metadata, stable individual slots, Organization/catalog pins, rank/VUS requirements, history and compare. Personnel, assignments, vacancy/occupancy and real staffing data remain outside v1.

### Military Positions Directory v1 — PR #36

```text
base: main@9ae05b9928903cc483ce415d7378b546e419264c
final feature head: 3756b2ec53a00f68d5c1f5c098d1c274f6b8d769
runtime validated head: c647a933011873048866c75978d3f506634011fd
merge commit: a6cfceb421fac8d0985e409770bb26a62fac0b14
migration: 014_military_positions_directory_v1.sql
status: MERGED
post-merge Actions: SUCCESS
```

Core behavior:

- evolves existing position catalog; no second parallel catalog;
- initial canonical draft: 24 synthetic entries / 9 explicit combined flags;
- no automatic publication;
- lifecycle `draft → published → superseded` and `draft → cancelled`;
- stable identity, optimistic revision guards, logical archive/restore and append-only readable history;
- four permissions `view/manage/publish/history`; no automatic non-owner grants;
- existing Staffing pins/history preserved; archived canonical entries excluded from new selectors;
- no VUS/rank/unit/person/equipment/occupancy properties in canonical position entity.

Validation on exact runtime head:

```text
TOTAL_INCREMENT_ALLOWLIST=38/38
CORRECTIVE_ALLOWLISTS=12/12,9/9,8/8,9/9
PHP_LINT=171_PASS
MIGRATIONS=001-014
INITIALIZATION_RUNS=2
DB_RUNTIME_CHECKER=167_PASS
HTTP_SMOKE=200,200,302
ASU_BLUE_DESKTOP=PASS
ASU_LIGHT_BLUE_DESKTOP=PASS
ASU_EVGENIYA_ROSTOVA_DESKTOP=PASS
MUTUAL_EXCLUSION=PASS
UI_F04=CLOSED
UI_F05=CLOSED
OPEN_FINDINGS=0
REAL_STAFFING_DATA_MUTATION=NONE
MOBILE=NOT_RUN_OUT_OF_SCOPE
```

Exact corrective history remains in `docs/design/MILITARY-POSITIONS-DIRECTORY-V1-*` and Git history; it does not need to be duplicated here.

## 5. Branch inventory at 2026-08-08 audit

Before the current documentation reconciliation, remote inventory was exactly:

```text
main @ b3dda6cae88072c1e74c25de28f7023a8d73620d
research/military-accounting-order-700 @ 69bf9c9e1609a40c7f4c27ff41b0ddeebabe2ffe
```

Already deleted after explicit owner authorization:

```text
design/military-positions-directory-v1
feature/military-positions-directory-v1
docs/handoff-lowest-unit-staffing-design
docs/handoff-military-accounting-research
```

Do not infer that the research branch is safe to delete.

## 6. Unique research branch

`research/military-accounting-order-700` is diverged and currently has 8 commits unique relative to `main`. It contains six unique documents:

```text
docs/research/military-accounting-order-700/README.md
docs/research/military-accounting-order-700/OFFICIAL-SOURCE-REGISTER.md
docs/research/military-accounting-order-700/LEGAL-AND-PROCESS-ANALYSIS.md
docs/research/military-accounting-order-700/TARGET-ACCOUNTING-MODEL.md
docs/research/military-accounting-order-700/ASU-VCH-MODERNIZATION-ROADMAP.md
docs/research/military-accounting-order-700/SCOPE-DECISION-PERSONNEL-SERVICE-ONLY.md
```

Research target is `PersonnelServiceAccounting`; `CitizenMilitaryAccounting` is excluded. Research is not merged implementation and requires reconciliation/decision before any deletion or implementation use.

## 7. Current planning stage

```text
ACTIVE_FUNCTIONAL_INCREMENT=NONE
ACTIVE_MATERIAL_TECHNICAL_INCREMENT=NONE
NEXT_PRODUCT_INCREMENT=NOT_SELECTED
NEXT_IMPLEMENTATION_APPROVAL=NONE
```

Potential future directions are planning only: Personnel Core/card, Staffing assignments, common Documents/Orders, common Audit, reporting/import/export, production deployment, branch protection Stage B and separate mobile verification.

## 8. Permanent operating rules

Ordinary material work uses documentation-first lifecycle and exact fail-closed gates. Mobile PASS is prohibited without actual mobile acceptance. Production deploy is never inferred.

Routine updates of only:

```text
docs/PROJECT-WORKING-RULES.md
docs/CHAT-HANDOFF.md
```

may be performed without repeated owner permission prompts, including documentation branch/commits/PR/Final Review/merge when exact and documentation-only. **Branch deletion is excluded and always needs separate owner authorization.**

## 9. Documentation model

Living docs describe current durable state. Historical Architecture/Specification/Review/Approval/Implementation/Testing and dated audits remain snapshots. `HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK`.

The lifecycle of the latest documentation reconciliation is checked live in GitHub and is not recursively copied into a new PR solely to record its own merge. This handoff should be updated when substantive project state changes.

## 10. Safety boundaries

- no real personnel/staffing/unit data in migrations/docs/test fixtures;
- no secret/local config in Git;
- no production deployment claim without execution;
- no mobile PASS without testing;
- no branch deletion without separate exact approval;
- no force-push/history rewrite without explicit authorization;
- `research/military-accounting-order-700` must be preserved until its unique content is intentionally reconciled.

## 11. Action log — documentation reconciliation 2026-08-08

Owner explicitly authorized a full documentation current-state audit and all required documentation-only actions through normal merge, while excluding branch deletion. During this cycle:

- live `main`, branches, PRs, Issues and Actions were audited;
- PR #35/#36, migrations 001–014 and 35-permission durable baseline were reconciled into living docs;
- historical/target records were classified and preserved instead of being rewritten as current state;
- `PROJECT-WORKING-RULES.md` was retained as permanent governance and the standing two-document maintenance rule was corrected so branch deletion is always separate;
- this handoff was rebuilt as the new-chat operational snapshot;
- `research/military-accounting-order-700` was confirmed as unique unmerged research and preserved;
- documentation reconciliation branch: `docs/current-state-reconciliation-2026-08-08`;
- branch deletion for this reconciliation is **NOT AUTHORIZED**.

The mutable head/PR/Actions/review/merge state of this documentation cycle must be read live from GitHub. This action-log entry is historical context and does not become stale merely because the PR later advances or merges.
