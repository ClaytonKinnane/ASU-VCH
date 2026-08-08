# Документация АСУ-ВЧ

## Операционные документы — читать первыми

1. [Постоянные правила работы над проектом](PROJECT-WORKING-RULES.md)
2. [Текущий handoff для перехода в новый чат](CHAT-HANDOFF.md)
3. [Текущее состояние проекта](PROJECT-STATUS.md)

`PROJECT-WORKING-RULES.md` — постоянный operational governance. `CHAT-HANDOFF.md` — living snapshot, который проверяется и при необходимости обновляется после значимых действий. Live GitHub/Git остаётся canonical source для mutable branches/PR/reviews/Actions/SHA.

## Current durable baseline

```text
latest merged functional increment: PR #36 / migration 014
PR #35: Lowest Unit Staffing Structure v1 / migration 013
migrations on main: 001–014
system roles: 4
system permissions: 35
built-in themes: 3
required CSS assets per theme: 10
active product implementation increment: none
remote research branch: research/military-accounting-order-700 / unique unmerged research
mobile: NOT RUN / OUT OF SCOPE
```

## Living documentation

- [Текущее состояние проекта](PROJECT-STATUS.md)
- [О проекте](PROJECT.md)
- [Правила разработки](DEVELOPMENT.md)
- [Среда разработки и запуска](ENVIRONMENT.md)
- [Локальный runbook](LOCAL-RUNBOOK.md)
- [Текущее состояние базы данных](DATABASE-CURRENT.md)
- [Темы оформления](THEMES.md)
- [Управление доступом](ACCESS.md)
- [План разработки](ROADMAP.md)
- [История изменений](CHANGELOG.md)
- [Архитектурные паттерны](ARCHITECTURAL-PATTERNS.md)
- [Матрица трассируемости](TRACEABILITY.md)
- [Текущая карта доменов](domains/README.md)
- [Living index migrations](migrations/README.md)
- [GitHub Local Automation](../tools/github-automation/README.md)

## Documentation semantic classes

1. **Living documentation/indexes** — текущий durable merged state; исправляются при substantive change или defect.
2. **Target architecture** — может быть шире runtime; не выдаётся за physical current state.
3. **Historical gate records** — Architecture, Specification, Review, Approval, Implementation, Validation/Test evidence; остаются snapshots своих gate.
4. **Immutable dated audit/cleanup records** — не переписываются задним числом.
5. **GitHub lifecycle evidence** — current SHA, branch/PR/review/Actions/merge/deletion; canonical в GitHub/Git.

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

Directory placement не заменяет semantic classification.

## Current physical/runtime authority

- current product state: `PROJECT-STATUS.md`;
- current DB schema: `DATABASE-CURRENT.md` + executable migrations/checkers;
- current permissions: `ACCESS.md` + migrations;
- active operating context: `CHAT-HANDOFF.md` + live GitHub;
- target database architecture: `DATABASE.md`.

## Последние completed functional increments

### PR #35 — Lowest Unit Staffing Structure v1

- migration 013;
- Staffing registers, versions, documents metadata, slots, VUS requirements, lifecycle/history/compare;
- no Personnel/Assignments/occupancy facts;
- merged and post-merge Actions SUCCESS.

### PR #36 — Military Positions Directory v1

- migration 014;
- managed canonical versioned military-position catalog over existing tables;
- initial 24-entry synthetic canonical draft, no automatic publication;
- stable identity, logical archive/restore, append-only history;
- four permissions, no automatic non-owner grants;
- existing Staffing pins preserved;
- full runner `167 PASS`, HTTP `200/200/302`, all three managed desktop themes PASS;
- open findings 0;
- mobile NOT RUN / OUT OF SCOPE;
- merged; post-merge Actions SUCCESS.

## Documentation audit 2026-08-08

- [Immutable audit](DOCUMENTATION-CURRENT-STATE-AUDIT-2026-08-08.md)
- [Architecture](architecture/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-2026-08-08-ARCHITECTURE.md)
- [Specification](specification/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-2026-08-08-SPECIFICATION.md)
- [Formal Review](review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-2026-08-08-FORMAL-REVIEW.md)
- [Owner Approval](decisions/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-2026-08-08-APPROVAL.md)

Historical reconciliation records from earlier dates remain immutable evidence and are not current-state instructions.

## Repository governance

Current HEAD, branches, PRs and Issues are checked dynamically. `SAFE TO DELETE` is classification, not permission. Branch deletion always requires separate explicit owner authorization. Mobile PASS is not claimed without actual acceptance.
