# Локальный runbook АСУ-ВЧ

## 1. Назначение

Runbook описывает синхронизацию, deploy и проверку stable baseline АСУ-ВЧ в Open Server Panel, а также read-only validation документационных инкрементов.

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

## 6. Documentation-only PR validation

Для PR #21 deploy и runtime retest не требуются, поскольку exact diff ограничен Markdown.

Текущий documentation PR:

```text
PR: #21 OPEN
branch: docs/post-pr20-baseline-refresh
base: main @ 3082ec6ecbeddb92bd65e1398f05a9339abb199b
approved changed paths: 25
merge: NOT AUTHORIZED
branch deletion: NOT AUTHORIZED
```

Read-only локальная проверка после синхронизации ветки:

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
- merge-base = `3082ec6...`;
- branch behind main = 0;
- PR #19 и PR #20 operational closures присутствуют;
- current-state docs показывают PR #21 open;
- migrations 001–011, roles 4, permissions 25, themes 3;
- relative links и secret scan;
- runtime/config/database/migrations/theme/tool diff отсутствует;
- Mobile PASS не заявляется;
- merge и branch deletion не выполнялись.

Результат фиксируется как:

```text
DOCUMENTATION_IMPLEMENTATION_STATUS=PASS
DOCUMENTATION_VALIDATION_STATUS=PASS
CHANGED_PATH_ALLOWLIST_STATUS=PASS
MARKDOWN_ONLY_STATUS=PASS
PR_STATUS=OPEN_21_NOT_MERGED
MERGE_STATUS=NOT_AUTHORIZED_NOT_PERFORMED
BRANCH_DELETION_STATUS=NOT_AUTHORIZED_NOT_PERFORMED
```

## 7. Branch inventory and cleanup gate

До любого cleanup:

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git branch --merged origin/main
```

Для каждой ветки проверяются tip, reachability, unique commits и связанный merged PR. `SAFE TO DELETE` не является разрешением.

После merge PR #21 требуется новая inventory и отдельное owner approval exact batch. Remote deletion выполняется первой; затем `fetch --prune` и approved local deletion через `git branch -d`.

## 8. Security boundaries

Не публикуются credentials, session data, temporary passwords и содержимое `config/local.php`. Mobile testing для PR #19/#20: `OUT OF SCOPE / NOT RUN`.
