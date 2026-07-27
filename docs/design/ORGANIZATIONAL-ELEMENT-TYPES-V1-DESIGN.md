# Справочник типов организационных элементов v1 — Architecture / Specification

## 1. Статус документа

- Проект: `АСУ-ВЧ`
- Инкремент: `Справочник типов организационных элементов v1`
- Ветка: `feature/organizational-element-types-directory`
- База: `main @ a1d29cac1652481dd844b325945abef2522ea630`
- Architecture: утверждена заказчиком
- Specification: версия `0.2`, утверждена заказчиком после Formal Review
- Дата утверждения: `2026-07-27`

## 2. Исследовательский вывод

Открытые нормативные источники подтверждают общие организационные категории Вооружённых Сил Российской Федерации, но не устанавливают единую детальную организационную схему для каждой воинской части.

Организационная структура конкретной воинской части определяется утверждённым штатом и организационно-распорядительными документами. Поэтому в АСУ-ВЧ необходимо разделять:

```text
тип организационного элемента
конкретный элемент структуры
структурное включение
отношение подчинённости
утверждённый штат
```

Жёсткая универсальная иерархия вида `дивизия → полк → батальон → рота` не принимается как обязательное правило системы.

## 3. Цель инкремента

Добавить системный read-only нормативно-методический каталог общих типов организационных элементов.

Справочник отвечает на вопрос:

> Какие общие типы органов военного управления, объединений, соединений, воинских частей, организаций и подразделений могут использоваться в будущей организационной структуре?

Справочник не содержит:

- фактические воинские части и подразделения;
- действительные номера и условные наименования;
- дислокацию;
- численность;
- вооружение и технику;
- фактическую подчинённость;
- утверждённые штаты;
- документы ограниченного доступа.

## 4. Архитектурные принципы

### 4.1 Разделение типа и экземпляра

```text
Тип организационного элемента
≠
конкретное подразделение
≠
утверждённый штат
≠
отношение подчинённости
```

Пример:

```text
Тип: батальон
Возможный класс: подразделение / воинская часть
Конкретный элемент: 1-й батальон конкретной части
```

В v1 хранится только тип и допустимые варианты его классификации.

### 4.2 Несколько классов для одного типа

Один тип может относиться к нескольким организационным классам в зависимости от нормативного контекста.

Связь типа и класса моделируется как многие-ко-многим.

### 4.3 Отсутствие жёсткой иерархии

В типах отсутствует `parent_id`.

В v1 не создаются универсальные правила:

```text
бригада обязательно содержит полки
полк обязательно содержит батальоны
батальон обязательно содержит роты
```

Такие связи относятся к будущим конкретным структурам или необязательным шаблонам.

### 4.4 Версионирование целиком

Каждая проверенная редакция каталога хранится как отдельная версия.

- текущей является ровно одна версия;
- предыдущая версия сохраняется;
- исторические строки не удаляются;
- новая редакция создаётся отдельной версией.

### 4.5 Read-only v1

В v1 отсутствуют:

- POST-маршруты;
- mutation services;
- формы создания, изменения и удаления;
- ручное изменение нормативно-методических данных.

## 5. Интерфейс

### 5.1 Плитка

Название:

```text
Организационные элементы и подразделения
```

Описание:

```text
Классификатор типов органов военного управления,
объединений, соединений, воинских частей,
организаций и подразделений.
```

Маршрут:

```text
/admin/directories/organizational-elements.php
```

### 5.2 Страница

Заголовок:

```text
Типы организационных элементов
```

Обязательное предупреждение:

> Справочник содержит общие типы организационных элементов. Он не является утверждённым штатом или организационной структурой конкретной воинской части и не определяет фактическую подчинённость.

Страница содержит:

1. возврат к справочникам;
2. badge `Только чтение`;
3. текущую версию и дату проверки;
4. четыре официальных источника;
5. поиск;
6. фильтр по организационному классу;
7. фильтр по вычисляемому организационному статусу;
8. таблицу типов;
9. empty state.

