# Правила работы над проектом «АСУ-ВЧ»

## 1. Назначение документа

Этот документ является постоянным операционным регламентом разработки проекта «АСУ-ВЧ».

Он предназначен для:

- сохранения единых правил работы независимо от чата и исполнителя;
- предотвращения потери утверждённых ограничений, gate-процесса и exact anchors;
- определения границ операций GitHub и локальной машины;
- фиксации постоянного разрешения на обслуживание этого документа и `docs/CHAT-HANDOFF.md`;
- быстрого восстановления рабочего контекста вместе с `docs/CHAT-HANDOFF.md`.

Перед любыми material-действиями над проектом необходимо прочитать:

1. `docs/PROJECT-WORKING-RULES.md`;
2. `docs/CHAT-HANDOFF.md`;
3. относящиеся к активному инкременту Architecture, Specification, Review, Approval, Implementation и Testing records;
4. актуальное состояние GitHub/Git.

## 2. Canonical sources и приоритет источников

При расхождении источников действует следующий приоритет:

1. GitHub/Git для текущего mutable lifecycle: branches, commits, exact SHA, Pull Requests, reviews, Actions, merge и branch cleanup;
2. утверждённые Architecture, Specification, Review и Approval активного инкремента;
3. этот документ для постоянных правил работы;
4. `docs/CHAT-HANDOFF.md` для оперативного состояния и перехода между чатами;
5. living project documentation;
6. historical gate records как immutable snapshots.

Historical record со значением `PENDING`, `NOT_AUTHORIZED` или аналогичным значением не означает, что соответствующая задача всё ещё открыта. Текущее состояние определяется GitHub/Git и актуальной living documentation.

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

## 3. Репозиторий и локальная среда

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

Текущий `main` HEAD нельзя определять только по записанному ранее SHA. Перед началом нового действия он должен быть повторно проверен через GitHub/Git.

## 4. Обязательный lifecycle material-инкремента

