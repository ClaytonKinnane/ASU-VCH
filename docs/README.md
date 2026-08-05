# Документация АСУ-ВЧ

## Актуальные документы

Living documentation описывает текущий durable merged baseline:

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
- [GitHub Local Automation](../tools/github-automation/README.md)

Каноническое functional состояние фиксируют `PROJECT-STATUS.md` и `DATABASE-CURRENT.md`. Фактическую schema определяют executable migrations, installer и профильные checker'ы. Live Git state определяется через GitHub/Git.

## Current baseline

```text
latest functional runtime baseline: PR #24 / migration 012
static CI baseline: PR #25
documentation governance baseline: PR #28
local automation foundation: PR #29
local automation corrected baseline: PR #30
durable technical capability coverage: through PR #30
migrations: 001–012
system roles: 4
system permissions: 25
built-in themes: 3
required CSS assets per theme: 10
GitHub Actions Static Verification: implemented
required status check: not enabled
branch protection: not enabled
active functional increment: none
active material technical increment: none
mobile testing: OUT OF SCOPE / NOT RUN
```

PR #24 добавил Military Ranks Directory v2 и migration 012. PR #25 добавил static CI Stage A. PR #28 закрепил terminal documentation model. PR #29 добавил local Git/GitHub/Codex automation package, а PR #30 исправил и усилил Windows PowerShell 5.1 first-run path и native regression harness.

Current HEAD определяется динамически:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

Living docs не хранят self-referential current-main SHA или transient PR/branch inventory. Exact SHA фиксируются как historical anchors в dated evidence.

## Классы документации

1. **Living documentation** — текущий durable merged baseline.
2. **Living indexes** — current domain/migration inventories внутри target catalogs.
3. **Target architecture** — модель, которая может быть шире runtime.
4. **Historical implemented specifications** — исходные requirements завершённых increments.
5. **Historical gate records** — Architecture, Specification, Formal Review, Approval, Implementation, Validation/Test Report и Final PR Review как snapshots соответствующего этапа.
6. **Immutable audit/cleanup records** — датированный snapshot.

Semantic classification overrides directory classification. Current-state section обновляется независимо от каталога файла.

Исторические `PENDING`, `NEXT GATE`, `NOT AUTHORIZED` и `NOT PERFORMED` сохраняются, если верно описывают состояние своего gate. Они не являются текущими открытыми задачами только из-за того, что позднейший gate уже завершён:

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

Architecture, Specification, Formal Review, Approval, Implementation, Validation и Final PR Review не переписываются лишь для копирования последующих lifecycle events.

## Terminal documentation model

Living documentation хранит устойчивое состояние проекта. Изменяемый repository lifecycle определяется динамически через GitHub/Git:

- текущие PR и их base/head;
- review submissions и review threads;
- Actions runs и job logs;
- текущий `main` SHA;
- branch inventory и события удаления веток.

Lifecycle новейшего documentation reconciliation PR не копируется обратно в living Markdown. Его номер, head SHA, merge commit, Actions run и cleanup остаются canonical в GitHub. Отсутствие такой копии после merge не является documentation defect и не требует post-merge Markdown closure.

Новый documentation increment создаётся только при реальной ошибке durable living state, broken normative rule, некорректной ссылке, security/testing claim defect или ином содержательном изменении.

## Последние завершённые durable increments

### Functional PR #24 — Military Ranks Directory v2

- migration 012;
- current v2 и historical/superseded v1;
- 8 compositions/categories и 8 semantic records;
- 20 unchanged rank codes/names/order;
- 2 version sources и 8 composition sources;
- 18 lifecycle/integrity/immutability triggers;
- read-only compatibility service;
- automated, DB, deploy/parity, HTTP smoke и manual desktop testing: PASS;
- post-merge verification: PASS;
- mobile: OUT OF SCOPE / NOT RUN.

### Static CI PR #25 — GitHub Actions Static Verification v1

- workflow `ASU-VCH Static Verification`;
- PR/push/manual triggers;
- Ubuntu 24.04 / PHP 8.5;
- read-only token;
- `git diff --check`, tracked PHP lint, 9 CI-safe checker'ов и clean-worktree guard;
- post-merge push run `30837637886`: SUCCESS;
- post-merge workflow_dispatch run `30839122892`: SUCCESS;
- required status check: not enabled;
- branch protection/settings: unchanged.

