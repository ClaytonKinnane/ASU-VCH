# АСУ-ВЧ

Автоматизированная система учёта военнослужащих «Войсковая часть».

## Текущий merged baseline

Стабильное состояние находится в `main`. Актуальный HEAD определяется динамически:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

Устойчивые baseline-категории:

```text
latest functional runtime baseline: PR #24 / migration 012
static CI baseline: PR #25
documentation governance baseline: PR #28
local automation foundation: PR #29
local automation corrected baseline: PR #30
durable technical capability coverage: through PR #30
PR #24 merge commit: feac7230616d3a8df98acb48f43a0b60f89f2255
PR #24 runtime/manual acceptance head: b44aed14ee1a54be213cbc939322ba21b02e7a58
PR #25 merge commit: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
PR #29 merge commit: 375f941be3f50f9f1f264da244f0dc31496e2a6f
PR #30 merge commit: 35abf7e29395bc662eeb2cecf5b1ea5a30fd7c77
migrations: 001–012
system roles: 4
system permissions: 25
built-in themes: 3
required CSS assets per theme: 10
active functional increment: none
active material technical increment: none
```

Exact SHA текущего `main` не хранится как самореферентное living field. Указанные SHA являются historical merge/test anchors.

## Реализовано

- установка, authentication, защищённые sessions и CSRF;
- RBAC, 4 system roles, 25 permissions и полный user lifecycle;
- обязательная смена временного пароля;
- три встроенные trusted themes;
- owner-only read-only справочники:
  - составы военнослужащих и воинские звания — current v2 и historical v1;
  - типы организационных элементов;
  - типовые воинские должности;
  - публичные сведения о военно-учётных специальностях;
- Reference-owned read-only compatibility service для version-scoped ranks/compositions;
- Organizational Structure v1: structures, versions, draft-tree, documents metadata, history и compare;
- GitHub Actions workflow `ASU-VCH Static Verification` для PR в `main`, push в `main` и manual diagnostics;
- Windows PowerShell 5.1 local automation package:
  - проверка или установка Git, GitHub CLI, Node.js LTS и Codex CLI;
  - режимы Codex authentication `Auto`, `ChatGPT`, `ApiKey`, `Skip`;
  - integrity manifest и атомарная установка local helpers;
  - native PowerShell 5.1 regression harness;
  - fail-closed cleanup helper с режимами `Doctor`, `Verify`, `Delete`.

Browser ChatGPT не получает прямого доступа к локальному компьютеру. Local automation является repository tooling и не публикуется как application runtime.

Migration 012 сохраняет 20 воинских званий, публикует current v2 с 8 composition/category records и оставляет v1 historical/superseded. Staffing tables, Organization bindings, personnel assignments и реальные unit/personnel data не добавлены.

Static CI является дополнительным signal. Он не заменяет MySQL, migrations, deploy, HTTP/browser и manual visual acceptance. Required status check и branch protection Stage B не включены.

## Локальная среда

```text
Windows 10/11
Open Server Panel 6.5.1
Apache
PHP 8.5.4
MySQL 8.4.x
Windows PowerShell 5.1
repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
web root: C:\OSPanel\home\asu-vch.local\public
URL: https://asu-vch.local
```

GitHub является источником истины. Локальный clone используется для synchronization, deploy, testing и отдельно разрешённых repository operations. Secrets и содержимое `config/local.php` в Git не помещаются.

## Процесс изменений

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing → Commit → Push → Pull Request
→ Final PR Review → separate merge approval → Merge
→ post-merge verification → separate branch deletion approval
```

Pull Request, merge и branch deletion требуют отдельных явных разрешений владельца.

## Документация

- [Индекс документации](docs/README.md)
- [Текущее состояние проекта](docs/PROJECT-STATUS.md)
- [О проекте](docs/PROJECT.md)
- [Текущее состояние базы данных](docs/DATABASE-CURRENT.md)
- [Локальный runbook](docs/LOCAL-RUNBOOK.md)
- [План разработки](docs/ROADMAP.md)
- [История изменений](docs/CHANGELOG.md)
- [GitHub Local Automation](tools/github-automation/README.md)

## Границы тестирования

PR #24 прошёл automated/runtime, DB, deploy/parity, HTTP smoke и manual desktop acceptance; post-merge verification — PASS.

PR #25 прошёл exact-head PR workflow и post-merge push/manual runs.

PR #30 прошёл native Windows PowerShell 5.1 regression validation (`58 PASS / 0 FAIL`), exact-head PR workflow и post-merge push verification. Это не является доказательством реальной GitHub/Codex authentication, paid API request или полной target-machine installation acceptance.

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
required status check: NOT ENABLED
branch protection: NOT ENABLED
```