Для каждого functional или material technical increment применяется последовательность:

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
→ Merge approval
→ Merge
→ Post-merge verification
→ Branch deletion approval
→ Branch deletion
```

Основные правила:

- новая реализация не начинается до утверждения Architecture, Specification, Review и Approval;
- каждый новый material increment выполняется в отдельной feature/documentation ветке;
- scope и exact changed-path allowlist фиксируются до изменения файлов;
- изменение base/head/path anchors приводит к fail-closed остановке и повторной проверке;
- скрытые remediation, scope expansion и дополнительные commits запрещены;
- Pull Request, Final PR Review, merge и branch deletion обычно требуют отдельных явных разрешений владельца;
- исключение для обслуживания двух постоянных документов определено в разделе 11;
- task-level owner authorization может заранее разрешить конкретные последующие gates, но не расширяется за явно разрешённый scope;
- merge approval никогда автоматически не означает branch deletion approval;
- repository settings, branch protection и required checks изменяются только отдельным утверждённым инкрементом;
- mobile testing не входит в обычную область работ;
- mobile PASS нельзя заявлять без отдельного фактического mobile acceptance.

## 5. Exact anchors и fail-closed поведение

Перед branch creation или первым write-действием фиксируются:

```text
REPOSITORY
BASE_BRANCH
EXPECTED_BASE_SHA
HEAD_BRANCH
SCOPE
CHANGED_PATH_ALLOWLIST
FORBIDDEN_PATHS
AUTHORIZED_ACTIONS
EXPLICITLY_NOT_AUTHORIZED_ACTIONS
```

Перед каждым следующим gate повторно проверяются:

- base branch SHA;
- feature head SHA;
- merge base;
- ahead/behind;
- changed paths;
- открытые findings;
- Actions exact-head status;
- наличие неожиданных commits или review changes.

При любом несовпадении операция останавливается. Нельзя автоматически исправлять расхождение, rebase, force-push, добавлять commit или расширять allowlist без соответствующего разрешения, кроме строго ограниченного обслуживания постоянных документов по разделу 11.

## 6. Распределение GitHub и локальных операций

### Через GitHub выполняются

Ассистент самостоятельно использует доступные GitHub-инструменты для:

- проверки branches, commits и exact SHA;
- чтения repository files;
- создания веток;
- создания и обновления файлов;
- commits и push через GitHub API;
- создания Pull Requests;
- reviews;
- проверки GitHub Actions;
- merge;
- post-merge verification;
- branch deletion, если доступный GitHub-инструмент действительно поддерживает удаление ref и имеется отдельное разрешение;
- иных доступных repository operations.

Пользователю не выдаются локальные Git/GitHub-команды, если операция доступна ассистенту через GitHub. Если нужная операция не поддерживается connector, допускается строго scoped локальная Git/GitHub-команда только в рамках явного разрешения и exact fail-closed gate.

### На локальной машине выполняются

Локальная машина используется для операций, которым необходим доступ к локальной среде:

- синхронизация `C:\Project\ASU-VCH`;
- deploy в `C:\OSPanel\home\asu-vch.local`;
- MySQL и migrations;
- local runtime checks;
- HTTP/browser testing;
- visual desktop acceptance;
- операции, зависящие от Open Server Panel или локальной файловой системы;
- отдельно разрешённые Git operations, которых нет в доступном GitHub connector.

Если нужен длинный PowerShell-сценарий:

- он добавляется отдельным файлом в репозиторий;
- в чат выдаются только короткие команды синхронизации и запуска;
- обязательна совместимость с Windows PowerShell 5.1;
- native stdout и stderr обрабатываются раздельно;
- `$LASTEXITCODE` считается authoritative;
- учитываются scalar/array edge cases и риск `System.Char` при индексировании одиночного результата;
- применяются bounded timeouts и fail-closed error handling.

## 7. Testing и допустимые claims

Разные классы проверок не подменяют друг друга.

```text
static CI != MySQL testing
static CI != migration testing
static CI != deploy verification
static CI != HTTP/browser testing
static CI != visual acceptance
static CI != mobile acceptance
```

Правила claims:

- documentation-only commit после runtime-tested head не объявляется runtime-tested;
- merge commit не подменяет фактический runtime/manual acceptance head;
- `PASS` заявляется только для реально выполненной проверки и exact проверенного head;
- `NOT RUN`, `OUT OF SCOPE` и `NOT REQUIRED` указываются явно;
- mobile PASS без отдельного тестирования запрещён;
- реальная authentication, paid API request или production deployment не считаются проверенными без фактического выполнения.

## 8. Documentation governance

Применяется terminal documentation model:

- GitHub/Git является canonical source для mutable PR/review/Actions/merge/branch lifecycle;
- historical gate records являются immutable snapshots;
- living documentation обновляется при substantive durable-state change или реальном content defect;
- рекурсивный documentation-only PR только для копирования lifecycle предыдущего documentation PR запрещён;
- handoff-документ должен оставаться полезным и актуальным, но не должен создавать бесконечную цепочку самообновлений только ради записи собственного merge SHA.

`docs/PROJECT-WORKING-RULES.md` является постоянным регламентом. `docs/CHAT-HANDOFF.md` содержит текущую рабочую картину, ключевые anchors, активный stage, принятые решения и журнал значимых действий. Exact mutable evidence всё равно повторно проверяется в GitHub.

## 9. Формат разрешений владельца

Для обычных gate используется готовый copy-paste текст разрешения, содержащий:

```text
repository
branch
base SHA
head SHA
scope
changed-path allowlist
authorized action
explicitly forbidden actions
```

Разрешение должно быть однозначным и ограниченным конкретным gate. Нельзя считать разрешение на PR разрешением на merge, а разрешение на merge — разрешением на branch deletion, если владелец явно не предоставил task-level разрешение на соответствующий gate. Branch deletion в любом случае требует отдельного явного разрешения.

## 10. Обязательное обслуживание handoff-состояния

При каждом значимом действии над проектом необходимо проверить и при необходимости обновить `docs/CHAT-HANDOFF.md`.

Значимыми действиями считаются:

- выбор, изменение или отмена scope;
- переход между lifecycle stages;
- утверждение Architecture, Specification, Review или Approval;
- branch creation;
- implementation commit;
- push;
- PR creation;
- Actions result;
- Final PR Review;
- finding, remediation или изменение risk status;
- merge;
- post-merge verification;
- branch cleanup;
- migration, deploy, DB, HTTP/browser или visual test result;
- изменение durable baseline;
- изменение постоянных правил проекта.

Простое read-only открытие файла или повторная проверка без изменения состояния не требует отдельной записи.

После каждого обновления handoff-документ должен отвечать на вопросы:

- что сейчас является current state;
- какой exact stage активен;
- что уже завершено;
- что ожидается дальше;
- какие разрешения получены;
- какие действия ещё не разрешены;
- какие findings открыты;
- какие проверки выполнены и на каком SHA;
- какие ветки и PR существуют;
- что должен сделать ассистент в новом чате первым.

## 11. Постоянное разрешение на два governance-документа

Владелец проекта предоставил постоянное разрешение не запрашивать дополнительные approvals для создания и поддержания в актуальном состоянии следующих файлов:

```text
docs/PROJECT-WORKING-RULES.md
docs/CHAT-HANDOFF.md
```

Standing authorization распространяется только на documentation-only изменения, необходимые для точного отражения правил и состояния проекта.

Разрешены без нового запроса владельцу:

- Research/Analysis для актуализации этих двух файлов;
- создание отдельной documentation branch;
- изменение только allowlisted файлов;
- commits и push;
- создание Pull Request;
- ожидание и проверка exact-head GitHub Actions;
- Final PR Review;
- normal merge методом `merge` после PASS;
- post-merge verification.

**Branch deletion не входит в standing authorization.** Ветка, созданная даже только для этих двух файлов, остаётся до отдельного явного owner authorization на exact deletion gate.

Строгий allowlist:

```text
ALLOWLIST_1=docs/PROJECT-WORKING-RULES.md
ALLOWLIST_2=docs/CHAT-HANDOFF.md
MAX_CHANGED_PATHS=2
CLASSIFICATION=documentation-only
```

Standing authorization не разрешает:

```text
branch deletion
runtime changes
configuration changes
database or migration changes
workflow changes
theme changes
deploy changes
automation-tool changes
integrity-manifest changes
repository settings changes
branch protection changes
required status check changes
изменение иных Markdown-файлов
скрытую remediation вне allowlist
```

Если для актуальности требуется изменить любой третий путь, обычный gate-процесс применяется полностью и операция останавливается до отдельного task-level разрешения. Такое разрешение может заранее включать commit/push/PR/review/merge, но branch deletion остаётся отдельным явным gate.

Несмотря на standing authorization, обязательны:

- отдельная branch;
- exact base/head verification;
- exact changed-path allowlist;
- GitHub Actions exact-head SUCCESS, если workflow применим;
- Final PR Review;
- fail-closed остановка при несовпадении anchors или появлении findings;
- отсутствие scope expansion.

## 12. Протокол перехода в новый чат

В начале нового чата ассистент должен:

1. открыть этот документ;
2. открыть `docs/CHAT-HANDOFF.md`;
3. проверить текущий `main` HEAD, remote branches, открытые PR и Issues;
4. проверить активный increment и его exact stage;
5. сверить GitHub mutable lifecycle с handoff snapshot;
6. сообщить пользователю найденное состояние и продолжить с незавершённого шага;
7. не повторять уже полученные разрешения;
8. не создавать новую ветку и не выполнять новый scope, если он не утверждён;
9. обновлять handoff по мере значимых действий.

## 13. Изменение этого регламента

Этот документ поддерживается как living governance document.

При изменении правил:

- новая формулировка должна быть конкретной и проверяемой;
- конфликт с более высоким canonical source должен быть устранён;
- ослабление fail-closed, testing или approval boundaries не допускается без явного решения владельца;
- изменение отражается в `docs/CHAT-HANDOFF.md`, если влияет на дальнейшую работу;
- точный GitHub lifecycle изменения остаётся canonical в GitHub/Git.
