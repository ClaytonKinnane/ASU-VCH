# Домен Staffing

## 1. Статус и назначение

Документ определяет границы и инварианты домена `Staffing` внутри единственного контура `PersonnelServiceAccounting`.

```text
INCREMENT=Lowest Unit Staffing Structure v1
BASE_SHA=d60db94e405979c8f29bdc3dcaae7950362fb13a
FEATURE_BRANCH=feature/lowest-unit-staffing-v1
IMPLEMENTATION=NOT STARTED
```

Домен хранит нормативную штатную структуру подразделений. Он не хранит военнослужащих и не определяет фактическую занятость позиции до появления отдельного домена назначений.

## 2. Ответственность домена

Staffing отвечает за:

- административный реестр штатной структуры;
- версии штатной структуры;
- документы-основания и их роли;
- индивидуальные штатные позиции;
- связь позиции со стабильным организационным элементом;
- связь позиции с опубликованным типом воинской должности;
- допустимые звания;
- разрешенные ВУС;
- lifecycle версии;
- предметную историю изменений;
- чтение штатной структуры по подразделениям.

## 3. Вне границ домена

Staffing не отвечает за:

- организационное дерево;
- справочники должностей, званий и ВУС;
- военнослужащих;
- назначения и временное исполнение обязанностей;
- фактическую вакантность;
- кадровые приказы по лицам;
- документы и файлы военнослужащих;
- отпуска;
- медицинские данные;
- учет граждан, призывников и запаса;
- государственные реестры воинского учета;
- общий Audit.

## 4. Зависимости

Разрешены:

```text
Staffing → Organization
Staffing → Directory/Reference catalogs
Staffing → Security authorization infrastructure
Staffing → database and HTTP infrastructure
```

Запрещены в v1:

```text
Staffing → Personnel
Staffing → Assignments
Staffing → Orders
Staffing → DocumentsVault
Staffing → Medical
Staffing → CitizenMilitaryAccounting
```

Staffing не изменяет таблицы Organization или каталогов напрямую.

## 5. Основные сущности

### 5.1. StaffingRegister

Административный контейнер штатной структуры одной `organizational_structure`.

Бизнес-правила:

- machine code уникален и неизменяем;
- register относится ровно к одной organizational structure;
- register может существовать без версии;
- archive является логическим;
- archive запрещен при наличии pending version;
- code не переиспользуется.

### 5.2. StaffingVersion

Версионный снимок штатной структуры.

Бизнес-правила:

- version number уникален внутри register;
- одновременно не более одной pending version;
- одновременно не более одной active version;
- содержимое меняется только в draft;
- версия фиксирует organizational structure version и catalog versions;
- approved version имеет effective date и primary basis;
- activation supersedes предыдущую active version;
- published states immutable.

### 5.3. StaffingSlotIdentity

Стабильная идентичность одной штатной позиции между версиями.

Бизнес-правила:

- относится к одному register;
- не содержит display fields;
- не изменяется и не удаляется физически;
- один identity встречается не более одного раза в версии;
- новый identity создается при появлении новой нормативной позиции;
- закрытие позиции не освобождает identity для повторного использования.

### 5.4. StaffingSlot

Снимок позиции в конкретной StaffingVersion.

Бизнес-правила:

- принадлежит одной version;
- относится к одному stable identity;
- относится к одному stable organizational element;
- organizational element обязан присутствовать в pinned structure version;
- position type и variant принадлежат pinned position catalog;
- rank references принадлежат pinned rank catalog;
- VUS references принадлежат pinned VUS catalog;
- одна строка равна одной позиции;
- position has normative state `active/suspended/closed`;
- occupation/vacancy fields отсутствуют;
- internal code, если указан, уникален внутри version;
- sibling sort order уникален внутри organizational element.

### 5.5. StaffingDocument

Реквизиты организационно-распорядительного основания без файла.

Бизнес-правила:

- document belongs to one register;
- number, date, type and title обязательны;
- document linked to published version becomes immutable;
- изменение реквизитов для нового draft выполняется copy-on-write.

### 5.6. StaffingVersionDocument

Связь version ↔ document.

Роли:

```text
primary_basis
additional_basis
amendment
```

Бизнес-правила:

- у approved/active version ровно один primary basis;
- sort order уникален внутри version;
- document and version belong to one register.

### 5.7. StaffingSlotVusRequirement

Связь slot ↔ VUS.

Типы:

```text
required
allowed
preferred
```

Бизнес-правила:

- duplicate VUS в одном slot запрещен;
- VUS принадлежит pinned catalog version;
- sort order уникален;
- `preferred` не означает обязательность;
- источник ограниченного доступа не реконструируется.

### 5.8. StaffingChangeEvent

Append-only запись успешно совершенного предметного изменения.

