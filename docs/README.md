# Документация АСУ-ВЧ

## Актуальные документы

Living documentation описывает текущий merged functional baseline:

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

Каноническое функциональное состояние фиксируют `PROJECT-STATUS.md` и `DATABASE-CURRENT.md`. Фактическую схему определяют migrations, installer и профильные checker'ы.

Current HEAD определяется через `origin/main`:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

Living docs не хранят самореферентный current-main SHA, изменчивое состояние активного PR или текущий branch inventory как постоянно актуальное поле. Exact states фиксируются в датированных evidence snapshots; live repository state определяется через GitHub и Git.

## Functional baseline

```text
latest functional PR: #20
PR #19: MERGED
PR #20: MERGED
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
mobile testing: OUT OF SCOPE / NOT RUN
```

## Завершённый documentation baseline refresh — PR #21

PR #21 `docs: refresh baseline after PR #19 and PR #20` завершён:

```text
final PR head: 4d44874ef02ffb9381334acfabfa383eba3e4ead
merge method: merge commit
merge commit: f5b53f2ee4453f293b58cbe486e0943ab602335b
repeat Documentation Validation: PASS
Final PR Review attempt 2: PASS
post-merge Git verification: PASS
runtime changes: none
```

После отдельного owner approval выполнен remote-first branch cleanup. Датированный terminal snapshot 2026-08-01 подтвердил `main only` на GitHub и локально, clean working tree и неизменность merge commit PR #21.

- [Post-PR21 Merge and Cleanup Closure 2026-08-01](POST-PR21-MERGE-CLEANUP-CLOSURE-2026-08-01.md)

Process records PR #21:

- [Architecture](architecture/POST-PR20-BASELINE-REFRESH-ARCHITECTURE.md)
- [Specification](specification/POST-PR20-BASELINE-REFRESH-SPECIFICATION.md)
- [Formal Review](review/POST-PR20-BASELINE-REFRESH-FORMAL-REVIEW.md)
- [Approval](decisions/POST-PR20-BASELINE-REFRESH-APPROVAL.md)
- [Implementation](implementation/POST-PR20-BASELINE-REFRESH-IMPLEMENTATION.md)
- [Documentation Validation](testing/POST-PR20-BASELINE-REFRESH-VALIDATION.md)

Closure documentation artifacts:

- [Closure Architecture](architecture/POST-PR21-MERGE-CLEANUP-CLOSURE-ARCHITECTURE.md)
- [Closure Specification](specification/POST-PR21-MERGE-CLEANUP-CLOSURE-SPECIFICATION.md)
- [Closure Formal Review](review/POST-PR21-MERGE-CLEANUP-CLOSURE-FORMAL-REVIEW.md)
- [Closure Approval](decisions/POST-PR21-MERGE-CLEANUP-CLOSURE-APPROVAL.md)
- [Closure Implementation](implementation/POST-PR21-MERGE-CLEANUP-CLOSURE-IMPLEMENTATION.md)
- [Closure Validation](testing/POST-PR21-MERGE-CLEANUP-CLOSURE-VALIDATION.md)

Эти process/evidence files являются историческими gate records и не используются как live status проекта.

## Классы документации

1. **Living documentation** — merged functional baseline и устойчивые завершённые governance facts.
2. **Historical process/test artifacts** — состояние конкретного gate или попытки.
3. **Operational increment records** — current outcome плюс сохранённая история; после merge получают closure section.
4. **Immutable cleanup records** — датированный terminal snapshot, не бессрочное описание будущего repository state.

Historical `NOT CREATED`, `NOT AUTHORIZED` и `RECHECK REQUIRED` не переписываются задним числом, но не используются как living current assertions.

## Repository governance

Historical evidence:

- [Post-PR21 merge and cleanup closure 2026-08-01](POST-PR21-MERGE-CLEANUP-CLOSURE-2026-08-01.md)
- [Repository cleanup closure 2026-07-31](REPOSITORY-CLEANUP-2026-07-31.md)
- [Repository audit 2026-07-30](REPOSITORY-AUDIT-2026-07-30.md)
- [Repository audit 2026-07-29](REPOSITORY-AUDIT-2026-07-29.md)

Текущее состояние определяется динамически:

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git status --short
```

Для GitHub дополнительно проверяются open Pull Requests и Issues. Датированный `main only` snapshot не запрещает создание позднейших утверждённых веток.

`SAFE TO DELETE` не является разрешением. Cleanup требует post-merge verification, fresh inventory, exact batch и отдельное owner approval.

## Целевая архитектура

- [Целевая архитектура базы данных](DATABASE.md)
- [Стартовая административная спецификация](STARTER-ADMIN-SPEC.md)
- [Предметные области](domains/README.md)
- [ERD](erd/)
- [Спецификации миграций](migrations/README.md)

## Правила актуальности

- GitHub — единственный источник истины.
- Current HEAD, PRs, Issues и branches определяются динамически.
- Documentation-only head не объявляется runtime-tested.
- Mobile PASS не заявляется без фактической acceptance.
- Merge и branch cleanup имеют отдельные approval gates.
- Завершение cleanup собственной documentation branch не требует нового closure increment, если living docs не зависят от её transient state.
