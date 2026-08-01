# Документация АСУ-ВЧ

## Актуальные документы

Эти документы описывают текущий merged baseline и обновляются при изменении состояния проекта:

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

Каноническое функциональное состояние фиксируют `PROJECT-STATUS.md` и `DATABASE-CURRENT.md`. Фактическую схему определяют `database/migrations/*.sql`, installer и профильные integration checker'ы.

Актуальный repository HEAD определяется через `origin/main`:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

Living documentation не хранит самореферентный SHA как постоянно актуальный `current main HEAD`. Точные SHA используются как исторические merge/test/refresh anchors и в датированных evidence snapshots.

## Текущий functional baseline

```text
latest functional PR: #20
PR #19: MERGED
PR #20: MERGED
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
mobile testing: OUT OF SCOPE / NOT RUN
```

Последние функциональные инкременты:

- PR #19 — owner-only read-only справочник типовых воинских должностей;
- PR #20 — owner-only read-only справочник публичных сведений о ВУС.

## Post-PR20 Baseline Refresh

Документационный инкремент:

- [Architecture](architecture/POST-PR20-BASELINE-REFRESH-ARCHITECTURE.md)
- [Specification](specification/POST-PR20-BASELINE-REFRESH-SPECIFICATION.md)
- [Formal Review](review/POST-PR20-BASELINE-REFRESH-FORMAL-REVIEW.md)
- [Approval](decisions/POST-PR20-BASELINE-REFRESH-APPROVAL.md)
- Implementation record и Documentation Validation добавляются в пределах утверждённого scope.

## Исторические process artifacts

Architecture, Specification, Review, Approval и датированные Test Evidence сохраняют состояние соответствующего gate. Формулировки `PENDING`, `NOT CREATED`, `NOT AUTHORIZED`, `RECHECK REQUIRED` или прежние baseline-значения внутри исторического артефакта не заменяют текущее состояние проекта и не переписываются задним числом.

Increment-документы, которые одновременно служат текущим operational record, получают отдельный post-merge closure без удаления истории попыток.

## Repository governance

Исторические administrative evidence:

- [Repository cleanup closure 2026-07-31](REPOSITORY-CLEANUP-2026-07-31.md)
- [Repository audit 2026-07-30](REPOSITORY-AUDIT-2026-07-30.md)
- [Repository audit 2026-07-29](REPOSITORY-AUDIT-2026-07-29.md)

Текущее количество remote branches определяется read-only командой:

```powershell
git ls-remote --heads origin
```

Техническая классификация ветки как merged или safe-to-delete не является разрешением на удаление. Любой cleanup требует fresh inventory и отдельного явного решения владельца проекта.

## Целевая архитектура

Документы этой группы могут описывать более широкий целевой scope, чем фактически реализованный runtime:

- [Целевая архитектура базы данных](DATABASE.md)
- [Стартовая административная спецификация](STARTER-ADMIN-SPEC.md)
- [Предметные области](domains/README.md)
- [ERD](erd/)
- [Спецификации миграций](migrations/README.md)

## Правила актуальности

1. GitHub — единственный источник истины.
2. Текущий HEAD определяется через `origin/main`.
3. Living docs описывают merged baseline; historical artifacts сохраняют состояние своего gate.
4. Текущие возможности подтверждаются merged code, migrations и результатами тестирования.
5. Секреты и `config/local.php` не публикуются.
6. Mobile PASS не заявляется без фактической мобильной приёмки.
7. Merge и branch cleanup требуют отдельных явных разрешений.
