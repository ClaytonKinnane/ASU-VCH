# Правила работы над проектом «АСУ-ВЧ»

## 1. Назначение

Этот файл — постоянный operational governance проекта. Он должен оставаться актуальным независимо от конкретного чата, ветки или инкремента.

Перед любыми material-действиями ассистент обязан:

1. прочитать `docs/PROJECT-WORKING-RULES.md`;
2. прочитать `docs/CHAT-HANDOFF.md`;
3. проверить live GitHub/Git state;
4. прочитать Architecture/Specification/Review/Approval активного scope, если он существует;
5. продолжить с текущего незавершённого gate, не повторяя уже полученные разрешения.

## 2. Canonical source priority

При конфликте источников:

1. GitHub/Git — mutable branches, commits, SHA, PR, reviews, Actions, merge/deletion state;
2. approved Architecture/Specification/Review/Approval активного increment;
3. этот документ — постоянные правила;
4. `docs/CHAT-HANDOFF.md` — оперативный current snapshot;
5. living documentation;
6. historical gate/audit records — snapshots своих дат/gates.

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

Исторические `PENDING`, `NOT AUTHORIZED`, `NEXT GATE` и подобные маркеры не объявляют текущую задачу без live/current evidence.

## 3. Репозиторий и локальная среда

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
local repository: C:\Project\ASU-VCH
deploy: C:\OSPanel\home\asu-vch.local
domain: https://asu-vch.local
Open Server Panel: 6.5.1
Apache
PHP: 8.5.4
MySQL: 8.4.x
Windows PowerShell: 5.1
```

Current `main` SHA всегда определяется live.

## 4. Обязательный lifecycle material increment

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing/Validation → Commit → Push → Pull Request
→ exact-head GitHub Actions → Final PR Review → Merge approval → Merge
→ Post-merge verification → Branch deletion approval → Branch deletion
```

Правила:

- implementation не начинается до approved Architecture, Specification, Review и Approval;
- material scope работает в отдельной branch;
- до write фиксируются exact base/head/scope/path allowlist и forbidden actions;
- moved anchor, unexpected path/commit или scope expansion → fail closed;
- скрытая remediation, rebase и force-push без отдельного разрешения запрещены;
- ordinary PR, merge и branch deletion — отдельные owner gates, если заранее не выдано точное task-level разрешение;
- разрешение на merge не означает разрешение на deletion;
- repository settings/branch protection/required checks — отдельный material increment;
- mobile testing не входит в обычный scope и Mobile PASS без фактического acceptance запрещён.

## 5. Exact anchors

Перед write/gate проверяются применимые:

```text
REPOSITORY
BASE_BRANCH
EXPECTED_BASE_SHA
HEAD_BRANCH
EXPECTED_HEAD_SHA
MERGE_BASE
SCOPE
CHANGED_PATH_ALLOWLIST
FORBIDDEN_PATHS
AUTHORIZED_ACTIONS
EXPLICITLY_FORBIDDEN_ACTIONS
```

Перед PR/merge/deletion anchors проверяются повторно. Несовпадение не исправляется автоматически.

## 6. GitHub и локальная машина

GitHub-инструменты используются для repository reads/writes, branches, commits, PR, reviews, Actions и merge, когда операция поддерживается.

Локальная машина используется для:

- synchronization/deploy;
- MySQL/migrations;
- local runtime/checkers;
- HTTP/browser/visual testing;
- операций, которые невозможно выполнить доступным GitHub connector.

Длинные PowerShell flows хранятся файлами в repository и совместимы с Windows PowerShell 5.1. Native exit code authoritative; stdout/stderr разделяются; timeouts bounded; secrets не передаются через args/env/logs.

## 7. Testing claims

```text
static CI != MySQL testing
static CI != migration testing
static CI != deploy verification
static CI != HTTP/browser testing
static CI != visual acceptance
static CI != mobile acceptance
```

- PASS относится только к реально выполненной проверке и exact head;
- documentation-only commit не становится runtime-tested;
- merge commit не заменяет runtime/manual acceptance head;
- `NOT RUN`, `OUT OF SCOPE`, `NOT REQUIRED` указываются явно;
- production deployment не считается выполненным без факта deploy;
- real authentication/paid API requests не выводятся из mock/static evidence.

## 8. Documentation model

Living docs отражают durable current merged state. Target docs могут описывать future state. Historical Architecture/Specification/Review/Approval/Implementation/Testing и dated audits сохраняются как snapshots и не переписываются лишь из-за позднейшего lifecycle.

GitHub lifecycle newest documentation PR не копируется обратно рекурсивным PR только ради записи его own merge/run/cleanup. Новый documentation cycle нужен для substantive content defect/change, а не для self-reference.

## 9. Обязательное обслуживание handoff

`docs/CHAT-HANDOFF.md` проверяется после каждого значимого изменения состояния проекта и обновляется, если snapshot стал неполным или неверным.

Значимые действия включают scope/stage change, approval, branch creation, implementation commit, push, PR, Actions result, Final PR Review, findings/remediation, merge, post-merge verification, branch cleanup, migration/deploy/DB/HTTP/visual evidence и изменение durable rules/baseline.

Handoff должен позволять новому чату сразу определить:

- current state и live anchors, которые требуется перепроверить;
- завершённые capabilities и validation evidence;
- active increment/stage или отсутствие active implementation;
- existing relevant branches/PR/issues;
- open findings;
- уже выданные permissions и explicit prohibitions;
- следующий safe action.

Не следует хранить в handoff каждую устаревшую промежуточную деталь, если она уже доступна в immutable historical records.

## 10. Постоянное разрешение на два operational docs

Владелец предоставляет standing authorization без повторных permission prompts на routine documentation-only maintenance только:

```text
docs/PROJECT-WORKING-RULES.md
docs/CHAT-HANDOFF.md
```

Если изменение ограничено этими двумя файлами и нужно для точности правил/current handoff, разрешены без нового запроса:

- analysis/reconciliation;
- отдельная `docs/...` branch;
- edits только этих двух paths;
- commits и push;
- Pull Request;
- exact-head Actions verification;
- Final PR Review;
- normal merge после PASS;
- post-merge verification.

**Branch deletion не входит в standing authorization.** Даже branch, созданная только для этих двух файлов, удаляется исключительно после отдельного явного owner authorization на exact deletion gate. Если такого разрешения нет, branch остаётся.

Standing authorization также не включает runtime/config/DB/migration/workflow/theme/tool/deploy/settings changes или третий Markdown path. Для более широкого documentation reconciliation требуется task-level owner authorization; такое разрешение может заранее включать PR/merge, но не подразумевает deletion.

## 11. Handoff и новый чат

В новом чате:

1. прочитать rules + handoff;
2. проверить live `main`, branches, open PR/Issues и relevant Actions;
3. сопоставить live state с handoff;
4. проверить active scope/stage и exact approvals;
5. сообщить найденное состояние;
6. продолжить с незавершённого шага;
7. не просить повторно разрешение, которое уже однозначно выдано и всё ещё применимо;
8. не начинать новый implementation scope без нового Research → Approval cycle.

## 12. Branch deletion

Перед deletion обязательны fresh main/branch tips, reachability/unique-commit proof, PR/post-merge state и exact owner-approved deletion batch. `SAFE TO DELETE` — только классификация, не permission. Force deletion запрещён без отдельного explicit authorization.

## 13. Изменение правил

Этот документ living. Любое ослабление security/testing/fail-closed boundaries требует явного owner decision. Изменение правил отражается в handoff, если влияет на дальнейшую работу.
