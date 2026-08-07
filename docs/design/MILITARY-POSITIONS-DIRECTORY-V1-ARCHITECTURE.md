# Military Positions Directory v1 — Architecture

## 1. Статус

```text
DOCUMENT=Architecture
VERSION=0.2
INCREMENT=Military Positions Directory v1
CONTOUR=PersonnelServiceAccounting
IMPLEMENTATION_BRANCH=feature/military-positions-directory-v1
IMPLEMENTATION_BASE_SHA=9ae05b9928903cc483ce415d7378b546e419264c
DESIGN_SOURCE_BRANCH=design/military-positions-directory-v1
DESIGN_SOURCE_HEAD=bad4057251f9ebf996d83b3e246df24127a5d5cc
DESIGN_SOURCE_MERGE_BASE=3d8a491ff2433994e8580152f190b298c765c66e
POST_STAFFING_RECONCILIATION=PASS
IMPLEMENTATION=AUTHORIZED
```

Версия 0.2 переносит утверждённую архитектуру 0.1 на exact post-Staffing baseline. `Lowest Unit Staffing Structure v1` слит через PR #35; migration 013 и runtime Staffing присутствуют. Старая design-ветка не является implementation base.

## 2. Цель

Создать единый глобальный версионируемый справочник канонических наименований воинских должностей, используемый всеми организационными элементами и всеми штатными версиями АСУ-ВЧ.

Справочник хранит только свойства самой должности. Он не хранит ВУС, штатное звание, подразделение, назначенного военнослужащего, фактическую занятость, штатное количество или конкретную технику.

## 3. Owner decision

Утверждённая модель:

```text
MilitaryPosition
├── stable identity
├── canonical name
├── full name nullable
├── short name nullable
├── combined flag
├── source metadata
├── note nullable
└── lifecycle/status
```

Утверждённый принцип:

```text
ДОЛЖНОСТЬ не знает о подразделении, ВУС, звании или военнослужащем.
ШТАТНАЯ ПОЗИЦИЯ связывает конкретную должность с организационным элементом и требованиями.
```

## 4. Existing-state analysis

Runtime уже содержит `military_position_catalog_versions`, `military_position_types` и связанный публичный read-only классификатор migration 010. Migration 010 использует lifecycle `building/published/superseded`, обязательные legacy-поля и 41 legacy trigger; её marker, compatibility loader и пять gzip/base64 payload-частей неизменяемы в этом increment.

Staffing v1 закрепляет `position_catalog_version_id`, хранит `position_type_id` и optional `position_variant_id`. Catalog-level связи с rank и organizational-element catalog versions сохраняются для этой совместимости, но не становятся свойствами canonical position.

Немедленное удаление текущих таблиц, legacy metadata или исторических записей запрещено архитектурой целостности.

## 5. Target architecture

### 5.1 Один каталог, не два

Не создаётся параллельная сущность `positions`. Эволюционирует существующая модель:

```text
military_position_catalog_versions
└── military_position_types
```

`military_position_types` становится каноническим реестром наименований должностей, сохраняя историческую совместимость legacy version.

### 5.2 Stable identity и revision

Для версии каталога добавляется optimistic `revision`. Для логической должности используется стабильный ключ, сохраняемый при копировании версии и переименовании в draft. Каждая version-scoped запись имеет собственный revision.

Целевые поля canonical entry:

```text
id
catalog_version_id
stable_key
code (legacy-compatible system key)
name
normalized_name
full_name nullable
short_name nullable
is_combined
source_type
source_reference nullable
note nullable
status
sort_order
revision
created_at / created_by
updated_at / updated_by
```

Internal id, stable key и system code не показываются пользователю как предметный код должности.

### 5.3 Что не является свойством должности

Запрещено переносить в canonical position:

```text
VUS
minimum_rank / maximum_rank / preferred_rank
organizational element / organizational element type
staffing slot number / quantity / local slot order
occupied / vacant
person assignment
equipment binding
```

### 5.4 Combined position

`is_combined` является явным признаком и не вычисляется по дефису. Составное наименование остаётся одной канонической должностью; автоматическое разбиение запрещено.

## 6. Catalog lifecycle

```text
draft → published → superseded
draft → cancelled
```

Опубликованная, superseded и cancelled версии неизменяемы. Одновременно существует не более одной draft и одной published version. Публикация атомарно supersede-ит previous published version и задаёт её `valid_to`.

