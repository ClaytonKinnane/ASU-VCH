# Предметные области АСУ-ВЧ

## Назначение и классификация

Каталог `docs/domains` содержит целевые спецификации предметных областей АСУ-ВЧ. Они определяют границы ответственности, инварианты, зависимости, аудит, безопасность и критерии готовности.

Этот `README.md` является **living domain index**, поскольку содержит текущую карту реализации. Документы отдельных доменов являются архитектурными требованиями и могут описывать целевую модель шире runtime.

Реализованное состояние фиксируется в `../PROJECT-STATUS.md`, `../DATABASE-CURRENT.md`, executable migrations и профильных Test Report. Наличие решения в целевой доменной спецификации само по себе не доказывает существование runtime-функции.

## Текущая фаза проекта

Проект находится в фазе **incremental implementation**:

```text
Project architecture        — APPROVED
Domain modeling             — CONTINUES PER INCREMENT
Implementation              — STARTED
Functional increments       — PR #1–#9, #12, #15, #19, #20 MERGED
Latest functional PR        — #20
Active functional increment — NONE
```

Фундаментальные решения изменяются только через отдельный Review и Approval с анализом влияния.

## Карта доменов

| Домен | Текущее состояние |
|---|---|
| Security | Реализован базовый runtime: пользователи, аутентификация, RBAC, approval, password change, rejection, archive/restore |
| Reference | Реализованы четыре специализированных owner-only read-only каталога: воинские звания, типы организационных элементов, типовые воинские должности и публичные сведения о ВУС; универсальный reference runtime не заявлен |
| Organization | Частично реализован runtime Organizational Structure v1: structures, versions, draft tree, document metadata, history и compare |
| Audit | Аудит критических операций реализован внутри Security и Organization; общий доменный журнал ещё не реализован |
| Infrastructure | Реализованы installer, migrations, local deploy, theme registry, health и CLI checker'ы |
| Documents | Общий Documents runtime не реализован; Organization хранит только собственную document metadata и связи с версиями |

### Реализованные специализированные Reference-каталоги

| Functional PR | Каталог | Migration |
|---:|---|---:|
| #8 | Составы военнослужащих и воинские звания | 007 |
| #9 | Типы организационных элементов | 008 |
| #19 | Типовые воинские должности | 010 |
| #20 | Публичные сведения о военно-учётных специальностях | 011 |

Каталоги PR #19 и PR #20 основаны только на утверждённом public-source scope. Они не создают кадровые назначения, штатные позиции конкретной организации или связи с персональными данными.

Будущие направления:

```text
Personnel
Staff positions and personnel assignments
Orders
Medical
Equipment
Transport
Training
Archive domain
Notifications
```

`Staff positions and personnel assignments` означает будущую кадровую/штатную модель и не дублирует уже реализованный публичный каталог типов воинских должностей.

Будущие направления не являются активными задачами и требуют полного documentation-first цикла.

## Владение данными

Каждая бизнес-концепция имеет один owning domain. Домен:

- определяет инварианты;
- владеет write-операциями;
- управляет lifecycle;
- публикует согласованные контракты;
- не допускает скрытого изменения чужих таблиц.

Чтение справочных данных не даёт права изменять владеющий домен.

## Реализованные границы Organization

Organizational Structure v1 реализует:

- aggregate root организационной структуры;
- stable organizational elements;
- version lifecycle `draft`, `approved`, `active`, `cancelled`;
- version-scoped дерево узлов;
- создание новой версии на основе действующей либо последней отменённой;
- изменение дерева только в draft;
- catalog-version binding к Reference;
- metadata документов и version-document links внутри Organization;
- immutable change events, history и compare;
- RBAC, CSRF, revision checks и транзакционные операции.

Migration 009 создаёт 7 таблиц и 16 DB triggers. Домен использует 6 permissions `organization.structures.*`.

## Ограничения Organization

Не реализованы:

- карточки военнослужащих;
- штатные позиции конкретной организации;
- кадровые назначения;
- численность, вооружение и иные закрытые сведения;
- общий Documents domain и document files;
- общий Audit domain.

Публичный каталог типов воинских должностей не снимает эти ограничения: он не является штатным расписанием и не содержит фактических назначений.

Metadata документов внутри Organization не передаёт домену владение универсальными документами. Реализованная структура не должна наполняться закрытыми или фактическими сведениями без отдельного утверждения scope и защиты.

## Допустимые зависимости

```text
Security       → Audit
Security       → Reference
Organization   → Reference
Organization   → Audit
Documents      → Security / Reference / Organization / Audit
Infrastructure → внешние технические системы
```

Фактически Organizational Structure v1 читает Reference catalog и хранит actor references на Security users, не меняя Security domain. `Reference` не зависит от прикладных доменов. `Security` не зависит от `Organization` для входа и авторизации.

## Порядок нового доменного инкремента

```text
Research
→ Analysis
→ Architecture
→ Specification
→ Review
→ Approval
→ Implementation
→ Testing
→ Commit
→ Push
→ Pull Request
→ Final PR Review
→ separate merge approval
→ Merge
→ post-merge verification
→ separate branch deletion approval
```

Для DB-инкремента ERD и Migration Specification включаются в Architecture/Specification до Implementation. Migration и runtime-код не создаются до утверждения соответствующей документации.

## Существующие архитектурные документы

- `SECURITY.md` и `SECURITY-REVIEW.md`;
- `REFERENCE.md`, review и approval;
- `ORGANIZATION.md`, review и Organizational Structure v1 addenda;
- `DOCUMENTS.md`, review и approval;
- ERD и migration specifications в соседних каталогах.

Эти документы могут описывать целевую модель шире текущей реализации и не должны трактоваться как доказательство существования runtime-функции.
