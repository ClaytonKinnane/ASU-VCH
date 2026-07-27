# Справочник типов организационных элементов v1 — Formal Review

## 1. Объект review

Проверены:

```text
docs/design/ORGANIZATIONAL-ELEMENT-TYPES-V1-DESIGN.md
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

База, ветка и финально проверенный runtime HEAD:

```text
main @ a1d29cac1652481dd844b325945abef2522ea630
feature/organizational-element-types-directory
runtime-tested GitHub HEAD @ 2e16450cef7f5cb0993b8838018b652b3059b1e6
```

Документационные изменения после runtime-проверки не изменяют исполняемый код.

## 2. Нормативный review

### 2.1 Общие организационные категории

Федеральный закон № 61-ФЗ подтверждает общие категории органов военного управления, объединений, соединений, воинских частей и организаций.

Статус: **PASS**.

### 2.2 Официальное употребление типов

Положение о порядке прохождения военной службы, Устав внутренней службы и Корабельный устав дают достаточную открытую доказательную базу для первоначального каталога.

Для каждого типа сохраняется различие между ролями источника:

```text
definition
classification
official-usage
authority-rule
historical-context
```

Статус: **PASS**.

### 2.3 Боевой пост

`Боевой пост` исключён из справочника, поскольку Корабельный устав определяет его как место на корабле, а не как организационное подразделение.

Статус: **CORRECTED / PASS**.

### 2.4 Смена

`Смена` исключена из первоначального каталога как временная или функциональная форма, не подтверждённая для v1 как устойчивый штатный тип подразделения.

Статус: **CORRECTED / PASS**.

### 2.5 Боевая часть

`Боевая часть` сохраняется как элемент корабельной организации. Официальное сокращение `БЧ` хранится в `short_name`.

Статус: **PASS**.

## 3. Architecture review

### 3.1 Разделение типа и экземпляра

Каталог не смешивает тип организационного элемента с конкретной воинской частью или подразделением.

Статус: **PASS**.

### 3.2 Отсутствие универсального дерева

Поле `parent_id` у типов отсутствует. Это предотвращает ложное представление о единой обязательной иерархии всех частей.

Статус: **PASS**.

### 3.3 Несколько классов

Связь многие-ко-многим корректно поддерживает контекстную классификацию управления, штаба, батальона и дивизиона.

Статус: **PASS**.

### 3.4 Будущая совместимость

Принятая модель позволяет позже добавлять конкретные структуры, отношения подчинённости, документы-основания и шаблоны без изменения семантики справочника.

Статус: **PASS**.

### 3.5 Bootstrap factory

Утверждённая архитектура требует factory-функцию репозитория в `app/bootstrap.php`.

Финальная реализация содержит:

```text
require_once __DIR__ . '/Directory/OrganizationalElementCatalogRepository.php';
organizational_element_catalog_repository(): OrganizationalElementCatalogRepository
```

Страница использует factory и не подключает класс напрямую.

CLI checker контролирует:

- наличие require в bootstrap;
- наличие factory-функции;
- использование factory страницей;
- отсутствие прямого конструктора;
- отсутствие прямого require на странице.

Runtime smoke test подтвердил создание и повторное использование экземпляра repository.

Статус: **CORRECTED / PASS**.

## 4. Database review

### 4.1 Версионирование

Каталог версионируется целиком. Generated column `current_guard` и UNIQUE-индекс обеспечивают не более одной текущей версии.

Наличие ровно одной текущей версии дополнительно проверяется repository и checker.

Статус: **PASS**.

### 4.2 Основной класс

Generated column `primary_guard` и UNIQUE-индекс обеспечивают не более одного основного класса для типа.

Наличие минимум одного и ровно одного основного класса проверяется checker.

Статус: **PASS**.

### 4.3 Целостность версии

Композитные внешние ключи гарантируют:

- принадлежность типа и класса одной версии;
- принадлежность alias и типа одной версии;
- принадлежность источника типа к источникам той же версии;
- принадлежность источника alias к источникам той же версии.

Статус: **PASS**.

### 4.4 Aliases

Глобальная уникальность alias внутри версии отклонена как чрезмерная.

Принято:

```text
UNIQUE(catalog_version_id, type_id, alias_type, alias)
```

Статус: **CORRECTED / PASS**.

### 4.5 CHECK constraints

Реализованы CHECK для:

- boolean-полей;
- дат;
- ролей источников;
- видов aliases;
- положительного `sort_order`;
- непустых текстовых значений.

Статус: **PASS**.

### 4.6 Идемпотентность migration

Migration 008 безопасно повторена после частичного отказа до регистрации migration:

- использованы `CREATE TABLE IF NOT EXISTS`;
- upsert выполняется по стабильным кодам;
- фиксированные числовые идентификаторы не используются;
- повторное выполнение не создало дубликатов связей.

Фактическая проверка:

```text
первый запуск: остановлен из-за connection collation
повторный запуск после исправления installer: PASS
контрольный повтор: Новых миграций нет
```

Статус: **PASS**.

### 4.7 Connection collation

Первый запуск выявил различие `utf8mb4_unicode_ci` и `utf8mb4_general_ci` в соединении installer и JSON seed.

В `database/install.php` добавлено:

```sql
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci
```

После исправления migration 008 и повторный installer прошли.

Статус: **CORRECTED / PASS**.

## 5. Классификационное состояние

Поле `independence_mode` удалено как дублирующее набор связанных классов.

Организационный статус вычисляется:

```text
non_subdivision_only
subdivision_only
mixed
```

Это исключает рассинхронизацию между типом и его классами.

Статус: **CORRECTED / PASS**.

## 6. Seed review

Подтверждено:

```text
источников версии: 4
классов: 6
типов: 28
связей тип–класс: 32
aliases: 0
```

Вычисляемые статусы:

```text
non_subdivision_only: 12
subdivision_only: 12
mixed: 4
```

Коды уточнены:

```text
battalion-division → divizion
weapon-team → raschet
```

Статус: **PASS**.

## 7. Repository review

Repository:

- остаётся read-only;
- использует prepared statements;
- не принимает SQL-фрагменты из пользовательского ввода;
- ограничивает поиск 150 символами на уровне route;
- ищет буквально по `name`, `short_name` и aliases;
- не создаёт `IN ()` для пустых наборов;
- не выполняет N+1-запросы;
- не дублирует типы при JOIN;
- сортирует по `sort_order`, затем `id`;
- требует ровно одну текущую версию.

Integration checker подтвердил поиск, фильтры и агрегацию связанных данных.

Статус: **PASS**.

## 8. RBAC / security review

Подтверждено:

- доступ владельца через `require_permission('system.*.*')`;
- тематический HTTP 403 для не-владельца;
- новые permissions отсутствуют;
- количество системных permissions остаётся 19;
- GET-фильтры не требуют CSRF;
- пользовательский вывод экранируется;
- официальные ссылки используют `noopener noreferrer`;
- CRUD и mutation routes отсутствуют;
- закрытые и фактические сведения не входят в scope.

Пользовательская desktop-приёмка подтвердила тематическую страницу `Доступ запрещен` и отсутствие данных справочника.

Статус: **PASS**.

## 9. UI review

### 9.1 Навигация

```text
Контент → Справочники → Типы организационных элементов
```

Статус: **PASS**.

### 9.2 Плитка

Плитка называется:

```text
Организационные элементы и подразделения
```

Она активна и содержит действие `Открыть →`.

Статус: **PASS**.

### 9.3 Предупреждение

Страница сообщает, что каталог не является утверждённым штатом или структурой конкретной части.

Статус: **PASS**.

### 9.4 Themes

Используется существующий `css/directories.css`. Checker проверяет опубликованный asset через `ThemeRegistry::assetUrl()`.

Пользовательская desktop-приёмка выполнена в темах:

```text
АСУ Синяя
АСУ Светлая синяя
```

Статус: **PASS**.

### 9.5 Поиск и фильтры

Desktop-приёмка и checker подтвердили:

```text
батальон → 1
БЧ → 1
боев → 1
Объединение → 1
Соединение → 3
Воинская часть → 7
Организация → 3
Подразделение → 16
non_subdivision_only → 12
subdivision_only → 12
mixed → 4
батальон + military-unit → 1
батальон + organization → 0
```

Empty state отображается штатно.

Статус: **PASS**.

## 10. Target runtime review

Фактически выполнены:

1. SQL backup до migration 008.
2. Backup deploy-файлов.
3. PHP lint на PHP 8.5.4.
4. Deploy с сохранением `config/local.php`.
5. SHA-256 source/deploy.
6. Migration 008 на MySQL 8.4.8.
7. Контрольный installer с `Новых миграций нет`.
8. Runtime smoke test bootstrap factory.
9. CLI checker нового справочника.
10. Регрессионный checker справочника воинских званий.
11. HTTP 200 опубликованных CSS обеих тем.
12. Desktop-приёмка владельца.
13. Проверка тематического запрета доступа не-владельца.
14. Синхронизация локальной ветки с GitHub HEAD и чистое рабочее дерево.

Финальные маркеры:

```text
BOOTSTRAP FACTORY SMOKE PASSED
ORGANIZATIONAL ELEMENT TYPES DIRECTORY CHECK PASSED
MILITARY RANKS DIRECTORY CHECK PASSED
```

Статус: **PASS**.

## 11. Regression review

Подтверждено:

- migration 007 зарегистрирована;
- справочник званий содержит 20 нормативных пар;
- checker справочника званий проходит;
- обе плитки справочников активны;
- системных permissions по-прежнему 19;
- обе desktop-темы отображают страницы справочников.

Статус: **PASS**.

## 12. Process conformance

Изменения исполняемого кода финальной correction внесены в feature-ветку через GitHub.

Локальная среда использована только для:

- fetch и синхронизации;
- backup;
- deploy;
- lint;
- installer;
- checker;
- runtime и desktop-приёмки.

Ошибочно созданный ранее локальный commit не был отправлен, сохранён в patch-файл и удалён при синхронизации с GitHub HEAD.

Финальное состояние:

```text
Local HEAD = GitHub HEAD
2e16450cef7f5cb0993b8838018b652b3059b1e6
расхождение: 0/0
рабочее дерево: чистое
```

Статус: **CORRECTED / PASS**.

## 13. Ограничения review

Не выполнялись и не заявляются:

- мобильное тестирование;
- CRUD типов;
- проверка реальных организационных структур;
- проверка закрытых или ограниченных сведений.

## 14. Итог Formal Review

```text
Normative review: PASS
Architecture review: PASS
Database review: PASS
Seed review: PASS
Repository review: PASS
RBAC/security review: PASS
UI review: PASS
Target runtime review: PASS
Regression review: PASS
Process conformance: PASS
Mobile testing: NOT RUN / OUT OF SCOPE
```

Итог: **PASS**.

Блокирующих замечаний для перевода PR в Ready for review нет.

Merge остаётся запрещён до отдельного явного разрешения заказчика.
