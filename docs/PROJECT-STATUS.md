# Текущее состояние проекта АСУ-ВЧ

Дата актуализации functional и technical baseline: `2026-08-03`.

## Репозиторий и anchors

Live HEAD определяется динамически:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
latest functional PR: #24
latest technical PR: #25
PR #24 merge commit: feac7230616d3a8df98acb48f43a0b60f89f2255
PR #24 runtime/manual acceptance head: b44aed14ee1a54be213cbc939322ba21b02e7a58
PR #24 Final PR Review remediation head: fe893e8315f7add80ed4d0501b41d8bc39b4b0e8
PR #25 merge commit: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
migrations: 001–012
system roles: 4
system permissions: 25
built-in themes: 3
required CSS assets per theme: 10
active functional increment: none
active technical increment: none
```

Documentation-only commits после runtime-tested head не объявляются runtime-tested. Exact merge/test SHA — historical anchors; current Git state определяется через GitHub/Git.

## Реализованные области

- bootstrap owner, authentication, protected sessions и CSRF;
- 4 system roles и 25 permissions;
- full user lifecycle и required password change;
- 3 trusted themes и 10 required CSS-assets per theme;
- owner-only read-only directories: ranks, organizational element types, military positions, public VUS;
- Organizational Structure v1;
- migrations 001–012;
- GitHub Actions Static Verification v1.

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

Не реализованы Staffing tables, штатные slots, Organization bindings, personnel assignments, реальные unit/personnel data и mutation UI.

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

## Technical PR #25 — GitHub Actions Static Verification v1

Реализован workflow:

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
```

Post-merge evidence:

```text
exact Final PR Review head: 0c6f7338f912e8797868d02d54fc015df7533ad6
push run: 30837637886 / SUCCESS
workflow_dispatch run: 30839122892 / SUCCESS
PHP in recorded runs: 8.5.9
tracked PHP files: 124 / 0 errors
Organization UI checker: 64 PASS / 0 FAIL
required status check: NOT ENABLED
branch protection/settings changed: NO
```

Static CI не заменяет MySQL, migration, deploy, HTTP/browser и visual testing. DB/deploy/browser/mobile checks не выполнялись в рамках PR #25.

## Themes

Три built-in themes:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

Каждая тема содержит 10 required CSS-assets, включая `css/military-ranks-v2.css`. Для `asu-evgeniya-rostova` дополнительно обязательны четыре local SVG-assets.

## Repository governance

PR #24 и PR #25 merged только после отдельных owner approvals и прошли post-merge verification. Их feature branches удалены после отдельных branch-deletion approvals.

Это завершённые dated outcomes. Current branch inventory остаётся dynamic:

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git status --short
```

## Следующий инкремент

Новый functional или technical scope не выбран и не утверждён. Любой material increment начинается с Research → Analysis → Architecture → Specification → Review → Approval.

## Постоянные gates

```text
Pull Request: separate explicit owner permission required
merge: separate explicit owner permission required
branch deletion: separate post-merge owner permission required
required branch-protection check: not enabled
mobile PASS: not claimed
```