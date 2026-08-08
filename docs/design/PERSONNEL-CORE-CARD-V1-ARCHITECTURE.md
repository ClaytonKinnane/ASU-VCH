# Personnel Core Card v1 — Architecture

## 1. Статус

```text
DOCUMENT=Architecture
VERSION=0.1
INCREMENT=Personnel Core Card v1
CONTOUR=PersonnelServiceAccounting
DOMAIN=Personnel
BASE_BRANCH=main
BASE_SHA=dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8
DESIGN_BRANCH=design/personnel-core-card-v1
IMPLEMENTATION_STATUS=NOT STARTED
SECURITY_MODEL=OWNER-ONLY PROTOTYPE / FINE-GRAINED ACCESS DEFERRED
```

## 2. Цель

Создать первый работающий Personnel runtime: каноническую карточку действующего военнослужащего с базовой идентичностью, типизированными служебными идентификаторами, поиском, archive/restore, optimistic concurrency и читаемой append-only history.

Increment должен стать стабильным фундаментом для последующих Assignments, Service Record, Contacts/Family, Documents/Media, Medical/Physical Identification, Special Cases и generated forms.

## 3. Связь с целевой моделью

Полная предметная архитектура находится в:

```text
docs/domains/PERSONNEL.md
```

Будущая расширенная модель доступа зафиксирована, но отложена:

```text
docs/design/PERSONNEL-ACCESS-FUTURE-DESIGN-NOTES.md
```

V1 не должен принимать решения, которые делают будущие разделы невозможными или заставляют дублировать текущую должность/подразделение внутри PersonnelRecord.

## 4. Scope

### В scope

- новый `Personnel` domain;
- canonical PersonnelRecord с immutable DB identity;
- ФИО;
- дата и место рождения;
- гражданство;
- национальность;
- вероисповедание;
- typed personnel identifiers;
- initial identifier types: personal number, dog tag, table number, call sign;
- card/list/search UI;
- aggregate-level optimistic revision;
- active/archive/restore lifecycle;
- append-only change events;
- owner-only prototype access через существующий `system_owner`;
- migration 015;
- synthetic DB/service/HTTP/browser validation;
- responsive design, desktop acceptance in all three current themes.

### Вне scope

- person → Staffing assignment;
- actual vacancy/occupancy;
- текущая должность/подразделение как поля PersonnelRecord;
- rank history;
- ВУС/qualifications;
- contracts/service history/orders;
- contacts/addresses/family relations;
- documents/file upload/photos;
- medical and physical-identification tables;
- legal/financial/digital-account tables;
- SpecialCases;
- generation of «Анкета», «Объективка», «Контрольно-розыскная карта»;
- fine-grained data access, organizational scope ACL and order-backed role assignments;
- new non-owner Personnel permissions;
- production deployment;
- mobile acceptance.

Out-of-scope означает «следующий отдельный increment», а не исключение из target Personnel model.

## 5. Current repository compatibility

Current `main` after PR #36 contains:

```text
migrations 001–014
Organization Structure v1
Staffing v1
Managed Military Positions Directory v1
Military Ranks v2
Public VUS
Security RBAC / owner wildcard
```

Personnel Core v1 должен:

- не создавать второе Organization tree;
- не изменять Staffing slots;
- не создавать fake assignment/occupancy state;
- не связывать Military Position напрямую с PersonnelRecord;
- не изменять Reference catalogs;
- не выдавать новые grants обычным ролям.

## 6. Aggregate

```text
PersonnelRecord
├── PersonnelIdentifier[*]
└── PersonnelChangeEvent[*]

PersonnelIdentifierType — reference-like system table for identifier semantics.
```

`PersonnelRecord` является aggregate root. Любая mutation child identifier сначала lock'ит PersonnelRecord и использует его `revision`.

## 7. PersonnelRecord

Предлагаемая физическая модель:

```text
personnel_records
├── id BIGINT UNSIGNED PK
├── last_name VARCHAR(100)
├── first_name VARCHAR(100)
├── middle_name VARCHAR(100) NULL
├── birth_date DATE
├── birth_place VARCHAR(255) NULL
├── citizenship VARCHAR(100) NULL
├── nationality VARCHAR(100) NULL
├── religion VARCHAR(150) NULL
├── record_status VARCHAR(16)  -- active|archived
├── revision BIGINT UNSIGNED
├── created_by BIGINT UNSIGNED
├── updated_by BIGINT UNSIGNED
├── created_at DATETIME(6)
├── updated_at DATETIME(6)
├── archived_at DATETIME(6) NULL
└── archived_by BIGINT UNSIGNED NULL
```

### Invariants

- `id` — единственный canonical internal identity;
- ФИО не является unique key;
- `last_name` и `first_name` обязательны;
- `middle_name` допускает отсутствие;
- `birth_date` обязательна для v1 и не может быть будущей датой;
- `record_status` только `active|archived` через CHECK/service/trigger enforcement;
- `revision >= 1`;
- archived record не принимает обычные content mutations;
- physical delete из UI/service отсутствует;
- пользовательские credentials в PersonnelRecord не хранятся.

