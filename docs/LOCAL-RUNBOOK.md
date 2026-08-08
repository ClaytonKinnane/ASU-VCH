# Локальный runbook АСУ-ВЧ

## 1. Current baseline

```text
repo: C:\Project\ASU-VCH
deploy: C:\OSPanel\home\asu-vch.local
URL: https://asu-vch.local
latest functional PR: #36 / migration 014
migrations: 001–014
roles: 4
permissions: 35
themes: 3
required CSS assets/theme: 10
```

Current HEAD is obtained from `origin/main`; documentation-only commits are not runtime-tested.

## 2. Synchronization

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

Expected: clean worktree, equal heads, divergence `0 0`.

## 3. Initialization / repeat

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

For current merged schema expect migrations 001–014 and no additional migration on repeat.

## 4. Current functional verification

Military Positions Directory v1 runner:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Test-MilitaryPositionsDirectoryV1.ps1' `
  -RepositoryPath 'C:\Project\ASU-VCH' `
  -ExpectedHead <EXACT_HEAD> `
  -RunInitialization `
  -RunHttpSmoke `
  -AllowInvalidCertificate
```

Latest accepted runtime evidence on `c647a933011873048866c75978d3f506634011fd`:

```text
inventory 38/38
PHP lint 171 PASS
migrations 001–014
DB/runtime 167 PASS
HTTP 200,200,302
three desktop themes PASS
mutual exclusion PASS
open findings 0
mobile NOT RUN / OUT OF SCOPE
```

Do not treat a later docs/merge commit as a new runtime-tested head.

## 5. Static GitHub Actions

Workflow: `ASU-VCH Static Verification`.

Check event, branch, exact SHA, conclusion and job steps. Static CI does not replace local MySQL/deploy/HTTP/visual testing. Required status check is not enabled.

## 6. Documentation-only validation

For a docs branch:

```powershell
git fetch --prune origin
git switch <docs-branch>
git pull --ff-only origin <docs-branch>
git rev-parse HEAD
git merge-base origin/main HEAD
git rev-list --left-right --count origin/main...HEAD
git diff --name-only origin/main...HEAD
git diff --check origin/main...HEAD
git status --short
```

Review:

- exact Markdown allowlist;
- behind main = 0 before PR/merge gate;
- baseline facts and historical semantic classification;
- relative links and stale assertions;
- migrations 001–014 / 35 permissions;
- no secret or Mobile PASS claims;
- no runtime/config/DB/migration/workflow/theme/deploy/tool changes.

## 7. Branch cleanup

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git branch --merged origin/main
```

Before deletion: exact main/tip, reachability, unique commits, PR/post-merge state and exact owner-approved deletion batch.

```text
SAFE TO DELETE != AUTHORIZED TO DELETE
```

Remote deletion first, then `fetch --prune`; no force deletion without explicit authorization. Standing maintenance of rules/handoff does **not** authorize its docs-branch deletion.

## 8. Local automation

Canonical guide: `../tools/github-automation/README.md`. Native PowerShell 5.1 regression baseline is `58 PASS / 0 FAIL` within tooling scope; real GitHub/Codex authentication and paid API request are not inferred.

## 9. Secrets

Do not publish production/instance credentials, `config/local.php`, sessions, tokens, API keys, OAuth/device codes, private keys or real temporary passwords.

Approved local-only fixture `Admin / 12315` remains local/bootstrap-only and requires password replacement; it is not production credential material.
