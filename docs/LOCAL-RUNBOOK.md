# Локальный runbook АСУ-ВЧ

## 1. Назначение

Runbook описывает synchronization, deploy, functional verification, static CI inspection, documentation validation и branch cleanup gates.

```text
repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
web root: C:\OSPanel\home\asu-vch.local\public
URL: https://asu-vch.local
```

## 2. Current anchors

```text
latest functional PR: #24
latest technical PR: #25
PR #24 merge: feac7230616d3a8df98acb48f43a0b60f89f2255
PR #24 runtime/manual acceptance: b44aed14ee1a54be213cbc939322ba21b02e7a58
PR #25 merge: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
migrations: 001–012
system roles: 4
system permissions: 25
built-in themes: 3
required CSS assets: 10
```

Current stable HEAD определяется через `origin/main`. Documentation-only commits не считаются runtime-tested.

## 3. Stable synchronization

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch main
git pull --ff-only origin main
git rev-parse HEAD
git rev-parse origin/main
git rev-list --left-right --count HEAD...origin/main
git status --short
```

Ожидается clean worktree, `HEAD=origin/main`, divergence `0 0`.

## 4. Runtime initialization

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1'
```

Repeat installer:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

Ожидается:

```text
Применено миграций: 12
Новых миграций нет.
```

## 5. PR #24 functional verification baseline

Post-merge verification подтвердил:

```text
migration 012: applied
repeat installer: 12 / no new migrations
Military Ranks source/loader/service checks: PASS
Military Ranks DB regression: PASS
deploy/source parity: PASS
HTTP smoke: PASS
manual desktop: PASS
working tree: clean
mobile: OUT OF SCOPE / NOT RUN
```

Evidence:

- `testing/MILITARY-RANKS-DIRECTORY-V2-TEST-REPORT.md`;
- `testing/MILITARY-RANKS-DIRECTORY-V2-MANUAL-DESKTOP-ACCEPTANCE-2026-08-03.md`;
- `review/MILITARY-RANKS-DIRECTORY-V2-PR-FINAL-REVIEW.md`.

## 6. GitHub Actions inspection

Workflow:

```text
ASU-VCH Static Verification
job: asu-vch-static-verification
```

GitHub UI:

1. открыть **Actions**;
2. выбрать `ASU-VCH Static Verification`;
3. проверить event, branch, exact SHA, conclusion и job steps;
4. для manual diagnostics использовать **Run workflow** на `main`;
5. не считать `Re-run all jobs` новым `workflow_dispatch` event.

Post-merge evidence PR #25:

```text
push run: 30837637886 / SUCCESS
workflow_dispatch run: 30839122892 / SUCCESS
required status check: NOT ENABLED
branch protection/settings changed: NO
```

Static CI не заменяет local MySQL, deploy, HTTP/browser или manual visual testing.

## 7. Documentation-only validation

Для approved documentation branch:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch <approved-docs-branch>
git pull --ff-only origin <approved-docs-branch>
git rev-parse HEAD
git merge-base origin/main HEAD
git rev-list --left-right --count origin/main...HEAD
git diff --name-only origin/main...HEAD
git diff --check origin/main...HEAD
git status --short
```

Проверяются:

- exact approved path allowlist;
- Markdown-only diff;
- branch behind `origin/main` = 0;
- baseline facts and historical anchors;
- relative links;
- stale current assertions;
- migration 001–012 consistency;
- required CSS asset count 10;
- PR #24 functional / PR #25 technical classification;
- CI Stage A/Stage B boundary;
- production/instance secret boundary;
- no Mobile PASS claim;
- absence of runtime/config/database/migration/workflow/theme/deploy/tool diff.

## 8. Branch inventory and cleanup

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git branch --merged origin/main
```

Для каждой branch проверяются exact tip, reachability, unique commits, PR/post-merge state и exact owner-approved deletion batch.

`SAFE TO DELETE` не является permission. Remote deletion выполняется первой, затем `git fetch --prune` и approved local deletion через `git branch -d`. Force deletion запрещён для обычного cleanup.

PR #24 и PR #25 feature branches были удалены после отдельных approvals. Это dated completed outcome, а не permanent future branch inventory.

## 9. Historical governance snapshots

PR #21 cleanup и PR #23 documentation audit сохраняются как immutable dated evidence. Их `main only` snapshots не запрещают позднейшие approved branches.

## 10. Security boundaries

Не публикуются:

- production credentials;
- instance/environment credentials;
- real temporary user passwords;
- session identifiers/data;
- `config/local.php`;
- tokens, private keys и другие secrets.

Existing public local-only fixture:

```text
username: Admin
password: 12315
environment: local only
must_change_password: true
```

Он не является production/instance secret, запрещён для production и иных accounts/environments, требует смены при первом входе и не отменяет запрет публикации real temporary passwords.

## 11. Permanent gates

```text
Pull Request: separate owner permission
merge: separate owner permission
branch deletion: separate post-merge owner permission
required status check: not enabled
mobile PASS: not claimed
```