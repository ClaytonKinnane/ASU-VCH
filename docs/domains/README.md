# Предметные области АСУ-ВЧ

## Назначение

Каталог `docs/domains` содержит целевые спецификации предметных областей АСУ-ВЧ. Они определяют границы ответственности, инварианты, зависимости, аудит, безопасность и критерии готовности.

Документы доменов являются архитектурными требованиями. Реализованное состояние фиксируется отдельно в `docs/PROJECT-STATUS.md`, `docs/DATABASE-CURRENT.md`, executable migrations и профильных Test Report.

## Текущая фаза проекта

Проект находится в фазе **incremental implementation**:

```text
Project architecture     — APPROVED
Domain modeling          — CONTINUES PER INCREMENT
Implementation           — STARTED
Functional increments    — PR #1–#9, #12, #15 MERGED
Active functional increment — NONE
```

Фундаментальные решения изменяются только через отдельный Review и Approval с анализом влияния.

## Карта доменов

| Домен | Текущее состояние |
|---|---|
| Security | Реализован базовый runtime: пользователи, аутентификация, RBAC, approval, password change, rejection, archive/restore |
| Reference | Реализованы специализированные read-only каталоги воинских званий и типов организационных элементов; универсальный reference runtime не заявлен |
| Organization | Частично реализован runtime Organizational Structure v1: structures, versions, draft tree, document metadata, history и compare |
| Audit | Аудит критических операций реализован внутри Security и Organization; общий доменный журнал ещё не реализован |
| Infrastructure | Реализованы installer, migrations, local deploy, theme registry, health и CLI checker'ы |
| Documents | Общий Documents runtime не реализован; Organization хранит только собственную document metadata и связи с версиями |

Будущие направления:

```text
Personnel
Positions and assignments
Orders
Medical
Equipment
Transport
Training
Archive domain
Notifications
```

Они не являются активными задачами и требуют полного documentation-first цикла.

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
- должности и штатные позиции;
- кадровые назначения;
- численность, вооружение и иные закрытые сведения;
- общий Documents domain и document files;
- общий Audit domain.

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
→ Domain Specification
→ Architecture options
→ Recommendation
→ Formal Review
→ Approval
→ ERD / Migration Specification
→ Implementation
→ Integration Tests
→ UI / Browser Acceptance
→ Pull Request
→ Separate Merge Approval
```

Migration и runtime-код не создаются до утверждения соответствующей спецификации.

## Существующие архитектурные документы

- `SECURITY.md` и `SECURITY-REVIEW.md`;
- `REFERENCE.md`, review и approval;
- `ORGANIZATION.md`, review и Organizational Structure v1 addenda;
- `DOCUMENTS.md`, review и approval;
- ERD и migration specifications в соседних каталогах.

Эти документы могут описывать целевую модель шире текущей реализации и не должны трактоваться как доказательство существования runtime-функции.
