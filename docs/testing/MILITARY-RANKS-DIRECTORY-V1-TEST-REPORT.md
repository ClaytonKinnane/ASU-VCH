# Справочник составов военнослужащих и воинских званий v1 — Test Report

## 1. Объект проверки

Инкремент:

```text
Справочник составов военнослужащих и воинских званий v1
```

База и ветка:

```text
main @ 89a7da6428d17b792a958c75fe636a90ae15dcb8
feature/military-ranks-directory
```

Дата предварительных проверок: `2026-07-27`.

## 2. Проверенная область

На этапе предварительной проверки подготовлены:

```text
database/migrations/007_military_ranks_directory.sql
app/Directory/MilitaryRankCatalogRepository.php
app/bootstrap.php
public/admin/directories.php
public/admin/directories/military-ranks.php
config/themes.php
themes/asu-blue/assets/css/directories.css
themes/asu-light-blue/assets/css/directories.css
tools/check-military-ranks-directory.php
docs/design/MILITARY-RANKS-DIRECTORY-V1-DESIGN.md
docs/design/MILITARY-RANKS-DIRECTORY-V1-REVIEW.md
docs/decisions/MILITARY-RANKS-DIRECTORY-V1-APPROVAL.md
```

## 3. Нормативная сверка

Статически подтверждено, что migration и checker содержат:

- 20 нормативных уровней;
- пару `младший сержант — старшина 2 статьи`;
- пару `сержант — старшина 1 статьи`;
- `Маршал Российской Федерации` с `naval_name = NULL`;
- порядок от 1 до 20;
- два нормативных источника;
- дату проверки `2026-07-27`.

Источники версии:

```text
Федеральный закон от 28.03.1998 № 53-ФЗ, статья 46
Указ Президента Российской Федерации от 16.09.1999 № 1237,
статья 20 Положения о порядке прохождения военной службы
```

Статус: **PASS (STATIC)**.

Фактическая проверка записей после применения migration на целевой БД выполняется CLI checker.

## 4. PHP syntax в доступной среде

Версия проверочной среды:

```text
PHP 8.4.16 (cli)
```

Проверены:

```text
app/Directory/MilitaryRankCatalogRepository.php
public/admin/directories.php
public/admin/directories/military-ranks.php
config/themes.php
tools/check-military-ranks-directory.php
```

Результат для каждого файла:

```text
No syntax errors detected
```

Статус: **PASS**.

`app/bootstrap.php` изменён только добавлением подключения repository и factory-функции; diff составляет 10 добавленных строк без удаления существующей логики. Его обязательный lint выполняется в целевой среде PHP 8.5.4.

Статус `app/bootstrap.php`: **PENDING TARGET PHP 8.5.4 LINT**.

## 5. Database migration review

Статически проверено:

- имя migration `007_military_ranks_directory.sql`;
- создание пяти таблиц;
- InnoDB;
- `utf8mb4_unicode_ci`;
- первичные, уникальные и внешние ключи;
- composite foreign keys для принадлежности одной версии;
- `ON DELETE SET NULL` для `created_by`;
- CHECK constraints для дат, порядка и непустых наименований;
- начальное наполнение двумя источниками;
- одна текущая версия;
- шесть составов;
- двадцать уровней;
- отсутствие изменений roles и permissions.

MySQL 8.4 недоступен в проверочной среде. Применение migration и повторный installer должны быть выполнены в локальном развёртывании пользователя.

Статус: **PASS (STATIC) / TARGET MYSQL PENDING**.

## 6. Repository review

Статически подтверждено:

- repository не содержит методов изменения данных;
- текущая версия должна определяться однозначно;
- запросы используют prepared statements;
- сортировка всегда выполняется по `sort_order`;
- поиск выполняется по `troop_name` и `naval_name`;
- recursive CTE включает дочерние офицерские составы при фильтре `officers`;
- состав и уровень связываются в пределах одной версии.

Статус: **PASS (STATIC)**.

Runtime-проверка repository включена в CLI checker и ожидает целевую MySQL 8.4.

## 7. UI / route review

Статически подтверждено:

- плитка переименована в `Составы военнослужащих и воинские звания`;
- плитка ведёт на `/admin/directories/military-ranks.php`;
- присутствует `Открыть →`;
- плитка `Подразделения` остаётся disabled;
- новая страница использует `require_permission('system.*.*')`;
- на странице отсутствуют формы POST и mutation routes;
- GET-поиск ограничивается 150 символами;
- неизвестный composition filter сбрасывается;
- официальные ссылки используют `noopener noreferrer`;
- отсутствующее корабельное звание отображается как `—`;
- подключается общий `css/directories.css` текущей темы.

Статус: **PASS (STATIC)**.

## 8. Theme assets

Статически подтверждено:

- `themes/asu-blue/assets/css/directories.css` существует;
- `themes/asu-light-blue/assets/css/directories.css` существует;
- оба assets включены в доверенный `required_assets` реестр;
- DOM/class contract един для обеих тем;
- активная плитка использует существующий hover-контракт.

Статус: **PASS (STATIC)**.

Desktop-визуальная приёмка обеих тем остаётся обязательной.

## 9. CLI checker

Добавлен:

```text
tools/check-military-ranks-directory.php
```

Checker является read-only и проверяет:

- регистрацию migration 007;
- пять таблиц;
- одну текущую версию;
- два источника;
- шесть составов;
- двадцать пар и порядок;
- repository search и filters;
- 19 системных permissions;
- assets обеих тем.

PHP syntax checker: **PASS**.

Runtime checker: **PENDING TARGET DATABASE**.

## 10. Scope / security

Подтверждено:

- новые permissions не добавляются;
- ожидаемое количество системных permissions остаётся 19;
- CRUD отсутствует;
- присвоение званий военнослужащим отсутствует;
- кадровая история отсутствует;
- модификаторы полного наименования отсутствуют;
- CSRF не требуется для read-only GET-интерфейса;
- пользовательский ввод не включается в SQL как SQL-фрагмент.

Статус: **PASS (STATIC)**.

## 11. Оставшиеся обязательные проверки

В целевой среде необходимо выполнить:

1. синхронизацию feature-ветки;
2. PHP lint всех изменённых PHP-файлов на PHP 8.5.4;
3. deploy runtime-файлов, migration, CSS и config;
4. `php database\install.php`;
5. повторный installer с результатом `Новых миграций нет`;
6. `php tools\check-military-ranks-directory.php`;
7. существующие RBAC regression checks;
8. owner navigation/runtime smoke;
9. тематическую HTTP 403 для не-владельца;
10. поиск и все фильтры;
11. desktop-приёмку обеих тем.

## 12. Мобильное тестирование

Мобильное тестирование исключено из области работ и не выполнялось. Нельзя заявлять, что мобильная версия проверена.

## 13. Предварительный итог

- Architecture / Specification: **APPROVED**;
- Formal Review: **PASS**;
- Approval: **RECORDED**;
- normative static review: **PASS**;
- PHP syntax доступных файлов: **PASS**;
- migration static review: **PASS**;
- repository / UI / RBAC static review: **PASS**;
- target PHP 8.5.4 full lint: **PENDING**;
- target MySQL 8.4 migration: **PENDING**;
- CLI integration checker: **PENDING RUNTIME**;
- desktop-приёмка обеих тем: **PENDING**;
- мобильное тестирование: **OUT OF SCOPE / NOT RUN**.

Merge до завершения целевых runtime-проверок, пользовательской desktop-приёмки и отдельного явного разрешения запрещён.