Изменение справочника выполняется только в draft. StaffingVersion фиксирует конкретную published catalog version; последующая публикация не меняет исторический штат и не выполняет hidden remap.

## 7. Initial rollout

Migration 014:

1. сохраняет legacy classifier как current published version;
2. адаптирует существующие таблицы без изменения migration 010;
3. создаёт одну canonical draft version;
4. создаёт в ней ровно 24 утверждённые синтетические записи;
5. не публикует draft автоматически.

При последующей owner-driven публикации canonical draft legacy version атомарно становится `superseded`, но остаётся read-only вместе со всей metadata и Staffing references.

Файл `ШДК РУ(1).xlsx` не входит в runtime. ВУС, звания, структура, количество строк и техника не импортируются.

## 8. Existing classifier compatibility

Сохраняются существующие variants, tariff grades, families, composition scopes, organizational contexts и source/legal provenance. Новые canonical entries не обязаны иметь эти связи.

Legacy version доступна в version-aware read-only UI. Physical DROP разрешим только отдельным будущим increment после доказательства отсутствия FK, trigger, runtime и historical Staffing dependencies.

## 9. Staffing integration

```text
staffing_versions.position_catalog_version_id
    → published military_position_catalog_versions.id

staffing_slots.position_type_id
    → military_position_types.id
```

`position_variant_id` остаётся optional legacy-compatible. Новая canonical entry используется с `position_variant_id = null`.

Существующие StaffingVersion остаются pinned к исходной версии. Draft-from-active сохраняет ту же catalog version. Archived canonical entries исключаются из выбора для новых slots, но остаются читаемыми в существующей истории.

## 10. UI и navigation

Основной экран `/admin/directories/military-positions.php` становится управляемым version-aware каталогом. Доступ к модулю появляется в `content.php` для обладателя view permission; плитка `directories.php` также permission-aware.

UI включает список версий, filters, entry cards/forms, lifecycle actions и readable history без raw JSON. Все labels и безопасные ошибки — на русском. Цвета задаются только theme variables/assets; desktop acceptance требуется для `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova`.

## 11. Permissions

```text
directories.military_positions.view
directories.military_positions.manage
directories.military_positions.publish
directories.military_positions.history
```

Owner wildcard сохраняется. Автоматические grants другим ролям запрещены. Page/action authorization, POST, CSRF, expected revision, transaction и PRG обязательны.

## 12. Data integrity и history

Обязательные инварианты:

- published/superseded/cancelled content immutable;
- normalized canonical name unique inside catalog version;
- stable key unique inside version and preserved across copies;
- physical delete отсутствует;
- draft mutations use expected revisions;
- publish is atomic;
- catalog history append-only;
- archived entry remains historical but is unavailable for new Staffing slots.

History events содержат actor, time, version, target, before/after и reason. UI показывает именованные поля, а не JSON.

## 13. Migration mechanism

```text
MIGRATION=database/migrations/014_military_positions_directory_v1.sql
MECHANISM=standalone SQL
MIGRATION_010_MARKER_TOUCH=FORBIDDEN
MIGRATION_010_COMPATIBILITY_TOUCH=FORBIDDEN
MIGRATION_010_PAYLOAD_TOUCH=FORBIDDEN
```

Migration должна быть idempotent для штатного repeat installer, поддерживать clean и existing DB, сохранять legacy schema/data и действовать fail closed при противоречивом состоянии.

## 14. Security and data classification

```text
PERSONAL_DATA=NONE
REAL_PERSONNEL=NONE
REAL_STAFFING_DATA=NONE
ASSIGNMENTS=NONE
OCCUPIED_VACANT=NONE
CITIZEN_MILITARY_ACCOUNTING=EXCLUDED
```

## 15. Non-goals

- VUS/rank requirement profiles;
- Excel importer;
- catalog remap/upgrade existing StaffingVersion;
- assignments, occupancy or equipment;
- organizational templates or external integrations;
- production deployment;
- mobile acceptance;
- destructive cleanup of legacy classifier schema.

## 16. Implementation contract

```text
BASE=main@9ae05b9928903cc483ce415d7378b546e419264c
BRANCH=feature/military-positions-directory-v1
MIGRATION=014_military_positions_directory_v1.sql
MAX_CHANGED_PATHS=38
EXACT_ALLOWLIST=38
PR=NOT AUTHORIZED
MERGE=NOT AUTHORIZED
MOBILE=NOT RUN / OUT OF SCOPE
```
