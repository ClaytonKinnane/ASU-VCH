# АСУ-ВЧ — постоянный handoff для нового чата

## 1. Как использовать этот документ

Этот файл является оперативной точкой входа для продолжения разработки проекта «АСУ-ВЧ» в новом чате.

В новом чате ассистент должен сначала:

1. прочитать `docs/PROJECT-WORKING-RULES.md`;
2. прочитать этот файл полностью;
3. самостоятельно проверить GitHub mutable state;
4. сопоставить GitHub state с данным snapshot;
5. продолжить с текущего незавершённого stage, не повторяя уже полученные разрешения.

Этот документ не заменяет GitHub/Git. Branches, commits, Pull Requests, Actions, reviews, merge и branch cleanup всегда повторно проверяются через GitHub.

## 2. Репозиторий и среда

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
local repository: C:\Project\ASU-VCH
deploy: C:\OSPanel\home\asu-vch.local
domain: https://asu-vch.local
Open Server Panel: 6.5.1
web server: Apache
PHP: 8.5.4
MySQL: 8.4
local shell: Windows PowerShell 5.1
```

## 3. Последний подтверждённый GitHub snapshot перед созданием handoff

Дата проверки: `2026-08-06`.

```text
verified main HEAD: fce7a9e317105ecaa0dbec96469bb0f60fad5835
main HEAD provenance: merge commit Pull Request #31
remote branch inventory: main only
open Pull Requests: 0
open GitHub Issues: 0
open findings: 0
active functional increment: none
active material technical increment: none
next functional increment: not selected / not approved
```

Текущий live `main` HEAD после появления этого файла должен определяться динамически через GitHub. Значение выше является историческим exact base anchor создания постоянных governance-документов, а не вечным current pointer.

## 4. Последний завершённый инкремент

```text
name: Documentation Current-State Reconciliation v3
Pull Request: #31
reviewed feature head: fce7131c8b8df96eec1bcd2a50d4560a1399221b
pre-merge base: 35abf7e29395bc662eeb2cecf5b1ea5a30fd7c77
merge commit: fce7a9e317105ecaa0dbec96469bb0f60fad5835
merge method: merge
changed paths: 16 Markdown files
Documentation Validation: PASS
Final PR Review: PASS
exact-head GitHub Actions: SUCCESS
post-merge verification: PASS
feature branch cleanup: complete
```

PR #31 является documentation-only reconciliation. Он не изменял runtime, config, database, migrations, workflow, themes, deploy, automation tools, integrity manifest или repository settings.

Living Markdown описывает durable baseline через PR #30. Lifecycle PR #31 намеренно не копировался обратно в living documentation. Это terminal documentation model, а не stale documentation.

## 5. Durable baseline

### Functional runtime baseline

```text
Pull Request: #24
migration: 012
merge commit: feac7230616d3a8df98acb48f43a0b60f89f2255
runtime/manual acceptance head: b44aed14ee1a54be213cbc939322ba21b02e7a58
```

### Static CI baseline

```text
Pull Request: #25
workflow: ASU-VCH Static Verification
merge commit: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
required status check: not enabled
```

### Documentation governance baseline

```text
Pull Request: #28
GitHub/Git: canonical for mutable lifecycle
historical gate records: immutable snapshots
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
recursive lifecycle-only documentation closure: prohibited
```

### Local automation baseline

```text
foundation: Pull Request #29
corrected PowerShell 5.1 baseline: Pull Request #30
PR #30 corrected implementation head: fede2aa8c9c7b896f142075caa69b35219d4016d
PR #30 merge commit: 35abf7e29395bc662eeb2cecf5b1ea5a30fd7c77
native Windows PowerShell 5.1 regression: 58 PASS / 0 FAIL
```

## 6. Реализованные области

### Platform и security

- bootstrap первого владельца системы;
- authentication;
- protected sessions;
- CSRF;
- public registration отключается после создания владельца;
- 4 system roles;
- 25 system permissions;
- полный user lifecycle;
- required password change;
- rejection audit;
- archive/restore.

### Themes

Built-in themes:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

Для каждой темы существует 10 required CSS assets.

### Owner-only read-only directories

- military ranks;
- organizational element types;
- military positions;
- public VUS.

### Organization и ranks

- Organizational Structure v1;
- migrations 001–012;
- Military Ranks Directory v2;
- current v2 и historical v1;
- version switching;
- search/filtering;
- source cards;
- derived/staffing badges;
- Reference-owned read-only compatibility service.

## 7. Что не реализовано

Следующее не реализовано и не считается активной задачей без отдельного Research → Approval cycle:

- Staffing tables;
- штатные slots;
- Organization bindings;
- personnel assignments;
- реальные unit/personnel data;
- mutation UI соответствующих справочников;
- production deployment infrastructure;
- branch protection Stage B;
- required status check;
- отдельная mobile acceptance.

## 8. Текущее планирование

```text
active functional increment: none
active material technical increment: none
next functional increment: not selected
next functional increment: not approved
open GitHub Issues at last baseline verification: 0
open Pull Requests at last baseline verification: 0
open findings: 0
```

Обсуждался, но не был выбран и не был утверждён кандидат:

```text
candidate: Staffing Structure v1
classification: possible future functional increment
proposed boundary: staffing foundation without personnel cards or assignments
status: discussion only / not approved / no implementation authorization
```

Другие possible future directions:

- personnel card;
- staffing structure and personnel assignments;
- common Documents domain;
- common Audit domain;
- production deployment infrastructure;
- branch protection Stage B / required status check;
- separate mobile verification increment.

Ни один пункт не становится active task только из-за наличия в этом списке.

## 9. Обязательный process

Для material increment:

```text
Research
→ Analysis
→ Architecture
→ Specification
→ Review
→ Approval
→ Implementation
→ Testing/Validation
→ Commit
→ Push
→ Pull Request
→ exact-head GitHub Actions
→ Final PR Review
→ отдельное Merge approval
→ Merge
→ Post-merge verification
→ отдельное Branch deletion approval
→ Branch deletion
```

Ключевые ограничения:

- не начинать implementation до утверждения Architecture, Specification, Review и Approval;
- создавать отдельную ветку для каждого material scope;
- обычный PR создавать только после отдельного разрешения;
- обычный Final PR Review выполнять только после отдельного разрешения и successful exact-head Actions;
- обычный merge выполнять только после отдельного разрешения;
- обычное branch deletion выполнять только после отдельного post-merge разрешения;
- fail closed при изменении exact head/base/path anchors;
- никаких скрытых remediation или дополнительных commits;
- не изменять repository settings, branch protection и required checks без отдельного инкремента;
- mobile testing исключено из обычного scope;
- не заявлять mobile PASS без фактического acceptance;
- static CI не заменяет MySQL, migration, deploy, HTTP/browser и visual testing;
- documentation-only commits после runtime-tested head не объявлять runtime-tested.

Полный регламент находится в `docs/PROJECT-WORKING-RULES.md`.

## 10. GitHub и локальные операции

### Ассистент выполняет через GitHub

- проверку branches, commits и exact SHA;
- чтение repository files;
- создание веток;
- commits и публикацию изменений;
- создание Pull Requests;
- review;
- проверку GitHub Actions;
- merge;
- post-merge verification;
- branch deletion;
- другие доступные GitHub operations.

Не нужно просить пользователя выполнять локальные Git/GitHub-команды, когда операция доступна через GitHub.

### Пользовательская локальная машина нужна для

- синхронизации `C:\Project\ASU-VCH`;
- deploy;
- MySQL и migrations;
- local runtime checks;
- HTTP/browser testing;
- visual desktop acceptance;
- операций с Open Server Panel и локальной файловой системой.

Длинный PowerShell-код должен добавляться файлом в репозиторий. В чат выдаются короткие команды запуска. Совместимость с Windows PowerShell 5.1 обязательна.

## 11. Постоянное разрешение на обслуживание governance-документов

Владелец предоставил standing authorization на создание и постоянное обслуживание:

```text
docs/PROJECT-WORKING-RULES.md
docs/CHAT-HANDOFF.md
```

Для documentation-only maintenance, ограниченного этими двумя файлами, не требуется повторно спрашивать разрешения на:

- отдельную documentation branch;
- commits и push;
- Pull Request;
- exact-head Actions verification;
- Final PR Review;
- merge после PASS;
- post-merge verification;
- branch deletion после PASS.

Строгий allowlist — только два указанных пути. Изменение любого третьего файла требует обычного отдельного gate.

Standing authorization не распространяется на runtime, config, DB, migrations, workflows, themes, deploy, automation tools, integrity manifest, repository settings, branch protection или required checks.

## 12. Правила обновления этого файла

Этот handoff обновляется при каждом значимом изменении project state, включая:

- новый или изменённый scope;
- изменение lifecycle stage;
- approvals;
- branch/commit/push;
- Pull Request;
- Actions result;
- Final PR Review;
- findings или remediation;
- merge и post-merge verification;
- branch cleanup;
- local DB/deploy/HTTP/browser/visual results;
- изменение durable baseline;
- изменение постоянных правил.

Незначимые read-only проверки без изменения состояния не требуют отдельной записи.

При обновлении необходимо одновременно поддерживать:

- раздел текущего состояния;
- active stage;
- next action;
- exact anchors, если они не создают self-recursive stale pointer;
- список approvals и запретов;
- findings;
- testing evidence;
- журнал значимых действий.

Mutable lifecycle всё равно остаётся canonical в GitHub/Git. Не создаётся рекурсивный PR только ради записи merge SHA самого handoff-maintenance PR.

## 13. Текущий operational state

```text
functional increment: none
material technical increment: none
governance maintenance: creation of permanent working-rules and chat-handoff documents
maintenance branch: docs/project-working-rules-and-chat-handoff
maintenance base: fce7a9e317105ecaa0dbec96469bb0f60fad5835
maintenance scope: exactly two new Markdown files
maintenance standing authorization: granted
runtime impact: none
DB/migration impact: none
workflow/settings impact: none
```

После завершения этого documentation-only maintenance cycle значение `governance maintenance` должно считаться завершённым. Точный PR, final head, Actions, merge и branch cleanup определяются через GitHub mutable evidence и могут быть добавлены в этот журнал до merge без создания post-merge recursive closure.

## 14. Next action

На момент создания этого файла:

```text
next project action after governance maintenance: continue discussion of next functional increment
new functional branch creation: not authorized because functional scope is not selected
implementation: not authorized
```

## 15. Журнал значимых действий

### 2026-08-06 — Baseline verification

- GitHub `main` подтверждён на `fce7a9e317105ecaa0dbec96469bb0f60fad5835`;
- remote inventory содержал только `main`;
- открытых Pull Requests не было;
- открытых GitHub Issues не было;
- активных functional/material technical increments не было.

### 2026-08-06 — Постоянные governance-документы

- владелец потребовал создать отдельный документ правил работы;
- владелец потребовал создать отдельный документ перехода в новый чат;
- владелец потребовал постоянно поддерживать оба документа в актуальном состоянии;
- владелец предоставил standing authorization без повторных permission prompts для обслуживания этих двух файлов;
- scope ограничен `docs/PROJECT-WORKING-RULES.md` и `docs/CHAT-HANDOFF.md`;
- создана documentation branch `docs/project-working-rules-and-chat-handoff` от exact base `fce7a9e317105ecaa0dbec96469bb0f60fad5835`;
- runtime, config, DB, migrations, workflows, themes, deploy, tools и repository settings не входят в scope.

## 16. Checklist для первого ответа в новом чате

Ассистент должен выполнить и сообщить:

```text
[ ] прочитан PROJECT-WORKING-RULES.md
[ ] прочитан CHAT-HANDOFF.md
[ ] повторно проверен live main HEAD
[ ] повторно проверен remote branch inventory
[ ] повторно проверены open PRs
[ ] повторно проверены open Issues
[ ] определён active increment и exact stage
[ ] проверены open findings
[ ] сверены approvals и forbidden actions
[ ] определён следующий разрешённый шаг
```

После проверки нельзя начинать новый material scope, пока он не обсуждён и не утверждён. Обслуживание двух governance-документов выполняется по standing authorization и не требует нового вопроса владельцу.
