# Документация АСУ-ВЧ

## Актуальные документы

Living documentation описывает текущий merged functional baseline и обновляется после material merge:

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

Каноническое функциональное состояние фиксируют `PROJECT-STATUS.md` и `DATABASE-CURRENT.md`. Фактическую схему определяют migrations, installer и профильные integration checker'ы.

Актуальный HEAD определяется через `origin/main`:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

Exact SHA используются как historical merge/test/refresh anchors, а не как самореферентное current-main поле.

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

## Post-PR20 Baseline Refresh

Текущий documentation-only инкремент:

```text
branch: docs/post-pr20-baseline-refresh
PR: #21 OPEN
classification: Markdown only
approved changed-path count: 25
merge: NOT AUTHORIZED
branch deletion: NOT AUTHORIZED
```

Process records:

- [Architecture](architecture/POST-PR20-BASELINE-REFRESH-ARCHITECTURE.md)
- [Specification](specification/POST-PR20-BASELINE-REFRESH-SPECIFICATION.md)
- [Formal Review](review/POST-PR20-BASELINE-REFRESH-FORMAL-REVIEW.md)
- [Approval](decisions/POST-PR20-BASELINE-REFRESH-APPROVAL.md)
- [Implementation](implementation/POST-PR20-BASELINE-REFRESH-IMPLEMENTATION.md)
- [Documentation Validation](testing/POST-PR20-BASELINE-REFRESH-VALIDATION.md)

Первый Final PR Review PR #21 выявил incomplete operational closure PR #19 и stale post-PR markers. Scope отдельно расширен с 22 до 25 Markdown-путей; remediation и повторная validation выполняются без runtime changes.

## Классы документации

1. **Living documentation** — текущий merged baseline.
2. **Historical process/test artifacts** — состояние конкретного gate или попытки.
3. **Operational increment records** — current status плюс сохранённая история; после merge получают closure section.

Исторические `NOT CREATED`, `NOT AUTHORIZED` и `RECHECK REQUIRED` не переписываются задним числом, но не должны оставаться current-status assertions.

## Repository governance

Исторические evidence:

- [Repository cleanup closure 2026-07-31](REPOSITORY-CLEANUP-2026-07-31.md)
- [Repository audit 2026-07-30](REPOSITORY-AUDIT-2026-07-30.md)
- [Repository audit 2026-07-29](REPOSITORY-AUDIT-2026-07-29.md)

Текущее количество remote branches определяется динамически:

```powershell
git ls-remote --heads origin
```

Technical `SAFE TO DELETE` не является разрешением. Cleanup требует post-merge verification, fresh inventory, exact batch и отдельное owner approval.

## Целевая архитектура

- [Целевая архитектура базы данных](DATABASE.md)
- [Стартовая административная спецификация](STARTER-ADMIN-SPEC.md)
- [Предметные области](domains/README.md)
- [ERD](erd/)
- [Спецификации миграций](migrations/README.md)

## Правила актуальности

- GitHub — единственный источник истины.
- Current HEAD определяется через `origin/main`.
- Documentation-only head не объявляется runtime-tested.
- Mobile PASS не заявляется без фактической mobile acceptance.
- PR creation, merge и branch cleanup имеют отдельные approval gates.
