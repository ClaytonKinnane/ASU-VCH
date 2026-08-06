# План развития PersonnelServiceAccounting в «АСУ-ВЧ»

## 1. Принцип развития

Разработка ведется снизу вверх: от нижнего подразделения и его штатной структуры к персональной карточке, назначениям, служебной истории, документам, отчетности и агрегации на вышестоящих уровнях.

Единственный функциональный контур:

```text
PersonnelServiceAccounting
```

`CitizenMilitaryAccounting` исключен решением владельца и не включается ни в один инкремент.

Каждый material increment проходит полный процесс:

```text
Research
→ Analysis
→ Architecture
→ Specification
→ Review
→ Approval
→ Implementation
→ Testing/Validation
→ Commit
→ Push
→ Pull Request
→ exact-head Actions
→ Final PR Review
→ Merge approval
→ Merge
→ Post-merge verification
→ Branch deletion approval
→ Branch deletion
```

## 2. Общие ограничения roadmap

- отдельная ветка для каждого material scope;
- зависимый инкремент не реализуется до merge и post-merge verification основания;
- реальные персональные данные не используются в тестовых сценариях;
- сведения ограниченного доступа не загружаются до утверждения защищенного контура;
- mobile acceptance не входит в обычный scope;
- static CI не заменяет MySQL, migrations, deploy, HTTP/browser и visual acceptance;
- каждая запись с юридическим значением должна иметь документ-основание и audit trail;
- государственный Реестр воинского учета, Реестр повесток и учет граждан не имитируются.

## 3. Целевая последовательность

### Increment 0. Data Classification and Security Foundation

**Цель:** определить, какие кадровые данные допустимы в текущем контуре до появления персональных карточек.

Scope:

- категории информации;
- правовые цели обработки;
- data owner;
- need-to-know;
- field-level permissions;
- scope/ABAC;
- аудит просмотра, печати, скачивания и экспорта;
- модель угроз;
- требования к размещению;
- сроки хранения;
- запрет загрузки секретных сведений в общий контур.

Результат:

```text
data category × role × operation × organizational scope
```

### Increment 1. Lowest Unit Staffing Structure v1

**Рекомендуемый первый функциональный инкремент.**

Scope:

- организационный узел нижнего подразделения;
- штатный документ;
- версии штатного документа;
- индивидуальная штатная позиция;
- связь с должностью;
- допустимое воинское звание;
- допустимая ВУС из разрешенного справочника;
- состояние позиции;
- read-only desktop dashboard;
- scoped permissions;
- audit;
- без персональных данных.

Основные состояния позиции:

```text
draft
active-vacant
active-occupied
temporarily-substituted
suspended
closed
historical
```

В v1 фактическое состояние `occupied` может быть зарезервировано для будущих назначений и не должно вводиться вручную как ложное кадровое утверждение.

### Increment 2. Personnel Core Card v1

Scope:

- immutable person ID;
- ФИО и история ФИО;
- дата и место рождения;
- гражданство;
- минимальные идентификационные реквизиты;
- личный номер, если разрешен;
- контакты;
- фотография без распознавания лица;
- field-level permissions;
- audit;
- без полного личного дела и назначения на позицию.

### Increment 3. Assignments and Service History v1

Scope:

- person → staffing slot;
- постоянное и временное назначение;
- интервалы действия;
- запрет несовместимых пересечений;
- основание и приказ;
- история переводов;
- включение и исключение из списков;
- служебные периоды;
- агрегаты штат/список/вакансии.

### Increment 4. Orders and Service Events v1

Scope:

- реестр приказов и выписок;
- типы служебных событий;
- связь одного приказа с несколькими событиями;
- project/verified/approved/cancelled states;
- effective date;
- before/after state;
- correction event вместо удаления истории;
- маршруты согласования;
- audit.

### Increment 5. Documents and Photo Vault v1

Scope:

- metadata-first document model;
- защищенное object storage;
- версии;
- SHA-256;
- MIME/signature validation;
- антивирусный карантин;
- classification;
- approval/signature status;
- physical-original location;
- retention and legal hold;
- audit просмотра, скачивания и печати;
- фотографии и миниатюры без распознавания лица.

### Increment 6. Ranks, VUS and Qualifications v1

Scope:

- история воинских званий;
- приказы о присвоении;
- разрешенные ВУС;
- классная квалификация;
- образование;
- курсы;
- языки;
- водительские категории;
- допуски;
- сроки действия;
- document evidence.

Официальные справочники ограниченного доступа не публикуются и не реконструируются.

### Increment 7. GAR/FIAS Address Subsystem v1

Scope:

- импорт официального пакета ГАР/ФИАС;
- staging/diff/approval/rollback;
- история адресных объектов;
- FIAS/GAR identifiers;
- ОКТМО/ОКАТО;
- поиск и нормализация;
- адрес регистрации;
- фактический и временный адрес;
- ручной адрес-исключение;
- legacy mapping КЛАДР;
- quality tasks.

### Increment 8. Reference Data Governance v1

Scope:

- source package;
- официальный источник;
- checksum/signature, если доступна;
- version/effective dates;
- quarantine;
- staging;
- diff;
- approval абсолютным администратором;
- transactional apply;
- rollback;
- aliases и legacy mappings без изменения official record;
- import report.

### Increment 9. Personnel Data Quality v1

Scope:

- внутренние кампании проверки данных;
- обязательность полей;
- документы-основания;
- field-level discrepancies;
- расхождение подразделение ↔ кадровый орган;
- конфликтующие назначения;
- просроченные документы;
- ownership и deadlines;
- resolution evidence;
- immutable result snapshot;
- inspection findings.

Это внутренний кадровый процесс, а не ежегодная сверка организации с военным комиссариатом по приказу № 700.

