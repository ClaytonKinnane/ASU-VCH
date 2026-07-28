# Test Plan: фактическая организационная структура v1

## Статус

```text
TESTING: NOT EXECUTED
PASS CLAIMS: NONE
TARGET ENVIRONMENT: Windows 10 / Open Server Panel 6.5.1 / Apache / PHP 8.5.4 / MySQL 8.4
FEATURE BRANCH: feature/organizational-structure-v1
```

Документ определяет обязательную локальную проверку implementation commit, размещённого в GitHub feature-ветке, до Pull Request. Используются только синтетические данные. Реальные номера частей, дислокация, численность, вооружение и реквизиты закрытых документов в тесты, скриншоты и отчёт не включаются.

## 1. Предусловия

```powershell
$RepositoryRoot = 'C:\Project\ASU-VCH'
$DeployRoot = 'C:\OSPanel\home\asu-vch.local'

Set-Location $RepositoryRoot
git branch --show-current
git status --short

if (-not (Test-Path "$DeployRoot\config\local.php")) {
    throw "Не найден deploy-конфиг: $DeployRoot\config\local.php"
}

$DeployConfigHashBefore = (Get-FileHash "$DeployRoot\config\local.php" -Algorithm SHA256).Hash
"DEPLOY_CONFIG_LOCAL_SHA256_BEFORE=$DeployConfigHashBefore"
```

Ожидается ветка `feature/organizational-structure-v1`, чистое рабочее дерево и существующий deploy-конфиг. `config/local.php` намеренно отсутствует в Git checkout и проверяется только в deploy-копии.

## 2. Статические проверки

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\Test-PhpSyntax.ps1
php .\tools\check-organizational-structure.php
```

Вторая команда выполняется после migration. До migration отдельно проверяются:

- отсутствие `DELIMITER` в SQL-файле;
- семь `CREATE TABLE`;
- шестнадцать `CREATE TRIGGER`;
- шесть permissions;
- отсутствие role-permission seed для `administrator`, `operator`, `viewer`;
- наличие `organization.css` во всех трёх темах;
- наличие `organization-tree.js`;
- наличие `tools/Backup-Database.ps1`.

## 3. Резервная копия БД

До deploy и migration выполняется штатный скрипт резервного копирования:

```powershell
Set-Location C:\Project\ASU-VCH
powershell -ExecutionPolicy Bypass -File .\tools\Backup-Database.ps1 `
    -DeployRoot 'C:\OSPanel\home\asu-vch.local'
```

Скрипт:

- читает параметры подключения из deploy-файла `config/local.php` через PHP;
- не выводит пароль в консоль и не передаёт его в командной строке;
- использует временный `defaults-extra-file`, удаляемый в `finally`;
- автоматически находит `mysqldump.exe` установленного модуля Open Server или принимает явный `-MySqlDumpExecutable`;
- создаёт полный SQL dump с routines, triggers и events;
- сохраняет backup вне web/deploy-каталога;
- проверяет наличие и непустой размер файла;
- выводит SHA-256.

Каталог по умолчанию:

```text
C:\OSPanel\backups\asu-vch
```

Ожидаемый результат содержит:

```text
BACKUP_STATUS=PASS
DATABASE_NAME=<local database name>
MYSQLDUMP_EXECUTABLE=<resolved executable>
MYSQLDUMP_VERSION=<version>
BACKUP_FILE=<absolute sql path>
BACKUP_SIZE_BYTES=<positive integer>
BACKUP_SHA256=<sha256>
```

Migration запрещена, пока `BACKUP_STATUS=PASS` не получен. Сам SQL-файл не добавляется в Git, deploy-каталог, тестовый отчёт или публичные артефакты. В Test Report фиксируются только путь, размер и SHA-256 без содержимого и учётных данных.

## 4. Migration

Перед запуском installer GitHub checkout разворачивается в `C:\OSPanel\home\asu-vch.local` утверждённым способом проекта с сохранением deploy-файла `config/local.php`.

```powershell
Set-Location C:\OSPanel\home\asu-vch.local
php .\database\install.php
php .\database\install.php
```

Первый запуск должен применить `009_organizational_structure_v1.sql`. Повторный запуск должен сообщить об отсутствии новых миграций. SHA-256 deploy-файла `config/local.php` после развёртывания должен совпасть с зафиксированным значением.

Контрольное состояние:

```text
applied migrations: 9
system roles: 4
system permissions: 25
organizational structures immediately after migration: 0
```

## 5. Интеграционный checker

```powershell
php .\tools\check-organizational-structure.php
```

Checker обязан подтвердить:

- таблицы, FK, generated guards и triggers;
- отсутствие автоматических назначений новых permissions обычным системным ролям;
- первоначальный черновик и единственный корень;
- изменение карточки структуры;
- запрет изменения машинного кода;
- повторный черновик после отмены первоначальной версии;
- revision и stale-write rejection;
- stable element IDs после клонирования;
- cycle prevention;
- copy-on-write документа опубликованной версии;
- неизменяемость опубликованных узлов, документов и lifecycle;
- archive/restore lifecycle;
- append-only историю;
- полный rollback синтетического сценария.

После checker количество эксплуатационных структур не должно увеличиться.

## 6. Regression

```powershell
php .\database\check-security-rbac.php
php .\database\check-security-user-approval.php
php .\database\check-security-required-password-change.php
php .\database\check-security-user-rejection.php
php .\database\check-security-user-archive-restore.php
php .\database\check-theme-management.php
php .\tools\check-military-ranks-directory.php
php .\tools\check-organizational-elements-directory.php
powershell -ExecutionPolicy Bypass -File .\tools\Test-LocalSmoke.ps1 -AllowInvalidCertificate
```

Если конкретный checker требует собственные аргументы, применяется его существующий runbook без изменения данных организационной структуры.

## 7. RBAC acceptance

Создаются временные синтетические пользовательские роли:

1. только `organization.structures.view`;
2. `view + update`;
3. `view + publish`;
4. без organization permissions.

Проверяется:

- view-only не видит mutation actions;
- update изменяет только черновик;
- publish утверждает и активирует, но не редактирует дерево без `update`;
- history отображается только с `organization.structures.history`;
- пользователь без `view` не видит плитку и получает тематический HTTP 403;
- `system_owner` сохраняет полный доступ через `system.*.*`;
- blocked/inactive/archived user не проходит общую аутентификацию и авторизацию.

## 8. Functional acceptance

На синтетической структуре проверяются:

- создание контейнера и корня;
- изменение административной карточки;
- добавление, изменение, перемещение, сортировка и удаление узлов;
- подтверждение удаления поддерева;
- поиск, раскрытие и сворачивание;
- добавление, изменение и отвязка документов;
- один основной документ;
- утверждение;
- запрет преждевременной активации;
- активация и supersede;
- сравнение версий;
- отмена черновика и утверждённой версии;
- архивирование и восстановление;
- история изменений.

## 9. Browser и themes

Фактически проверяются:

```text
asu-blue
asu-light-blue
asu-evgeniya-rostova
```

Для каждой темы:

- список структур;
- карточка;
- глубокое дерево;
- длинные наименования;
- формы и details;
- success/error modal;
- HTTP 403;
- keyboard focus;
- отсутствие отсутствующих assets и HTTP 404.

## 10. Mobile acceptance

Проверка выполняется фактически на mobile viewport или устройстве:

- отсутствует горизонтальная прокрутка документа;
- карточки дерева читаемы на глубине;
- действия доступны без drag-and-drop;
- формы и модальные окна помещаются по ширине;
- длинные строки переносятся;
- focus и touch targets остаются доступными.

`Mobile PASS` запрещено указывать без результатов этого раздела.

## 11. Post-test integrity

```powershell
$DeployConfigHashAfter = (Get-FileHash 'C:\OSPanel\home\asu-vch.local\config\local.php' -Algorithm SHA256).Hash
"DEPLOY_CONFIG_LOCAL_SHA256_AFTER=$DeployConfigHashAfter"

Set-Location C:\Project\ASU-VCH
git status --short
```

SHA-256 deploy-файла `config/local.php` должен совпасть с исходным. В рабочей БД и отчёте не должны остаться синтетические checker-структуры.

## 12. Test Report gate

После фактического выполнения создаётся:

```text
docs/testing/ORGANIZATIONAL-STRUCTURE-V1-TEST-REPORT.md
```

Отчёт фиксирует команды, фактические выводы, версии среды, путь/размер/SHA-256 backup, desktop/mobile результаты, найденные дефекты и итоговый статус. До этого Pull Request не создаётся. Исправления по результатам Testing вносятся непосредственно в GitHub feature-ветку отдельными осмысленными commit.
