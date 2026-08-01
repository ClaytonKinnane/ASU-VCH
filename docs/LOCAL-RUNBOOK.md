# Локальный runbook АСУ-ВЧ

## 1. Назначение

Runbook описывает синхронизацию, deploy и проверку stable baseline АСУ-ВЧ, а также read-only validation documentation-only changes.

```text
repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
web root: C:\OSPanel\home\asu-vch.local\public
URL: https://asu-vch.local
```

## 2. Functional anchors

```text
latest functional PR: #20
PR #19 merge: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #20 merge / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime: 9db06c4a26066ca25dc36c627c1236089a3c1238
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

## 6. Documentation-only validation

Post-PR20 Baseline Refresh отслеживается PR #21 и веткой `docs/post-pr20-baseline-refresh`. Live PR state определяется в GitHub; stable runbook не хранит `OPEN/MERGED` как постоянно актуальное поле.

Approved validation contract:

```text
base / merge-base: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
final changed-path allowlist: 25 Markdown paths
runtime/config/database/migrations/theme/tool changes: none
```

Read-only validation:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch docs/post-pr20-baseline-refresh
git pull --ff-only origin docs/post-pr20-baseline-refresh
git rev-parse HEAD
git merge-base origin/main HEAD
git rev-list --left-right --count origin/main...HEAD
git diff --name-only origin/main...HEAD
git diff --check origin/main...HEAD
git status --short
```

Проверяются:

- exact changed-path count = 25;
- все changed paths имеют расширение `.md`;
- branch behind `origin/main` = 0;
- PR #19 и PR #20 operational closures;
- migrations 001–011, roles 4, permissions 25, themes 3;
- relative links и secret scan;
- no non-Markdown diff;
- no Mobile PASS claim;
- live PR state соответствует GitHub;
- merge и branch deletion выполняются только после отдельных approvals.

Датированный Validation evidence фиксирует exact head и PR state на момент проверки.

## 7. Branch inventory and cleanup gate

До cleanup:

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git branch --merged origin/main
```

Для каждой ветки проверяются tip, reachability, unique commits и связанный merged PR. `SAFE TO DELETE` не является разрешением.

После завершения PR #21 требуется post-merge verification, новая inventory и отдельное owner approval exact batch. Remote deletion выполняется первой; затем `fetch --prune` и approved local deletion через `git branch -d`.

## 8. Security boundaries

Не публикуются credentials, session data, temporary passwords и содержимое `config/local.php`. Mobile testing для PR #19/#20: `OUT OF SCOPE / NOT RUN`.
