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

До запуска Testing дерево должно быть чистым, divergence — `0 0`.

## Automated Testing

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryOccupationalSpecialtiesDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Ожидаемый финал:

```text
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=NOT_RUN
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
PR_STATUS=NOT_CREATED
```

После PASS выполняется отдельная ручная desktop-приёмка. PR не создаётся без отдельного разрешения.
