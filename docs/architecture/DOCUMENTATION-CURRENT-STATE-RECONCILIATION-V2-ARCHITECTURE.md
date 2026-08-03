# Documentation Current-State Reconciliation v2 — Architecture

## 1. Статус

```text
stage: Architecture
status: PREPARED FOR OWNER REVIEW
classification: documentation-only
repository: ClaytonKinnane/ASU-VCH
approved baseline: main @ c567429b3aa4d629a4e7c11fec7e3dbae907d92e
branch: docs/documentation-current-state-reconciliation-v2
date: 2026-08-03
implementation authorized: NO
```

## 2. Назначение

Инкремент синхронизирует всю semantically living документацию АСУ-ВЧ с текущим merged состоянием после:

- functional PR #24 — Military Ranks Directory v2;
- migration 012;
- technical PR #25 — GitHub Actions Static Verification v1;
- post-merge verification PR #24 и PR #25;
- отдельного удаления feature-веток PR #24 и PR #25.

Изменение строго документационное. Runtime, schema, migrations, workflow, themes, deploy, branch protection, GitHub settings и Git refs не изменяются.

## 3. Подтверждённый baseline

Перед созданием ветки повторно проверено:

```text
EXPECTED_MAIN=c567429b3aa4d629a4e7c11fec7e3dbae907d92e
ACTUAL_MAIN=c567429b3aa4d629a4e7c11fec7e3dbae907d92e
MAIN_STATUS=IDENTICAL
COMMITS_AHEAD=0
COMMITS_BEHIND=0
TARGET_BRANCH_BEFORE_CREATE=ABSENT
PR24_FEATURE_BRANCH=ABSENT
PR25_FEATURE_BRANCH=ABSENT
PREFLIGHT=PASS
```

Current `main` SHA не переносится в living documents как самореферентное постоянное поле. Exact SHA используется только как historical baseline данного process record.

## 4. Исходные findings

Read-only audit относительно утверждённого baseline завершён со статусом:

```text
DOCUMENTATION_CURRENT_STATE_CONSISTENCY=CHANGES_REQUIRED
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=6
MINOR_FINDINGS=1
```

Подтверждены следующие классы расхождений:

1. living baseline завершался PR #20 и migrations 001–011;
2. physical schema и migration index не включали migration 012;
3. theme contract указывал 9 CSS-assets вместо фактических 10;
4. GitHub CI продолжал обозначаться как будущий или отсутствующий;
5. operational records PR #25 сохраняли незакрытые pre-merge current-status fields;
6. changelog не включал PR #24 и PR #25;
7. operational records PR #24 не содержали полного post-merge и branch-lifecycle closure.

## 5. Архитектурные принципы reconciliation

### 5.1 Semantic classification overrides directory classification

Раздел обновляется как living/current-state, если сообщает:

- текущий functional или technical baseline;
- текущую нумерацию migrations;
- реализованные domains, catalogs, routes или capabilities;
- набор roles, permissions, themes или required assets;
- наличие или отсутствие CI;
- устойчивый завершённый governance outcome.

Каталог файла не освобождает current-state assertion от обновления.

### 5.2 Historical evidence remains historical

Architecture, Specification, Approval, Test Evidence и Final PR Review сохраняют факты своего gate. Исторические формулировки `NOT AUTHORIZED`, `NOT PERFORMED`, `PENDING` и pre-merge status не переписываются как будто они никогда не существовали.

Для завершённых инкрементов добавляются отдельные closure sections, содержащие:

- final PR head;
- merge commit;
- post-merge verification;
- branch deletion outcome;
- явное разделение runtime-tested head и documentation/merge heads.

### 5.3 Functional и technical baselines разделяются

Living documentation должна различать:

```text
latest functional PR: #24
latest technical PR: #25
migrations: 001–012
current main: determined dynamically
```

PR #25 не объявляется новым runtime/database baseline. Он добавляет static CI capability и post-merge CI evidence.

### 5.4 Source-of-truth hierarchy

