# Локальный runbook АСУ-ВЧ

## 1. Назначение

Runbook описывает синхронизацию, deploy и проверку stable baseline АСУ-ВЧ, documentation-only validation, branch inventory и утверждённый cleanup.

```text
repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
web root: C:\OSPanel\home\asu-vch.local\public
URL: https://asu-vch.local
```

## 2. Functional anchors

```text
latest functional PR: #20
latest completed documentation PR: #21
PR #19 merge: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #20 merge / functional refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime: 9db06c4a26066ca25dc36c627c1236089a3c1238
PR #21 merge: f5b53f2ee4453f293b58cbe486e0943ab602335b
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
```

Current stable HEAD определяется через `origin/main`; documentation-only heads не считаются runtime-tested.

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

Требования: clean working tree, fast-forward only, local HEAD = `origin/main`, divergence `0 0`.

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
Применено миграций: 11
Новых миграций нет.
```

## 5. Профильные runtime runners

Military Positions:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryPositionsDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Public VUS:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryOccupationalSpecialtiesDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

## 6. Generic documentation-only validation

Для утверждённой documentation branch:

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

- exact owner-approved changed-path allowlist;
- Markdown-only diff;
- branch behind `origin/main` = 0;
- baseline facts и устойчивые anchors;
- relative Markdown links;
- stale current-state assertions;
- secrets и содержимое `config/local.php` отсутствуют;
- historical snapshots не переписаны задним числом;
- no Mobile PASS claim;
- runtime/config/database/migrations/themes/tools/Git refs unchanged.

Live PR state определяется в GitHub и фиксируется только в датированных evidence records.

## 7. Исторический PR #21 и cleanup

PR #21 завершён merge commit `f5b53f2ee4453f293b58cbe486e0943ab602335b`; post-merge Git verification — PASS.

После отдельного owner approval выполнен remote-first cleanup:

```text
remote deleted: 3 / 3
local deleted: 13 / 13
terminal remote branches: main only
terminal local branches: main only
working tree: clean
force deletion: not used
terminal verification: PASS
```

Удалённая ветка `docs/post-pr20-baseline-refresh` является historical branch name и не используется как operational checkout target.

Evidence: [Post-PR21 Merge and Cleanup Closure 2026-08-01](POST-PR21-MERGE-CLEANUP-CLOSURE-2026-08-01.md).

## 8. Branch inventory and cleanup gate

Fresh inventory:

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git branch --merged origin/main
```

Для каждой ветки проверяются:

1. exact tip;
2. reachability из актуального `origin/main`;
3. unique commits;
4. связанный PR и post-merge state;
5. exact owner-approved deletion batch.

`SAFE TO DELETE` не является разрешением.

Remote deletion выполняется первой. После подтверждения отсутствия remote refs выполняются `git fetch --prune` и только утверждённое локальное удаление через `git branch -d`. `git branch -D` и force-update refs для обычного cleanup запрещены.

## 9. Terminal verification после cleanup

```powershell
git fetch --prune origin
git rev-parse HEAD
git rev-parse origin/main
git status --porcelain
git for-each-ref --format='%(refname:short)' refs/heads
git ls-remote --heads origin
```

Проверяются:

- local `main` = `origin/main` = ожидаемый merge commit;
- working tree clean;
- local branch set соответствует утверждённому результату;
- remote branch set соответствует утверждённому результату;
- `main` не перемещена неожиданно;
- force deletion не использовался.

Датированный `main only` snapshot не является запретом на позднейшее создание новой утверждённой ветки.

## 10. Security boundaries

Не публикуются credentials, session data, temporary passwords и содержимое `config/local.php`. Mobile testing для PR #19/#20: `OUT OF SCOPE / NOT RUN`.
