# Локальный runbook АСУ-ВЧ

## 1. Назначение

Документ описывает воспроизводимую синхронизацию, deploy и проверку АСУ-ВЧ в Open Server Panel без локальной разработки исходников.

```text
репозиторий: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
web root: C:\OSPanel\home\asu-vch.local\public
URL: https://asu-vch.local
```

## 2. Предварительные условия

- Windows 10/11;
- Open Server Panel 6.5.1;
- Apache;
- PHP 8.5.4;
- MySQL 8.4.8;
- Windows PowerShell 5.1;
- полный Git-клон проекта;
- настроенный deploy-only файл `C:\OSPanel\home\asu-vch.local\config\local.php`.

Секреты и содержимое `config/local.php` не выводятся и не передаются в GitHub.

## 3. Синхронизация с GitHub

Для стабильной версии:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch main
git pull --ff-only origin main
git rev-parse HEAD
```

Для активного инкремента:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch feature/theme-evgeniya-rostova
git pull --ff-only origin feature/theme-evgeniya-rostova
git rev-parse HEAD
git rev-list --left-right --count HEAD...origin/feature/theme-evgeniya-rostova
git status --short
```

Требования:

- рабочее дерево до и после синхронизации чистое;
- ветка обновляется только fast-forward;
- divergence равен `0/0`;
- локальные commit и push не выполняются;
- тестируется точный GitHub HEAD.

## 4. Резервное копирование

Перед deploy сохраняются изменяемые deploy-файлы. Перед migration, меняющей схему или данные, дополнительно создаётся SQL backup и фиксируется его SHA-256.

`Evgeniya Rostova Theme v1` не содержит migration и не меняет данные, поэтому новый SQL backup для этого инкремента не требуется. Backup изменяемых deploy-файлов и контроль сохранности `config/local.php` обязательны.

Минимально сохраняются существующие:

```text
config/themes.php
database/check-theme-management.php
public/themes
tools — если каталог присутствует в deploy-root
themes — если checker-only source copy присутствует в deploy-root
```

Содержимое `config/local.php` не выводится. До и после deploy сравнивается только SHA-256 файла.

## 5. Полная локальная инициализация

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1'
```

Сценарий:

1. определяет `php.exe`;
2. выполняет PHP lint;
3. запускает `deploy\Deploy-Local.ps1`;
4. сохраняет существующий `config/local.php`;
5. копирует `app`, `config`, `database`, `public` и публикует темы в `public/themes`;
6. восстанавливает `config/local.php`;
7. запускает installer.

При первой установке параметры в созданном из example-файла `config/local.php` проверяются до installer.

## 6. Installer

Ручной запуск:

```powershell
php C:\OSPanel\home\asu-vch.local\database\install.php
```

Для текущего baseline и `Evgeniya Rostova Theme v1` ожидается:

```text
Применено миграций: 8
Новых миграций нет.
```

Installer запускается повторно. Число пользователей и состояние bootstrap-регистрации зависят от локальной БД и не являются константой проекта.

## 7. Local-only test owner

Для изолированной тестовой установки допускается:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SeedLocalOwner
```

Seed разрешён только при `environment=local`, не является production-bootstrap и не должен использоваться как постоянная учётная запись. Учётные данные не фиксируются в проектной документации.

## 8. Повторная проверка без deploy

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

Этот режим не синхронизирует файлы и используется только когда deploy-копия уже подтверждена.

## 9. HTTP smoke

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Test-LocalSmoke.ps1'
```

Проверяются:

- `/` — HTTP 200;
- `/health.php` — HTTP 200 и подключение к БД;
- `/admin/` для анонимного пользователя — HTTP 302.

## 10. Theme checker'ы

После deploy выполняются:

```powershell
php C:\OSPanel\home\asu-vch.local\database\check-theme-management.php
php C:\OSPanel\home\asu-vch.local\database\check-theme-asset-failure.php
```

Первый checker проверяет:

- default `asu-blue`;
- точный список трёх тем;
- 11 обязательных assets `asu-evgeniya-rostova`;
- безопасные CSS/SVG URL;
- отсутствие external resources и theme-specific JavaScript;
- SVG safety;
- migration 006 и actor FK;
- registered stored theme;
- транзакционный write/read с rollback;
- отклонение invalid slug.

Второй checker работает только с временной копией assets, удаляет в sandbox `plush-bunny.svg` и подтверждает unavailable/missing-assets behavior. Реальные файлы и active theme не изменяются.

## 11. Профильные directory checker'ы

Deploy-процесс публикует runtime-темы в `public/themes`, но каталог `tools` не является runtime-каталогом. Для контролируемой проверки checker-файлы копируются из точного чистого checkout в deploy-root без редактирования:

```powershell
$SourceRoot = 'C:\Project\ASU-VCH'
$TargetRoot = 'C:\OSPanel\home\asu-vch.local'