## 6. RBAC

Используется существующее разрешение:

```php
require_permission('system.*.*');
```

Новые permissions не добавляются.

Ожидаемое количество системных permissions остаётся `19`.

Не-владелец получает тематический HTTP `403 Forbidden`.

## 7. Migration

Добавляется:

```text
database/migrations/008_organizational_element_types_directory.sql
```

Migration зависит от migration 007 и повторно использует общий реестр:

```text
legal_sources
```

Migration не изменяет таблицы справочника воинских званий.

## 8. Таблицы

Добавляются семь таблиц.

### 8.1 `organizational_element_catalog_versions`

Поля:

- `id BIGINT UNSIGNED`;
- `code VARCHAR(120)`;
- `name VARCHAR(255)`;
- `is_current BOOLEAN`;
- `current_guard TINYINT GENERATED`;
- `valid_from DATE`;
- `valid_to DATE NULL`;
- `verified_at DATE`;
- `created_by BIGINT UNSIGNED NULL`;
- `created_at DATETIME`.

Ограничения:

- PK `id`;
- UNIQUE `code`;
- UNIQUE `current_guard`;
- CHECK `is_current IN (0,1)`;
- CHECK периода действия;
- FK `created_by → users.id` с `ON DELETE SET NULL`.

`current_guard`:

```sql
CASE WHEN is_current = 1 THEN 1 ELSE NULL END
```

обеспечивает не более одной текущей версии на уровне БД.

Наличие ровно одной текущей версии дополнительно проверяется repository и checker.

### 8.2 `organizational_element_catalog_version_sources`

Поля:

- `catalog_version_id`;
- `legal_source_id`;
- `source_role VARCHAR(80)`;
- `sort_order SMALLINT UNSIGNED`.

PK:

```text
(catalog_version_id, legal_source_id)
```

Допустимые роли:

```text
general-composition
classification
internal-service
naval-organization
```

### 8.3 `organizational_element_classes`

Поля:

- `id`;
- `catalog_version_id`;
- `code`;
- `name`;
- `description`;
- `sort_order`;
- `created_at`.

Ограничения:

- UNIQUE `(catalog_version_id, code)`;
- UNIQUE `(catalog_version_id, name)`;
- UNIQUE `(catalog_version_id, sort_order)`;
- UNIQUE `(id, catalog_version_id)`;
- непустые строки;
- `sort_order > 0`.

### 8.4 `organizational_element_types`

Поля:

- `id`;
- `catalog_version_id`;
- `code`;
- `name`;
- `short_name NULL`;
- `description`;
- `applicability_note`;
- `sort_order`;
- `created_at`.

Поле `independence_mode` отсутствует. Организационный статус вычисляется по связанным классам.

Ограничения:

- UNIQUE `(catalog_version_id, code)`;
- UNIQUE `(catalog_version_id, name)`;
- UNIQUE `(catalog_version_id, sort_order)`;
- UNIQUE `(id, catalog_version_id)`;
- непустые обязательные строки;
- `sort_order > 0`.

### 8.5 `organizational_element_type_classes`

Поля:

- `catalog_version_id`;
- `type_id`;
- `class_id`;
- `is_primary BOOLEAN`;
- `primary_guard BIGINT UNSIGNED GENERATED`;
- `context_note VARCHAR(1000)`;
- `sort_order SMALLINT UNSIGNED`.

PK:

```text
(type_id, class_id)
```

`primary_guard`:

```sql
CASE WHEN is_primary = 1 THEN type_id ELSE NULL END
```

UNIQUE по `primary_guard` обеспечивает не более одного основного класса на тип.

Композитные FK гарантируют принадлежность типа и класса одной версии.

Бизнес-инварианты checker:

- у каждого типа минимум один класс;
- у каждого типа ровно один основной класс;
- дополнительный класс имеет непустой `context_note`.

### 8.6 `organizational_element_type_aliases`

Поля:

