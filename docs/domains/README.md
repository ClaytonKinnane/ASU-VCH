# Предметные области АСУ-ВЧ

## Назначение

Каталог `docs/domains` содержит целевые спецификации предметных областей АСУ-ВЧ. Они определяют границы ответственности, инварианты, зависимости, аудит, безопасность и критерии готовности.

Документы доменов являются архитектурными требованиями. Реализованное состояние фиксируется отдельно в `docs/PROJECT-STATUS.md`, `docs/DATABASE-CURRENT.md`, migrations и профильных Test Report.

## Текущая фаза проекта

Проект находится в фазе **incremental implementation**:

```text
Project architecture     — APPROVED
Domain modeling          — CONTINUES PER INCREMENT
Implementation           — STARTED
Functional increments    — PR #1–#9 MERGED
Active increment         — NONE
```

Фундаментальные решения изменяются только через отдельный review и Approval с анализом влияния.

## Карта доменов

| Домен | Текущее состояние |
|---|---|
| Security | Реализован базовый runtime: пользователи, аутентификация, RBAC, approval, password change, rejection, archive/restore |
| Reference | Реализованы специализированные read-only каталоги воинских званий и типов организационных элементов; универсальный reference runtime не заявлен |
| Organization | Утверждена целевая архитектура; реализован только открытый классификатор типов, без конкретных частей, дерева и подчинённости |
| Audit | Аудит критических пользовательских операций реализован внутри соответствующих инкрементов; общий доменный журнал ещё не реализован |
| Infrastructure | Реализованы installer, migrations, local deploy, theme registry, health и CLI checker'ы |
| Documents | Целевая архитектура подготовлена; runtime не реализован |

Будущие направления:

```text
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

## Текущие границы Organization

Справочник типов организационных элементов:

- содержит только общие открытые типы;
- отделяет тип от организационного класса;
- не содержит реальных воинских частей, номеров и дислокации;
- не моделирует фактическую структуру или подчинённость;
- не является штатным расписанием;
- остаётся read-only.

Конкретные структуры могут появиться только в отдельном инкременте с собственной моделью узлов и отношений.

## Допустимые зависимости

Целевые направления зависимостей:

```text
Security       → Audit
Security       → Reference
Organization   → Reference
Organization   → Audit
Documents      → Security / Reference / Organization / Audit
Infrastructure → внешние технические системы
```

`Reference` не зависит от прикладных доменов. `Security` не зависит от `Organization` для входа и авторизации.

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
- `ORGANIZATION.md` и review;
- `DOCUMENTS.md`, review и approval;
- ERD и migration specifications в соседних каталогах.

Эти документы могут описывать целевую модель шире текущей реализации и не должны трактоваться как доказательство существования runtime-функции.
