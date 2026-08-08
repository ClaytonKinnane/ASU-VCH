# Среда разработки и запуска

## Application environment

```text
Windows 10/11
Open Server Panel: 6.5.1
Apache
PHP: 8.5.4
MySQL: 8.4.x
Windows PowerShell: 5.1
repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
web root: C:\OSPanel\home\asu-vch.local\public
URL: https://asu-vch.local
```

Git clone не является web root. `config/local.php` и instance secrets сохраняются локально и не коммитятся.

## Current repository/runtime expectation

```text
migrations in main: 001–014
expected initialized local DB after current installer: 14 applied / no new migrations
system roles: 4
system permissions: 35
built-in themes: 3
required CSS assets per theme: 10
```

A local machine that has not been synchronized/applied to current `main` may legitimately show an older migration count; verify exact repository HEAD before interpreting output.

## Synchronization

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch main
git pull --ff-only origin main
git rev-parse HEAD
git rev-parse origin/main
git status --short
```

Expected: clean worktree and local `main == origin/main` before validation/deploy.

## Initialization

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

For current merged schema the repeat result is expected to report 14 applied migrations and no new migrations.

SQL backup is required before material schema/data migration unless a deviation is explicitly approved and recorded.

## Deploy contract

`deploy\Deploy-Local.ps1` publishes application/config/database/public/themes and preserves existing `config/local.php`. Documentation, `.git` and repository-only tooling are not application deploy artifacts.

## Current functional runner

Managed Military Positions Directory v1:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Test-MilitaryPositionsDirectoryV1.ps1' `
  -RepositoryPath 'C:\Project\ASU-VCH' `
  -ExpectedHead <EXACT_HEAD> `
  -RunInitialization `
  -RunHttpSmoke `
  -AllowInvalidCertificate
```

Historical runners for Military Ranks, public VUS, Organization and Staffing remain repository evidence/tools for their scopes.

## Latest functional evidence

Exact Military Positions runtime head `c647a933011873048866c75978d3f506634011fd`:

```text
PHP lint: 171 PASS
migrations 001–014: PASS
repeat initialization: PASS
DB/runtime checker: 167 PASS
HTTP: 200,200,302
three managed desktop themes: PASS
mobile: NOT RUN / OUT OF SCOPE
```

## GitHub-hosted static CI

`ASU-VCH Static Verification` runs on PR to main, push to main and `workflow_dispatch` with Ubuntu 24.04/PHP 8.5.x. It is static evidence only and does not replace MySQL/deploy/HTTP/visual testing. Required status check and branch protection Stage B are not enabled.

## Local Git/GitHub/Codex tooling

Repository automation through PR #30 supports Git, GitHub CLI, Node.js LTS, Codex CLI, authentication-mode separation, integrity checks, atomic helper install/rollback and cleanup verification.

Native PR #30 regression `58 PASS / 0 FAIL` does not prove real account authentication, paid API requests or complete target-machine acceptance.

## Security/testing boundaries

```text
production deployment: NOT PERFORMED
mobile: NOT RUN / OUT OF SCOPE
mobile PASS: NOT CLAIMED
real paid API request: NOT CLAIMED
```
