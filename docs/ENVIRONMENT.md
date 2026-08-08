# Среда разработки и запуска

## Поддерживаемая application-среда

```text
ОС: Windows 10/11
Open Server Panel: 6.5.1
Web server: Apache
PHP: 8.5.4
MySQL: 8.4.x
Shell: Windows PowerShell 5.1
```

Последняя полная accepted functional validation выполнена для PR #36 Military Positions Directory v1 на exact runtime head `c647a933011873048866c75978d3f506634011fd` в локальной Windows/Open Server/MySQL среде. GitHub-hosted static runs используют Ubuntu 24.04 и PHP 8.5.x; это CI evidence, а не изменение local runtime requirement.

## Local repository tooling

Local automation package through PR #30 поддерживает:

```text
Git for Windows
WinGet
GitHub CLI
Node.js LTS
npm
Codex CLI
Windows PowerShell 5.1
```

Canonical guide:

- [GitHub Local Automation](../tools/github-automation/README.md)

Tooling является repository-only и не публикуется как application deploy. Installer может проверять или устанавливать approved components, выполнять staged interactive login flows, проверять manifest и атомарно устанавливать helpers.

Границы evidence:

```text
native PowerShell 5.1 regression PR #30: 58 PASS / 0 FAIL
real GitHub authentication acceptance: NOT CLAIMED
real Codex authentication acceptance: NOT CLAIMED
account verification: NOT CLAIMED
paid OpenAI API request: NOT RUN
complete target-machine installation acceptance: NOT CLAIMED
```

Browser ChatGPT не получает прямого доступа к локальному компьютеру.

## Разделение каталогов

```text
Git clone:   C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
web root:    C:\OSPanel\home\asu-vch.local\public
local URL:   https://asu-vch.local
local helpers: C:\Tools\ASU-VCH
```

Git clone не является web root. Существующий `C:\OSPanel\home\asu.local` не относится к АСУ-ВЧ.

## Deploy contract

`deploy\Deploy-Local.ps1` публикует app, config, database, public, themes и OSP project config. Existing `config/local.php` сохраняется и восстанавливается. Documentation, `.git` и repository-only tools не публикуются как application files.

## Stable synchronization

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

Local `main` должна совпадать с `origin/main`, worktree — clean.

## Initialization

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1'
```

Repeat installer:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\Initialize-Local.ps1' -SkipDeploy
```

Current expected result для synchronized current `main`:

```text
Применено миграций: 14
Новых миграций нет.
```

Migrations 009–012 используют compatibility mechanisms. Migrations 010–011 имеют gzip/base64 packaging; migration 012 использует dedicated loader and fail-closed marker. Migrations 013 и 014 являются standalone increment migrations; migration 014 не меняет migration-010 packaging.

Перед schema/data migration требуется SQL backup. Для PR #24 отсутствие pre-migration backup зафиксировано как process deviation; post-migration backup создан и проверен. Для migration 014 pre-migration backup и repeat initialization были выполнены и прошли.

## Required theme assets

Каждая тема публикует 10 required CSS-assets:

```text
css/theme.css
css/auth.css
css/account.css
css/users.css
css/theme-management.css
css/directories.css
css/military-ranks-v2.css
css/military-occupational-specialties.css
css/organization.css
css/operation-result-modal.css
```

Для `asu-evgeniya-rostova` дополнительно обязательны 4 local SVG-assets.

## Functional runners

Managed Military Positions Directory v1:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryPositionsDirectoryV1.ps1' `
  -RepositoryPath 'C:\Project\ASU-VCH' `
  -ExpectedHead <EXACT_HEAD> `
  -RunInitialization `
  -RunHttpSmoke `
  -AllowInvalidCertificate
```

Historical Military Positions classifier:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryPositionsDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Public VUS:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File '.\tools\Test-MilitaryOccupationalSpecialtiesDirectory.ps1' `
  -DeployRoot 'C:\OSPanel\home\asu-vch.local' `
  -AllowInvalidCertificate
```

Military Ranks v2 evidence is recorded in:

- `testing/MILITARY-RANKS-DIRECTORY-V2-TEST-REPORT.md`;
- `testing/MILITARY-RANKS-DIRECTORY-V2-MANUAL-DESKTOP-ACCEPTANCE-2026-08-03.md`;
- `review/MILITARY-RANKS-DIRECTORY-V2-PR-FINAL-REVIEW.md`.

Historical PR #24 functional result:

```text
applied migrations: 12
repeat installer: no new migrations
Military Ranks v2 checks: PASS
DB regression: PASS
deploy/source parity: PASS
HTTP smoke: PASS
manual desktop: PASS
```

Latest accepted PR #36 functional result:

```text
PHP lint: 171 PASS
applied migrations: 14
repeat initialization: PASS / no new migration
Military Positions DB/runtime checker: 167 PASS
HTTP smoke: 200,200,302
asu-blue desktop: PASS
asu-light-blue desktop: PASS
asu-evgeniya-rostova desktop: PASS
mutual exclusion: PASS
open findings: 0
real Staffing data mutation: NONE
mobile: OUT OF SCOPE / NOT RUN
```

## GitHub Actions Static Verification

Workflow `ASU-VCH Static Verification` runs on PR/push/manual events with Ubuntu 24.04 and PHP 8.5. It checks whitespace, tracked PHP syntax, 9 CI-safe checker'ов and final repository integrity.

```text
PR #25 post-merge push run: 30837637886 / SUCCESS
PR #25 post-merge manual run: 30839122892 / SUCCESS
PR #30 exact-head PR run: 31024419654 / SUCCESS
PR #30 post-merge push run: 31025264683 / SUCCESS
PR #36 post-merge main run: SUCCESS
required status check: NOT ENABLED
branch protection changed: NO
```

CI does not execute MySQL, deploy, HTTP/browser or visual acceptance.

## Configuration protection

Нельзя публиковать production/instance credentials, DB password, session data, `config/local.php`, real temporary user passwords, tokens, API keys или private keys. Authentication secrets не передаются через command arguments, environment variables или logs.

## Testing boundaries

```text
functional mobile testing: OUT OF SCOPE / NOT RUN
production deployment: NOT PERFORMED
local automation real authentication acceptance: NOT CLAIMED
paid API request: NOT RUN
mobile PASS: NOT CLAIMED
```