- `id`;
- `catalog_version_id`;
- `type_id`;
- `alias_type`;
- `alias`;
- `legal_source_id`;
- `sort_order`;
- `created_at`.

Допустимые виды:

```text
official-short-name
official-variant
historical-name
search-synonym
```

Уникальность:

```text
(catalog_version_id, type_id, alias_type, alias)
```

Источник alias должен входить в источники той же версии каталога.

В первоначальном наполнении aliases отсутствуют.

### 8.7 `organizational_element_type_sources`

Поля:

- `catalog_version_id`;
- `type_id`;
- `legal_source_id`;
- `source_role`;
- `provision_detail`;
- `sort_order`.

PK:

```text
(type_id, legal_source_id, source_role)
```

Допустимые роли:

```text
definition
classification
official-usage
authority-rule
historical-context
```

Источник типа должен одновременно входить в источники версии.

Для каждого типа обязательна минимум одна связь с источником.

## 9. Первичная версия

```text
code: rf-organizational-elements-2026-07-27
name: Типы организационных элементов Вооружённых Сил Российской Федерации
is_current: 1
valid_from: 2026-07-27
valid_to: NULL
verified_at: 2026-07-27
```

## 10. Источники версии

Добавляются четыре записи в `legal_sources`:

1. `federal-law-61-fz-article-11` — Федеральный закон от 31.05.1996 № 61-ФЗ `Об обороне`, статья 11;
2. `presidential-decree-1237-article-11` — Указ Президента РФ от 16.09.1999 № 1237, статья 11 Положения;
3. `presidential-decree-1495-internal-service-charter` — Указ Президента РФ от 10.11.2007 № 1495, Устав внутренней службы;
4. `presidential-decree-511-ship-charter` — Указ Президента РФ от 31.07.2022 № 511, Корабельный устав ВМФ.

Официальные URL должны указывать на официальные опубликованные тексты.

Дата проверки: `2026-07-27`.

## 11. Организационные классы

Создаются шесть классов:

| № | Код | Наименование |
|---:|---|---|
| 1 | `military-command-body` | Орган военного управления |
| 2 | `association` | Объединение |
| 3 | `formation` | Соединение |
| 4 | `military-unit` | Воинская часть |
| 5 | `organization` | Организация |
| 6 | `subdivision` | Подразделение |

Классы не образуют дерево.

## 12. Первичное наполнение типов

Создаются 28 типов.

| № | Код | Наименование | Класс или классы |
|---:|---|---|---|
| 1 | `administration` | управление | орган военного управления; подразделение |
| 2 | `headquarters` | штаб | орган военного управления; подразделение |
| 3 | `service` | служба | подразделение |
| 4 | `direction` | направление | подразделение |
| 5 | `department` | отдел | подразделение |
| 6 | `army` | армия | объединение |
| 7 | `corps` | корпус | соединение |
| 8 | `division` | дивизия | соединение |
| 9 | `brigade` | бригада | соединение |
| 10 | `regiment` | полк | воинская часть |
| 11 | `arsenal` | арсенал | воинская часть |
| 12 | `test-center` | испытательный центр | воинская часть |
| 13 | `storage-supply-base` | база хранения и снабжения | воинская часть |
| 14 | `enterprise` | предприятие | организация |
| 15 | `institution` | учреждение | организация |
| 16 | `military-educational-organization` | военная образовательная организация | организация |
| 17 | `battalion` | батальон | подразделение; воинская часть |
| 18 | `divizion` | дивизион | подразделение; воинская часть |
| 19 | `company` | рота | подразделение |
| 20 | `battery` | батарея | подразделение |
| 21 | `platoon` | взвод | подразделение |
| 22 | `group` | группа | подразделение |
| 23 | `section` | отделение | подразделение |
| 24 | `team` | команда | подразделение |
| 25 | `raschet` | расчёт | подразделение |
| 26 | `crew` | экипаж | подразделение |
| 27 | `ship` | корабль | воинская часть |
| 28 | `combat-unit` | боевая часть | подразделение |

