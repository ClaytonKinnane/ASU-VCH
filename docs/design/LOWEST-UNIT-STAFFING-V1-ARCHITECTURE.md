# Lowest Unit Staffing Structure v1 — Architecture

## 1. Статус

```text
DOCUMENT=Architecture
INCREMENT=Lowest Unit Staffing Structure v1
CONTOUR=PersonnelServiceAccounting
BASE_BRANCH=main
BASE_SHA=d60db94e405979c8f29bdc3dcaae7950362fb13a
FEATURE_BRANCH=feature/lowest-unit-staffing-v1
IMPLEMENTATION_STATUS=NOT STARTED
```

Документ определяет архитектуру первого функционального инкремента единственного целевого контура `PersonnelServiceAccounting`.

## 2. Цель

Создать версионный штатный фундамент нижнего подразделения без учета конкретных военнослужащих.

Инкремент должен позволить уполномоченному пользователю:

- создать штатный реестр для существующей организационной структуры;
- сформировать проект версии штатного документа;
- привязать индивидуальные штатные позиции к стабильным элементам организационной структуры;
- определить тип должности, допустимые звания и разрешенные ВУС;
- утвердить и ввести версию в действие;
- просматривать штат нижнего подразделения в виде списка и агрегатов;
- видеть позиции без персональных данных;
- получить доказуемую историю изменений.

## 3. Архитектурная граница

### 3.1. В scope

- отдельный домен `Staffing` внутри `PersonnelServiceAccounting`;
- versioned staffing register;
- документы-основания без файлов;
- индивидуальные штатные позиции;
- привязка к `organizational_structure_elements.id`;
- фиксация конкретной версии организационной структуры;
- ссылки на опубликованные справочники должностей, званий и публичных ВУС;
- lifecycle `draft → approved → active → superseded`;
- cancellation до activation;
- read-only представления для нижнего подразделения;
- защищенный management UI для уполномоченных пользователей;
- существующий RBAC;
- optimistic concurrency;
- append-only domain history;
- migration 013;
- синтетические tests/checkers.

### 3.2. Вне scope

- реальные военнослужащие;
- ФИО и персональные данные;
- назначения конкретных лиц;
- история службы;
- приказы по военнослужащим;
- фото и файловое хранилище;
- отпуска;
- медицинские сведения;
- `CitizenMilitaryAccounting`;
- призывники, запас, бронирование, повестки;
- государственный Реестр воинского учета;
- импорт реальных штатных документов;
- OCR;
- внешний API;
- production deployment;
- mobile acceptance;
- сведения ограниченного распространения в репозитории, seed, checker или публичных скриншотах.

## 4. Совместимость с существующей архитектурой

### 4.1. Organization Structure v1

Migration 009 уже реализует:

```text
organizational_structures
organizational_structure_elements
organizational_structure_versions
organizational_structure_nodes
```

`organizational_structure_elements.id` является стабильной идентичностью организационного элемента между версиями.

Staffing не создает параллельные таблицы `military_units`, `departments` или собственное организационное дерево.

### 4.2. Catalogs

Используются существующие published catalogs:

```text
military_position_catalog_versions
military_position_types
military_position_variants
military_rank_catalog_versions
military_ranks
military_occupational_specialty_catalog_versions
military_occupational_specialties
```

Staffing version закрепляет конкретные catalog versions. Ссылки на элементы справочника проверяются в рамках закрепленной версии.

### 4.3. Security

Используется существующий `AuthorizationService`, owner wildcard `system.*.*`, CSRF, authenticated session и permission-aware navigation.

Новые permissions не назначаются автоматически ролям `administrator`, `operator`, `viewer`.

## 5. Доменная модель

```text
StaffingRegister
└── StaffingVersion
    ├── StaffingVersionDocument
    ├── StaffingSlot
    │   └── StaffingSlotVusRequirement
    └── StaffingChangeEvent

StaffingSlotIdentity — стабильная идентичность позиции между версиями.
```

### 5.1. StaffingRegister

Корень административного контейнера.

Свойства:

- immutable machine code;
- display name;
- organizational structure;
- status `active/archived`;
- audit metadata.

Один register относится ровно к одной `organizational_structure`.

### 5.2. StaffingVersion

Версионный снимок штатной структуры.

Свойства:

- register;
- based-on version;
- pinned organizational structure version;
- pinned position catalog version;
- pinned rank catalog version;
- pinned VUS catalog version;
- version number;
- status;
- effective interval;
- change reason;
- revision;
- lifecycle metadata.

