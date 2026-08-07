# Lowest Unit Staffing Structure v1 — Architecture

## 1. Статус

```text
DOCUMENT=Architecture
VERSION=0.2
INCREMENT=Lowest Unit Staffing Structure v1
CONTOUR=PersonnelServiceAccounting
BASE_BRANCH=main
BASE_SHA=d60db94e405979c8f29bdc3dcaae7950362fb13a
FEATURE_BRANCH=feature/lowest-unit-staffing-v1
IMPLEMENTATION_STATUS=NOT STARTED
```

## 2. Цель

Создать версионный штатный фундамент, начиная с нижнего подразделения, без учета конкретных военнослужащих.

Уполномоченный пользователь сможет создать реестр штатной структуры, подготовить draft version, добавить индивидуальные штатные позиции, закрепить документы-основания, утвердить/активировать версию и просматривать штат по организационным элементам.

## 3. Граница

### В scope

- домен `Staffing` внутри `PersonnelServiceAccounting`;
- register и versioned snapshots;
- документы-основания без файлов;
- individual staffing slots;
- stable slot identity;
- связь со stable `organizational_structure_elements.id`;
- pinned Organization/position/rank/VUS catalog versions;
- lifecycle и optimistic concurrency;
- management UI и read-only views;
- existing RBAC;
- append-only domain history;
- migration 013;
- синтетические проверки.

### Вне scope

- военнослужащие и персональные данные;
- назначения лиц и фактическая укомплектованность;
- кадровые приказы по лицам;
- файлы, фото, отпуска, медицинские сведения;
- `CitizenMilitaryAccounting`, призывники, запас, бронирование и повестки;
- Реестр воинского учета;
- импорт реальных штатов, OCR, внешний API;
- catalog-version migration для существующего active staffing;
- fine-grained subtree ACL;
- production deployment и mobile acceptance.

## 4. Совместимость

Migration 009 владеет организационным деревом:

```text
organizational_structures
organizational_structure_elements
organizational_structure_versions
organizational_structure_nodes
```

Staffing не создает `military_units`, `departments` или второе дерево. Позиция хранит stable organizational element и проверяет его присутствие в pinned organization version.

Используются существующие published catalogs должностей, званий и публичных ВУС. Staffing не изменяет catalog data.

## 5. Доменная модель

```text
StaffingRegister
└── StaffingVersion
    ├── StaffingVersionDocument
    ├── StaffingSlot
    │   └── StaffingSlotVusRequirement
    └── StaffingChangeEvent

StaffingSlotIdentity — stable identity между версиями.
```

### StaffingRegister

- code immutable и globally unique;
- name/note mutable только при active administrative status;
- ровно одна linked organizational structure;
- status `active/archived`.

### StaffingVersion

Фиксирует:

- register и based-on version;
- organization structure version;
- position catalog version;
- rank catalog version;
- VUS catalog version;
- version number/label;
- status;
- `[effective_from, effective_to)`;
- reason;
- revision и lifecycle metadata.

### StaffingSlotIdentity

Неизменяемая идентичность одной нормативной позиции. Не содержит display/business fields и не переиспользуется.

### StaffingSlot

Version snapshot:

- slot identity;
- organizational element;
- position type и optional variant;
- internal code и display name;
- minimum/maximum/preferred rank;
- normative state;
- note и sort order;
- VUS requirements.

Одна строка равна одной позиции; `quantity` отсутствует.

### Documents

Metadata only:

```text
document_type
document_date
document_number
title
note
role=primary_basis|additional_basis|amendment
```

Approved/active version имеет ровно один primary basis. Published document metadata immutable; изменение в новом draft выполняется copy-on-write.

### Change events

Append-only предметная история успешных команд. Общий Security Audit не заменяется.

## 6. Lifecycle

```text
draft → approved → active → superseded
draft → cancelled
approved → cancelled
```

Инварианты:

1. Не более одной pending version (`draft`/`approved`) на register.
2. Не более одной active version.
3. Content mutation только в draft.
4. Published states immutable.
5. Initial draft пустой и фиксирует current compatible catalogs.
6. Draft from active копирует active snapshot **с теми же pinned catalog versions**.
7. Смена catalog versions у register с active staffing в v1 запрещена и выносится в отдельный migration increment.
8. Activation атомарно supersedes предыдущую active version.
9. Activation выполняется вручную.