Официальное сокращение `БЧ` хранится в `short_name` типа `combat-unit`.

`боевой пост` исключён, поскольку является местом на корабле, а не организационным подразделением.

`смена` исключена как временная/функциональная форма, не подтверждённая для v1 как устойчивый штатный тип подразделения.

## 13. Основные классификации

Для типов с несколькими классами:

| Тип | Основной класс | Дополнительный класс |
|---|---|---|
| управление | Орган военного управления | Подразделение |
| штаб | Орган военного управления | Подразделение |
| батальон | Подразделение | Воинская часть |
| дивизион | Подразделение | Воинская часть |

Для всех остальных типов единственный класс является основным.

## 14. Вычисляемый организационный статус

Организационный статус вычисляется по набору классов:

| Код | Условие | Отображение |
|---|---|---|
| `non_subdivision_only` | отсутствует `subdivision` | Не является подразделением |
| `subdivision_only` | имеется только `subdivision` | Только подразделение |
| `mixed` | имеются `subdivision` и другой класс | Зависит от утверждённой структуры |

Контрольные количества:

```text
non_subdivision_only: 12
subdivision_only: 12
mixed: 4
```

## 15. Контрольные количества seed

```text
версий: 1
источников версии: 4
классов: 6
типов: 28
связей тип–класс: 32
aliases: 0
```

По классам:

```text
Орган военного управления: 2
Объединение: 1
Соединение: 3
Воинская часть: 7
Организация: 3
Подразделение: 16
```

## 16. Repository

Добавляется:

```text
app/Directory/OrganizationalElementCatalogRepository.php
```

Обязательные методы:

```php
currentVersion(): array
versionSources(int $versionId): array
classes(int $versionId): array
searchTypes(int $versionId, string $query, ?string $classCode, ?string $scope): array
classesForTypes(int $versionId, array $typeIds): array
sourcesForTypes(int $versionId, array $typeIds): array
aliasesForTypes(int $versionId, array $typeIds): array
```

Требования:

- только prepared statements;
- отсутствие mutation-методов;
- отсутствие N+1-запросов;
- безопасная обработка пустого списка идентификаторов;
- не более четырёх агрегирующих запросов на страницу;
- сортировка по `sort_order`, затем `id`;
- текущая версия определяется однозначно;
- типы не дублируются при фильтрации по связям.

Factory-функция добавляется в `app/bootstrap.php`.

## 17. Поиск и фильтры

GET-параметры:

```text
q
class
scope
```

### 17.1 Поиск

- `trim`;
- максимум 150 символов;
- регистронезависимый поиск;
- поля: `name`, `short_name`, aliases;
- буквальный поиск через `LOCATE` или эквивалент без wildcard-интерпретации `%` и `_`.

### 17.2 Класс

Допустимы только коды текущей версии:

```text
military-command-body
association
formation
military-unit
organization
subdivision
```

Неизвестное значение сбрасывается.

### 17.3 Организационный статус

Допустимы:

```text
non_subdivision_only
subdivision_only
mixed
```

Неизвестное значение сбрасывается.

Поиск и фильтры применяются через `AND`.

## 18. Таблица UI

Колонки:

| Колонка | Содержание |
|---|---|
| Тип | полное и сокращённое наименование |
| Возможные классы | основной класс первым |
| Организационный статус | вычисляемое человекочитаемое значение |
| Основание | доказательная роль и источник |
| Примечание | ограничение применения |

Empty state:

```text
По заданным условиям типы организационных элементов не найдены.
Измените поисковый запрос или сбросьте фильтры.
```

## 19. Themes

Используется существующий зарегистрированный asset:

```text
css/directories.css
```

Обновляются CSS обеих тем:

```text
themes/asu-blue/assets/css/directories.css
themes/asu-light-blue/assets/css/directories.css
```

При deploy CSS копируется также в активный каталог:

```text
public/themes/<slug>/assets/css/directories.css
```

