# Справочник типов организационных элементов v1 — Test Report

## 1. Объект проверки

Инкремент:

```text
Справочник типов организационных элементов v1
```

База, ветка и проверенный GitHub HEAD:

```text
main @ a1d29cac1652481dd844b325945abef2522ea630
feature/organizational-element-types-directory
GitHub HEAD @ 2e16450cef7f5cb0993b8838018b652b3059b1e6
```

Дата финализации отчёта: `2026-07-27`.

## 2. Утверждённая область

Проверены:

```text
database/install.php
database/migrations/008_organizational_element_types_directory.sql
app/bootstrap.php
app/Directory/OrganizationalElementCatalogRepository.php
public/admin/directories.php
public/admin/directories/organizational-elements.php
themes/asu-blue/assets/css/directories.css
themes/asu-light-blue/assets/css/directories.css
tools/check-organizational-elements-directory.php
```

Документация:

```text
docs/design/ORGANIZATIONAL-ELEMENT-TYPES-V1-DESIGN.md
docs/design/ORGANIZATIONAL-ELEMENT-TYPES-V1-REVIEW.md
docs/decisions/ORGANIZATIONAL-ELEMENT-TYPES-V1-APPROVAL.md
docs/testing/ORGANIZATIONAL-ELEMENT-TYPES-V1-TEST-REPORT.md
```

## 3. Целевая среда

```text
Open Server Panel 6.5.1
Apache
PHP 8.5.4
MySQL 8.4.8
Windows / PowerShell 5.1
Deploy root: C:\OSPanel\home\asu-vch.local
```

Мобильное тестирование исключено из области работ и не выполнялось.

## 4. Контрольная модель

Подтверждено:

```text
новых таблиц: 7
источников версии: 4
организационных классов: 6
типов: 28
связей тип–класс: 32
aliases: 0
non_subdivision_only: 12
subdivision_only: 12
mixed: 4
системных permissions: 19
```

Типы `смена` и `боевой пост` отсутствуют.

Официальное сокращение:

```text
боевая часть — БЧ
```

## 5. Резервное копирование

До первого применения migration 008 создан SQL backup:

```text
C:\Project\Backups\ASU-VCH\asu_vch-before-migration-008-20260727-200613.sql
размер: 34063 байт
SHA-256: 5E77E817A06903609390BB338AF001A25FA009D115EDCCDFA3CC4E6AF240C99D
```

Также создан backup deploy-файлов:

```text
C:\Project\Backups\ASU-VCH\deploy-files-before-migration-008-20260727-200613
```

После частичного отказа создан повторный SQL backup:

```text
C:\Project\Backups\ASU-VCH\asu_vch-before-migration-008-retry-20260727-204735.sql
размер: 50413 байт
SHA-256: 2C1080A508C6C2BA5C19C9AD8013F7DE02CB3EFC3310515BA8E34F9DD55B96CF
```

И повторный backup deploy-файлов:

```text
C:\Project\Backups\ASU-VCH\deploy-files-before-migration-008-retry-20260727-204735
```

Перед финальной синхронизацией factory-исправления создан backup:

```text
C:\Project\Backups\ASU-VCH\remote-factory-sync-20260727-221110
```

Локальный технический дубликат commit сохранён вне репозитория:

```text
C:\Project\Backups\ASU-VCH\remote-factory-sync-20260727-221110\discarded-local-duplicate.patch
```

После этого локальная ветка приведена к GitHub HEAD. Локальный дубликат не отправлялся в GitHub.

## 6. Migration 008

### 6.1 Первый запуск

Первый запуск остановился на сравнении строк с разными connection collations:

```text
Illegal mix of collations
utf8mb4_unicode_ci
utf8mb4_general_ci
```

Migration не была зарегистрирована, но часть объектов могла быть создана.

### 6.2 Коррекция

В `database/install.php` соединение installer приведено к:

```sql
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci
```

### 6.3 Повторный запуск

Повторное применение после частичного отказа завершилось успешно:

```text
Применена миграция: 008_organizational_element_types_directory.sql
```

Контрольный повтор installer:

```text
Применено миграций: 8
Новых миграций нет.
```

Статус migration 008: **PASS**.

## 7. Architecture conformance correction

Утверждённая Specification требует factory-функцию репозитория в `app/bootstrap.php`.

На GitHub выполнено:

- подключение `OrganizationalElementCatalogRepository` в bootstrap;
- добавление `organizational_element_catalog_repository()`;
- перевод страницы с прямого конструктора на factory;
- добавление архитектурных проверок в CLI checker.

Проверенный runtime smoke test:

```text
BOOTSTRAP FACTORY SMOKE PASSED
```

Integration checker дополнительно вернул:

```text
OK bootstrap factory
```

Статус architecture conformance: **PASS**.

## 8. PHP lint, deploy и целостность файлов

PHP lint пройден для GitHub-исходников и deploy-копий:

```text
app/bootstrap.php
app/Directory/OrganizationalElementCatalogRepository.php
public/admin/directories.php
public/admin/directories/organizational-elements.php
database/install.php
tools/check-organizational-elements-directory.php
```

Deploy выполнен из GitHub feature-ветки.

SHA-256 исходников и deploy-копий совпал для всех публикуемых файлов.

`config/local.php` сохранён без изменений.

Финальная синхронизация:

```text
Local HEAD: 2e16450cef7f5cb0993b8838018b652b3059b1e6
GitHub HEAD: 2e16450cef7f5cb0993b8838018b652b3059b1e6
Расхождение local/remote: 0/0
Рабочее дерево: чистое
```

Статус deploy и целостности: **PASS**.

## 9. CLI integration checker

Фактический финал:

```text
OK bootstrap factory
OK migration 008
OK tables: 7
OK schema constraints
OK current catalog version
OK legal sources: 4
OK organizational classes: 6
OK organizational element types: 28
OK type-class links: 32
OK class distribution
OK organizational scopes: 12/12/4
OK repository search and filters
OK system permissions: 19
OK theme assets: 2
ORGANIZATIONAL ELEMENT TYPES DIRECTORY CHECK PASSED
```

Статус: **PASS**.

## 10. Регрессия справочника воинских званий

Фактический финал:

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

Дополнительно в desktop UI подтверждено отображение 20 строк.

Статус: **PASS**.

## 11. HTTP assets

Опубликованные CSS обеих тем доступны:

```text
asu-blue — HTTP 200, 7612 символов
asu-light-blue — HTTP 200, 7391 символов
```

Статус: **PASS**.

## 12. Desktop-приёмка владельца

По пользовательской приёмке подтверждены:

- активная плитка `Организационные элементы и подразделения`;
- маршрут `/admin/directories/organizational-elements.php`;
- заголовок, предупреждение о границах данных и badge `Только чтение`;
- одна текущая версия и четыре официальных источника;
- полный каталог: 28 типов;
- отсутствие CRUD и mutation controls;
- корректный empty state;
- тёмная desktop-тема `АСУ Синяя`;
- светлая desktop-тема `АСУ Светлая синяя`.

Поиск:

```text
батальон → 1
БЧ → 1
боев → 1
```

Фильтры классов:

```text
Объединение → 1
Соединение → 3
Воинская часть → 7
Организация → 3
Подразделение → 16
```

Совместные фильтры:

```text
батальон + Воинская часть → 1
батальон + Организация → 0
```

Организационные статусы:

```text
Не является подразделением → 12
Только подразделение → 12
Зависит от утверждённой структуры → 4
```

Статус desktop-приёмки владельца: **PASS**.

## 13. Проверка не-владельца

Подтверждена тематическая страница:

```text
Доступ запрещен
```

Route использует `require_permission('system.*.*')`, а общий authorization helper устанавливает HTTP 403 перед выводом тематической страницы.

Данные справочника пользователю без разрешения не отображаются.

Статус: **PASS**.

## 14. Границы проверки

Не выполнялись и не заявляются:

- мобильное тестирование;
- CRUD типов;
- реальные воинские части и подразделения;
- фактическая структура и подчинённость;
- численность, вооружение и дислокация;
- закрытые или ограниченные документы.

## 15. Финальный статус

```text
Architecture: APPROVED / CONFORMANT
Specification v0.2: APPROVED / CONFORMANT
Formal Review: PASS
Approval: RECORDED
Implementation: PASS
Migration 008: PASS
Automated target runtime testing: PASS
Desktop acceptance: PASS
Dark desktop theme: PASS
Light desktop theme: PASS
Non-owner access denial: PASS
Military ranks regression: PASS
Mobile testing: NOT RUN / OUT OF SCOPE
GitHub/local synchronization: PASS
Ready for review: YES
Merge: PROHIBITED UNTIL SEPARATE EXPLICIT APPROVAL
```

Итог Test Report: **PASS**.
