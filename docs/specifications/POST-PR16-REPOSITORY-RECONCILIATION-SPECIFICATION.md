# Post-PR16 Repository Reconciliation — Specification

## 1. Статус

```text
increment: Post-PR16 Repository Reconciliation
document type: Specification
status: READY FOR FORMAL REVIEW
architecture commit: d6280f2b365d866f0e1f4cda05eb6e8bc6b48917
implementation: NOT STARTED
branch deletion: NOT AUTHORIZED / NOT PERFORMED
```

## 2. Цель

Привести living documentation и repository-audit layer в соответствие с состоянием после merge PR #16, устранить самореферентное хранение «текущего main SHA» и подготовить доказательную базу для отдельного последующего решения об удалении ненужных веток.

## 3. Исходные факты

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
main at increment start: 72630757c1a72a6bd971cf819cff9bdd36c148bf
last completed documentation PR before reconciliation: #16
last completed documentation merge before reconciliation:
72630757c1a72a6bd971cf819cff9bdd36c148bf
last functional PR: #15
last functional merge commit:
5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD:
238868950c5f7417ea3d1c283610f2d282d4395a
```

Functional baseline:

```text
migrations: 001–009
system roles: 4
system permissions: 25
built-in themes: 3
organization tables: 7
organization triggers: 16
organization permissions: 6
mobile testing: OUT OF SCOPE / NOT RUN
```

## 4. Branch snapshots

### 4.1 Pre-reconciliation snapshot

До создания рабочей ветки:

```text
total branches: 18
main: 1
non-main branches: 17
```

### 4.2 During-reconciliation state

После создания `docs/post-pr16-repository-reconciliation`:

```text
total branches: 19
main: 1
pre-reconciliation non-main branches: 17
active reconciliation branch: 1
current non-main branches: 18
```

### 4.3 Cleanup rule

Ни одна ветка не удаляется в рамках implementation данного инкремента. После merge требуется свежий read-only inventory и отдельное явное разрешение.

## 5. Functional requirements

### FR-001 — Current repository pointer

Living documentation должна определять актуальный repository HEAD через `origin/main`, а не через самореферентное статическое поле `current main HEAD`.

Обязательная воспроизводимая команда:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

### FR-002 — Functional anchor separation

Living documentation должна явно хранить:

```text
last functional PR: #15
last functional merge commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
```

### FR-003 — Documentation anchor separation

Living documentation должна явно хранить исторический anchor:

```text
last completed documentation PR before reconciliation: #16
last completed documentation merge before reconciliation:
72630757c1a72a6bd971cf819cff9bdd36c148bf
```

Формулировка должна оставаться истинной после собственного merge reconciliation-инкремента.

### FR-004 — PR #16 completion

PR #16 должен быть отражён как:

```text
state: MERGED
method: merge
merge commit: 72630757c1a72a6bd971cf819cff9bdd36c148bf
scope: README.md and docs/** only
runtime/deploy/database changes: none
```

### FR-005 — Baseline refresh completion

Post-Organizational-Structure v1 Baseline Refresh должен быть отражён как завершённый documentation-only increment, а не как начатый или текущий.

### FR-006 — Historical audit preservation

`docs/REPOSITORY-AUDIT-2026-07-29.md` не изменяется. Он обозначается как historical pre-refresh snapshot.

### FR-007 — New repository audit

Создаётся:

```text
docs/REPOSITORY-AUDIT-2026-07-30.md
```

### FR-008 — Full branch coverage

Новый audit обязан перечислить все 17 pre-reconciliation non-main веток:

```text
docs/post-organizational-structure-v1-baseline-refresh
feature/organizational-structure-v1
docs/runtime-baseline-self-reference-fix
docs/evgeniya-rostova-theme-v1-post-merge-status
feature/theme-evgeniya-rostova
docs/evgeniya-rostova-theme-v1-design
docs/fix-project-status-audit-state
docs/project-documentation-audit-2026-07-27
feature/organizational-element-types-directory
feature/military-ranks-directory
feature/directories-landing
feature/asu-blue-tile-hover
feature/theme-asu-light-blue
feature/user-archive-restore
feature/user-rejection-audit
feature/required-password-change
feature/initial-site
```

### FR-009 — Ahead/behind evidence

Audit должен зафиксировать:

| Ветка | behind | ahead |
|---|---:|---:|
| `docs/post-organizational-structure-v1-baseline-refresh` | 1 | 0 |
| `feature/organizational-structure-v1` | 25 | 0 |
| `docs/runtime-baseline-self-reference-fix` | 123 | 0 |
| `docs/evgeniya-rostova-theme-v1-post-merge-status` | 126 | 0 |
| `feature/theme-evgeniya-rostova` | 130 | 0 |
| `docs/evgeniya-rostova-theme-v1-design` | 140 | 2 |
| `docs/fix-project-status-audit-state` | 141 | 0 |
| `docs/project-documentation-audit-2026-07-27` | 144 | 0 |
| `feature/organizational-element-types-directory` | 160 | 0 |
| `feature/military-ranks-directory` | 184 | 0 |
| `feature/directories-landing` | 203 | 0 |
| `feature/asu-blue-tile-hover` | 228 | 0 |
| `feature/theme-asu-light-blue` | 246 | 0 |
| `feature/user-archive-restore` | 264 | 0 |
| `feature/user-rejection-audit` | 294 | 0 |
| `feature/required-password-change` | 316 | 0 |
| `feature/initial-site` | 331 | 0 |

Эти значения являются audit snapshot и не должны трактоваться как вечные current values.

### FR-010 — Standard branch classification

Для 16 веток с `ahead = 0` audit должен указывать:

```text
CONTENT FULLY REACHABLE FROM MAIN
TECHNICALLY SAFE TO DELETE
DELETION REQUIRES SEPARATE EXPLICIT APPROVAL
```

### FR-011 — Diverged branch proof

Для `docs/evgeniya-rostova-theme-v1-design` audit обязан содержать:

```text
branch HEAD: 988d803f5659d9d9bf4b23fc24ee83dc0faf4fd1
unique commits: 2
```

И blob proof:

```text
docs/design/EVGENIYA-ROSTOVA-THEME-V1-DESIGN.md
main blob:   709e6fb6896425c5f377e801f379fcb66eb4623f
branch blob: 709e6fb6896425c5f377e801f379fcb66eb4623f

docs/design/EVGENIYA-ROSTOVA-THEME-V1-REVIEW.md
main blob:   e19229a50ee10ee8ed1d7496896d73baee6d08f0
branch blob: e19229a50ee10ee8ed1d7496896d73baee6d08f0
```

Обязательный вывод:

```text
BYTEWISE_DOCUMENT_COMPARISON_STATUS=PASS
```

### FR-012 — Active branch exclusion

Новый audit обязан отдельно описать:

```text
docs/post-pr16-repository-reconciliation
```

Классификация:

```text
ACTIVE / KEEP UNTIL OWN MERGE AND SEPARATE CLEANUP APPROVAL
```

Ветка не включается в уже оценённый cleanup-set из 17 веток.

### FR-013 — No deletion claim

Все затрагиваемые документы должны содержать:

```text
actual branch deletion: NOT PERFORMED
branch deletion authorization: NOT GRANTED
```

### FR-014 — Post-merge cleanup gate

Документация должна требовать:

1. merge reconciliation PR;
2. локальную синхронизацию `main`;
3. новый read-only inventory;
4. проверку всех non-main веток против нового `main`;
5. отдельное явное разрешение;
6. удаление remote branches;
7. `git fetch --prune` и verification.

## 6. Deliverable requirements

### DR-001 — `README.md`

Обновить baseline block так, чтобы:

- `origin/main` был текущим repository pointer;
- PR #15 и PR #16 были разделены;
- tested runtime HEAD не приписывался documentation merge;
- не использовался неоднозначный `merged main commit`.

### DR-002 — `docs/README.md`

- новый audit 2026-07-30 должен стать актуальным repository audit;
- audit 2026-07-29 должен быть обозначен historical snapshot;
- добавить правило о запрете self-referential current-main SHA.

### DR-003 — `docs/PROJECT-STATUS.md`

Должен содержать:

- current pointer `origin/main`;
- last functional PR/merge/tested HEAD;
- last documentation PR #16;
- PR #16 в таблице completed documentation PR;
- текущий branch audit snapshot 18 total / 17 non-main;
- active reconciliation branch отдельно;
- branch deletion not performed.

### DR-004 — `docs/PROJECT.md`

- разделить repository state и functional baseline;
- отметить PR #16 как завершённый documentation increment;
- не менять implemented runtime claims.

### DR-005 — `docs/ROADMAP.md`

- отметить baseline refresh и PR #16 как завершённые;
- описать reconciliation как documentation maintenance increment без формулировки, которая станет ложной после merge;
- сохранить checker cleanup как отдельный будущий technical increment;
- сохранить branch cleanup отдельным административным gate.

### DR-006 — `docs/CHANGELOG.md`

Добавить запись о:

- merge PR #16;
- merge commit `72630757...`;
- локальной синхронизации PASS;
- новом branch audit;
- решении `17 technically safe to delete`;
- отсутствии фактического удаления;
- начале reconciliation process без заявления о его будущем merge.

### DR-007 — `docs/DATABASE-CURRENT.md`

- не менять schema facts;
- заменить неоднозначный repository field;
- явно отделить last functional merge и tested runtime HEAD;
- указать, что documentation-only PR #16 не изменил БД.

### DR-008 — `docs/LOCAL-RUNBOOK.md`

- current SHA должен определяться командой;
- функциональные expected values остаются прежними;
- добавить read-only branch inventory procedure;
- branch deletion commands не включать до отдельного Approval либо поместить только как запрещённый future gate без исполняемого сценария.

### DR-009 — New audit

`docs/REPOSITORY-AUDIT-2026-07-30.md` должен удовлетворять FR-007–FR-014.

## 7. Non-functional requirements

### NFR-001 — Documentation-only

Diff ограничен:

```text
README.md
docs/**
```

### NFR-002 — Historical integrity

Исторические artifacts не переписываются задним числом.

### NFR-003 — Security

Не допускаются:

- credentials;
- DB password;
- test-owner password;
- session data;
- содержимое `config/local.php`;
- private operational data.

### NFR-004 — Mobile claim

Допустимо только:

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```

### NFR-005 — No administrative mutation

Implementation не должно:

- удалять branches;
- изменять refs;
- закрывать или создавать PR без отдельного gate;
- выполнять merge;
- выполнять deploy.

## 8. Validation requirements

### VR-001 — Exact changed paths

Ожидаются:

```text
8 modified living documents
1 new repository audit
process documents for this increment
```

Запрещены runtime/tooling paths.

### VR-002 — Stale marker scan

В living documents не должно оставаться current-state утверждений:

```text
merged main commit: 5aaf0a7...
last completed documentation PR: #14
baseline refresh: started/current
branches assessed: 17 total / 16 non-main as current state
```

Исторические разделы и audit 2026-07-29 исключаются из этого правила.

### VR-003 — Self-reference scan

Living documents не должны использовать точный SHA как постоянно актуальный `current main HEAD`.

### VR-004 — Branch completeness

Все 17 pre-reconciliation non-main веток должны присутствовать ровно по одному разу в inventory table нового audit.

### VR-005 — Blob proof

Оба blob SHA должны совпадать с `main` и special branch.

### VR-006 — Link validation

Все добавленные относительные Markdown-ссылки должны разрешаться в существующие файлы ветки.

### VR-007 — Secret validation

Поиск не должен находить секреты или фактические credentials.

### VR-008 — Git state

Перед PR:

```text
base: main @ 72630757c1a72a6bd971cf819cff9bdd36c148bf
branch behind main: 0
branch changed paths: README.md and docs/** only
branch deletion: none
```

Если `main` изменится до PR, требуется rebase/merge analysis и обновление audit snapshot до продолжения.

## 9. Acceptance criteria

```text
AC-01 Architecture / Specification / Review approved
AC-02 8/8 living documents updated
AC-03 new audit 2026-07-30 created
AC-04 PR #16 represented as merged
AC-05 functional and documentation anchors separated
AC-06 no self-referential current-main SHA in living docs
AC-07 17/17 pre-reconciliation non-main branches assessed
AC-08 16 ancestor branches classified safe
AC-09 diverged branch blob proof PASS
AC-10 active reconciliation branch excluded from cleanup set
AC-11 branch deletion NOT PERFORMED
AC-12 runtime/tooling paths unchanged
AC-13 Markdown links PASS
AC-14 secret scan PASS
AC-15 mobile PASS not claimed
AC-16 fresh post-merge inventory required before deletion
```

## 10. Rejection criteria

Инкремент отклоняется, если:

- удалена хотя бы одна ветка;
- изменён runtime, schema или checker source;
- старый audit переписан;
- PR #16 не отражён как merged;
- текущий repository pointer снова привязан к точному pre-merge SHA;
- пропущена ветка;
- diverged branch не имеет bytewise proof;
- cleanup объявлен выполненным;
- active reconciliation branch включена в pre-approved deletion set;
- заявлен mobile PASS.

## 11. Gate

После Formal Review требуется отдельная формулировка Approval:

> Утверждаю Architecture / Specification / Review для Post-PR16 Repository Reconciliation. Разрешаю реализацию документационного инкремента в ветке `docs/post-pr16-repository-reconciliation`. Удаление веток не разрешаю.
