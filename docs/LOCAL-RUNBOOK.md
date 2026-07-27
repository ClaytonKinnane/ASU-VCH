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

- Windows 11;
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

Требования:

- рабочее дерево до и после синхронизации чистое;
- `main` обновляется только fast-forward;
- локальные commit и push не выполняются;
- для активного инкремента используется точное имя согласованной ветки и ожидаемый SHA.

## 4. Резервное копирование

Перед deploy сохраняются изменяемые deploy-файлы. Перед новой миграцией, меняющей схему или данные, дополнительно создаётся SQL backup и фиксируется его SHA-256.

Повторная post-merge установка без новых миграций не требует нового SQL backup, но backup deploy-файлов сохраняется.

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
5. копирует `app`, `config`, `database`, `public` и опубликованные темы;
6. восстанавливает `config/local.php`;
7. запускает installer.

При первой установке параметры в созданном из example-файла `config/local.php` проверяются до installer.

## 6. Installer

Ручной запуск:

```powershell
php C:\OSPanel\home\asu-vch.local\database\install.php
```

В текущем baseline ожидается:

```text
Применено миграций: 8
Новых миграций нет.
```

Число пользователей и состояние bootstrap-регистрации зависят от локальной БД и не являются константой проекта.

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

При локальном недоверенном сертификате используется предусмотренный сценарием параметр разрешения локального сертификата.

Проверяются:

- `/` — HTTP 200;
- `/health.php` — HTTP 200 и подключение к БД;
- `/admin/` для анонимного пользователя — HTTP 302.

## 10. Integration checker'ы

После изменений запускаются все профильные и регрессионные проверки. Для текущих справочников обязательны:

```powershell
php C:\OSPanel\home\asu-vch.local\tools\check-military-ranks-directory.php
php C:\OSPanel\home\asu-vch.local\tools\check-organizational-elements-directory.php
```

Ожидаемые финальные маркеры:

```text
MILITARY RANKS DIRECTORY CHECK PASSED
ORGANIZATIONAL ELEMENT TYPES DIRECTORY CHECK PASSED
```

Checker организационных элементов также подтверждает bootstrap factory, migration 008, 7 таблиц, 4 источника, 6 классов, 28 типов, 32 связи, статусы 12/12/4, 19 permissions и assets обеих тем.

## 11. Проверка тем

Обязательные опубликованные ресурсы должны возвращать HTTP 200:

```text
/themes/asu-blue/assets/css/theme.css
/themes/asu-light-blue/assets/css/theme.css
/themes/asu-blue/assets/css/directories.css
/themes/asu-light-blue/assets/css/directories.css
```

Исходные и deploy-файлы проверяются по SHA-256.

## 12. Browser-приёмка

Объём ручной проверки определяется спецификацией инкремента. Обычно проверяются:

- owner navigation;
- разрешённые и запрещённые прямые маршруты;
- тематические HTTP 403;
- поиск, фильтры и empty state;
- отсутствие неразрешённых mutation controls;
- обе desktop-темы.

Мобильная приёмка выполняется только когда прямо включена в scope. Для последних справочных инкрементов она не выполнялась.

## 13. Фиксация результата

При ошибке сохраняются:

- точная команда;
- полный текст ошибки без секретов;
- ветка и SHA;
- версии PHP и MySQL;
- затронутый checker;
- HTTP status и безопасный фрагмент ответа;
- соответствующие записи журналов Apache/PHP без sensitive data.

Успешный результат фиксирует ветку, local/remote SHA, расхождение, backup, installer, checker'ы и отсутствие локальных изменений.
