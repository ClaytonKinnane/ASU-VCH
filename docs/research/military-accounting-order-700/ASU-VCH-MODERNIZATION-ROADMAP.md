# План продолжения разработки и модернизации «АСУ-ВЧ»

## 1. Принцип развития

Разработка начинается с нижнего организационного уровня, но авторитетная персональная запись остается в кадровом органе воинской части. Нижнее подразделение получает простой рабочий контур для подтверждения состава, штатных позиций, назначений, отсутствий и задач по актуализации.

Каждый этап является отдельным material increment и проходит полный процесс:

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing/Validation → Commit → Push → Pull Request
→ exact-head Actions → Final PR Review → Merge approval → Merge
→ Post-merge verification → Branch deletion approval → Branch deletion
```

Исследовательская ветка не разрешает реализацию перечисленных этапов.

## 2. Целевая последовательность

### Increment 0. Data Classification and Security Foundation

**Цель:** до появления персональных данных определить допустимый контур.

Scope:

- категории информации: общедоступная, персональные данные, специальные категории, биометрические данные, служебная тайна, государственная тайна;
- data owner и legal purpose;
- need-to-know, field-level permissions, scope-based access;
- аудит просмотра, печати, скачивания и экспорта;
- модель угроз и требования к размещению;
- правила хранения/удаления/архива;
- запрет загрузки секретной информации в общий контур.

Результат: утвержденная security architecture и matrix `data category × role × operation × scope`.

### Increment 1. Lowest Unit Staffing Structure v1

**Рекомендуемый первый функциональный инкремент.**

Scope:

- организационный узел нижнего подразделения;
- штатный документ/версия;
- штатная позиция/слот;
- связь с существующими справочниками должностей, званий и разрешенных ВУС;
- количество позиций и вакансии;
- статус позиции: активна, вакантна, занята, временно замещается, закрыта;
- read-only dashboard нижнего подразделения;
- область ответственности командира/ответственного;
- без ФИО, фото, документов и назначений конкретных лиц.

Почему первым: существующая Organizational Structure v1 уже дает основание; штатные слоты создают корректную структуру для последующих назначений.

### Increment 2. Personnel Identity Card v1

Scope:

- внутренний immutable person identifier;
- ФИО и история ФИО;
- дата/место рождения;
- гражданство;
- минимальные документы идентификации;
- контакты;
- фотография без распознавания лица;
- consent/legal-basis records, где применимо;
- field-level permissions;
- без назначения на должность и без полного личного дела.

### Increment 3. Assignment and Service History v1

Scope:

- назначение person → staffing slot;
- постоянное/временное исполнение;
- интервалы действия;
- запрет пересечений;
- основание/приказ;
- история переводов;
- звание и служебные события;
- агрегаты штат/список/вакансии.

### Increment 4. Documents and Photo Vault v1

Scope:

- metadata-first document model;
- object storage;
- versioning, hashes, antivirus quarantine;
- category/classification;
- approval/signature status;
- physical-original location;
- retention and legal hold;
- access/download/print audit;
- signed document workflow.

### Increment 5. Citizen Military Accounting Boundary v1

Scope:

- отдельная карточка воинского учета гражданина;
- категории учета, общий/специальный учет;
- постановка/снятие/переход статуса;
- разрешенные формы приказа № 700;
- документ воинского учета;
- transition events active service ↔ reserve;
- без несанкционированной интеграции с государственным Реестром.

### Increment 6. GAR/FIAS Address Subsystem v1

Scope:

- импорт официального XML ГАР/ФИАС;
- staging/diff/approval/rollback;
- история адресных объектов;
- ID FIAS/GAR, ОКТМО/ОКАТО;
- поиск и нормализация;
- ручной адрес-исключение;
- legacy mapping КЛАДР;
- задачи последующей сверки.

### Increment 7. Reference Data Governance v1

Scope:

- source packages;
- official source metadata;
- checksum/signature where available;
- version/effective dates;
- absolute-admin approval;
- transactional apply;
- rejected/quarantined packages;
- aliases/mappings without изменения official data;
- import reports.

### Increment 8. Reconciliation and Data Quality v1

Scope:

- кампании ежегодной и внеплановой сверки;
- field-level discrepancies;
- сравнение нескольких источников;
- ownership and deadlines;
- resolution evidence;
- quality indicators;
- inspection findings and remediation;
- immutable result snapshot.

### Increment 9. Statutory Forms and Reporting v1

Scope:

- legal editions and form versions;
- применимые формы приказа № 700;
- списки сверки и журналы;
- `as-of` reports;
- reproducible PDF/print output;
- signature/approval;
- report hash and source dataset version;
- dashboards completeness/accuracy.

### Increment 10. Leave Planning v1

Scope:

- annual entitlement;
- wishes and proposals;
- unit coverage constraints;
- approvals;
- commander order;
- actual leave and changes;
- travel time/additional leave where applicable;
- plan/fact/balance;
- conflict calendar;
- no automatic final approval.

### Increment 11. Higher-Echelon Aggregation v1

Scope:

- configurable parent-child hierarchy;
- aggregated readiness of records, not combat readiness;
- staffing and vacancy aggregates;
- data-quality and reconciliation status;
- leave load;
- drill-down only with explicit right;
- prevention of cross-unit leakage.

### Increment 12. Authorized External Integration Gateway

Scope only after official authorization:

- interface contract;
- participant identity;
- qualified electronic signature;
- certified cryptographic protection;
- message journal and idempotency;
- data minimization;
- reconciliation and rejection queues;
- disaster recovery;
- legal and security acceptance.

No «scraping» or imitation of the government Register is allowed.

## 3. Proposed branch strategy

Research is maintained in:

```text
research/military-accounting-order-700
```

После утверждения roadmap каждый functional increment получает отдельную ветку, например:

```text
feature/lowest-unit-staffing-v1
feature/personnel-identity-card-v1
feature/personnel-assignments-v1
feature/personnel-document-vault-v1
feature/citizen-military-accounting-v1
feature/gar-fias-addresses-v1
feature/reference-governance-v1
feature/reconciliation-quality-v1
feature/reporting-forms-v1
feature/leave-planning-v1
```

Одновременные ветки допускаются только для независимых scope с отдельными base/head/allowlist. Не допускается параллельная реализация зависимого этапа до merge и post-merge verification его основания.

## 4. Acceptance matrix по классам этапов

### Documentation/research

- official-source traceability;
- completeness review;
- internal consistency;
- no runtime/config/DB diff;
- exact changed-path allowlist;
- Markdown validation.

### Database/domain implementation

- migrations on clean and current database;
- rollback strategy where applicable;
- constraints and concurrency tests;
- authorization tests;
- audit tests;
- data migration/import tests;
- backup/restore impact.

### UI

- desktop browser acceptance;
- role/scope visibility;
- keyboard and validation behavior;
- no data leakage in errors/search/export;
- mobile remains out of scope unless separately approved.

### File storage

- antivirus/quarantine tests;
- content-type spoofing tests;
- hash/integrity tests;
- authorization and direct-link denial;
- backup/restore;
- retention/deletion workflow;
- audit.

### Reference imports

- official package provenance;
- malformed/oversized package rejection;
- staging and diff;
- transactional apply;
- rollback;
- idempotency;
- conflict handling;
- admin approval audit.

## 5. Decision points requiring owner approval

Before Architecture of Increment 1 the owner must decide:

1. Какой конкретный организационный узел считается пилотным нижним подразделением без раскрытия реальных закрытых данных.
2. Нужна ли в первой версии только одна штатная версия или draft/current/historical.
3. Требуются ли количественные позиции (`quantity > 1`) или каждая позиция является отдельным slot.
4. Какие существующие публичные справочники разрешено связывать со штатной позицией.
5. Какие роли видят подразделение и агрегаты.
6. Какие поля могут иметь признаки служебной тайны и должны быть исключены из пилота.

## 6. Рекомендация по первому утверждаемому scope

```text
NAME=Lowest Unit Staffing Structure v1
CLASSIFICATION=functional
BASE=current main after re-verification
IN_SCOPE=organizational node, staffing document versions, individual staffing slots, vacancy state, links to public rank/position/VUS references, read-only desktop views, scoped permissions, audit
OUT_OF_SCOPE=real personnel, personal data, photos, files, assignments, orders, leave, medical data, citizen military accounting, external integrations, production deployment, mobile acceptance, classified/restricted data
```

Этот scope минимален, создает фундамент снизу и не требует преждевременной обработки персональных данных.

## 7. Текущий статус

```text
Research: prepared
Analysis: prepared
Architecture: NOT STARTED
Specification: NOT STARTED
Review: NOT AUTHORIZED YET
Approval: NOT GRANTED
Implementation: NOT AUTHORIZED
Pull Request for research branch: NOT AUTHORIZED
Merge: NOT AUTHORIZED
```