Checker обязан проверять опубликованный asset через `ThemeRegistry::assetUrl()`.

## 20. Integration checker

Добавляется:

```text
tools/check-organizational-elements-directory.php
```

Контрольные значения:

```text
migration 008: 1
новых таблиц: 7
текущих версий: 1
источников версии: 4
организационных классов: 6
типов: 28
связей тип–класс: 32
non_subdivision_only: 12
subdivision_only: 12
mixed: 4
permissions: 19
theme assets: 2
```

Дополнительно проверяются:

- точные 28 кодов и наименований;
- у каждого типа минимум один класс;
- у каждого типа ровно один основной класс;
- у каждого типа минимум один источник;
- единство версии всех связей;
- наличие UNIQUE, FK и CHECK constraints;
- `батальон → 1`;
- `БЧ → боевая часть`;
- `боев → 1`;
- `association → 1`;
- `formation → 3`;
- `military-unit → 7`;
- `organization → 3`;
- `subdivision → 16`;
- `батальон + military-unit → 1`;
- `батальон + organization → 0`;
- опубликованные CSS обеих тем.

Ожидаемый финал:

```text
ORGANIZATIONAL ELEMENT TYPES DIRECTORY CHECK PASSED
```

## 21. Идемпотентность migration

Migration должна выдерживать повторный запуск после частичного отказа до регистрации в таблице `migrations`:

- `CREATE TABLE IF NOT EXISTS`;
- upsert seed по стабильным кодам;
- отсутствие заранее зафиксированных числовых `id`;
- получение FK по кодам;
- отсутствие дублирования связей;
- повторное выполнение всего SQL безопасно.

## 22. Критерии desktop-приёмки

### Владелец

- плитка активна;
- маршрут открывается;
- отображается 28 типов;
- отображаются четыре источника;
- доступны шесть классов;
- поиск `БЧ` находит `боевая часть`;
- все фильтры работают отдельно и совместно;
- сброс возвращает 28 строк;
- корректный empty state;
- CRUD-controls отсутствуют;
- обе desktop-темы отображаются корректно.

### Не-владелец

Прямой маршрут возвращает тематический HTTP `403 Forbidden`.

### Регрессия

- справочник воинских званий сохраняет 20 строк;
- checker migration 007 проходит;
- permissions остаются 19;
- обе плитки справочников активны;
- страницы отображаются в обеих desktop-темах.

## 23. Явно исключено

В v1 запрещено добавлять:

- фактические части и подразделения;
- номера, условные наименования и дислокацию;
- численность, вооружение и задачи;
- конкретную подчинённость;
- конструктор организационной структуры;
- типовые штаты и шаблоны;
- процессы согласования и утверждения;
- CRUD;
- закрытые документы.

Мобильное тестирование исключено и не заявляется как выполненное.

## 24. Планируемые файлы

```text
app/Directory/OrganizationalElementCatalogRepository.php
app/bootstrap.php
database/migrations/008_organizational_element_types_directory.sql
public/admin/directories.php
public/admin/directories/organizational-elements.php
themes/asu-blue/assets/css/directories.css
themes/asu-light-blue/assets/css/directories.css
tools/check-organizational-elements-directory.php
docs/design/ORGANIZATIONAL-ELEMENT-TYPES-V1-DESIGN.md
docs/design/ORGANIZATIONAL-ELEMENT-TYPES-V1-REVIEW.md
docs/decisions/ORGANIZATIONAL-ELEMENT-TYPES-V1-APPROVAL.md
docs/testing/ORGANIZATIONAL-ELEMENT-TYPES-V1-TEST-REPORT.md
```

## 25. Статус

```text
Research: PASS
Analysis: PASS
Architecture: APPROVED
Specification v0.2: APPROVED
Formal Review: PASS WITH REQUIRED CORRECTIONS
Implementation: AUTHORIZED
```

Реализация начинается только в указанной feature-ветке. Merge допускается после полного тестирования, пользовательской desktop-приёмки и отдельного явного разрешения.