# АСУ-ВЧ — постоянный handoff для нового чата

## 1. Обязательный порядок начала

Перед material-действиями:

1. прочитать `docs/PROJECT-WORKING-RULES.md` и этот документ;
2. самостоятельно проверить live GitHub state: `main`, branches, PR, Issues, Actions и reviews;
3. сопоставить live state с exact anchors ниже;
4. продолжить с текущего незавершенного gate;
5. не повторять уже полученные разрешения;
6. fail closed при изменении base/head/scope/path allowlist.

GitHub/Git — canonical source mutable lifecycle. SHA всегда перепроверяются.

## 2. Репозиторий и среда

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
main at snapshot: d60db94e405979c8f29bdc3dcaae7950362fb13a
local repository: C:\Project\ASU-VCH
deploy: C:\OSPanel\home\asu-vch.local
domain: https://asu-vch.local
Open Server Panel: 6.5.1
Apache
PHP: 8.5.4
MySQL: 8.4
PowerShell: 5.1
```

GitHub operations выполняются ассистентом. Локальная машина используется для sync/deploy/MySQL/migrations/runtime/HTTP/browser/visual desktop acceptance. Длинные PowerShell-сценарии хранятся файлами в репозитории и совместимы с Windows PowerShell 5.1.

## 3. Постоянный lifecycle

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing/Validation → Commit → Push → Pull Request
→ exact-head Actions → Final PR Review → separate Merge approval
→ Merge → Post-merge verification → separate Branch deletion approval
→ Branch deletion
```

Static CI не заменяет MySQL, migrations, deploy, HTTP/browser и visual acceptance. Mobile не считается проверенным без отдельного инкремента.

## 4. Standing authorization governance

Без повторных permission prompts можно поддерживать только:

```text
docs/PROJECT-WORKING-RULES.md
docs/CHAT-HANDOFF.md
```

Разрешены отдельная docs branch, commit, PR, exact-head Actions, Final PR Review, merge после PASS и post-merge verification. Standing authorization не распространяется на третий путь, runtime, DB, migrations, workflows, themes, deploy, tools или settings.

Branch deletion остается отдельно контролируемым. Новый recursive PR только ради записи merge SHA/cleanup самого handoff запрещен; merged governance PR + successful post-merge check является terminal evidence.

## 5. Stable baseline

```text
runtime baseline: PR #24 / migrations through 012
static CI baseline: PR #25
GitHub/Git governance: PR #28
local automation: PR #29 and #30
permanent rules/handoff: PR #32
latest handoff merge before this update: PR #33 / d60db94e405979c8f29bdc3dcaae7950362fb13a
```

Реализованы authentication, CSRF, user lifecycle, 4 system roles, 25 permissions, 3 themes, public ranks/positions/VUS, organizational element types, Organizational Structure v1 и migrations 001–012.

## 6. Неизменное решение по продукту

```text
TARGET_CONTOUR=PersonnelServiceAccounting
CitizenMilitaryAccounting=EXCLUDED
```

Не разрабатываются учет призывников/запаса, общий и специальный учет граждан, бронирование, учет работников организаций, муниципальный первичный учет, повестки, Реестр воинского учета и Реестр повесток.

Приказ Минобороны России № 700 используется только как дополнительный источник применимых требований к документальному основанию, штатной должности, ВУС, приказам и действиям штаба воинской части. Полное кадрово-служебное регулирование требует иных официальных актов.

## 7. Research branch

```text
SCOPE=Military Accounting Order 700 Research reframed for PersonnelServiceAccounting only
BRANCH=research/military-accounting-order-700
ORIGINAL_BASE_SHA=7ae5bcf77826870d6beee7293f101f679a521c56
CURRENT_HEAD=69bf9c9e1609a40c7f4c27ff41b0ddeebabe2ffe
AHEAD_FROM_ORIGINAL_BASE=8
BEHIND_FROM_ORIGINAL_BASE=0
CHANGED_PATHS=6
RUNTIME_CONFIG_DB_DIFF=0
REBASE=NO
PULL_REQUEST=NOT CREATED
BRANCH_DELETION=NOT AUTHORIZED
```

Allowlist:

```text
docs/research/military-accounting-order-700/README.md
docs/research/military-accounting-order-700/OFFICIAL-SOURCE-REGISTER.md
docs/research/military-accounting-order-700/LEGAL-AND-PROCESS-ANALYSIS.md
docs/research/military-accounting-order-700/TARGET-ACCOUNTING-MODEL.md
docs/research/military-accounting-order-700/ASU-VCH-MODERNIZATION-ROADMAP.md
docs/research/military-accounting-order-700/SCOPE-DECISION-PERSONNEL-SERVICE-ONLY.md
```

