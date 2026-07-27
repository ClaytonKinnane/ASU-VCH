# Справочник типов организационных элементов v1 — Test Report

## 1. Объект проверки

Инкремент:

```text
Справочник типов организационных элементов v1
```

База и ветка:

```text
main @ a1d29cac1652481dd844b325945abef2522ea630
feature/organizational-element-types-directory
```

Дата подготовки предварительного отчёта: `2026-07-27`.

## 2. Утверждённая область

Проверке подлежат:

```text
database/migrations/008_organizational_element_types_directory.sql
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
```

## 3. Контрольная модель

Ожидается:

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

Типы `смена` и `боевой пост` не должны присутствовать.

Официальное сокращение:

```text
боевая часть — БЧ
```

## 4. Обязательные проверки целевой среды

На локальной среде пользователя необходимо выполнить:

1. синхронизацию feature-ветки;
2. создание SQL backup перед migration 008;
3. резервное копирование изменяемых deploy-файлов;
4. PHP lint новых и изменённых PHP-файлов на PHP 8.5.4;
5. deploy с сохранением `config/local.php`;
6. публикацию CSS в `themes` и `public/themes`;
7. проверку SHA-256 исходных и deploy-файлов;
8. применение migration 008 на MySQL 8.4;
9. повторный installer с результатом `Новых миграций нет`;
10. запуск `tools/check-organizational-elements-directory.php`;
11. повторный запуск checker справочника воинских званий;
12. проверку owner navigation;
13. тематическую HTTP 403 для не-владельца;
14. поиск и все фильтры;
15. desktop-приёмку обеих тем.

## 5. CLI checker

Ожидаемый финал:

```text
ORGANIZATIONAL ELEMENT TYPES DIRECTORY CHECK PASSED
```

Checker проверяет:

- migration 008;
- семь таблиц и ключевые ограничения;
- одну текущую версию;
- четыре источника;
- шесть классов;
- точные 28 типов;
- 32 связи и ровно один основной класс каждого типа;
- наличие источника у каждого типа;
- распределение классов и вычисляемых статусов;
- поиск и совместную фильтрацию;
- 19 системных permissions;
- исходные и опубликованные CSS обеих тем через `ThemeRegistry`.

## 6. Desktop-приёмка

### Владелец

Проверить:

- активную плитку `Организационные элементы и подразделения`;
- маршрут `/admin/directories/organizational-elements.php`;
- предупреждение о границах справочника;
- четыре источника;
- 28 типов;
- шесть классов;
- поиск `батальон`, `БЧ`, `боев`;
- фильтры классов `1 / 3 / 7 / 3 / 16` для соответствующих классов;
- статусы `12 / 12 / 4`;
- поиск вместе с фильтрами;
- empty state;
- отсутствие CRUD и mutation controls;
- обе desktop-темы.

### Не-владелец

Прямой запрос должен вернуть:

```text
403 Forbidden
```

и существующую тематическую страницу `Доступ запрещен`.

## 7. Регрессия

Обязательно подтвердить:

- справочник воинских званий содержит 20 строк;
- migration 007 остаётся зарегистрированной;
- `tools/check-military-ranks-directory.php` проходит;
- обе плитки справочников активны;
- количество системных permissions остаётся 19;
- страницы справочников полностью отображаются в обеих темах.

## 8. Мобильное тестирование

Мобильное тестирование исключено из области работ и не выполняется.

Нельзя заявлять, что мобильная версия проверена.

## 9. Предварительный статус

```text
Architecture: APPROVED
Specification v0.2: APPROVED
Formal Review: PASS
Approval: RECORDED
Implementation: IN PROGRESS
Target runtime testing: PENDING
Desktop acceptance: PENDING
Merge: PROHIBITED
```

Финальный статус будет заполнен только после фактических проверок в целевой среде и пользовательской desktop-приёмки.
