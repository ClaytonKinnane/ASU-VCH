# Среда разработки и запуска

## Целевая среда

- Windows 11
- Open Server Panel 6.5.1
- Apache
- MySQL 8.4
- PHP 8.5
- минимально поддерживаемая версия PHP: 8.3
- Windows PowerShell 5.1

## Разделение каталогов

Полный клон репозитория располагается в:

```text
C:\Project\ASU-VCH
```

Клон является полным представлением GitHub-репозитория и используется только для получения изменений, выполнения команд Git, запуска сценария развертывания и проверки полученной версии. Он не является корнем веб-сервера.

Каталог локального сайта Open Server Panel располагается отдельно:

```text
C:\OSPanel\home\asu-vch.local
```

Публичный каталог Apache:

```text
C:\OSPanel\home\asu-vch.local\public
```

Локальный домен:

```text
https://asu-vch.local
```

Существующий проект `C:\OSPanel\home\asu.local` не относится к тестовому развертыванию АСУ-ВЧ и не должен изменяться.

## Файлы развертывания

Конфигурация проекта Open Server Panel хранится в репозитории:

```text
deploy\ospanel\.osp\project.ini
```

Сценарий локального развертывания:

```text
deploy\Deploy-Local.ps1
```

Сценарий копирует:

```text
public\*                         -> C:\OSPanel\home\asu-vch.local\public
themes\*                         -> C:\OSPanel\home\asu-vch.local\public\themes
deploy\ospanel\.osp\project.ini -> C:\OSPanel\home\asu-vch.local\.osp\project.ini
```

Темы хранятся в корне репозитория в каталоге `themes`, но при развертывании публикуются внутри `public\themes`, поскольку Apache не предоставляет доступ к файлам за пределами `web_root`.

В каталог сайта не копируются `.git`, `.github`, `docs`, `deploy`, `README.md`, `LICENSE`, `.gitignore` и другие служебные материалы репозитория.

## Первое клонирование

Откройте Windows PowerShell 5.1 и выполните:

```powershell
New-Item -ItemType Directory -Path 'C:\Project' -Force | Out-Null
Set-Location -LiteralPath 'C:\Project'
git clone --branch feature/initial-site https://github.com/ClaytonKinnane/ASU-VCH.git
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status
```

Ожидается, что активной будет ветка `feature/initial-site`, а рабочее дерево будет чистым.

## Обновление существующего клона

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status
git switch feature/initial-site
git pull --ff-only origin feature/initial-site
```

Перед `git pull` рабочее дерево должно быть чистым. Локальные ручные изменения в тестовом клоне не допускаются.

## Локальное развертывание

Запускать сценарий нужно из полного клона:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File '.\deploy\Deploy-Local.ps1'
```

Сценарий:

1. подтверждает наличие полного клона и обязательных исходных каталогов;
2. проверяет `C:\OSPanel\bin\ospanel.exe`;
3. проверяет версию Open Server Panel;
4. проверяет наличие модулей `Apache`, `PHP-8.5` и `MySQL-8.4`;
5. подтверждает точный безопасный путь назначения;
6. очищает только содержимое `C:\OSPanel\home\asu-vch.local`;
7. копирует публичные файлы, темы и конфигурацию проекта;
8. выводит итоговый отчет.

После успешного развертывания перечитайте конфигурацию проектов или перезапустите Open Server Panel, затем откройте:

```text
https://asu-vch.local
```

## Диагностика

### Сценарий сообщает, что `.git` не найден

Сценарий запущен не из полного клона либо каталог `.git` отсутствует. Проверьте путь:

```powershell
Test-Path -LiteralPath 'C:\Project\ASU-VCH\.git'
```

Ожидаемый результат: `True`.

### Не найден Open Server Panel

Проверьте:

```powershell
Test-Path -LiteralPath 'C:\OSPanel\bin\ospanel.exe'
```

Ожидаемый результат: `True`.

### Не найден модуль

Проверьте установленные каталоги:

```powershell
Get-ChildItem -LiteralPath 'C:\OSPanel\modules' -Directory |
    Sort-Object Name |
    Select-Object Name
```

Обязательны `Apache`, `PHP-8.5` и `MySQL-8.4`.

### Сайт открывается без оформления

Проверьте наличие опубликованных ресурсов:

```powershell
Test-Path -LiteralPath 'C:\OSPanel\home\asu-vch.local\public\themes\asu-blue\assets\css\theme.css'
Test-Path -LiteralPath 'C:\OSPanel\home\asu-vch.local\public\themes\asu-blue\assets\js\auth.js'
```

Оба результата должны быть `True`.

### Домен не открывается

Проверьте наличие конфигурации:

```powershell
Get-Content -LiteralPath 'C:\OSPanel\home\asu-vch.local\.osp\project.ini'
```

Затем перечитайте конфигурацию проектов или перезапустите Open Server Panel и убедитесь, что Apache запущен.

Подключение приложения к MySQL будет реализовано отдельной согласованной задачей.