| Область | Источник истины |
|---|---|
| Live HEAD, branches, PRs, Issues | GitHub / Git |
| Current project capability map | `docs/PROJECT-STATUS.md` и профильные living docs |
| Physical schema | `docs/DATABASE-CURRENT.md`, executable migrations, installer, compatibility loaders |
| Theme registry и required assets | `config/themes.php` |
| CI workflow contract | `.github/workflows/static-verification.yml` |
| Runtime test result PR #24 | dated Test Report и post-merge evidence |
| CI test result PR #25 | dated Test Report, Final PR Review и post-merge workflow evidence |
| Historical gate state | increment-specific process records |

### 5.5 No capability overclaim

Reconciliation не должна утверждать:

- что static CI заменяет MySQL, migration, deploy, HTTP, browser или visual testing;
- что required status check или branch protection уже включены;
- что mobile acceptance выполнялась;
- что merge commit является runtime-tested head, если runtime testing выполнялось на feature head;
- что target architecture полностью реализована.

## 6. Current-state model после remediation

Living documentation должна согласованно отражать:

```text
latest functional PR: #24
latest technical PR: #25
functional merge commit: feac7230616d3a8df98acb48f43a0b60f89f2255
technical merge commit: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
migrations: 001–012
system roles: 4
system permissions: 25
built-in themes: 3
required CSS assets per theme: 10
GitHub Actions static workflow: implemented
required branch-protection check: not enabled
branch protection changed by PR #25: no
mobile testing PR #24: OUT OF SCOPE / NOT RUN
mobile testing PR #25: OUT OF SCOPE / NOT RUN
active implementation increment: none
```

Current branch and PR inventories остаются dynamic facts и не фиксируются как бессрочное состояние.

## 7. Physical schema reconciliation

`docs/DATABASE-CURRENT.md` и `docs/migrations/README.md` должны включить migration 012 и фактический Military Ranks Directory v2 outcome:

- v1: superseded/historical;
- v2: published/current;
- 8 compositions/categories;
- 8 version-scoped semantic records;
- 20 rank records с неизменными codes/names/order;
- 2 version-source links;
- 8 composition-source links;
- lifecycle fields и generated guards;
- 2 новые tables для semantics и composition sources;
- 18 lifecycle/integrity/immutability triggers;
- compatibility loader и fail-closed marker;
- repeat installer: 12 migrations, no new migrations.

Schema facts берутся из merged implementation и post-merge verification, а не выводятся из target documents.

## 8. Theme contract reconciliation

Living theme documentation должна соответствовать `config/themes.php`:

```text
built-in themes: 3
required CSS assets per theme: 10
new profile asset: css/military-ranks-v2.css
```

Для `asu-evgeniya-rostova` дополнительно сохраняются четыре required SVG-assets.

## 9. CI reconciliation

Living documentation должна фиксировать реализованный Stage A:

- workflow `ASU-VCH Static Verification`;
- job `asu-vch-static-verification`;
- triggers: pull request to `main`, push to `main`, workflow dispatch;
- Ubuntu 24.04, PHP 8.5;
- read-only permissions;
- event-aware `git diff --check`;
- lint tracked PHP;
- 9 explicit CI-safe checker'ов;
- final repository integrity check;
- PR exact-head run: PASS;
- post-merge push run: PASS;
- post-merge manual run: PASS.

Одновременно явно фиксируется:

```text
branch protection mutation: NOT PERFORMED
required status check: NOT ENABLED
Stage B: separately gated
```

## 10. Operational closure architecture

Для PR #24 и PR #25 closure добавляется только в operational records:

- Implementation;
- Test Report;
- Final PR Review.

Closure не изменяет первоначальные verdicts. Каждый документ получает отдельный раздел `Post-merge and branch-lifecycle closure`, в котором отражаются факты после соответствующего gate.

PR #24 closure должен включать:

- final feature head `2e996849ec51be4d83676aa779bf7e797e35932e`;
- merge commit `feac7230616d3a8df98acb48f43a0b60f89f2255`;
- post-merge verification PASS;
- deploy, parity, repeat installer, DB regression и HTTP smoke PASS;
- feature branch deletion completed under separate approval;
- runtime-tested head остаётся `b44aed14ee1a54be213cbc939322ba21b02e7a58`;
- remediation recheck head остаётся historical anchor.

PR #25 closure должен включать:

