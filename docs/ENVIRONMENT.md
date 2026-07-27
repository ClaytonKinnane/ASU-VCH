# Среда разработки и запуска

## Целевая среда

```text
ОС: Windows 11
Open Server Panel: 6.5.1
Web server: Apache
PHP: 8.5.4
MySQL: 8.4.8
Shell: Windows PowerShell 5.1
```

Проект проверяется в этой среде. Изменение версий модулей требует отдельной проверки совместимости.

## Разделение каталогов

```text
полный Git-клон: C:\Project\ASU-VCH
deploy root:      C:\OSPanel\home\asu-vch.local
Apache web root:  C:\OSPanel\home\asu-vch.local\public
локальный URL:    https://asu-vch.local
```

Git-клон не является web root. Он используется только для получения GitHub-изменений и тестирования. Исходники и документация не редактируются локально.

Существующий проект `C:\OSPanel\home\asu.local` не относится к АСУ-ВЧ и не должен изменяться.

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

Перед очисткой deploy root сценарий сохраняет существующий `config/local.php` во временный файл и восстанавливает его после копирования. При первой установке `config/local.php` создаётся из `config/local.example.php` и должен быть проверен до запуска installer.

Документация, `.git`, GitHub metadata и прочие repository-only файлы в deploy root не копируются.

## Синхронизация стабильной версии

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch main
git pull --ff-only origin main
```

Рабочее дерево должно быть чистым. Для проверки незавершённого инкремента используется точная утверждённая feature-ветка и ожидаемый remote HEAD.

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
6. применяет только ещё не зарегистрированные миграции.

Для повторного installer без deploy:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

Локальный test-owner может создаваться только специальным local-only seed-сценарием. Его учётные данные не являются частью документации и не должны использоваться вне изолированной локальной среды.

## База данных

Текущий installer поддерживает migrations 001–008. Подключение выполняется через PDO с `utf8mb4`; migration seed использует согласованную collation `utf8mb4_unicode_ci`.

Перед новой миграцией, меняющей структуру или данные, необходимо создать SQL backup. Повторный запуск installer после успешного применения должен вывести:

```text
Новых миграций нет.
```

## Проверки после deploy

Минимальный набор:

- PHP lint исходников и deploy-копий;
- совпадение SHA-256 изменённых файлов;
- неизменность `config/local.php`;
- installer и ожидаемое число миграций;
- профильные CLI integration checker'ы;
- регрессионные checker'ы затронутых модулей;
- HTTP smoke `/`, `/health.php`, `/admin/`;
- HTTP 200 обязательных assets тем;
- browser-приёмка, предусмотренная спецификацией.

## Диагностика

Проверьте наличие модулей:

```powershell
Get-ChildItem -LiteralPath 'C:\OSPanel\modules' -Directory |
    Sort-Object Name |
    Select-Object Name
```

Обязательны каталоги `Apache`, `PHP-8.5` и `MySQL-8.4`.

Проверьте конфигурацию проекта:

```powershell
Get-Content -LiteralPath 'C:\OSPanel\home\asu-vch.local\.osp\project.ini'
```

Проверьте опубликованные темы:

```powershell
Test-Path -LiteralPath 'C:\OSPanel\home\asu-vch.local\public\themes\asu-blue\assets\css\theme.css'
Test-Path -LiteralPath 'C:\OSPanel\home\asu-vch.local\public\themes\asu-light-blue\assets\css\theme.css'
```

При диагностике нельзя публиковать пароль БД, session data или содержимое `config/local.php`.
