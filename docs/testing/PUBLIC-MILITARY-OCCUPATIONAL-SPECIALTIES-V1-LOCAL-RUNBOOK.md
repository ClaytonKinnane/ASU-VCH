# Local Runbook — Public Military Occupational Specialties v1

## Синхронизация

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

До запуска Testing:

```text
CURRENT_BRANCH=feature/public-military-occupational-specialties-directory
ORIGIN_FEATURE_DIVERGENCE=0 0
IMPLEMENTATION_FILE_COUNT=25
WORKING TREE=CLEAN
PR_STATUS=OPEN_20_NOT_MERGED
```

## Automated Testing after Final PR Review remediation

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryOccupationalSpecialtiesDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Runner выполняет backup, deploy, PHP lint, installer дважды, основной integration checker, отдельный UI checker, regressions, source/deploy parity и HTTP smoke.

Integration checker должен подтвердить новые markers:

```text
OK record_type all without organization includes direct disclosures
OK direct-disclosure without organization includes direct disclosures
OK training-program excludes direct disclosures
OK record_type all with organization excludes direct disclosures
OK direct-disclosure with organization produces no direct-disclosure rows
OK organization filter returns only matching training programs
```

UI checker должен завершиться marker:

```text
MILITARY_OCCUPATIONAL_SPECIALTIES_UI_CHECK=PASS
```

Theme-management regression должен подтвердить:

```text
OK Evgeniya Rostova required assets registered
OK Evgeniya Rostova asset URL: css/military-occupational-specialties.css
OK theme management integration check completed
```

Ожидаемый финал:

```text
IMPLEMENTATION_FILE_COUNT=25
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=TARGETED_RECHECK_REQUIRED
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
PR_STATUS=OPEN_20_NOT_MERGED
```

Migration loader дополнительно обязан подтвердить:

```text
archive SHA-256: 1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39
SQL SHA-256:     26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9
parts:           2
```

## Targeted Manual Desktop Recheck

После Automated Testing PASS под владельцем проверить:

1. без фильтров — 17 записей;
2. `Тип записи = Все` + организация — только программы выбранной организации, без нормативных примеров;
3. `Тип записи = Нормативные примеры` + организация — пустое состояние;
4. `Тип записи = Программы подготовки` + та же организация — тот же набор программ;
5. сброс — снова 17 записей;
6. Console errors = 0, HTTP/asset 404 = 0.

Ранее принятая визуальная проверка трёх тем и двух desktop-разрешений сохраняется, поскольку CSS/theme assets не изменялись.

После targeted PASS обновляется существующий manual acceptance evidence-файл без добавления нового changed path, затем выполняется повторный Final PR Review.

Merge и удаление ветки не разрешены.
