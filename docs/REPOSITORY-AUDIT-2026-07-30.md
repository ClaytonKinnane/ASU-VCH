# Repository Audit — 2026-07-30

## 1. Назначение

Документ фиксирует post-PR16 snapshot репозитория, соответствие living documentation и техническую классификацию всех веток, существовавших до создания `docs/post-pr16-repository-reconciliation`.

Audit является documentation-only артефактом. Он не изменяет runtime, БД, checker source, deploy или Git refs.

## 2. Audit metadata

```text
date: 2026-07-30
repository: ClaytonKinnane/ASU-VCH
default branch: main
audit main snapshot: 72630757c1a72a6bd971cf819cff9bdd36c148bf
last completed documentation PR before reconciliation: #16
last completed documentation merge before reconciliation: 72630757c1a72a6bd971cf819cff9bdd36c148bf
last functional PR: #15
last functional merge commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
open pull requests at pre-reconciliation audit: 0
branch deletion authorization: NOT GRANTED
actual branch deletion: NOT PERFORMED
```

`audit main snapshot` является точным датированным snapshot, а не самореферентным living-полем. Актуальный HEAD после этого момента определяется через `origin/main`.

## 3. Functional и documentation baseline

```text
migrations: 001–009
system roles: 4
system permissions: 25
built-in themes: 3
organizational structure tables: 7
organizational structure triggers: 16
organizational structure permissions: 6
```

PR #16 был documentation-only и изменил только `README.md` и `docs/**`. Runtime, deploy, database, migrations и checker source не изменялись. Проверенный runtime baseline остаётся привязан к `238868950c5f7417ea3d1c283610f2d282d4395a`.

## 4. Living documentation finding

После merge PR #16 документы, содержавшие неоднозначное поле `merged main commit`, снова стали неточными: это значение относилось к functional merge PR #15, но выглядело как текущий HEAD репозитория.

Post-PR16 Repository Reconciliation вводит устойчивую модель:

```text
current repository pointer: origin/main
last functional PR: #15
last functional merge commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
last completed documentation PR before reconciliation: #16
last completed documentation merge before reconciliation: 72630757c1a72a6bd971cf819cff9bdd36c148bf
```

Точный `current main HEAD` больше не хранится как постоянно актуальное поле living documentation.

## 5. Branch inventory snapshots

### 5.1 Pre-reconciliation snapshot

До создания рабочей ветки данного инкремента:

```text
total branches: 18
main: 1
non-main branches: 17
```

### 5.2 During-reconciliation state

После создания `docs/post-pr16-repository-reconciliation`:

```text
total branches: 19
main: 1
pre-reconciliation non-main branches assessed: 17
active reconciliation branch: 1
current non-main branches during implementation: 18
```

Активная reconciliation-ветка не входит в pre-reconciliation cleanup-set и должна сохраняться до собственного review, PR, merge и отдельного post-merge cleanup approval.

## 6. Полный pre-reconciliation branch inventory

Все значения `ahead`/`behind` рассчитаны относительно `main @ 72630757c1a72a6bd971cf819cff9bdd36c148bf`.

| № | Ветка | PR | Behind | Ahead | Состояние | Классификация |
|---:|---|---:|---:|---:|---|---|
| 1 | `docs/post-organizational-structure-v1-baseline-refresh` | #16 | 1 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 2 | `feature/organizational-structure-v1` | #15 | 25 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 3 | `docs/runtime-baseline-self-reference-fix` | #14 | 123 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 4 | `docs/evgeniya-rostova-theme-v1-post-merge-status` | #13 | 126 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 5 | `feature/theme-evgeniya-rostova` | #12 | 130 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 6 | `docs/evgeniya-rostova-theme-v1-design` | — | 140 | 2 | diverged; special blob proof ниже | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 7 | `docs/fix-project-status-audit-state` | #11 | 141 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 8 | `docs/project-documentation-audit-2026-07-27` | #10 | 144 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 9 | `feature/organizational-element-types-directory` | #9 | 160 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 10 | `feature/military-ranks-directory` | #8 | 184 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 11 | `feature/directories-landing` | #7 | 203 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 12 | `feature/asu-blue-tile-hover` | #6 | 228 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 13 | `feature/theme-asu-light-blue` | #5 | 246 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 14 | `feature/user-archive-restore` | #4 | 264 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 15 | `feature/user-rejection-audit` | #3 | 294 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 16 | `feature/required-password-change` | #2 | 316 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |
| 17 | `feature/initial-site` | #1 | 331 | 0 | полностью достижима из `main` | `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL` |

### 6.1 Итог по обычным merged-веткам

