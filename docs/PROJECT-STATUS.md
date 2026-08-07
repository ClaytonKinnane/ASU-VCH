# Текущее состояние проекта АСУ-ВЧ

Дата актуализации durable functional и active implementation state: `2026-08-07`.

## Репозиторий и anchors

Live HEAD определяется динамически:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
latest merged functional baseline: PR #35 / migration 013
current main SHA: 9ae05b9928903cc483ce415d7378b546e419264c
static CI baseline: PR #25
documentation governance baseline: PR #28
local automation foundation: PR #29
local automation corrected baseline: PR #30
durable technical capability coverage: through PR #30
PR #24 merge commit: feac7230616d3a8df98acb48f43a0b60f89f2255
PR #24 runtime/manual acceptance head: b44aed14ee1a54be213cbc939322ba21b02e7a58
PR #25 merge commit: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
PR #29 merge commit: 375f941be3f50f9f1f264da244f0dc31496e2a6f
PR #30 corrected implementation head: fede2aa8c9c7b896f142075caa69b35219d4016d
PR #30 merge commit: 35abf7e29395bc662eeb2cecf5b1ea5a30fd7c77
migrations on main: 001–013
implementation target migration: 014
system roles: 4
system permissions on main: 31
target permissions after migration 014: 35
built-in themes: 3
required CSS assets per theme: 10
active functional increment: Military Positions Directory v1
active material technical increment: none
```

Documentation-only commits после runtime-tested head не объявляются runtime-tested. Exact merge/test SHA — historical anchors; current Git state определяется через GitHub/Git.

## Реализованные functional области

- bootstrap owner, authentication, protected sessions и CSRF;
- 4 system roles и 31 permissions on current main;
- full user lifecycle и required password change;
- 3 trusted themes и 10 required CSS-assets per theme;
- owner-only read-only directories: ranks, organizational element types and public VUS;
- merged Lowest Unit Staffing Structure v1 / migration 013;
- Organizational Structure v1;
- migrations 001–013.

## Functional PR #24 — Military Ranks Directory v2

```text
status: MERGED
migration: 012
v1 lifecycle: superseded / historical
v2 lifecycle: published / current
compositions/categories: 8
version-scoped semantics: 8
rank records: 20 unchanged codes/names/order
version sources: 2
composition sources: 8
added lifecycle/integrity/immutability triggers: 18
new system permissions: 0
```

Реализованы version switching, historical v1 view, current v2, search/filtering, source cards, derived/staffing badges и Reference-owned read-only compatibility service.

Staffing tables/slots and protected management UI were later implemented and merged through PR #35. Personnel assignments and real unit/personnel data remain excluded.

### PR #24 testing and post-merge

```text
static/source checks: PASS
migration 012: PASS
repeat installer: 12 / no new migrations
DB integration/regression: PASS
deploy/source parity: PASS
HTTP smoke: PASS
manual desktop acceptance: PASS
post-merge verification: PASS
mobile: OUT OF SCOPE / NOT RUN
```

Merge commit не подменяет исходный runtime/manual acceptance head.

## Static CI PR #25 — GitHub Actions Static Verification v1

```text
workflow: ASU-VCH Static Verification
job: asu-vch-static-verification
triggers: pull_request to main / push to main / workflow_dispatch
runner: ubuntu-24.04
PHP: 8.5.x
permissions: contents read
tracked PHP lint: enabled
CI-safe checkers: 9
final worktree guard: enabled
required status check: NOT ENABLED
branch protection/settings changed: NO
```

Post-merge evidence:

```text
push run: 30837637886 / SUCCESS
workflow_dispatch run: 30839122892 / SUCCESS
PHP in recorded runs: 8.5.9
tracked PHP files: 124 / 0 errors
Organization UI checker: 64 PASS / 0 FAIL
```

Static CI не заменяет MySQL, migration, deploy, HTTP/browser и visual testing.

## Documentation governance PR #28

PR #28 закрепил terminal documentation model:

```text
mutable PR/SHA/run/branch lifecycle: canonical in GitHub/Git
historical gate records: immutable snapshots
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
recursive post-merge Markdown closure solely for lifecycle copying: prohibited
```

Living documentation обновляется при substantive durable-state change или реальном content defect, а не только потому, что завершился последний documentation PR.

## Local GitHub/Codex Automation — PR #29 + PR #30

Repository tooling включает:

- one-command Windows PowerShell 5.1 installer;
- проверку или установку Git через WinGet;
- проверку или установку GitHub CLI и интерактивный GitHub login flow;
- Node.js LTS и официальный npm package `@openai/codex@latest`;
- authentication modes `Auto`, `ChatGPT`, `ApiKey`, `Skip`;
- secure API-key stdin handling;
- integrity manifest;
- atomic helper staging/install/rollback;
- native PowerShell 5.1 regression harness;
- Cleanup Doctor и fail-closed `Verify`/`Delete`.

PR #30 historical evidence:

```text
exact corrected implementation head: fede2aa8c9c7b896f142075caa69b35219d4016d
native Windows PowerShell: 5.1.28000.2525
native regression: 58 PASS / 0 FAIL
repository worktree restoration: PASS
PATH and LOCALAPPDATA restoration: PASS
exact-head PR workflow run: 31024419654 / SUCCESS
post-merge push run: 31025264683 / SUCCESS
merge commit: 35abf7e29395bc662eeb2cecf5b1ea5a30fd7c77
```

Эти проверки подтверждают repository/native regression contract. Они не объявляют PASS для реальной GitHub/Codex authentication, account verification, paid OpenAI API request или полной target-machine installation acceptance.

Local automation не меняет PHP runtime, MySQL, migrations, themes, deploy, application routes, GitHub Actions workflow или repository settings. Browser ChatGPT не получает прямого доступа к локальному компьютеру.

## Themes

Три built-in themes:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

Каждая тема содержит 10 required CSS-assets, включая `css/military-ranks-v2.css`. Для `asu-evgeniya-rostova` дополнительно обязательны четыре local SVG-assets.

## Repository governance

PR, merge и branch deletion требуют отдельных owner approvals. Current branch inventory остаётся dynamic:

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git status --short
```

## Следующий инкремент

Active approved functional increment:

```text
name: Military Positions Directory v1
base: main@9ae05b9928903cc483ce415d7378b546e419264c
branch: feature/military-positions-directory-v1
migration: 014_military_positions_directory_v1.sql
design: Architecture/Specification/Formal Review 0.2
implementation: present in worktree
allowed paths: 38 / maximum 38
local MySQL/deploy/HTTP/desktop validation: NOT RUN
Pull Request: NOT AUTHORIZED / NOT CREATED
merge: NOT AUTHORIZED
branch deletion: NOT AUTHORIZED
```

The increment evolves the existing migration-010 catalog into a managed canonical directory, seeds one 24-entry synthetic draft, preserves legacy/Staffing history and adds four permissions without non-owner grants. No production deployment or mobile acceptance is included.

## Постоянные gates

```text
Pull Request: separate explicit owner permission required
merge: separate explicit owner permission required
branch deletion: separate post-merge owner permission required
required branch-protection check: not enabled
mobile PASS: not claimed
```