### 5.3. StaffingSlotIdentity

Стабильный идентификатор одной штатной позиции между версиями. Он не содержит изменяемых реквизитов должности. В каждой версии позиция представлена отдельной `StaffingSlot` строкой.

### 5.4. StaffingSlot

Снимок индивидуальной штатной позиции в одной версии.

Свойства:

- stable slot identity;
- organizational structure element;
- position type;
- optional position variant;
- internal code;
- display name;
- sort order;
- lifecycle state in version;
- note;
- minimum/maximum/preferred rank;
- zero or more VUS requirements.

Каждая строка представляет ровно один slot. Поле `quantity` отсутствует.

### 5.5. Documents

Хранятся только реквизиты:

- type;
- date;
- number;
- title;
- note.

Файлы не входят в v1.

Роли документов:

```text
primary_basis
additional_basis
amendment
```

Утверждаемая версия имеет ровно один `primary_basis`.

### 5.6. Change events

Append-only предметная история успешных операций. Она не заменяет будущий общий Audit.

## 6. Lifecycle

```text
draft → approved → active → superseded
draft → cancelled
approved → cancelled
```

Правила:

1. Для register одновременно существует не более одной pending version (`draft` или `approved`).
2. Одновременно существует не более одной `active` version.
3. Содержимое версии изменяется только в `draft`.
4. `approved`, `active`, `superseded`, `cancelled` immutable, кроме строго определенных lifecycle transitions.
5. Новая draft version копирует active version; при отсутствии active пустой initial draft создается явно.
6. Периоды действия полуоткрытые: `[effective_from, effective_to)`.
7. Activation новой версии закрывает предыдущую active version датой начала новой версии и переводит ее в `superseded`.
8. Activation выполняется вручную.

## 7. Связь с организационной структурой

### 7.1. Pinned structure version

Каждая staffing version фиксирует `organizational_structure_version_id`.

Добавление slot допускается только если:

- organizational structure version принадлежит register structure;
- версия имеет status `active` или `superseded`;
- stable organizational element присутствует в pinned version.

### 7.2. Stable element reference

`StaffingSlot` хранит `organizational_structure_element_id`, а не только `organizational_structure_node_id`.

Для проверки присутствия элемента в конкретной версии используется composite FK/validation через node snapshot.

### 7.3. Нижний уровень

Архитектура не жестко кодирует «рота», «взвод» или «отделение». Любой не-root organizational element может иметь штатные позиции, если его тип разрешен pinned position catalog context.

## 8. Catalog pinning

### 8.1. Position catalog

`StaffingVersion.position_catalog_version_id` указывает на published version.

`StaffingSlot.position_type_id` обязан принадлежать этой version.

`position_variant_id` необязателен и, если указан, принадлежит тому же type/version.

### 8.2. Rank catalog

Position catalog фиксирует совместимую rank catalog version. Staffing version дублирует `rank_catalog_version_id` для явной DB-integrity проверки и должна совпадать с pinned position catalog.

Rank requirement:

```text
minimum_rank_id nullable
maximum_rank_id nullable
preferred_rank_id nullable
```

Правила:

- все rank IDs принадлежат pinned rank version;
- minimum seniority ≤ maximum seniority;
- preferred, если указан, находится внутри диапазона;
- отсутствие диапазона означает «не определено», а не «любое звание допустимо».

### 8.3. VUS catalog

Staffing version фиксирует published public VUS catalog version.

Slot может иметь несколько VUS requirements:

```text
required
allowed
preferred
```

В v1 запрещено добавлять коды, отсутствующие в разрешенном published catalog.

## 9. Состояние позиции

До появления Assignments domain система не имеет источника истины о занятии slot конкретным лицом.

Поэтому v1 хранит только нормативное состояние позиции:

```text
active
suspended
closed
```

В read model для active slot отображается:

```text
assignment_state=not-managed-in-v1
```

Система не утверждает фактическую вакантность. Статусы `occupied`, `vacant` и `temporarily-substituted` появятся только вместе с Assignments domain и будут вычисляемыми.

## 10. Предлагаемая схема БД

Migration:

```text
database/migrations/013_lowest_unit_staffing_v1.sql
```

Таблицы:

```text
staffing_registers
staffing_slot_identities
staffing_versions
staffing_documents
staffing_version_documents
staffing_slots
staffing_slot_vus_requirements
staffing_change_events
```

### 10.1. staffing_registers

- unique code;
- FK `organizational_structure_id`;
- status `active/archived`;
- immutable code, structure and creation metadata;
- archive only without pending version.

