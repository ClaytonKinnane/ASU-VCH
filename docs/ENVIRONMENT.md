# Среда разработки и запуска

## Поддерживаемая локальная среда

```text
ОС: Windows 10/11
Open Server Panel: 6.5.1
Web server: Apache
PHP: 8.5.4
MySQL: 8.4.x
Shell: Windows PowerShell 5.1
```

Последний полный runtime-прогон PR #20 выполнен в Windows/Open Server среде с PHP 8.5.4 и MySQL 8.4. Изменение major/minor версий модулей требует отдельной проверки совместимости.

## Разделение каталогов

```text
полный Git-клон: C:\Project\ASU-VCH
deploy root:      C:\OSPanel\home\asu-vch.local
Apache web root:  C:\OSPanel\home\asu-vch.local\public
локальный URL:    https://asu-vch.local
```

Git-клон не является web root. Существующий проект `C:\OSPanel\home\asu.local` не относится к АСУ-ВЧ и не должен изменяться.

## Состав deploy

`deploy\Deploy-Local.ps1` копирует:

```text
app\       -> C:\OSPanel\home\asu-vch.local\app
config\    -> C:\OSPanel\home\asu-vch.local\config
database\  -> C:\OSPanel\home\asu-vch.local\database
public\    -> C:\OSPanel\home\asu-vch.local\public
themes\    -> C:\OSPanel\home\asu-vch.local\public\themes
deploy\ospanel\.osp\project.ini -> C:\OSPanel\home\asu-vch.local\.osp\project.ini
```

Сценарий сохраняет существующий `config/local.php` и восстанавливает его после копирования. Документация, `.git` и repository-only tools в deploy root не копируются, если они не входят в контролируемый runner scope.

## Синхронизация стабильной версии

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch main
git pull --ff-only origin main
git rev-parse HEAD
git rev-parse origin/main
git status --short
```

Рабочее дерево должно быть чистым, локальная `main` — совпадать с `origin/main`.

## Развёртывание

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1'
```

Сценарий:

1. находит PHP 8.5;
2. выполняет PHP lint;
3. запускает controlled deploy;
4. сохраняет или создаёт `config/local.php`;
5. запускает `database\install.php`;
6. применяет только незарегистрированные migrations.

Повторный installer без deploy:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

## База данных

Текущий merged baseline содержит migrations `001–011`.

После применения всех migrations повторный installer должен подтвердить:

```text
Применено миграций: 11
Новых миграций нет.
```

Migrations 009–011 используют compatibility packaging там, где это требуется ограничениями Windows PowerShell/GitHub transport. Проверка archive/canonical SQL hash входит в профильные runner'ы.

Перед migration, меняющей schema или данные, создаётся SQL backup с размером и SHA-256.

## Тематические assets

Каждая тема публикует девять обязательных CSS-assets:

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

Для `asu-evgeniya-rostova` дополнительно обязательны четыре локальных SVG.

## Профильные runner'ы

Military Positions:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryPositionsDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Public Military Occupational Specialties:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryOccupationalSpecialtiesDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

`-AllowInvalidCertificate` применяется только для локального self-signed HTTPS.

Последний полный VUS runtime-прогон подтвердил:

```text
PHP lint: 113 files / 0 errors
applied migrations: 11
repeat installer: no new migrations
VUS integration checker: PASS
VUS UI checker: PASS
organization regression: 58 PASS / 0 FAIL
source/deploy parity: 14 paths / PASS
HTTP smoke: PASS
```

## Минимальные проверки после deploy

- PHP lint source и deploy;
- сохранность SHA-256 `config/local.php`;
- installer и repeat installer;
- профильные directory checker'ы;
- security, theme, directory и Organization regressions;
- source/deploy parity для затронутых runtime paths;
- HTTP smoke `/`, `/health.php`, `/admin/`;
- HTTP 200 обязательных theme assets;
- browser acceptance, предусмотренная Specification.

## Защита конфигурации

Нельзя публиковать:

- пароль БД;
- session data;
- содержимое `config/local.php`;
- реальные credentials test-owner;
- временные пароли.

До и после deploy допускается сравнивать только SHA-256 `config/local.php`.

## Границы тестирования

```text
mobile testing for PR #19/#20: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```
