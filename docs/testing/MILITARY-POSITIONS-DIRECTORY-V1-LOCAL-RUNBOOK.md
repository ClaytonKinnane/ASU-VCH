# Historical Local Runbook — Military Positions Directory v1

## Current classification

```text
INCREMENT: Military Positions Directory v1
PR: #19 CLOSED / MERGED
MERGE_COMMIT: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
TESTED_RUNTIME_HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
AUTOMATED_TESTING_STATUS: PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS: PASS
MOBILE_TESTING_STATUS: OUT_OF_SCOPE_NOT_RUN
DOCUMENT_CLASS: HISTORICAL INCREMENT RUNBOOK
```

Этот документ больше не является основным stable runbook. Для текущего `main` используется `docs/LOCAL-RUNBOOK.md`.

## Historical pre-merge workflow

Во время реализации тестировалась ветка:

```text
feature/military-positions-directory
```

Локальный клон:

```text
repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
URL: https://asu-vch.local
```

Historical synchronization:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch feature/military-positions-directory
git pull --ff-only origin feature/military-positions-directory
git rev-parse HEAD
git merge-base origin/main HEAD
git status --short
```

Historical runner:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
    -File '.\tools\Test-MilitaryPositionsDirectory.ps1' `
    -AllowInvalidCertificate
```

Runner выполнял:

- exact implementation scope verification;
- 5-part migration package reconstruction and SHA-256 checks;
- SQL backup;
- controlled deploy с сохранением `config/local.php`;
- PHP lint;
- installer twice;
- military positions integration checker;
- directory, security, theme и Organization regressions;
- source/deploy parity;
- HTTP smoke;
- final Git/config integrity.

## Historical successful result

```text
TESTED_RUNTIME_HEAD=0455f0120c881bb9ba6e9df8f80ea0af89819be9
PHP_FILES_LINTED=108
PHP_LINT_ERRORS=0
APPLIED_MIGRATIONS=10
MILITARY_POSITIONS_DIRECTORY_CHECK=PASS
ORGANIZATION_REGRESSION=58_PASS_0_FAIL
SOURCE_DEPLOY_PARITY_STATUS=PASS
HTTP_SMOKE_STATUS=PASS
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=PASS
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
```

## Post-merge note

PR #19 merged в `main` commit `99f9f283...`. Повторный local runtime test именно на merge commit не выполнялся и не заявляется. Tested runtime anchor остаётся `0455f012...`.

Feature-ветка не удаляется в рамках Post-PR20 Baseline Refresh. Cleanup требует fresh inventory и отдельного owner approval.

## Stable operations

Для stable baseline выполнять инструкции из:

```text
docs/LOCAL-RUNBOOK.md
```

Не следует восстанавливать pre-merge workflow этого historical runbook как текущий процесс после удаления feature-ветки.
