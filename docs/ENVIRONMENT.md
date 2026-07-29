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

Organizational Structure v1 фактически проверялся в локальной Windows/Open Server среде с PHP 8.5.4 и MySQL 8.4. Изменение major/minor версий модулей требует отдельной проверки совместимости.

## Разделение каталогов

```text
полный Git-клон: C:\Project\ASU-VCH
deploy root:      C:\OSPanel\home\asu-vch.local
Apache web root:  C:\OSPanel\home\asu-vch.local\public
локальный URL:    https://asu-vch.local
```

Git-клон не является web root. Он используется для получения GitHub-изменений и тестирования. Существующий проект `C:\OSPanel\home\asu.local` не относится к АСУ-ВЧ и не должен изменяться.

## Состав deploy

Сценарий `deploy\Deploy-Local.ps1` копирует:

```text
app\       -> C:\OSPanel\home\asu-vch.local\app
config\    -> C:\OSPanel\home\asu-vch.local\config
database\  -> C:\OSPanel\home\asu-vch.local\database
public\    -> C:\OSPanel\home\asu-vch.local\public
themes\    -> C:\OSPanel\home\asu-vch.local\public\themes
deploy\ospanel\.osp\project.ini -> C:\OSPanel\home\asu-vch.local\.osp\project.ini
```

Перед очисткой deploy root сценарий сохраняет существующий `config/local.php` и восстанавливает его после копирования. При первой установке файл создаётся из `config/local.example.php` и проверяется до installer.

Документация, `.git`, GitHub metadata и repository-only файлы в deploy root не копируются.

## Синхронизация стабильной версии

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch main
git pull --ff-only origin main
git rev-parse HEAD
```

Рабочее дерево должно быть чистым, а локальная `main` — совпадать с `origin/main`.

## Развёртывание

Полный цикл:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1'
```

Сценарий:

1. находит PHP 8.5;
2. выполняет PHP lint;
3. запускает контролируемый deploy;
4. сохраняет или создаёт `config/local.php`;
5. запускает `database\install.php`;
6. применяет только ещё не зарегистрированные migrations.

Для повторного installer без deploy:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

## База данных

Текущий installer поддерживает migrations `001–009`. Подключение выполняется через PDO с `utf8mb4`; migration seed использует `utf8mb4_unicode_ci`.

После успешного применения повторный installer должен вывести:

```text
Применено миграций: 9
Новых миграций нет.
```

Перед migration, меняющей schema или данные, создаётся SQL backup с фиксацией размера и SHA-256.

## Тематические assets

Каждая тема должна публиковать:

```text
css/theme.css
css/auth.css
css/account.css
css/users.css
css/theme-management.css
css/directories.css
css/organization.css
css/operation-result-modal.css
```

Для `asu-evgeniya-rostova` дополнительно обязательны четыре локальных SVG.

## Проверки после deploy

Минимальный набор:

- PHP lint исходников и deploy-копий;
- совпадение SHA-256 изменённых файлов;
- неизменность `config/local.php`;
- installer и ожидаемые 9 migrations;
- профильные CLI integration checker'ы;
- theme, directory и security regression checker'ы;
- HTTP smoke `/`, `/health.php`, `/admin/`;
- HTTP 200 обязательных theme assets;
- browser-приёмка, предусмотренная Specification.

Проверенный комплексный runner Organizational Structure v1:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
    -File '.\tools\Test-OrganizationalStructureV1.ps1' `
    -AllowInvalidCertificate
```

`-AllowInvalidCertificate` используется только для локального self-signed HTTPS.

## Защита конфигурации

Нельзя публиковать:

- пароль БД;
- session data;
- содержимое `config/local.php`;
- реальные credentials test-owner.

До и после deploy допускается сравнивать только SHA-256 `config/local.php`.

## Границы тестирования

```text
mobile testing for Organizational Structure v1: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```