## 7. Organization binding

Каждая StaffingVersion ссылается на version той же OrganizationalStructure.

Slot может ссылаться на **любой** organizational element, присутствующий в pinned version, включая root, если это соответствует штатному документу и разрешенному position catalog context. Название инкремента отражает порядок внедрения «снизу вверх», а не DB-запрет верхних уровней.

Проверки:

- structure/version ownership;
- status pinned organization version = active или superseded;
- element присутствует в snapshot;
- element type согласован с position catalog relation, когда relation определена.

## 8. Catalog pinning

### Position

`position_type_id` принадлежит pinned position version. Optional variant принадлежит selected type и той же version.

### Rank

Pinned rank version должна совпадать с rank version, закрепленной position catalog.

```text
minimum_rank_id nullable
maximum_rank_id nullable
preferred_rank_id nullable
```

Все IDs принадлежат pinned version; min ≤ max; preferred находится в диапазоне. Пустой диапазон означает «не определено».

### VUS

Pinned VUS version имеет published status. Requirements:

```text
required|allowed|preferred
```

Только записи разрешенного existing public catalog; свободный код запрещен.

## 9. Position state

V1 хранит только нормативное состояние:

```text
active|suspended|closed
```

Фактические `occupied/vacant/temporarily-substituted` отсутствуют до Assignments domain. Read model показывает `assignment_state=not-managed-in-v1` и не делает утверждений об укомплектованности.

## 10. База данных

```text
database/migrations/013_lowest_unit_staffing_v1.sql
```

Tables:

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

Обязательные механизмы:

- unique register code;
- pending/active generated guards;
- unique version number;
- unique slot identity per version;
- unique internal code per version when present;
- unique sort order per organizational element;
- composite keys/FKs for pinned version consistency;
- triggers for immutability, lifecycle and cross-table invariants;
- append-only events;
- no physical deletion of published history.

## 11. Concurrency

Lock order:

```text
StaffingRegister
→ StaffingVersion
→ Documents / Slots / Requirements
```

Каждая draft mutation требует `expected_revision`; success increments revision and appends event. Stale command полностью отклоняется.

## 12. Permissions

```text
staffing.registers.view
staffing.registers.create
staffing.registers.update
staffing.registers.publish
staffing.registers.archive
staffing.registers.history
```

Owner получает доступ через `system.*.*`. Автоматические grants non-owner roles запрещены. V1 применяет permissions ко всему модулю; subtree ACL — отдельный будущий Security increment.

## 13. Application и UI

```text
app/Staffing/
public/admin/staffing/
```

Application разделяется на repository/service/traits/functions по существующему Organization pattern.

HTTP rules:

- GET read-only;
- POST mutations;
- authentication, permission, CSRF;
- revision check;
- transaction;
- PRG redirect;
- safe errors.

Views:

- register list/card;
- version card;
- slots grouped by organizational element;
- slot/document forms;
- compare;
- history.

Плитка размещается в permission-aware `public/admin/content.php`. Поддерживаются три темы. Desktop acceptance обязателен; mobile остается `NOT TESTED`.

## 14. Testing

- migration 001–013 on clean DB;
- migration 013 on current DB after backup;
- lifecycle and immutable-state constraints;
- Organization/catalog consistency;
- stale revision and transaction rollback;
- permission and CSRF matrix;
- all lifecycle/use cases;
- no personnel fields/data;
- desktop browser in three themes;
- Organization/Directory/Security/Theme regressions.

Test fixtures contain only synthetic non-sensitive values.

## 15. Architecture decisions

```text
ADR-1=Separate Staffing domain; no parallel organization tree
ADR-2=Stable slot identity + version snapshots
ADR-3=One row per slot; quantity excluded
ADR-4=Organization and catalog versions pinned
ADR-5=Draft copy keeps the same catalogs; catalog migration deferred
ADR-6=Root and non-root organization elements permitted
ADR-7=No vacancy/occupancy truth before Assignments
ADR-8=Published content immutable
ADR-9=Documents metadata only
ADR-10=Existing module-level RBAC; subtree ACL deferred
ADR-11=Personnel and CitizenMilitaryAccounting excluded
```

## 16. Acceptance

Architecture passes when Specification and Review confirm the same boundary, invariants, path allowlist and validation plan without unresolved blocking/major findings.