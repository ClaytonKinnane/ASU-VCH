# Локальный runbook АСУ-ВЧ

## 1. Назначение

Документ описывает воспроизводимую синхронизацию, deploy и проверку текущего stable baseline АСУ-ВЧ в Open Server Panel без локальной разработки исходников.

```text
repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
web root: C:\OSPanel\home\asu-vch.local\public
URL: https://asu-vch.local
```

## 2. Поддерживаемая среда

- Windows 10/11;
- Open Server Panel 6.5.1;
- Apache;
- PHP 8.5.4;
- MySQL 8.4.x;
- Windows PowerShell 5.1;
- полный Git-клон проекта;
- deploy-only `config/local.php`.

Секреты и содержимое `config/local.php` не выводятся и не передаются в GitHub.

## 3. Repository pointer и functional anchors

Актуальный stable HEAD определяется из GitHub:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git fetch --prune origin
git rev-parse origin/main
```

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

Точный current `main` не хранится как самореферентное living-поле. Documentation-only commits после tested runtime не объявляются runtime-протестированными.

## 4. Синхронизация stable baseline

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

Требования:

- working tree чистое до и после;
- только fast-forward;
- local HEAD равен `origin/main`;
- divergence `0 0`;
- для stable verification используется `main`, а не завершённая feature/docs-ветка.

## 5. Read-only branch inventory

Перед cleanup:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git branch --merged origin/main
```

Для каждой ветки проверяются:

- tip и tracking state;
- достижимость из актуального `origin/main`;
- число commits в `origin/main..branch`;
- связанный PR и merge status;
- отсутствие незакоммиченных локальных изменений.

```text
technical safe-to-delete classification: not deletion approval
branch deletion without explicit owner approval: prohibited
```

## 6. Резервное копирование

Перед deploy сохраняются изменяемые deploy-файлы. Перед schema/data migration создаётся SQL backup и фиксируются path, size и SHA-256.

`config/local.php` не выводится; сравнивается только его SHA-256.

## 7. Полная локальная инициализация

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1'
```

Для repeat installer без deploy:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

## 8. Installer

```powershell
php C:\OSPanel\home\asu-vch.local\database\install.php
```

Для текущего baseline ожидается:

```text
Применено миграций: 11
Новых миграций нет.
```

Installer запускается повторно. Число пользователей и локальные рабочие данные не являются project constants.

## 9. Профильные runtime runner'ы

### Military Positions

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryPositionsDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Проверенный runtime HEAD:

```text
0455f0120c881bb9ba6e9df8f80ea0af89819be9
```

### Public Military Occupational Specialties

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryOccupationalSpecialtiesDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Проверенный runtime HEAD:

```text
9db06c4a26066ca25dc36c627c1236089a3c1238
```

Runner подтверждает backup, deploy, PHP lint, installer twice, профильные checker'ы, regressions, source/deploy parity, HTTP smoke и repository integrity.

## 10. Последние проверенные результаты

PR #19:

```text
PHP lint: 108 files / 0 errors
applied migrations: 10
military positions checker: PASS
organization regression: 58 PASS / 0 FAIL
automated testing: PASS
manual desktop acceptance: PASS
```

PR #20:

```text
PHP lint: 113 files / 0 errors
applied migrations: 11
VUS integration checker: PASS
VUS UI checker: PASS
organization regression: 58 PASS / 0 FAIL
source/deploy parity: 14 paths / PASS
HTTP smoke: PASS
automated testing: PASS
manual desktop acceptance: PASS
targeted manual desktop recheck: PASS
```

## 11. HTTP smoke

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-LocalSmoke.ps1' `
  -AllowInvalidCertificate
```

Ожидается:

- `/` — HTTP 200;
- `/health.php` — HTTP 200;
- `/admin/` для anonymous — HTTP 302.

## 12. Theme assets

Каждая тема публикует:

```text
css/theme.css
css/auth.css
css/account.css
css/users.css
css/theme-management.css
css/directories.css
css/military-occupational-specialties.css
css/organization.css
css/operation-result-modal.css
```

`asu-evgeniya-rostova` дополнительно публикует четыре обязательных SVG.

## 13. Desktop/browser acceptance

Для owner-only directories проверяются:

- owner access и ordinary-role HTTP 403;
- default counts, search, filters, combined filters и empty state;
- official source links;
- отсутствие mutation controls;
- все три темы при 1920×1080 и 1366×768;
- console errors = 0;
- asset/HTTP 404 = 0.

Для VUS дополнительно проверены composition policy организации и исключение нормативных примеров при выбранной организации.

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```

## 14. Documentation-only validation

Для documentation refresh deploy и runtime retest не требуются при соблюдении exact Markdown allowlist. Проверяются:

- branch/base/merge-base;
- changed-path count и allowlist;
- отсутствие non-Markdown diff;
- baseline facts PR #19/#20;
- migrations 001–011;
- living-document consistency;
- current-state/historical-artifact separation;
- Markdown links;
- secret scan;
- отсутствие branch deletion.

## 15. Фиксация результата

Сохраняются:

- branch и exact head;
- base/merge-base и divergence;
- changed paths;
- test/runtime anchors;
- validation result;
- явные `PR NOT CREATED`, `MERGE NOT AUTHORIZED`, `BRANCH DELETION NOT PERFORMED`;
- отсутствие Mobile PASS claim.
