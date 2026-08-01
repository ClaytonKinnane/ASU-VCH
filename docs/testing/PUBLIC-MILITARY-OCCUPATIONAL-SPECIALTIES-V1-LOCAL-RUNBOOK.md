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
IMPLEMENTATION_FILE_COUNT=18
WORKING TREE=CLEAN
```

## Automated Testing

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryOccupationalSpecialtiesDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Runner выполняет backup, deploy, PHP lint, installer дважды, integration checker, regressions, source/deploy parity и HTTP smoke.

Ожидаемый финал:

```text
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=NOT_RUN
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
PR_STATUS=NOT_CREATED
```

Migration loader дополнительно обязан подтвердить:

```text
archive SHA-256: 1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39
SQL SHA-256:     26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9
parts:           2
```

После automated PASS выполняется отдельная ручная desktop-приёмка. PR не создаётся без отдельного разрешения.