Шестнадцать веток имеют `ahead = 0`. Их HEAD является предком audit `main`; уникальных commits или файлового содержимого вне `main` в них нет.

## 7. Special branch proof

Ветка:

```text
branch: docs/evgeniya-rostova-theme-v1-design
branch HEAD: 988d803f5659d9d9bf4b23fc24ee83dc0faf4fd1
unique commits relative to audit main: 2
behind audit main: 140
pull request: none
```

Уникальные commits:

```text
ef13e85a0802e1ce4a318f4af20beead07634c50
docs: add Evgeniya Rostova theme architecture and specification

988d803f5659d9d9bf4b23fc24ee83dc0faf4fd1
docs: add Evgeniya Rostova theme formal review
```

Они затрагивают только два документа, которые позднее были включены в feature-ветку темы и объединены через PR #12.

### 7.1 Architecture / Specification file

```text
path: docs/design/EVGENIYA-ROSTOVA-THEME-V1-DESIGN.md
main blob: 709e6fb6896425c5f377e801f379fcb66eb4623f
design branch blob: 709e6fb6896425c5f377e801f379fcb66eb4623f
main size: 38901 bytes
design branch size: 38901 bytes
byte-identical: true
```

### 7.2 Formal Review file

```text
path: docs/design/EVGENIYA-ROSTOVA-THEME-V1-REVIEW.md
main blob: e19229a50ee10ee8ed1d7496896d73baee6d08f0
design branch blob: e19229a50ee10ee8ed1d7496896d73baee6d08f0
main size: 24113 bytes
design branch size: 24113 bytes
byte-identical: true
```

Проверка завершена маркером:

```text
BYTEWISE_DOCUMENT_COMPARISON_STATUS=PASS
```

Совпадение Git blob SHA означает совпадение полной последовательности байтов. Поэтому два уникальных commit не содержат файлового содержимого, отсутствующего в `main`.

## 8. Active reconciliation branch

```text
branch: docs/post-pr16-repository-reconciliation
created from: main @ 72630757c1a72a6bd971cf819cff9bdd36c148bf
classification: KEEP UNTIL OWN REVIEW / PR / MERGE AND SEPARATE POST-MERGE CLEANUP APPROVAL
```

Ветка не включена в решение по 17 pre-reconciliation веткам. После её будущего merge она должна быть повторно проверена вместе со всеми оставшимися non-main ветками.

## 9. Cleanup conclusion

```text
pre-reconciliation non-main branches assessed: 17
technically safe to delete after separate approval: 17
active reconciliation branch assessed for cleanup: NO
actual branch deletion: NOT PERFORMED
branch deletion authorization: NOT GRANTED
```

Удаление 17 pre-reconciliation refs не изменит:

- дерево файлов `main`;
- merged PR history;
- deploy-копию;
- базу данных;
- commits, достижимые из `main`;
- содержание двух документов special diverged branch.

Однако branch deletion является отдельной административной операцией и не разрешена данным аудитом.

## 10. Обязательный post-merge gate

После merge Post-PR16 Repository Reconciliation необходимо:

1. синхронизировать локальный `main` с `origin/main`;
2. выполнить `git fetch --prune origin`;
3. получить полный список фактически существующих non-main refs;
4. повторно проверить ahead/behind каждой ветки относительно нового `origin/main`;
5. повторно проверить active reconciliation branch;
6. подтвердить отсутствие новых веток и открытых PR;
7. получить отдельное явное разрешение владельца проекта на точный список удаляемых веток.

## 11. Ограничения достоверности

- audit фиксирует snapshot на 2026-07-30;
- будущие commits, PR и ветки должны оцениваться отдельно;
- branch cleanup не выполнялся;
- runtime/deploy/database данным documentation-only инкрементом не изменялись и повторно не тестировались;
- mobile testing не выполнялось;
- legacy checker exact-count debt остаётся отдельным техническим инкрементом.

## 12. Итог

```text
REPOSITORY CONTENT: CONSISTENT WITH MERGED PR #15 AND PR #16
LIVING DOCUMENTATION MODEL: RECONCILED / POST-MERGE DURABLE
HISTORICAL AUDIT 2026-07-29: PRESERVED
PRE-RECONCILIATION NON-MAIN BRANCHES ASSESSED: 17
TECHNICALLY SAFE TO DELETE AFTER SEPARATE APPROVAL: 17
ACTIVE RECONCILIATION BRANCH: KEEP
ACTUAL BRANCH DELETION: NOT PERFORMED
BRANCH DELETION AUTHORIZATION: NOT GRANTED
MOBILE TESTING: OUT OF SCOPE / NOT RUN
```