- exact reviewed/approved PR head `0c6f7338f912e8797868d02d54fc015df7533ad6`;
- merge commit `c567429b3aa4d629a4e7c11fec7e3dbae907d92e`;
- automatic push run `30837637886` PASS;
- workflow dispatch run `30839122892` PASS;
- branch protection/settings unchanged;
- feature branch deletion completed under separate approval;
- DB/deploy/browser/mobile checks remained out of scope.

## 11. Documentation-only validation architecture

Validation выполняется относительно exact implementation head и включает:

1. exact baseline, merge-base и divergence;
2. exact changed-path allowlist;
3. Markdown-only diff;
4. `git diff --check`;
5. relative link resolution для всех добавленных/изменённых links;
6. stale current-state assertion scan;
7. migration range and count consistency scan;
8. theme asset count consistency scan;
9. functional/technical PR classification scan;
10. CI Stage A / Stage B boundary scan;
11. historical SHA preservation;
12. no false runtime-tested merge-head claim;
13. mobile claim scan;
14. production/instance secret boundary review;
15. absence of runtime/config/database/migration/workflow/theme/deploy/tool diff.

GitHub Actions может дополнительно проверить whitespace и tracked PHP, но documentation validation не объявляет static CI заменой semantic documentation review.

## 12. Exact proposed changed-path allowlist

### Living documentation — 15 paths

1. `README.md`
2. `docs/README.md`
3. `docs/PROJECT-STATUS.md`
4. `docs/PROJECT.md`
5. `docs/ROADMAP.md`
6. `docs/CHANGELOG.md`
7. `docs/DATABASE-CURRENT.md`
8. `docs/ENVIRONMENT.md`
9. `docs/LOCAL-RUNBOOK.md`
10. `docs/THEMES.md`
11. `docs/ARCHITECTURAL-PATTERNS.md`
12. `docs/domains/README.md`
13. `docs/migrations/README.md`
14. `docs/DEVELOPMENT.md`
15. `docs/ACCESS.md`

### Operational closure — 6 paths

16. `docs/implementation/MILITARY-RANKS-DIRECTORY-V2-IMPLEMENTATION.md`
17. `docs/testing/MILITARY-RANKS-DIRECTORY-V2-TEST-REPORT.md`
18. `docs/review/MILITARY-RANKS-DIRECTORY-V2-PR-FINAL-REVIEW.md`
19. `docs/implementation/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-IMPLEMENTATION.md`
20. `docs/testing/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-TEST-REPORT.md`
21. `docs/review/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-PR-FINAL-REVIEW.md`

### Audit and process records — 8 paths

22. `docs/DOCUMENTATION-CURRENT-STATE-AUDIT-2026-08-03.md`
23. `docs/architecture/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-ARCHITECTURE.md`
24. `docs/specification/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-SPECIFICATION.md`
25. `docs/review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-FORMAL-REVIEW.md`
26. `docs/decisions/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-APPROVAL.md`
27. `docs/implementation/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-IMPLEMENTATION.md`
28. `docs/testing/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-VALIDATION.md`
29. `docs/review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-PR-FINAL-REVIEW.md`

```text
EXPECTED_PATH_COUNT=29
EXPECTED_MARKDOWN_PATH_COUNT=29
EXPECTED_NON_MARKDOWN_PATH_COUNT=0
```

Расширение или сокращение allowlist требует отдельного owner approval до Implementation.

## 13. Explicit exclusions

Запрещено изменять:

- `.github/workflows/static-verification.yml`;
- application code;
- database code, migrations и canonical SQL packages;
- `config/themes.php`;
- theme assets;
- public routes;
- deploy scripts;
- tools и checker'ы;
- branch protection;
- required checks;
- repository settings;
- Actions settings;
- secrets, environments или permissions;
- branches, кроме уже разрешённого создания documentation branch;
- production или local database.

## 14. Gate model

```text
Architecture prepared
→ Specification prepared
→ Formal Review prepared
→ STOP
→ separate owner approval of Architecture, Specification, Formal Review and exact 29-path allowlist
→ Implementation
→ Documentation Validation
→ Commit / Push completion
→ Pull Request
→ Final PR Review on exact head
→ separate merge approval
→ Merge
→ post-merge verification
→ separate branch deletion approval
```

Создание этого Architecture document не разрешает Implementation.
