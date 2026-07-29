# Локальный runbook АСУ-ВЧ

## 1. Назначение

Документ описывает воспроизводимую синхронизацию, deploy и проверку текущего стабильного baseline АСУ-ВЧ в Open Server Panel без локальной разработки исходников.

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
- настроенный deploy-only `C:\OSPanel\home\asu-vch.local\config\local.php`.

Секреты и содержимое `config/local.php` не выводятся и не передаются в GitHub.

## 3. Текущий baseline

```text
merged main commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
migrations: 001–009
system roles: 4
system permissions: 25
built-in themes: 3
```

Merge commit не заявляется повторно runtime-протестированным. Проверенный runtime HEAD зафиксирован отдельно.

## 4. Синхронизация стабильной версии

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch main
git pull --ff-only origin main
git rev-parse HEAD
git rev-list --left-right --count HEAD...origin/main
git status --short
```

Требования:

- рабочее дерево до и после синхронизации чистое;
- обновление выполняется только fast-forward;
- divergence равен `0 0`;
- локальные commit и push не выполняются;
- для стабильной проверки используется `main`, а не завершённая feature-ветка.

## 5. Резервное копирование

Перед deploy сохраняются изменяемые deploy-файлы. Перед новой migration, меняющей schema или данные, дополнительно создаётся SQL backup и фиксируется его SHA-256.

Содержимое `config/local.php` не выводится. До и после deploy сравнивается только SHA-256 файла.

## 6. Полная локальная инициализация

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1'
```

Сценарий:

1. определяет `php.exe`;
2. выполняет PHP lint;
3. запускает `deploy\Deploy-Local.ps1`;
4. сохраняет существующий `config/local.php`;
5. копирует runtime-каталоги и публикует темы в `public/themes`;
6. восстанавливает `config/local.php`;
7. запускает installer.

Для повторной проверки installer без deploy:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

## 7. Installer

Ручной запуск:

```powershell
php C:\OSPanel\home\asu-vch.local\database\install.php
```

Для текущего baseline ожидается:

```text
Применено миграций: 9
Новых миграций нет.
```

Installer запускается повторно. Число пользователей и локальные данные не являются константой проекта.

## 8. Local-only test owner

В изолированной локальной установке допускается:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SeedLocalOwner
```

Seed разрешён только при `environment=local`. Учётные данные не фиксируются в проектной документации.

## 9. Основной комплексный runner

Для текущего Organizational Structure v1 используется проверенный runner:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
powershell.exe -NoProfile -ExecutionPolicy Bypass `
    -File '.\tools\Test-OrganizationalStructureV1.ps1' `
    -AllowInvalidCertificate
```

`-AllowInvalidCertificate` допустим только для локального self-signed HTTPS.

Runner выполняет:

- source/deploy UI contract checks;
- PHP lint;
- controlled deploy с сохранением `config/local.php`;
- migration compatibility check;
- installer и repeat installer;
- Organizational Structure schema/scenario checks;
- theme, directory и security regressions;
- HTTP smoke;
- backup и контрольные SHA-256.

Ожидаемые ключевые результаты проверенного baseline:

```text
UI contract checks: 64 PASS / 0 FAIL
organization checks: 58 PASS / 0 FAIL
PHP lint: 104 files / 0 errors
applied migrations: 9
system roles: 4
system permissions: 25
HTTP smoke: / 200, /health.php 200, /admin/ 302
AUTOMATED_TESTING_STATUS=PASS
```

## 10. Legacy checker technical debt

Некоторые старые direct checker-файлы исходно содержат exact-count assertion `19` permissions. Комплексный Organizational Structure runner применяет `PermissionBaselineRegressionAdapter`, чтобы выполнить их как совместимые baseline regressions при 25 permissions.

Следствия:

- основной воспроизводимый способ проверки — `Test-OrganizationalStructureV1.ps1`;
- прямой запуск отдельных legacy checker-файлов может быть несовместим с текущими 25 permissions;
- source cleanup этих checker-файлов и решение по adapter выделены в отдельный будущий технический инкремент;
- нельзя считать отказ отдельного legacy direct checker доказательством runtime-дефекта без анализа exact-count условия.

## 11. HTTP smoke

Отдельный smoke:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
    -File '.\tools\Test-LocalSmoke.ps1' `
    -AllowInvalidCertificate
```

Проверяются:

- `/` — HTTP 200;
- `/health.php` — HTTP 200 и подключение к БД;
- `/admin/` для анонимного пользователя — HTTP 302.

## 12. Theme assets

Каждая из трёх тем должна публиковать:

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

Тема `asu-evgeniya-rostova` дополнительно публикует четыре обязательных SVG.

## 13. Desktop/browser-приёмка

Для Organizational Structure v1 проверяются:

- список и карточка структуры;
- создание и изменение структуры;
- версии и lifecycle actions;
- дерево, поиск, expand/collapse и node controls;
- документы, история и сравнение;
- permission-denied behavior;
- operation-result messages;
- keyboard navigation и focus-visible;
- отсутствие console errors и asset 404;
- `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova`.

```text
manual desktop acceptance: PASS
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```

## 14. Фиксация результата

Успешная проверка фиксирует:

- branch и exact SHA;
- local/remote divergence;
- clean working tree;
- backup path, size и SHA-256;
- `config/local.php` SHA preservation;
- migration count;
- checker totals;
- HTTP statuses;
- desktop acceptance result;
- явное отсутствие mobile PASS claim.

При ошибке сохраняются точная команда и полный безопасный вывод без credentials, session data и содержимого `config/local.php`.
