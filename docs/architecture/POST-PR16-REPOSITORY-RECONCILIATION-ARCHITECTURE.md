# Post-PR16 Repository Reconciliation — Architecture

## 1. Статус документа

```text
increment: Post-PR16 Repository Reconciliation
document type: Architecture
status: READY FOR FORMAL REVIEW
implementation: NOT STARTED
branch deletion: NOT AUTHORIZED / NOT PERFORMED
```

## 2. Контекст

После merge documentation-only PR #16 и локальной fast-forward синхронизации фактический GitHub baseline изменился:

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
main at reconciliation start: 72630757c1a72a6bd971cf819cff9bdd36c148bf
last completed documentation PR: #16
last functional PR: #15
last functional merge commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
```

Runtime, schema и функциональный tested baseline не изменились. PR #16 изменил только `README.md` и `docs/**`.

После merge PR #16 часть living documentation снова стала неточной, потому что документы называли merge commit PR #15 текущим `main` и описывали baseline refresh как незавершённый процесс. Одновременно существующий repository audit фиксирует исторический pre-refresh snapshot и не распространяет вывод автоматически на созданную позднее refresh-ветку.

## 3. Проблема self-reference

Документационный PR не может заранее знать собственный будущий merge commit. Если living document содержит поле вида:

```text
current main HEAD: <SHA до merge этого документационного PR>
```

то после merge этого же PR поле немедленно устаревает.

Поэтому архитектура запрещает использовать точный SHA как постоянно актуальное значение `current main HEAD` в living documentation.

## 4. Архитектурное решение

### 4.1 Четыре слоя документации

Документация разделяется на четыре логических слоя.

#### A. Functional baseline

Содержит устойчивые факты о последнем функциональном инкременте:

```text
last functional PR: #15
last functional merge commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
migrations: 001–009
system roles: 4
system permissions: 25
built-in themes: 3
```

Этот слой не меняется из-за documentation-only merge.

#### B. Repository pointer

Актуальное состояние репозитория определяется динамически:

```powershell
git fetch --prune origin
git rev-parse origin/main
git rev-list --left-right --count main...origin/main
```

Living documentation должна ссылаться на `origin/main` как источник текущего HEAD, а не пытаться постоянно хранить самореферентный SHA.

#### C. Historical merge anchors

Точные merge SHA разрешены как исторические anchors с однозначной семантикой:

```text
last functional merge commit
last completed documentation merge before this reconciliation
```

Для начала инкремента:

```text
last completed documentation PR before reconciliation: #16
last completed documentation merge before reconciliation:
72630757c1a72a6bd971cf819cff9bdd36c148bf
```

Эти формулировки остаются истинными после собственного merge reconciliation-инкремента.

#### D. Dated audit snapshots

Датированный repository audit может содержать точные SHA, число веток, ahead/behind и классификацию, потому что явно фиксирует snapshot на конкретную дату и момент процесса.

## 5. Branch inventory model

### 5.1 Pre-reconciliation snapshot

До создания рабочей ветки данного инкремента подтверждён snapshot:

```text
total branches: 18
main: 1
non-main branches: 17
```

Из 17 non-main веток:

- 16 имеют `ahead_by = 0` относительно `main`;
- `docs/evgeniya-rostova-theme-v1-design` имеет 2 уникальных commit, но оба затронутых файла имеют одинаковые Git blob SHA с `main`;
- все 17 признаны технически безопасными для удаления после отдельного явного разрешения.

### 5.2 Active reconciliation branch

После snapshot создана:

```text
docs/post-pr16-repository-reconciliation
```

Следовательно, в период выполнения инкремента репозиторий содержит:

```text
total branches: 19
main: 1
pre-reconciliation non-main branches assessed: 17
active reconciliation branch: 1
current non-main branches during implementation: 18
```

Рабочая ветка не наследует автоматически cleanup-решение pre-reconciliation snapshot и имеет классификацию:

```text
KEEP UNTIL OWN REVIEW / APPROVAL / IMPLEMENTATION / TESTING / PR / MERGE
AND SEPARATE POST-MERGE CLEANUP APPROVAL
```

### 5.3 Финальный cleanup gate

После merge reconciliation-инкремента должен выполняться новый read-only inventory всех существующих non-main веток. Только этот inventory может быть основанием для финального удаления.

Удаление веток не является частью документационного implementation и выполняется отдельной административной операцией после явного разрешения владельца проекта.

## 6. Scope реализации после Approval

### 6.1 Обновляемые living documents

```text
README.md
docs/README.md
docs/PROJECT-STATUS.md
docs/PROJECT.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/DATABASE-CURRENT.md
docs/LOCAL-RUNBOOK.md
```

### 6.2 Новый audit artifact

```text
docs/REPOSITORY-AUDIT-2026-07-30.md
```

### 6.3 Process artifacts

До реализации создаются только:

```text
docs/architecture/POST-PR16-REPOSITORY-RECONCILIATION-ARCHITECTURE.md
docs/specifications/POST-PR16-REPOSITORY-RECONCILIATION-SPECIFICATION.md
docs/reviews/POST-PR16-REPOSITORY-RECONCILIATION-REVIEW.md
```

После отдельного Approval допускаются Approval, Implementation и Validation records.

## 7. Исторические документы

Не изменяются:

- `docs/REPOSITORY-AUDIT-2026-07-29.md`;
- Architecture / Specification / Review / Approval предыдущих инкрементов;
- Test Attempts, Test Reports и PR review artifacts;
- исторические Changelog-разделы, кроме добавления новой записи текущего инкремента.

Старый audit остаётся корректным историческим snapshot и не должен переписываться под состояние после PR #16.

## 8. Каноническая терминология

Living documentation должна различать:

```text
current repository pointer: origin/main
last functional PR: #15
last functional merge commit: 5aaf0a7...
tested runtime HEAD: 23886895...
last completed documentation PR before reconciliation: #16
last completed documentation merge before reconciliation: 72630757...
```

Запрещён неоднозначный термин:

```text
merged main commit
```

если не указано, к какому функциональному или документационному merge он относится.

## 9. Требования к новому repository audit

Новый audit должен содержать:

1. точный audit snapshot SHA `main`;
2. сведения о PR #15 и PR #16;
3. функциональный tested baseline;
4. полный список 17 pre-reconciliation non-main веток;
5. ahead/behind каждой ветки относительно audit `main`;
6. классификацию каждой ветки;
7. повторное blob-доказательство для `docs/evgeniya-rostova-theme-v1-design`;
8. отдельный раздел об active reconciliation branch;
9. явное указание, что branch deletion не выполнялось;
10. требование нового post-merge inventory перед cleanup.

## 10. Out of scope

Запрещены:

```text
PHP changes
SQL changes
JavaScript changes
CSS changes
migration/schema changes
permission changes
checker source changes
PermissionBaselineRegressionAdapter changes
deploy
installer execution
runtime/database retesting
Git branch deletion
Git ref rewriting
PR creation
merge
mobile testing
```

## 11. Validation architecture

После implementation должны быть выполнены:

### 11.1 Git scope validation

Допустимые пути:

```text
README.md
docs/**
```

Запрещённые пути:

```text
app/**
config/**
database/**
deploy/**
public/**
themes/**
tools/**
```

### 11.2 Documentation consistency validation

Проверяется:

- отсутствие неоднозначного `merged main commit` как current-state поля;
- корректное разделение functional и documentation anchors;
- PR #16 отмечен как merged;
- baseline refresh отмечен как завершённый;
- audit 2026-07-29 сохранён как historical snapshot;
- новый audit содержит 18 веток pre-reconciliation snapshot: 1 main + 17 non-main;
- active reconciliation branch учитывается отдельно;
- branch deletion отмечено `NOT PERFORMED`;
- mobile testing отмечено `OUT OF SCOPE / NOT RUN`.

### 11.3 Link and secret validation

Проверяются Markdown-ссылки, отсутствие credentials, временных паролей, session data и содержимого `config/local.php`.

## 12. Failure policy

Инкремент не может пройти validation, если:

- изменён runtime/tooling path;
- удалена или перемещена ветка;
- изменён исторический audit 2026-07-29;
- living documentation продолжает называть `5aaf0a7...` текущим HEAD репозитория;
- новый audit пропускает любую из 17 pre-reconciliation non-main веток;
- diverged-ветка признана безопасной без blob-доказательства;
- current reconciliation branch ошибочно включена в уже разрешённый cleanup-set;
- заявлен mobile PASS.

## 13. Gate model

```text
Architecture
→ Specification
→ Formal Review
→ Owner Approval
→ Documentation Implementation
→ Documentation Validation
→ Commit / Push
→ Pull Request
→ Final PR Review
→ Separate Merge Approval
→ Merge
→ Local main synchronization
→ Fresh read-only branch inventory
→ Separate branch deletion approval
→ Branch cleanup
→ Cleanup verification
```

## 14. Архитектурный итог

```text
DOCUMENTATION MODEL: POST-MERGE DURABLE
SELF-REFERENTIAL CURRENT MAIN SHA: PROHIBITED IN LIVING DOCS
FUNCTIONAL BASELINE: UNCHANGED
PRE-RECONCILIATION NON-MAIN BRANCHES ASSESSED: 17
ACTIVE RECONCILIATION BRANCH: EXCLUDED FROM PRE-AUDIT CLEANUP SET
BRANCH DELETION: OUT OF SCOPE / NOT AUTHORIZED
IMPLEMENTATION: NOT STARTED
```