New-Item -ItemType Directory -Path (Join-Path $TargetRoot 'tools') -Force | Out-Null
Copy-Item -LiteralPath (Join-Path $SourceRoot 'tools\check-all-theme-directory-assets.php') -Destination (Join-Path $TargetRoot 'tools') -Force
Copy-Item -LiteralPath (Join-Path $SourceRoot 'tools\check-military-ranks-directory.php') -Destination (Join-Path $TargetRoot 'tools') -Force
Copy-Item -LiteralPath (Join-Path $SourceRoot 'tools\check-military-ranks-directory-core.php') -Destination (Join-Path $TargetRoot 'tools') -Force
Copy-Item -LiteralPath (Join-Path $SourceRoot 'tools\check-organizational-elements-directory.php') -Destination (Join-Path $TargetRoot 'tools') -Force
Copy-Item -LiteralPath (Join-Path $SourceRoot 'tools\check-organizational-elements-directory-core.php') -Destination (Join-Path $TargetRoot 'tools') -Force
```

Прежние core-checker'ы сохраняют историческую source-side проверку `themes/{slug}/assets/css/directories.css`. Для этой read-only проверки в deploy-root создаётся checker-only source copy тем; Apache продолжает обслуживать только `public/themes`:

```powershell
$SourceThemes = 'C:\Project\ASU-VCH\themes'
$TargetThemes = 'C:\OSPanel\home\asu-vch.local\themes'
if (Test-Path -LiteralPath $TargetThemes) {
    Remove-Item -LiteralPath $TargetThemes -Recurse -Force
}
Copy-Item -LiteralPath $SourceThemes -Destination $TargetThemes -Recurse -Force
```

После подготовки выполняются:

```powershell
php C:\OSPanel\home\asu-vch.local\tools\check-military-ranks-directory.php
php C:\OSPanel\home\asu-vch.local\tools\check-organizational-elements-directory.php
```

Ожидаемые финальные маркеры:

```text
OK registered theme directory assets: 3
MILITARY RANKS DIRECTORY CHECK PASSED
ORGANIZATIONAL ELEMENT TYPES DIRECTORY CHECK PASSED
```

Core-checker'ы дополнительно подтверждают нормативные данные, repository search/filter behavior и 19 permissions.

## 12. Security regression checker'ы

После theme checker'ов повторяются существующие проверки:

```powershell
php C:\OSPanel\home\asu-vch.local\database\check-security-rbac.php
php C:\OSPanel\home\asu-vch.local\database\check-security-user-approval.php
php C:\OSPanel\home\asu-vch.local\database\check-security-required-password-change.php
php C:\OSPanel\home\asu-vch.local\database\check-security-user-rejection.php
php C:\OSPanel\home\asu-vch.local\database\check-security-user-archive-restore.php
```

Количество системных permissions остаётся `19`.

## 13. Проверка опубликованных assets темы «Евгения Ростова»

HTTP 200 должны вернуть:

```text
/themes/asu-evgeniya-rostova/assets/css/theme.css
/themes/asu-evgeniya-rostova/assets/css/auth.css
/themes/asu-evgeniya-rostova/assets/css/account.css
/themes/asu-evgeniya-rostova/assets/css/users.css
/themes/asu-evgeniya-rostova/assets/css/theme-management.css
/themes/asu-evgeniya-rostova/assets/css/directories.css
/themes/asu-evgeniya-rostova/assets/css/operation-result-modal.css
/themes/asu-evgeniya-rostova/assets/img/hearts-pattern.svg
/themes/asu-evgeniya-rostova/assets/img/balloons.svg
/themes/asu-evgeniya-rostova/assets/img/teddy-bear.svg
/themes/asu-evgeniya-rostova/assets/img/plush-bunny.svg
```

SVG должен обслуживаться корректным SVG MIME type. Для всех одиннадцати assets подтверждается совпадение SHA-256 между source и `public/themes` deploy-копией.

## 14. Desktop/browser-приёмка

Для `asu-evgeniya-rostova` проверяются:

- login;
- dashboard;
- settings и theme management;
- users list/create/view;
- change-password;
- directories landing и оба справочника;
- тематическая HTTP 403;
- success/error operation-result modal;
- search, filters, empty state, status badges;
- отсутствие перекрытия controls декоративными SVG;
- focus-visible, hover, disabled и danger states;
- отсутствие горизонтального scroll на утверждённых desktop viewport;
- persistence после reload и logout/login.

Последовательно активируются `asu-evgeniya-rostova`, `asu-light-blue` и `asu-blue`; для двух существующих тем выполняется desktop smoke/regression. После теста возвращается согласованная активная тема.

Мобильная приёмка этого инкремента не выполняется и Mobile PASS не заявляется.

## 15. Фиксация результата

При ошибке сохраняются:

- точная команда;
- полный текст ошибки без секретов;
- ветка и SHA;
- версии PHP и MySQL;
- затронутый checker;
- HTTP status и безопасный фрагмент ответа;
- соответствующие записи журналов Apache/PHP без sensitive data.

Успешный результат фиксирует ветку, local/remote SHA, расхождение, backup deploy-файлов, hash сохранности `config/local.php`, installer, checker'ы, HTTP assets, browser acceptance и отсутствие локальных изменений.