Минимальные поля:

- register;
- version;
- slot identity, если применимо;
- actor;
- event type;
- before state;
- after state;
- reason;
- timestamp.

## 6. Агрегаты

### 6.1. StaffingRegister aggregate

Отвечает за:

- create/update administrative card;
- archive/restore;
- create initial draft;
- create draft from active version.

### 6.2. StaffingVersion aggregate

Отвечает за:

- draft content;
- documents;
- slots;
- VUS requirements;
- approve/cancel/activate;
- revision and locking.

Все изменения одной draft version выполняются транзакционно.

## 7. Lifecycle

```text
draft → approved → active → superseded
draft → cancelled
approved → cancelled
```

Недопустимы:

- active → draft;
- superseded → active;
- cancelled → draft;
- content mutation outside draft;
- activation without primary basis;
- activation without at least one active slot;
- overlapping active effective periods.

## 8. Инварианты catalog pinning

1. Position catalog version имеет status published.
2. Rank catalog version совпадает с версией, закрепленной position catalog.
3. Organizational element catalog version, закрепленная position catalog, совместима с pinned Organization structure version.
4. VUS catalog version имеет status published.
5. Position type belongs to pinned position catalog.
6. Position variant belongs to selected type and version.
7. Rank IDs belong to pinned rank version.
8. VUS IDs belong to pinned VUS version.

## 9. Инварианты Organization

1. Register references existing organizational structure.
2. StaffingVersion references version of the same structure.
3. Pinned structure version status is active or superseded.
4. Slot references stable `organizational_structure_elements.id`.
5. Element is present as node in pinned structure version.
6. Root military-unit element cannot receive slots in v1.
7. Staffing never changes Organization rows.

## 10. Rank requirement

Поля:

```text
minimum_rank_id
maximum_rank_id
preferred_rank_id
```

Правила:

- все поля nullable;
- minimum seniority cannot exceed maximum;
- preferred lies inside range when range exists;
- empty range means unavailable/not specified;
- UI must not label empty range as “любое звание”.

## 11. Состояние и vacancy semantics

Normative slot state:

```text
active
suspended
closed
```

Assignment state в v1:

```text
not-managed-in-v1
```

Запрещено:

- manual occupied flag;
- manual actual vacancy flag;
- связь с person ID;
- показ фиктивной укомплектованности.

## 12. Permissions

```text
staffing.registers.view
staffing.registers.create
staffing.registers.update
staffing.registers.publish
staffing.registers.archive
staffing.registers.history
```

Owner access обеспечивается `system.*.*`.

V1 не вводит subtree ACL. Permissions применяются ко всему модулю. Ограничение по подразделениям до отдельного Security increment не заявляется как реализованное.

## 13. Domain services

```text
StaffingService
StaffingLifecycleService
StaffingSlotService
```

Обязанности:

- enforce domain invariants;
- use transactions;
- lock in canonical order;
- validate expected revision;
- append change events;
- return domain-safe errors.

## 14. Repositories

```text
StaffingRepository
StaffingReadModelRepository
```

Repository не предоставляет generic update/delete API. Published data changes only via domain commands.

## 15. Domain events v1

```text
StaffingRegisterCreated
StaffingRegisterUpdated
StaffingRegisterArchived
StaffingRegisterRestored
StaffingDraftCreated
StaffingSlotAdded
StaffingSlotUpdated
StaffingSlotRemovedFromDraft
StaffingDocumentLinked
StaffingDocumentUnlinked
StaffingVersionApproved
StaffingVersionCancelled
StaffingVersionActivated
StaffingVersionSuperseded
```

Events are business history, not replacement for security audit.

## 16. Основные use cases

- создать register;
- изменить его display metadata;
- создать initial draft;
- создать draft from active;
- добавить документ-основание;
- добавить индивидуальный slot;
- изменить slot in draft;
- удалить slot from draft snapshot;
- задать rank range;
- задать VUS requirements;
- approve version;
- cancel version;
- activate version;
- view active staffing by unit;
- compare versions;
- view history;
- archive/restore register.

## 17. Data classification

V1 не требует персональных данных. Реальные штатные сведения могут иметь ограниченный режим и не должны попадать в GitHub.

Repository fixtures use only synthetic names and codes.

## 18. Future extensions

Отдельными инкрементами:

- delegated organizational scope;
- Personnel Core;
- Assignments and computed vacancy;
- Orders integration;
- file documents;
- import/export;
- staffing reports;
- higher-echelon aggregates.

## 19. Запрет второго контура

Staffing не содержит и не должен получить поля или процессы для:

- категории учета гражданина;
- постановки/снятия с учета;
- запаса;
- бронирования;
- повесток;
- Реестра воинского учета.

`CitizenMilitaryAccounting` остается исключенным.