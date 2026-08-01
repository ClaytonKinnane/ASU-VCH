# Local Runbook — Public Military Occupational Specialties v1

## Исторический feature-branch runbook

Ниже сохранён порядок, использованный до merge PR #20.

### Синхронизация feature-ветки

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch feature/public-military-occupational-specialties-directory
git pull --ff-only origin feature/public-military-occupational-specialties-directory
git rev-parse HEAD
git rev-parse origin/feature/public-military-occupational-specialties-directory
git rev-list --left-right --count origin/feature/public-military-occupational-specialties-directory...HEAD
git status --short
```

Исторический pre-merge expectation:

```text
CURRENT_BRANCH=feature/public-military-occupational-specialties-directory
ORIGIN_FEATURE_DIVERGENCE=0 0
IMPLEMENTATION_FILE_COUNT=25
WORKING_TREE=CLEAN
PR_STATUS=OPEN_20_NOT_MERGED
```

### Automated Testing after Final PR Review remediation

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryOccupationalSpecialtiesDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Runner выполняет backup, deploy, PHP lint, installer дважды, основной integration checker, UI checker, regressions, source/deploy parity и HTTP smoke.

Integration markers:

```text
OK record_type all without organization includes direct disclosures
OK direct-disclosure without organization includes direct disclosures
OK training-program excludes direct disclosures
OK record_type all with organization excludes direct disclosures
OK direct-disclosure with organization produces no direct-disclosure rows
OK organization filter returns only matching training programs
```

```text
MILITARY_OCCUPATIONAL_SPECIALTIES_UI_CHECK=PASS
```

Migration package:

```text
archive SHA-256: 1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39
SQL SHA-256:     26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9
parts:           2
```

Исторический runner final marker на remediation runtime head:

```text
IMPLEMENTATION_FILE_COUNT=25
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=TARGETED_RECHECK_REQUIRED
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
PR_STATUS=OPEN_20_NOT_MERGED
```

### Targeted Manual Desktop Recheck

Проверялись:

1. без фильтров — 17 записей;
2. `Все + организация` — только программы организации;
3. `Нормативные примеры + организация` — empty state;
4. `Программы подготовки + организация` — ожидаемые программы;
5. reset — 17 записей;
6. Console errors = 0, HTTP/asset 404 = 0.

Результат: `PASS`.

## Post-merge operational closure

```text
PR: #20
PR_STATE: CLOSED
MERGED: YES
MERGE_COMMIT: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
TESTED_RUNTIME_HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
FINAL_FEATURE_HEAD: bea147505a85010b61fe938eb07ec474d76cdab5
AUTOMATED_TESTING_STATUS: PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS: PASS
TARGETED_MANUAL_DESKTOP_RECHECK_STATUS: PASS
FINAL_PR_REVIEW_STATUS: PASS
POST_MERGE_GIT_VERIFICATION_STATUS: PASS
MOBILE_TESTING_STATUS: OUT_OF_SCOPE_NOT_RUN
```

Исторические pre-merge markers выше описывают соответствующие этапы и не являются текущим статусом.

## Stable-main synchronization

После merge для получения стабильного baseline используется `main`:

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

Post-PR20 merge anchor:

```text
3082ec6ecbeddb92bd65e1398f05a9339abb199b
```

Актуальный current `main` всегда определяется через `origin/main`; merge SHA является историческим anchor.

## Re-running the profile runner

Профильный runner остаётся воспроизводимым для regression/testing нового runtime scope:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryOccupationalSpecialtiesDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Но его exact branch/path preflight был создан для feature/PR lifecycle. Для нового инкремента runner запускается только после отдельного scope review и при необходимости адаптируется в новой ветке; исторический tested runtime не подменяется documentation-only head.

## Branch lifecycle

Feature-ветка была сохранена после merge по явному условию owner. Её техническая достижимость из `main` подтверждена, но удаление выполняется только после:

1. merge Post-PR20 Baseline Refresh;
2. fresh remote/local inventory;
3. отдельного точного deletion approval;
4. terminal verification.

Настоящий documentation refresh не удаляет ветки.
