# Military Positions Directory v1 — Architecture

## 1. Статус

```text
DOCUMENT=Architecture
VERSION=0.6
INCREMENT=Military Positions Directory v1
CONTOUR=PersonnelServiceAccounting
IMPLEMENTATION_BRANCH=feature/military-positions-directory-v1
IMPLEMENTATION_BASE_SHA=9ae05b9928903cc483ce415d7378b546e419264c
DESIGN_SOURCE_BRANCH=design/military-positions-directory-v1
DESIGN_SOURCE_HEAD=bad4057251f9ebf996d83b3e246df24127a5d5cc
DESIGN_SOURCE_MERGE_BASE=3d8a491ff2433994e8580152f190b298c765c66e
POST_STAFFING_RECONCILIATION=PASS
ORIGINAL_IMPLEMENTATION=AUTHORIZED
FIRST_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED
SECOND_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
THIRD_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
FOURTH_CORRECTIVE_UI_IMPLEMENTATION=PENDING_OWNER_APPROVAL
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

## 17. Corrective desktop interaction architecture (2026-08-07)

### 17.1 Acceptance finding

Desktop acceptance exact runtime head `7751430288d2b0669dee4fe14101f809f5828db5` выявил три открытых UI finding:

- version-card action `Открыть` растягивается и остаётся самоссылкой на detail page;
- version-specific history расположена после длинного entry list вместо contextual header actions;
- основание архивирования/восстановления постоянно показано в боковой колонке и визуально отделено от выбранного lifecycle action, особенно при раскрытом editor.

До corrective implementation и повторной desktop-проверки:

```text
DESKTOP_ACCEPTANCE=FAIL
OPEN_UI_FINDINGS=3
PULL_REQUEST=NOT_AUTHORIZED
```

### 17.2 Version-card modes

`version-card.php` получает явный presentation mode от caller:

- list mode показывает только компактную, нерастягивающуюся кнопку `Открыть`;
- detail mode не показывает `Открыть`; справа вверху находятся компактные действия `История этой версии` (только при history permission) и `Закрыть`;
- `Закрыть` возвращает к списку версий и к anchor той же версии; JavaScript/history API не требуется;
- global header navigation `К версиям` сохраняется как page-level fallback.

### 17.3 Entry lifecycle action layout

Редактирование записи и изменение её состояния остаются независимыми действиями. Основание архивирования или восстановления скрыто до явного раскрытия соответствующего действия. После раскрытия label, input и подтверждающая кнопка находятся в одном полноширинном action panel под entry fields; panel не образует боковую колонку рядом с раскрытым editor.

Archive/restore остаются POST + CSRF + expected-revision + PRG операциями. Service, repository, database schema и payload contract не меняются.

### 17.4 Styling contract

Version actions и entry lifecycle summaries используют content-sized controls (`width: auto`, без растягивания на свободную ширину). Три theme CSS получают симметричный managed block и используют только существующие theme variables.

### 17.5 Corrective boundary

```text
CORRECTIVE_CHANGED_PATHS=12
RUNTIME_OR_DATABASE_MODEL_CHANGE=NO
MIGRATION_CHANGE=NO
ROUTE_OR_PERMISSION_CHANGE=NO
JAVASCRIPT_CHANGE=NO
FIRST_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED
```

Corrective allowlist:

```text
public/admin/directories/military-positions.php
public/admin/directories/military-positions/version.php
public/admin/directories/military-positions/views/version-card.php
public/admin/directories/military-positions/views/entry-card.php
themes/asu-blue/assets/css/directories.css
themes/asu-light-blue/assets/css/directories.css
themes/asu-evgeniya-rostova/assets/css/directories.css
tools/check-military-positions-directory-v1.php
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md
docs/CHAT-HANDOFF.md
```
## 18. Corrective implementation mapping

Owner Approval на exact documentation head получен 2026-08-07. Реализация ограничена утверждёнными presentation paths:

- caller явно задаёт list/detail mode для version card;
- list card имеет стабильный anchor и компактное действие `Открыть`;
- detail card показывает contextual `История этой версии` и `Закрыть`;
- entry editor и archive/restore являются двумя полноширинными disclosure actions;
- reason input существует внутри state disclosure и скрыт, пока действие не раскрыто;
- три managed CSS block остаются симметричными и используют только theme variables;
- checker контролирует поведение и exact corrective commit allowlist.

Runtime/DB model, migrations, repositories, services, routes, permissions, CSRF/revision/PRG contracts и JavaScript не изменены. Полный автоматический gate на exact head `6b63efd6d3a6e7567cc48106bd8c12bd9371e585` пройден; повторная desktop acceptance выявила UI-F04 и остаётся `FAIL`.


## 19. Second corrective desktop action layout (2026-08-07)

### 19.1 Acceptance finding

На exact runtime head `6b63efd6d3a6e7567cc48106bd8c12bd9371e585` автоматический gate прошёл полностью. Owner-provided desktop screenshot темы `asu-blue` подтвердил, что основание архивирования скрыто до выбора действия, но выявил новый presentation finding UI-F04: `Архивировать должность` помещена в отдельный полноширинный контейнер и визуально отделена от равноправного действия `Изменить`.

```text
DESKTOP_ACCEPTANCE=FAIL
UI_F04=OPEN
SECOND_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
```

### 19.2 Common entry action row

Для каждой управляемой draft-записи `Изменить` и `Архивировать должность` / `Восстановить должность` являются равноправными компактными disclosure controls:

- оба control находятся рядом в одной общей строке действий;
- оба имеют одинаковые размеры, border, background, typography и interaction states;
- в закрытом состоянии вокруг lifecycle control отсутствует отдельный полноширинный panel;
- disclosure одной записи взаимоисключающие: одновременно раскрывается не более одной формы;
- раскрытая форма изменения либо форма основания lifecycle action занимает отдельную полноширинную строку под общей строкой controls;
- обязательное основание и подтверждающая кнопка остаются внутри раскрытой lifecycle form.

Native disclosure semantics сохраняются; JavaScript не добавляется. POST, CSRF, expected revisions, PRG и маршруты archive/restore не меняются.

### 19.3 Second corrective boundary

```text
SECOND_CORRECTIVE_ALLOWLIST_PATHS=9
RUNTIME_OR_DATABASE_MODEL_CHANGE=NO
MIGRATION_CHANGE=NO
ROUTE_OR_PERMISSION_CHANGE=NO
JAVASCRIPT_CHANGE=NO
SECOND_CORRECTIVE_DESIGN_REVIEW=PASS
SECOND_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
```

Exact second corrective allowlist:

```text
public/admin/directories/military-positions/views/entry-card.php
themes/asu-blue/assets/css/directories.css
themes/asu-light-blue/assets/css/directories.css
themes/asu-evgeniya-rostova/assets/css/directories.css
tools/check-military-positions-directory-v1.php
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md
docs/CHAT-HANDOFF.md
```

Owner Approval was granted against exact documentation head `294cd91e26513217187cbf07447b2e769aa2ff72`. The implementation changes exactly the nine paths above:

- both entry disclosures share one entry-scoped native `details[name]` group;
- both summaries use one identical compact control style in the shared action row;
- the collapsed lifecycle action has no panel shell;
- the opened edit or lifecycle form occupies a full-width row below both controls;
- the lifecycle form retains its reason and confirmation together;
- the checker enforces the nine-path commit inventory and UI-C06 structure.

```text
SECOND_CORRECTIVE_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
SECOND_CORRECTIVE_STATIC_VALIDATION=PASS
SECOND_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=PASS
SECOND_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
BRANCH_DELETION=NOT_AUTHORIZED
```

Runtime/DB model, migrations, repositories, services, routes, permissions, CSRF/revision/PRG contracts and JavaScript remain unchanged. Pull Request, merge, force-push and branch deletion remain unauthorized.


## 20. Third corrective desktop action layout (2026-08-07)

### 20.1 Acceptance findings

Полный автоматический gate exact runtime head `297c9e6566c0010556324506bb0c9947b4ed6f43` прошёл: second corrective inventory `9/9`, итоговый DB/runtime checker `148 PASS`, initialization и HTTP smoke — PASS. Owner-provided desktop screenshot темы `asu-blue` затем подтвердил два presentation finding:

- UI-F04 остаётся открытым: `Изменить` и `Архивировать должность` визуально разнесены, несмотря на требование соседнего размещения;
- UI-F05 открыт: раскрытая форма основания построена вертикальной колонкой и не читается как одно связанное действие.

```text
DESKTOP_ACCEPTANCE=FAIL
UI_F04_RETEST=FAIL_OPEN
UI_F05=OPEN
OPEN_UI_FINDINGS=5_PENDING_RETEST
THIRD_CORRECTIVE_UI_IMPLEMENTATION=AUTHORIZED_AND_IMPLEMENTED_PENDING_VALIDATION
```

### 20.2 Deterministic adjacent controls

Native `details[name]` и взаимное исключение форм сохраняются. Для desktop layout summary редактирования и summary lifecycle-действия получают явные соседние grid columns; автоматическое размещение browser layout больше не определяет расстояние между ними. Между controls остаётся один фиксированный малый `column-gap`, обе кнопки имеют прежний общий compact style.

### 20.3 Single-line lifecycle reason

В раскрытой форме на обычной desktop-ширине подпись `Основание архивирования` / `Основание восстановления`, текстовое поле и подтверждающая кнопка находятся в одной горизонтальной строке:

- подпись не отрывается от поля;
- поле занимает доступную ширину;
- подтверждающая кнопка остаётся content-sized;
- весь lifecycle form по-прежнему расположен отдельной полноширинной строкой под двумя action controls;
- существующий narrow-viewport fallback может складывать элементы вертикально и не входит в mobile acceptance.

PHP markup, native disclosure semantics, archive/restore payload, POST, CSRF, expected revisions, PRG и routes не меняются.

### 20.4 Third corrective boundary

```text
THIRD_CORRECTIVE_ALLOWLIST_PATHS=8
RUNTIME_OR_DATABASE_MODEL_CHANGE=NO
PHP_TEMPLATE_CHANGE=NO
MIGRATION_CHANGE=NO
ROUTE_OR_PERMISSION_CHANGE=NO
JAVASCRIPT_CHANGE=NO
THIRD_CORRECTIVE_DESIGN_REVIEW=PASS
THIRD_CORRECTIVE_UI_IMPLEMENTATION=AUTHORIZED_AND_IMPLEMENTED_PENDING_VALIDATION
```

Exact third corrective allowlist:

```text
themes/asu-blue/assets/css/directories.css
themes/asu-light-blue/assets/css/directories.css
themes/asu-evgeniya-rostova/assets/css/directories.css
tools/check-military-positions-directory-v1.php
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md
docs/CHAT-HANDOFF.md
```

Owner Approval was granted against exact documentation head `b1768ad5ffce5e1da1057096bcf6e02063cea3a1`. The implementation changes exactly the eight paths above:

- both action summaries receive explicit first and second desktop grid columns;
- the fixed 10px action gap is no longer affected by opened-form auto-placement;
- the lifecycle form remains a full-width second row;
- the lifecycle label/input unit and confirmation button render in one desktop row;
- the existing 760px fallback stacks the form and label;
- the checker enforces UI-C09–UI-C11 and the exact eight-path commit inventory.

```text
THIRD_CORRECTIVE_IMPLEMENTATION=COMPLETE_PENDING_VALIDATION
THIRD_CORRECTIVE_STATIC_VALIDATION=PENDING_EXACT_COMMIT
THIRD_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=NOT_RUN
THIRD_CORRECTIVE_DESKTOP_ACCEPTANCE=NOT_RUN
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
BRANCH_DELETION=NOT_AUTHORIZED
```

PHP markup, runtime/DB model, migrations, repositories, services, routes, permissions, CSRF/revision/PRG contracts and JavaScript remain unchanged. Pull Request, merge, force-push, branch deletion, `main` changes and scope expansion remain unauthorized.


## 21. Fourth corrective stable action toolbar and reason layout (2026-08-08)

### 21.1 Acceptance findings

Полный автоматический gate exact runtime head `caee9845ef2faf4245397299d232ad820b77040c` прошёл: third corrective inventory `8/8`, итоговый DB/runtime checker `157 PASS`, initialization и HTTP smoke — PASS. Последующий owner-provided desktop screenshot canonical draft в теме `asu-blue` показал, что оба presentation finding остаются открытыми:

- UI-F04 повторно подтверждён: при открытом lifecycle disclosure `Изменить` и `Архивировать должность` снова разнесены по ширине карточки;
- UI-F05 повторно подтверждён: горизонтальная связка подписи и короткого поля основания не даёт понятной иерархии; owner требует подпись над полем и немного более длинное поле.

```text
DESKTOP_ACCEPTANCE=FAIL
THIRD_CORRECTIVE_STATIC_VALIDATION=PASS
THIRD_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=PASS
THIRD_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
UI_F04_RETEST_2=FAIL_OPEN
UI_F05_RETEST=FAIL_OPEN
FOURTH_CORRECTIVE_UI_IMPLEMENTATION=PENDING_OWNER_APPROVAL
```

### 21.2 Stable action-toolbar boundary

Причина повторного UI-F04 — применение `display: contents` к native `<details>`: в Edge раскрытое содержимое влияет на grid contribution и расстояние между summary, несмотря на явные номера колонок. Fourth corrective больше не использует flattened `<details>` как источник grid items.

Оба native `details[name]` остаются взаимно исключающими disclosure-переключателями, но каждый содержит только свой `summary` и сам является обычным compact grid item в первой или второй колонке. Edit form и lifecycle form выводятся отдельными sibling-панелями во второй полноширинной строке; видимость каждой панели управляется состоянием соответствующего предшествующего `details`. Summary получает `aria-controls`, а панель — уникальный entry-scoped `id`.

Так раскрытая панель больше не участвует в расчёте ширины колонок controls и физически не может раздвинуть кнопки. Backend form action, поля payload, CSRF, expected revisions, PRG, `details[name]` mutual exclusion и отсутствие JavaScript сохраняются.

### 21.3 Vertical label and longer reason field

Lifecycle panel остаётся полноширинным блоком под toolbar. Внутри него на desktop:

- `Основание архивирования` / `Основание восстановления` находится отдельной строкой непосредственно над полем;
- label и input остаются одной доступной label/input единицей;
- input получает понятную ограниченную desktop-ширину около `360px`, существенно больше наблюдаемого короткого поля, но не растягивается на всю карточку;
- подтверждающая кнопка располагается справа и выравнивается по нижней границе поля;
- существующий breakpoint `760px` складывает поле и кнопку вертикально; mobile acceptance остаётся вне scope.

### 21.4 Fourth corrective boundary

```text
FOURTH_CORRECTIVE_ALLOWLIST_PATHS=9
RUNTIME_OR_DATABASE_MODEL_CHANGE=NO
PHP_TEMPLATE_PRESENTATION_CHANGE=YES
MIGRATION_CHANGE=NO
ROUTE_OR_PERMISSION_CHANGE=NO
JAVASCRIPT_CHANGE=NO
FOURTH_CORRECTIVE_DESIGN_REVIEW=PASS
FOURTH_CORRECTIVE_UI_IMPLEMENTATION=PENDING_OWNER_APPROVAL
```

Exact fourth corrective allowlist:

```text
public/admin/directories/military-positions/views/entry-card.php
themes/asu-blue/assets/css/directories.css
themes/asu-light-blue/assets/css/directories.css
themes/asu-evgeniya-rostova/assets/css/directories.css
tools/check-military-positions-directory-v1.php
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md
docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md
docs/CHAT-HANDOFF.md
```

До отдельного owner Approval разрешена только публикация этого четырёхдокументного checkpoint. Runtime implementation, Pull Request, merge, force-push, branch deletion, изменение `main` и расширение scope не разрешены.