`citizenship`, `nationality`, `religion`, `birth_place` на первом prototype хранятся как аккуратно ограниченные human-readable values. Нормализация справочниками может быть добавлена отдельно без уничтожения raw values.

## 8. Identifier types

```text
personnel_identifier_types
├── id BIGINT UNSIGNED PK
├── code VARCHAR(64) UNIQUE
├── name VARCHAR(150)
├── description VARCHAR(255) NULL
├── enforce_global_unique TINYINT(1)
├── allow_history TINYINT(1)
├── is_system TINYINT(1)
├── sort_order INT UNSIGNED
├── created_at DATETIME(6)
└── updated_at DATETIME(6)
```

Initial system rows:

```text
personal_number  — Личный номер       — global active uniqueness
service_dog_tag  — Жетон               — global active uniqueness
table_number     — Табельный номер     — no global uniqueness in v1
call_sign        — Позывной             — no global uniqueness in v1
```

Эти значения являются prototype semantics и не реконструируют закрытые классификаторы.

System identifier type нельзя удалить после использования.

## 9. Personnel identifiers

```text
personnel_identifiers
├── id BIGINT UNSIGNED PK
├── personnel_id BIGINT UNSIGNED
├── identifier_type_id BIGINT UNSIGNED
├── value VARCHAR(255)
├── valid_from DATE NULL
├── valid_to DATE NULL
├── note VARCHAR(255) NULL
├── created_by BIGINT UNSIGNED
├── created_at DATETIME(6)
├── ended_by BIGINT UNSIGNED NULL
└── ended_at DATETIME(6) NULL
```

### Invariants

- active identifier = `valid_to IS NULL`;
- один active identifier одного type на одного person в v1;
- для `enforce_global_unique=1` active value уникально между persons;
- история не переписывается заменой строки: replace завершает старую запись и создает новую;
- ended historical identifier не активируется обратно; создается новая active record;
- `valid_to >= valid_from`, если обе даты заданы;
- value trim, length 1–255;
- identifier не существует без PersonnelRecord;
- physical delete historical identifier запрещен service/UI.

DB layer должен обеспечивать per-person active guard и fail-closed global uniqueness для system types, помеченных `enforce_global_unique`.

## 10. Change events

```text
personnel_change_events
├── id BIGINT UNSIGNED PK
├── personnel_id BIGINT UNSIGNED
├── actor_user_id BIGINT UNSIGNED
├── event_type VARCHAR(64)
├── target_type VARCHAR(64)
├── target_id BIGINT UNSIGNED NULL
├── before_values JSON NULL
├── after_values JSON NULL
├── reason VARCHAR(500) NULL
└── occurred_at DATETIME(6)
```

Initial event vocabulary:

```text
personnel.created
personnel.core_updated
personnel.archived
personnel.restored
identifier.added
identifier.replaced
identifier.ended
```

Event table append-only. Update/delete triggers reject mutation.

Sensitive future domains не должны автоматически копировать полный payload в общий event JSON; для v1 event payload содержит только core/identifier fields, входящие в этот increment.

## 11. Lifecycle

```text
active → archived
archived → active
```

Archive/restore — логическое состояние карточки, не утверждение о фактическом увольнении или прохождении службы. Полноценный служебный статус появится в Service Record domain.

Правила:

- create → `active`, revision 1;
- update только `active`;
- identifiers mutate только у `active` record;
- archive требует expected revision и reason;
- restore требует expected revision;
- каждый success increments root revision;
- lifecycle event append-only;
- historical data не удаляется.

## 12. Concurrency

Canonical lock order:

```text
PersonnelRecord
→ relevant PersonnelIdentifier rows
→ append PersonnelChangeEvent
```

Каждая mutation получает `expected_revision`.

Если `expected_revision != current revision`, вся операция отклоняется до изменений.

Transaction включает domain mutation, root revision increment и event append.

## 13. Search/read model

List/search должен поддерживать:

- full/partial ФИО;
- дату рождения;
- active/archived status;
- identifier value across initial types.

Result columns:

```text
ФИО
Дата рождения
Личный номер
Жетон
Табельный номер
Позывной
Статус карточки
Обновлено
```

Отсутствующие identifier values отображаются как `—`.

Search выполняется prepared statements; wildcard input нормализуется; query bounded.

## 14. Card UI

Путь prototype:

```text
/admin/content.php
→ Военнослужащие
→ /admin/personnel/persons.php
→ /admin/personnel/person.php?id=...
```

Card header:

```text
[photo placeholder]
ФИО
Личный номер / жетон / позывной при наличии
Статус карточки
revision / updated timestamp secondary
```

В первом increment реальная photo отсутствует; используется нейтральный placeholder без file storage.