Research conclusions and roadmap now consistently exclude `CitizenMilitaryAccounting`.

## 8. Active functional increment

```text
NAME=Lowest Unit Staffing Structure v1
CLASSIFICATION=functional
CONTOUR=PersonnelServiceAccounting
BASE_BRANCH=main
BASE_SHA=d60db94e405979c8f29bdc3dcaae7950362fb13a
FEATURE_BRANCH=feature/lowest-unit-staffing-v1
CURRENT_FEATURE_HEAD=3af453f1e093e3a5b1c1d69365211a2abe7c8215
MERGE_BASE=d60db94e405979c8f29bdc3dcaae7950362fb13a
AHEAD=7
BEHIND=0
CURRENT_CHANGED_PATHS=4
CURRENT_DIFF=documentation-only
```

Current paths:

```text
docs/domains/STAFFING.md
docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md
docs/design/LOWEST-UNIT-STAFFING-V1-SPECIFICATION.md
docs/design/LOWEST-UNIT-STAFFING-V1-REVIEW.md
```

## 9. Completed stages for active increment

```text
Research=COMPLETE
Analysis=COMPLETE
Architecture=COMPLETE / version 0.2
Specification=COMPLETE / version 0.2
Formal Review=PASS
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS=0
Approval=PENDING
Implementation=NOT STARTED
Testing=NOT STARTED
Pull Request=NOT AUTHORIZED YET
Merge=NOT AUTHORIZED
Branch deletion=NOT AUTHORIZED
```

Resolved review findings:

1. root organizational element is allowed; rollout from lower level is not a DB prohibition;
2. draft copied from active keeps the same catalog versions; catalog migration is deferred;
3. exact proposed implementation maximum is 44 paths, not 42.

## 10. Approved design summary

- separate `Staffing` domain; no parallel organizational tree;
- link to stable `organizational_structure_elements.id` and pinned Organization version;
- one row per individual staffing slot;
- stable slot identity across versions;
- version lifecycle `draft → approved → active → superseded`, cancellation before activation;
- pinned position/rank/public-VUS catalogs;
- no person, assignment, occupancy or actual vacancy in v1;
- documents are metadata-only;
- six new module permissions, no automatic non-owner grants;
- module-level RBAC only; subtree ACL deferred;
- migration planned as `013_lowest_unit_staffing_v1.sql`;
- protected management UI plus read-only representations;
- synthetic-only tests and documentation.

Operational condition:

```text
NO_REAL_STAFFING_DATA_BEFORE_SECURITY_FOUNDATION
```

Development/testing may use synthetic data. Real штатные сведения are not operationally accepted until a separate Data Classification and Security Foundation defines information category, deployment boundary, need-to-know, threat model and protection requirements.

## 11. Proposed implementation scope

The Specification enumerates exactly 44 maximum paths:

- 4 current process documents;
- migration 013;
- `app/Staffing/*` exact files;
- `public/admin/staffing/*` exact files;
- `app/bootstrap.php` and `public/admin/content.php`;
- two validation tools;
- six living project documentation files.

The authoritative exact list is section 9 of `docs/design/LOWEST-UNIT-STAFFING-V1-SPECIFICATION.md`. Any path outside it requires re-approval.

## 12. Next gate

Owner must approve exact feature head `3af453f1e093e3a5b1c1d69365211a2abe7c8215`, Architecture/Specification/Review, functional scope and 44-path implementation allowlist.

After approval, permitted sequence:

```text
Implementation → exact validation → local MySQL/deploy/HTTP/browser instructions
→ commit/push validation → separate PR gate
```

No PR, merge or deletion should be inferred from implementation approval unless explicitly included in the owner’s next exact authorization.

## 13. Branch inventory warning

At this snapshot, known non-main branches include:

```text
research/military-accounting-order-700
feature/lowest-unit-staffing-v1
docs/handoff-military-accounting-research
docs/handoff-lowest-unit-staffing-design
```

Do not delete any branch without explicit authorization.

## 14. Current action on chat restart

1. Verify live `main` and feature head.
2. Read the four active design documents.
3. Confirm Review remains PASS and compare remains 4-path documentation-only.
4. Wait for or process the exact owner Approval gate.
5. Do not begin runtime implementation from a moved head or expanded path list.