### Documentation governance PR #28 — Terminal Documentation Consistency

- terminal documentation model;
- GitHub/Git as canonical source for mutable lifecycle evidence;
- `HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK`;
- recursive closure solely for copying PR lifecycle: prohibited;
- runtime, database, workflow, tools and settings: unchanged.

### Local automation PR #29 — GitHub Local Automation Bootstrap

- one-command Windows PowerShell 5.1 bootstrap;
- Git, GitHub CLI, Node.js LTS and Codex setup flows;
- Codex authentication modes;
- integrity manifest and atomic helper installation;
- fail-closed remote branch cleanup helper;
- user guide and local Codex instructions;
- repository/static validation boundary preserved.

### Corrective PR #30 — PowerShell 5.1 First-Run Hardening

- authoritative native exit-code handling;
- separated stdout/stderr;
- safe `.cmd`/`.bat` invocation through `%ComSpec%`;
- collection normalization and bounded process timeouts;
- explicit ChatGPT/API-key mode enforcement;
- API key through secure stdin only;
- Cleanup Doctor gating;
- native Windows PowerShell 5.1 validation: `58 PASS / 0 FAIL`;
- exact-head PR workflow and post-merge push verification: SUCCESS;
- runtime, DB, migrations, themes, deploy, workflow and repository settings: unchanged.

Real GitHub/Codex authentication, paid API requests and complete target-machine installation acceptance are not inferred from mock/native regression evidence.

## Documentation consistency records

### 2026-08-03 — Current-State Reconciliation v2 — completed

- [Immutable audit](DOCUMENTATION-CURRENT-STATE-AUDIT-2026-08-03.md)
- [Architecture](architecture/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-ARCHITECTURE.md)
- [Specification](specification/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-SPECIFICATION.md)
- [Formal Review](review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-FORMAL-REVIEW.md)
- [Approval](decisions/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-APPROVAL.md)
- [Implementation](implementation/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-IMPLEMENTATION.md)
- [Validation](testing/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-VALIDATION.md)
- [Final PR Review](review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-PR-FINAL-REVIEW.md)

```text
PR: #26 CLOSED / MERGED
exact reviewed head: 7f9d0c0b04de2930abb00a0feedc5d2e375dbaea
merge commit: d9cb74245e09d8be6cd80fc5d7972e426d0aaaf7
post-merge push run: 30846778001 / SUCCESS
post-merge verification: PASS
original documentation branch: deleted after separate approval
```

### 2026-08-03 — Current-State Reconciliation v2 Closure — completed

- [Architecture](architecture/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-ARCHITECTURE.md)
- [Specification](specification/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-SPECIFICATION.md)
- [Formal Review](review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-FORMAL-REVIEW.md)
- [Approval](decisions/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-APPROVAL.md)
- [Implementation](implementation/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-IMPLEMENTATION.md)
- [Validation](testing/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-VALIDATION.md)
- [Final PR Review](review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-PR-FINAL-REVIEW.md)

Closure исправил durable living-status сведения PR #26 и сохранил исторические gate facts. Позднее PR #28 заменил recursive closure terminal documentation model.

### 2026-08-02 — Full Documentation Consistency Reconciliation

- [Immutable audit](DOCUMENTATION-CONSISTENCY-AUDIT-2026-08-02.md)
- [Validation](testing/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-VALIDATION.md)

Historical PR #21 и cleanup records сохраняются как dated evidence, а не current project status.

## Целевая архитектура

- [Целевая архитектура базы данных](DATABASE.md)
- [Предметные области](domains/README.md)
- [ERD](erd/)
- [Спецификации migrations](migrations/README.md)

`DATABASE.md` является target architecture. Current physical state сверяется с `DATABASE-CURRENT.md` и executable migrations.

## Repository governance

Current PRs, Issues и branches проверяются динамически:

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git status --short
```

`SAFE TO DELETE` не является authorization. Merge и cleanup имеют отдельные owner gates. Mobile PASS не заявляется без фактической acceptance.
