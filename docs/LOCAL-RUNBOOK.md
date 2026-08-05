# Локальный runbook АСУ-ВЧ

## 1. Назначение

Runbook описывает synchronization, deploy, functional verification, static CI inspection, local automation и branch cleanup gates.

```text
repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
web root: C:\OSPanel\home\asu-vch.local\public
URL: https://asu-vch.local
```

## 2. Current anchors

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
required CSS assets: 10
```

Current stable HEAD определяется через `origin/main`. Documentation-only commits не считаются runtime-tested.

## 3. Stable synchronization

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch main
git pull --ff-only origin main
git rev-parse HEAD
git rev-parse origin/main
git rev-list --left-right --count HEAD...origin/main
git status --short
```

Ожидается clean worktree, `HEAD=origin/main`, divergence `0 0`.

## 4. Runtime initialization

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1'
```

Repeat installer:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

Ожидается:

```text
Применено миграций: 12
Новых миграций нет.
```

## 5. PR #24 functional verification baseline

Post-merge verification подтвердил:

```text
migration 012: applied
repeat installer: 12 / no new migrations
Military Ranks source/loader/service checks: PASS
Military Ranks DB regression: PASS
deploy/source parity: PASS
HTTP smoke: PASS
manual desktop: PASS
working tree: clean
mobile: OUT OF SCOPE / NOT RUN
```

Evidence:

- `testing/MILITARY-RANKS-DIRECTORY-V2-TEST-REPORT.md`;
- `testing/MILITARY-RANKS-DIRECTORY-V2-MANUAL-DESKTOP-ACCEPTANCE-2026-08-03.md`;
- `review/MILITARY-RANKS-DIRECTORY-V2-PR-FINAL-REVIEW.md`.

## 6. GitHub Actions inspection

Workflow:

```text
ASU-VCH Static Verification
job: asu-vch-static-verification
```

GitHub UI:

1. открыть **Actions**;
2. выбрать `ASU-VCH Static Verification`;
3. проверить event, branch, exact SHA, conclusion и job steps;
4. для manual diagnostics использовать **Run workflow** на `main`;
5. не считать `Re-run all jobs` новым `workflow_dispatch` event.

Historical evidence:

```text
PR #25 push run: 30837637886 / SUCCESS
PR #25 workflow_dispatch run: 30839122892 / SUCCESS
PR #30 exact-head PR run: 31024419654 / SUCCESS
PR #30 post-merge push run: 31025264683 / SUCCESS
required status check: NOT ENABLED
branch protection/settings changed: NO
```

Static CI не заменяет local MySQL, deploy, HTTP/browser или manual visual testing.

## 7. GitHub Local Automation

Canonical guide:

- [GitHub Local Automation](../tools/github-automation/README.md)

Installer modes:

```text
Install
Doctor
Repair
```

Cleanup modes:

```text
Doctor
Verify
Delete
```

Cleanup exact gates:

```text
main exact SHA
merged PR exact head
exact merge commit
successful post-merge push run/job/steps
canonical post-merge PASS evidence
remote branch exact SHA
branch ahead of main = 0
unique unmerged commits = 0
ApprovalToken == BranchName case-sensitive
```

`Delete` разрешён только после отдельного owner approval. Единственная разрешённая destructive-команда helper:

```text
git push origin --delete <approved-branch>
```

Helper удаляет только утверждённую remote-ветку. Local branch cleanup остаётся отдельным controlled local step после `fetch --prune`. Force deletion запрещён.

Native PR #30 evidence (`58 PASS / 0 FAIL`) не является доказательством реальной GitHub/Codex authentication, account verification или paid API request.

## 8. Documentation-only validation

Для approved documentation branch:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch <approved-docs-branch>
git pull --ff-only origin <approved-docs-branch>
git rev-parse HEAD
git merge-base origin/main HEAD
git rev-list --left-right --count origin/main...HEAD
git diff --name-only origin/main...HEAD
git diff --check origin/main...HEAD
git status --short
```

Проверяются:

- exact approved path allowlist;
- Markdown-only diff;
- branch behind `origin/main` = 0;
- baseline facts and historical anchors;
- relative links;
- stale current assertions;
- migration 001–012 consistency;
- required CSS asset count 10;
- separated functional/CI/governance/local-automation baseline;
- terminal anti-recursion invariant;
- production/instance secret boundary;
- no Mobile PASS claim;
- absence of runtime/config/database/migration/workflow/theme/deploy/tool diff.

## 9. Branch inventory and cleanup

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git branch --merged origin/main
```

Для каждой branch проверяются exact tip, reachability, unique commits, PR/post-merge state и exact owner-approved deletion batch.

`SAFE TO DELETE` не является permission. Remote deletion выполняется первой, затем `git fetch --prune` и отдельно approved local deletion через `git branch -d`.

Current branch inventory не хранится как permanent living field.

## 10. Historical governance snapshots

PR #21 cleanup и PR #23 documentation audit сохраняются как immutable dated evidence. Их `main only` snapshots не запрещают позднейшие approved branches.

Historical gate markers не являются текущими задачами:

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

Lifecycle новейшего documentation PR остаётся в GitHub и не требует recursive post-merge Markdown closure.

## 11. Security boundaries

Не публикуются:

- production credentials;
- instance/environment credentials;
- real temporary user passwords;
- session identifiers/data;
- `config/local.php`;
- GitHub tokens;
- OpenAI API keys;
- OAuth/device codes;
- cookies;
- private keys.

Existing public local-only fixture:

```text
username: Admin
password: 12315
environment: local only
must_change_password: true
```

Он не является production/instance secret, запрещён для production и иных accounts/environments, требует смены при первом входе и не отменяет запрет публикации real temporary passwords.

## 12. Permanent gates

```text
Pull Request: separate owner permission
merge: separate owner permission
branch deletion: separate post-merge owner permission
required status check: not enabled
mobile PASS: not claimed
recursive lifecycle-only Markdown closure: prohibited
```
