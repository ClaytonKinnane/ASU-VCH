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
- [Текущая карта доменов](domains/README.md)
- [Текущий index migrations](migrations/README.md)

Каноническое функциональное состояние фиксируют `PROJECT-STATUS.md` и `DATABASE-CURRENT.md`. Фактическую схему определяют executable migrations, installer и профильные checker'ы.

Current HEAD определяется через `origin/main`:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

Living docs не хранят самореферентный current-main SHA, изменчивое состояние активного PR или текущий branch inventory как постоянно актуальное поле. Exact states фиксируются в датированных evidence snapshots; live repository state определяется через GitHub и Git.

### Semantic classification rule

Класс документа определяется не только каталогом, но и содержанием. Раздел является living/current-state, если он сообщает текущий functional baseline, нумерацию migrations, карту реализованных доменов, набор ролей/permissions/themes или repository state.

Поэтому `domains/README.md` и `migrations/README.md` обновляются вместе с functional baseline, хотя соседние документы каталогов могут быть target architecture.

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
2. **Living indexes** — current domain/migration inventories внутри каталогов, которые также содержат target documents.
3. **Target architecture** — утверждённая или исследуемая модель, которая может быть шире runtime.
4. **Historical implemented specifications** — исходные requirements завершённых инкрементов; сохраняются с явным temporal framing.
5. **Historical process/test artifacts** — состояние конкретного gate или попытки.
6. **Operational increment records** — current outcome плюс сохранённая история; после merge получают closure section.
7. **Immutable audit/cleanup records** — датированный snapshot, не бессрочное описание будущего repository state.

Historical `NOT CREATED`, `NOT AUTHORIZED` и `RECHECK REQUIRED` не переписываются задним числом, но не используются как living current assertions.

## Целевая архитектура

- [Целевая архитектура базы данных](DATABASE.md)
- [Предметные области](domains/README.md) — living index с ссылками на target domain documents
- [ERD](erd/)
- [Спецификации миграций](migrations/README.md) — living index с ссылками на target migration specifications

`DATABASE.md` описывает target architecture. Текущее физическое состояние всегда сверяется с `DATABASE-CURRENT.md` и executable migrations.

## Historical implemented specifications

- [Стартовая административная спецификация](STARTER-ADMIN-SPEC.md) — реализована функциональным PR #1; не является текущим implementation plan.

Исходные requirements и известные для своего времени ограничения сохраняются. Current outcome определяется living documentation.

## Documentation consistency audit — 2026-08-02

- [Immutable audit record](DOCUMENTATION-CONSISTENCY-AUDIT-2026-08-02.md)
- [Architecture](architecture/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-ARCHITECTURE.md)
- [Specification](specification/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-SPECIFICATION.md)
- [Formal Review](review/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-FORMAL-REVIEW.md)
- [Approval](decisions/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-APPROVAL.md)
- [Implementation](implementation/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-IMPLEMENTATION.md)
- [Validation](testing/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-VALIDATION.md)

Эти ссылки не фиксируют transient PR/branch state. Live workflow state определяется через GitHub.

## Repository governance

Historical evidence:

- [Documentation consistency audit 2026-08-02](DOCUMENTATION-CONSISTENCY-AUDIT-2026-08-02.md)
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

## Правила актуальности

- GitHub — единственный источник истины для live Git state.
- Current HEAD, PRs, Issues и branches определяются динамически.
- Documentation-only head не объявляется runtime-tested.
- Current-state sections обновляются независимо от расположения файла.
- Target architecture не представляется как уже реализованная schema.
- Historical specifications получают temporal framing, но не переписываются задним числом.
- Mobile PASS не заявляется без фактической acceptance.
- Merge и branch cleanup имеют отдельные approval gates.
- Завершение cleanup собственной documentation branch не требует нового closure increment, если living docs не зависят от её transient state.