### Реализованные sections

- Обзор;
- Персональные данные;
- Идентификаторы;
- История изменений.

### Visible roadmap sections

Для ориентации в будущей полной карточке допускается показывать компактные non-interactive placeholders:

- Служба и назначения;
- Контакты и семья;
- Документы и фото;
- Медицинские сведения;
- Опознавательные сведения;
- Особые случаи;
- Формы и отчеты.

Каждый placeholder обязан явно иметь статус `Не реализовано в v1`; он не должен создавать ложное впечатление, что данные отсутствуют или проверены.

## 15. Prototype access boundary

По решению владельца fine-grained Personnel security переносится на будущий этап.

V1 использует:

```text
require_system_owner()
```

для всех Personnel routes.

Следствия:

- migration 015 добавляет `0` permissions;
- role grants не меняются;
- `public/admin/content.php` показывает активную плитку Personnel только `system_owner`;
- обычные roles не получают Personnel access;
- это temporary prototype boundary, а не окончательная модель.

## 16. Database migration

```text
database/migrations/015_personnel_core_card_v1.sql
```

Creates:

```text
personnel_records
personnel_identifier_types
personnel_identifiers
personnel_change_events
```

Seed content ограничивается четырьмя identifier type definitions. Personnel rows не seed'ятся.

Обязательные DB controls:

- FKs to `users` where actor fields are stored;
- no cascade delete of Personnel history;
- status CHECK;
- revision CHECK;
- valid date CHECK;
- active identifier per person/type guard;
- trigger/service global uniqueness for configured types;
- append-only event triggers;
- archive mutation guards where practical;
- indexes for ФИО, birth date, status and identifier search.

## 17. Application structure

```text
app/Personnel/
├── PersonnelRepository.php
├── PersonnelService.php
├── PersonnelSupportTrait.php
├── PersonnelCreateUpdateTrait.php
├── PersonnelIdentifierTrait.php
├── PersonnelLifecycleTrait.php
└── functions.php
```

Pattern повторяет established domain split: repositories read, service/traits own transactional commands, controllers remain thin.

## 18. HTTP rules

- authenticated system owner on every route;
- GET only reads/forms;
- POST only mutations;
- CSRF on mutations;
- strict positive integer parsing;
- expected revision mandatory;
- transaction + PRG;
- no raw exception/SQL data in user output;
- escaped HTML;
- `Cache-Control: no-store, private` on Personnel screens;
- `Referrer-Policy: same-origin`;
- `X-Content-Type-Options: nosniff`.

## 19. Themes and responsive UI

Используются существующие theme assets/components без hardcoded colors.

Architecture не требует нового CSS asset. Если существующих компонентов окажется недостаточно для Specification behavior, реализация должна fail closed до scope expansion, а не добавлять неожиданные theme files.

Desktop visual acceptance обязателен для:

```text
asu-blue
asu-light-blue
asu-evgeniya-rostova
```

Responsive markup обязателен, но actual mobile testing остается `NOT RUN / OUT OF SCOPE`.

## 20. Testing strategy

- migration 001–015 clean DB;
- migration 015 after current 014 with backup;
- repeat initialization;
- zero seeded persons;
- identifier type seed exactly expected;
- DB constraints and append-only history;
- stale revision rollback;
- create/update/archive/restore;
- identifier add/replace/end;
- owner access and non-owner denial;
- list/search/card/history;
- synthetic Cyrillic names and identifiers only;
- no real personnel data in repository/tests;
- HTTP smoke;
- three-theme desktop acceptance;
- Organization/Staffing/Directory/Security regression;
- mobile explicitly not claimed.

## 21. Architecture decisions

```text
ADR-P01=Canonical PersonnelRecord; no document-specific person tables
ADR-P02=Position/unit/occupancy excluded until Assignments; no duplicated truth
ADR-P03=Four-table v1 core: records, identifier types, identifiers, change events
ADR-P04=Identifiers are temporal rows, not columns on PersonnelRecord
ADR-P05=All aggregate mutations revision-guarded and transactional
ADR-P06=Archive/restore is card lifecycle, not service-status semantics
ADR-P07=Personnel history append-only
ADR-P08=Full target data model preserved; medical/special/etc deferred, not prohibited
ADR-P09=Fine-grained Personnel security deferred; v1 system_owner only
ADR-P10=No new permissions or non-owner grants in migration 015
ADR-P11=Existing themes/components reused; no planned theme asset expansion
ADR-P12=No real personnel fixture or seed data
```

## 22. Acceptance gate

Architecture passes when Specification and Formal Review confirm:

- same v1 scope;
- same owner-only temporary access boundary;
- no hidden Assignment/Service/Medical/Files scope;
- exact implementation path allowlist;
- exact migration number 015;
- complete validation plan;
- no unresolved blocking/major findings.

Runtime implementation remains prohibited until explicit owner Approval.