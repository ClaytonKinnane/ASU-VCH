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

Проверенная runtime-ревизия:

```text
7a136007a82edd2c59bf15d0910e1a5f577a96a4
```

Дата финальной desktop-приёмки: `2026-07-27`.

## 2. Целевая среда

```text
Windows 10
PowerShell 5.1
Open Server Panel 6.5.1
Apache
PHP 8.5.4
MySQL 8.4.8
локальный домен: https://asu-vch.local
локальное развёртывание: C:\OSPanel\home\asu-vch.local
```

Мобильное тестирование исключено из области работ и не выполнялось.

## 3. Проверенная область

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

## 4. Локальный checkout

Локальный репозиторий:

```text
C:\Project\ASU-VCH
```

Подтверждено:

```text
branch: feature/military-ranks-directory
tracking: origin/feature/military-ranks-directory
pull --ff-only: PASS
working tree: clean
tested HEAD: 7a136007a82edd2c59bf15d0910e1a5f577a96a4
```

Статус: **PASS**.

## 5. Резервное копирование

До применения migration создан SQL dump:

```text
C:\Project\Backups\ASU-VCH\asu_vch-before-migration-007-20260727-155004.sql
```

Параметры:

```text
размер: 22061 байт
SHA-256: C7BE1F009EC458CA691D278A83CD91BA9188CB7DB8FBB84C09862EB02F7FA0D4
mysqldump: MySQL 8.4.8
```

Также сохранены существовавшие deploy-файлы:

```text
C:\Project\Backups\ASU-VCH\deploy-files-before-migration-007-20260727-155004
```

Перед исправлением публикации CSS создан дополнительный backup:

```text
C:\Project\Backups\ASU-VCH\published-theme-assets-before-fix-20260727-160628
```

Статус: **PASS**.

## 6. PHP syntax

На целевом PHP `8.5.4` проверены:

```text
app/bootstrap.php
app/Directory/MilitaryRankCatalogRepository.php
config/themes.php
public/admin/directories.php
public/admin/directories/military-ranks.php
tools/check-military-ranks-directory.php
```

Проверка выполнена как в repository, так и в deploy-копии.

Результат для каждого файла:

```text
No syntax errors detected
```

После исправления checker также повторно прошёл lint на PHP `8.5.4`.

Статус: **PASS**.

## 7. Deploy и контроль целостности

В развёртывание переданы runtime-файлы, migration, config, checker и CSS обеих тем.

Для всех девяти файлов исходной поставки подтверждено совпадение SHA-256 между:

```text
C:\Project\ASU-VCH
C:\OSPanel\home\asu-vch.local
```

`config/local.php` не изменялся.

После выявления отсутствующих опубликованных assets CSS обеих тем дополнительно размещён в:

```text
public/themes/asu-blue/assets/css/directories.css
public/themes/asu-light-blue/assets/css/directories.css
```

HTTP-проверка опубликованных ресурсов:

```text
asu-blue: HTTP 200, 4856 байт
asu-light-blue: HTTP 200, 4796 байт
```

Статус: **PASS**.

## 8. Database migration

На MySQL `8.4.8` применена:

```text
007_military_ranks_directory.sql
```

После первого запуска подтверждено:

```text
всего migrations: 7
migration 007 зарегистрирована: 1
legal_sources: 2
military_rank_catalog_versions: 1
military_personnel_compositions: 6
military_rank_levels: 20
```

Повторный запуск installer не изменил количество migrations и не создал дубликаты нормативных данных.

Статус migration: **PASS**.

Статус повторного installer: **PASS**.

## 9. CLI integration checker

Выполнен:

```text
php tools\check-military-ranks-directory.php
```

Финальный результат:

```text
OK migration 007
OK tables: 5
OK current catalog version
OK legal sources: 2
OK compositions: 6
OK normative rank pairs: 20
OK repository search and filters
OK system permissions: 19
OK theme assets: 2
MILITARY RANKS DIRECTORY CHECK PASSED
```

Checker подтверждает:

- регистрацию migration 007;
- наличие пяти таблиц;
- одну текущую версию каталога;
- два нормативных источника;
- шесть составов;
- двадцать нормативных уровней и порядок;
- работу repository search и filters;
- неизменность количества системных permissions — `19`;
- наличие исходных и реально опубликованных CSS обеих тем.

Статус: **PASS**.

## 10. Нормативная сверка

Runtime-проверкой и desktop-приёмкой подтверждены:

- 20 уровней воинских званий;
- пара `младший сержант — старшина 2 статьи`;
- исправленная пара `сержант — старшина 1 статьи`;
- отсутствие ошибочной пары `сержант — старшина 2 статьи`;
- `Маршал Российской Федерации` без корабельного эквивалента, отображаемого как `—`;
- нормативный порядок строк от 1 до 20;
- два нормативных источника;
- дата проверки `2026-07-27`.

Источники версии:

```text
Федеральный закон от 28.03.1998 № 53-ФЗ, статья 46
Указ Президента Российской Федерации от 16.09.1999 № 1237,
статья 20 Положения о порядке прохождения военной службы
```

Статус: **PASS**.

## 11. Выявленный и исправленный дефект

### D-01 — CSS справочников не опубликован в активный каталог ThemeRegistry

Первоначальное runtime-поведение:

- `/admin/directories.php` отображал только фон без содержимого;
- PHP log содержал `RuntimeException: Ресурс темы недоступен`;
- ошибка возникала при вызове `theme_asset('css/directories.css')`.

Причина:

- при наличии `public/themes` ThemeRegistry использует этот каталог как активный `assetRoot`;
- первый выборочный deploy разместил новые CSS только в `themes/...`;
- опубликованные файлы `public/themes/.../css/directories.css` отсутствовали.

Исправление локального развёртывания:

- CSS обеих тем опубликован в `public/themes/...`;
- SHA-256 исходных, deploy-source и published assets совпал;
- оба CSS возвращают HTTP `200`;
- страницы справочников отображаются полностью.

Предотвращение регрессии:

- checker дополнен созданием `ThemeRegistry`;
- checker вызывает `assetUrl()` для `css/directories.css` обеих тем;
- отсутствие реально опубликованного ресурса теперь завершает checker ошибкой.

Статус D-01: **FIXED / PASS**.

## 12. Desktop-приёмка страницы справочников

Пользователь предоставил desktop-скриншоты.

Подтверждено:

- страница `/admin/directories.php` отображается полностью;
- плитка называется `Составы военнослужащих и воинские звания`;
- плитка активна и содержит `Открыть →`;
- плитка `Подразделения` остаётся в статусе `В разработке`;
- переход на `/admin/directories/military-ranks.php` работает;
- отображаются заголовок, read-only badge, версия, даты и два источника;
- основной список без фильтра содержит 20 строк;
- длинные наименования не нарушают desktop layout.

Статус: **PASS**.

## 13. Фильтры

Пользователь проверил все составы:

```text
Солдаты, матросы, сержанты, старшины: 6
Прапорщики и мичманы: 2
Офицеры (все): 12
Младшие офицеры: 4
Старшие офицеры: 3
Высшие офицеры: 5
```

Родительский фильтр `Офицеры` включает младших, старших и высших офицеров.

Нормативный порядок строк сохраняется.

Статус: **PASS**.

## 14. Поиск и совместная фильтрация

Подтверждено:

```text
старшина 1 статьи: 1 строка, сержант — старшина 1 статьи
Маршал: 1 строка, корабельное звание —
адмирал: 4 строки
несуществующее звание: 0 строк и корректное сообщение
```

Совместная работа поиска и фильтра:

```text
адмирал + Высшие офицеры: 4 строки
адмирал + Младшие офицеры: 0 строк
```

GET-параметры сохраняют выбранные значения, ошибок PHP/SQL/HTTP 500 не выявлено.

Статус: **PASS**.

## 15. Read-only и безопасность

Подтверждено:

- отсутствуют create/edit/delete/save controls;
- отсутствуют mutation routes и POST-формы;
- доступны только просмотр, GET-поиск, фильтрация и ссылки;
- отсутствующее корабельное звание выводится безопасным прочерком;
- пользовательский ввод обрабатывается prepared statements;
- новые permissions отсутствуют;
- количество системных permissions остаётся `19`.

Статус: **PASS**.

## 16. RBAC / тематическая 403

Владелец открывает страницу справочника.

Не-владелец при прямом запросе:

```text
GET /admin/directories/military-ranks.php
```

получает:

```text
403 Forbidden
```

Edge DevTools Network подтвердил HTTP status `403`, а браузер отобразил тематическую страницу `Доступ запрещен` с кнопкой возврата к панели.

Redirect `302` и доступ с `200` для не-владельца отсутствуют.

Статус runtime RBAC: **PASS**.

## 17. Темы

### `АСУ Синяя`

- страница справочников: **PASS**;
- страница каталога: **PASS**;
- таблица и фильтры: **PASS**;
- поиск и empty state: **PASS**;
- read-only presentation: **PASS**.

### `АСУ Светлая синяя`

- страница каталога: **PASS**;
- таблица из 20 строк: **PASS**;
- фильтры: **PASS**;
- поиск и empty state: **PASS**;
- длинные названия и нормативные блоки: **PASS**.

Итог desktop-приёмки обеих тем: **PASS**.

## 18. Мобильное тестирование

Мобильное тестирование исключено из области работ и не выполнялось.

Нельзя заявлять, что мобильная версия проверена.

Статус: **OUT OF SCOPE / NOT RUN**.

## 19. Итог

- Architecture / Specification: **APPROVED**;
- Formal Review: **PASS**;
- Approval: **RECORDED**;
- local checkout: **PASS**;
- backup: **PASS**;
- target PHP 8.5.4 full lint: **PASS**;
- deploy и SHA-256: **PASS**;
- target MySQL 8.4.8 migration: **PASS**;
- repeat installer: **PASS**;
- CLI integration checker: **PASS**;
- normative data: **PASS**;
- D-01 published theme assets: **FIXED / PASS**;
- owner navigation: **PASS**;
- filters: **PASS**;
- search и combined filter: **PASS**;
- read-only/security: **PASS**;
- thematic HTTP 403: **PASS**;
- desktop-приёмка `АСУ Синяя`: **PASS**;
- desktop-приёмка `АСУ Светлая синяя`: **PASS**;
- мобильное тестирование: **OUT OF SCOPE / NOT RUN**.

Инкремент готов к переводу PR в ready for review.

Merge разрешается только после отдельной явной команды заказчика. Ветка не удаляется без отдельного разрешения.