### Increment 10. Personnel Reporting and Cards v1

Scope:

- списочный состав;
- штат/список/вакансии;
- назначения;
- служебная история;
- звания;
- ВУС и квалификации;
- отсутствия;
- сроки документов;
- quality dashboards;
- versioned templates;
- as-of reports;
- reproducible PDF/print;
- approval/signature;
- output hash;
- классификация и права экспорта.

Формы первичного воинского учета граждан и формы организаций не входят в scope.

### Increment 11. Leave Planning v1

Scope:

- ежегодное право;
- основной и дополнительные отпуска;
- пожелания;
- предложение подразделения;
- проверка минимального присутствия;
- проверка конфликтов;
- кадровое согласование;
- решение командира;
- приказ;
- plan/fact/balance;
- перенос, продление и отзыв;
- версии графика;
- запрет автоматического окончательного решения.

### Increment 12. Higher-Echelon Personnel Aggregation v1

Scope:

- configurable parent-child hierarchy;
- staffing aggregates;
- vacancy aggregates;
- personnel counts по разрешенным разрезам;
- data quality;
- document deadlines;
- leave load;
- drill-down только с отдельным правом;
- prevention of cross-unit leakage;
- без показателей боевой готовности.

### Increment 13. Authorized Personnel Systems Integration Gateway

Scope только после отдельного официального допуска:

- interface contract;
- participant identity;
- qualified electronic signature, где требуется;
- certified cryptographic protection, где требуется;
- message journal;
- idempotency;
- data minimization;
- rejection and reconciliation queues;
- disaster recovery;
- legal and security acceptance.

Интеграция с государственными реестрами воинского учета не является целевой функцией этого roadmap.

## 4. Удаленные направления

Из roadmap полностью удалены:

- Citizen Military Accounting Boundary v1;
- карточка гражданина, подлежащего воинскому учету;
- общий и специальный учет граждан;
- постановка и снятие с воинского учета;
- бронирование;
- повестки;
- государственный Реестр воинского учета;
- Реестр повесток;
- формы № 6–10 приказа № 700;
- интеграция с процессами военных комиссариатов для учета граждан.

## 5. Ветки

Research:

```text
research/military-accounting-order-700
```

Примеры будущих веток:

```text
feature/data-classification-security-foundation
feature/lowest-unit-staffing-v1
feature/personnel-core-card-v1
feature/personnel-assignments-service-history-v1
feature/personnel-orders-events-v1
feature/personnel-document-vault-v1
feature/personnel-ranks-vus-qualifications-v1
feature/gar-fias-addresses-v1
feature/reference-data-governance-v1
feature/personnel-data-quality-v1
feature/personnel-reporting-v1
feature/personnel-leave-planning-v1
feature/personnel-higher-echelon-aggregation-v1
```

Одновременные ветки допускаются только для независимых scope. Зависимые feature-ветки не должны расходиться до merge основания.

## 6. Acceptance matrix

### 6.1. Research/documentation

- official-source traceability;
- точная применимость к `PersonnelServiceAccounting`;
- отсутствие скрытого `CitizenMilitaryAccounting`;
- внутренняя согласованность;
- exact path allowlist;
- отсутствие runtime/config/DB diff.

### 6.2. Database/domain

- migration на чистой и текущей БД;
- ограничения целостности;
- temporal interval tests;
- authorization tests;
- audit tests;
- concurrency tests;
- rollback/repair strategy;
- backup/restore impact.

### 6.3. UI

- desktop browser acceptance;
- role/scope visibility;
- keyboard and validation behavior;
- отсутствие утечки данных в ошибках, поиске и экспорте;
- mobile acceptance только отдельным инкрементом.

### 6.4. File storage

- MIME/signature spoofing;
- antivirus/quarantine;
- hash/integrity;
- direct-link denial;
- authorization;
- backup/restore;
- retention/legal hold;
- audit.

### 6.5. Reference import

- provenance;
- malformed package rejection;
- oversized package handling;
- staging/diff;
- approval;
- transactional apply;
- rollback;
- idempotency;
- conflict handling;
- audit.

## 7. Первый функциональный инкремент

```text
NAME=Lowest Unit Staffing Structure v1
CLASSIFICATION=functional
DEPENDENCY=existing Organizational Structure v1
```

### In scope

- один или несколько организационных узлов нижнего подразделения;
- штатный документ;
- draft/current/historical versions;
- individual staffing slots;
- position reference;
- allowed rank range;
- allowed VUS links из разрешенного справочника;
- active/vacant/suspended/closed states;
- read-only desktop list and details;
- scoped permissions;
- audit events;
- migration after current migration 012;
- tests and documentation.

### Out of scope

- реальные военнослужащие;
- ФИО;
- фотографии;
- персональные документы;
- назначения лиц;
- приказы по лицам;
- отпуска;
- медицинские сведения;
- внешние интеграции;
- production deployment;
- mobile acceptance;
- секретные или ограниченные сведения.

## 8. Решения, требуемые Architecture

Architecture первого инкремента должна определить:

1. отдельный slot или количественная строка;
2. жизненный цикл штатного документа;
3. правила effective dates;
4. связь с существующим Organization Structure;
5. допустимые существующие справочники;
6. источник состояния вакансии до появления назначений;
7. права по подразделению;
8. audit events;
9. DB constraints;
10. read-only UI и маршруты;
11. migration and validation plan.

## 9. Текущий статус

```text
Research: prepared and reframed
Analysis: prepared and reframed
Architecture for Increment 1: NEXT
Specification for Increment 1: NOT COMPLETED
Review for Increment 1: NOT COMPLETED
Approval of exact Architecture/Specification: REQUIRED
Implementation: NOT STARTED
Research PR: NOT CREATED
Branch deletion: NOT AUTHORIZED
```