### 10.2. staffing_slot_identities

- FK register;
- immutable identity;
- stable across staffing versions.

### 10.3. staffing_versions

- unique `(register_id, version_number)`;
- one pending guard;
- one active guard;
- composite FKs to pinned versions;
- revision > 0;
- lifecycle checks;
- effective date checks.

### 10.4. staffing_slots

- unique `(staffing_version_id, slot_identity_id)`;
- unique internal code within version when not null;
- unique sort order within organizational element;
- composite FKs for register/version/catalog consistency;
- no occupancy flag.

### 10.5. VUS requirements

- primary key `(staffing_slot_id, vus_id)`;
- requirement type CHECK;
- catalog version consistency;
- sort order unique per slot.

### 10.6. Triggers

DB triggers enforce:

- published version immutability;
- stable identity immutability;
- lifecycle transitions;
- pinned catalog compatibility;
- slot belongs to pinned organizational snapshot;
- append-only events;
- no physical deletion of published/historical data.

## 11. Concurrency

Mutation commands lock in this order:

```text
StaffingRegister
→ StaffingVersion
→ Documents / Slots / Requirements
```

Draft updates require `expected_revision`.

After each successful draft mutation:

- revision increments;
- change event is appended;
- stale request receives conflict response and no partial write.

## 12. Permissions

```text
staffing.registers.view
staffing.registers.create
staffing.registers.update
staffing.registers.publish
staffing.registers.archive
staffing.registers.history
```

`system.*.*` retains owner access.

V1 не вводит новый reusable organizational ACL subsystem. Non-owner access предоставляется существующими roles/permissions на уровне всего модуля. Fine-grained subtree scope откладывается до отдельного Security increment перед реальной делегацией по подразделениям.

## 13. Application components

```text
app/Staffing/
├── StaffingRepository.php
├── StaffingService.php
├── StaffingLifecycleService.php
├── StaffingSlotService.php
└── StaffingReadModelRepository.php
```

No arbitrary SQL is exposed outside repositories.

## 14. HTTP/UI

Proposed routes:

```text
/admin/content/staffing
/admin/content/staffing/view
/admin/content/staffing/create
/admin/content/staffing/update
/admin/content/staffing/version/create
/admin/content/staffing/version/approve
/admin/content/staffing/version/activate
/admin/content/staffing/version/cancel
/admin/content/staffing/document/*
/admin/content/staffing/slot/*
/admin/content/staffing/history
```

Mutation routes:

- POST only;
- CSRF protected;
- permission checked;
- revision checked;
- PRG redirect;
- safe error messages.

Views:

- register list;
- register card;
- version summary;
- organizational subtree;
- slot list grouped by unit;
- slot details;
- documents;
- history;
- compare versions.

UI must render in all three current themes. Desktop acceptance is required. Mobile remains unverified and out of scope.

## 15. Testing architecture

### 15.1. Database

- migration on clean DB;
- migration on current DB through 012;
- lifecycle constraints;
- catalog consistency;
- organizational snapshot consistency;
- immutable published data;
- stale revision rejection;
- test rollback.

### 15.2. Application

- permission deny/allow;
- CSRF;
- create register;
- create/copy draft;
- add/update/remove slot in draft;
- approve/activate/cancel;
- no personnel fields;
- safe validation errors.

### 15.3. Browser

- desktop navigation;
- list/card/detail;
- lifecycle workflow;
- three themes;
- empty state;
- large synthetic slot list;
- access denial.

## 16. Security and test data

Only synthetic non-sensitive data enters repository tests.

The application must not ship real unit names, staffing tables, personnel, documents, locations or restricted classifiers.

## 17. Architecture decisions

```text
ADR-1=Separate Staffing domain, no parallel organization tree
ADR-2=Stable slot identity plus version snapshots
ADR-3=Individual slots; quantity excluded
ADR-4=Pin organizational and catalog versions
ADR-5=No assignment/vacancy truth before Assignments domain
ADR-6=Published versions immutable
ADR-7=Documents metadata only in v1
ADR-8=Existing RBAC only; subtree ACL deferred
ADR-9=Personnel and CitizenMilitaryAccounting excluded
```

## 18. Acceptance

Architecture is acceptable when Review confirms:

- no conflict with Organization Structure v1;
- no duplicate organizational hierarchy;
- exact catalog pinning;
- individual slot model;
- no premature personnel/assignment model;
- no hidden CitizenMilitaryAccounting;
- implementation can be bounded by an exact changed-path allowlist.