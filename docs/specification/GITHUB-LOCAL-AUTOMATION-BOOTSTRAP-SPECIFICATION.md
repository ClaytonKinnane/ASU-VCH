# GitHub Local Automation Bootstrap — Specification

Status: `PROPOSED / PRE-IMPLEMENTATION`

Date: `2026-08-04`

Baseline:

```text
main: d9c7db67ee2df8204281d67a4c134528a499573b
branch: tools/github-local-automation-bootstrap
branch start: d9c7db67ee2df8204281d67a4c134528a499573b
```

## 1. Scope

Create a Windows PowerShell 5.1 package that, after the repository is synchronized locally, is started with one command and performs all automatable preparation required for a local Codex agent to work effectively with `ClaytonKinnane/ASU-VCH`.

Canonical user command:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File 'C:\Project\ASU-VCH\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

The script may open browser authentication and UAC prompts. The user must complete those security prompts, after which the same script continues verification.

## 2. Exact changed-path allowlist

### Project-agent instruction

1. `AGENTS.md`

### Process records

2. `docs/architecture/GITHUB-LOCAL-AUTOMATION-BOOTSTRAP-ARCHITECTURE.md`
3. `docs/specification/GITHUB-LOCAL-AUTOMATION-BOOTSTRAP-SPECIFICATION.md`
4. `docs/review/GITHUB-LOCAL-AUTOMATION-BOOTSTRAP-FORMAL-REVIEW.md`
5. `docs/decisions/GITHUB-LOCAL-AUTOMATION-BOOTSTRAP-APPROVAL.md`
6. `docs/implementation/GITHUB-LOCAL-AUTOMATION-BOOTSTRAP-IMPLEMENTATION.md`
7. `docs/testing/GITHUB-LOCAL-AUTOMATION-BOOTSTRAP-VALIDATION.md`
8. `docs/review/GITHUB-LOCAL-AUTOMATION-BOOTSTRAP-PR-FINAL-REVIEW.md`

### Tooling package

9. `tools/github-automation/Install-ASUVCHGitHubAutomation.ps1`
10. `tools/github-automation/Test-ASUVCHGitHubAutomation.ps1`
11. `tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1`
12. `tools/github-automation/Start-ASUVCHCodex.ps1`
13. `tools/github-automation/automation-manifest.json`
14. `tools/github-automation/README.md`

```text
TOTAL_PATHS=14
MARKDOWN_PATHS=9
POWERSHELL_PATHS=4
JSON_PATHS=1
```

No path outside this exact allowlist may be changed.

The Final PR Review path remains absent until the separately authorized Pull Request Final Review stage.

## 3. Capability outcome

Successful target-machine acceptance must produce:

```text
POWERSHELL_5_1_READY=YES
WINGET_READY=YES
GIT_READY=YES
GITHUB_CLI_READY=YES
GITHUB_AUTH_READY=YES
GITHUB_REPOSITORY_WRITE_ACCESS=YES
CODEX_READY=YES
CODEX_CHATGPT_AUTH_READY=YES
ASU_VCH_REPOSITORY_READY=YES
ASU_VCH_AGENT_INSTRUCTIONS_READY=YES
ASU_VCH_LOCAL_HELPERS_READY=YES
LOCAL_CODEX_AGENT_READY=YES
```

The result applies to a local Codex session. It must not claim that the browser ChatGPT connector gained local terminal access.

## 4. Root `AGENTS.md` requirements

Path:

```text
AGENTS.md
```

The file must:

- be UTF-8 Markdown;
- remain concise and below 32 KiB;
- apply to the complete repository tree;
- define repository, local and deployment paths;
- define the lifecycle:

```text
Architecture → Specification → Review → Approval → Implementation →
Testing → Commit → Push → PR → Final PR Review → separate Merge approval →
Merge → post-merge verification → separate branch-deletion approval
```

It must require:

- exact-SHA fail-closed gates;
- explicit path allowlists;
- no implementation before owner approval;
- no PR without separate permission;
- no Merge without separate permission;
- no branch deletion without separate permission;
- no branch-protection/settings mutation without separate permission;
- no secret disclosure;
- no unsupported runtime/mobile testing claims;
- clear `Действия, требуемые от вас` sections for user gates.

It must not contain credentials or machine-specific secrets.

## 5. Installer interface

Path:

```text
tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
```

Required declaration:

```powershell
#requires -Version 5.1
[CmdletBinding(SupportsShouldProcess = $true)]
```

Required parameters:

```powershell
-Mode Install|Repair|Doctor
-RepositoryPath 'C:\Project\ASU-VCH'
-InstallPath 'C:\Tools\ASU-VCH'
-RepositoryFullName 'ClaytonKinnane/ASU-VCH'
-RemoteName 'origin'
-MainBranch 'main'
```

Defaults:

```text
Mode=Install
RepositoryPath=C:\Project\ASU-VCH
InstallPath=C:\Tools\ASU-VCH
RepositoryFullName=ClaytonKinnane/ASU-VCH
RemoteName=origin
MainBranch=main
```

Optional switches may include:

```powershell
-NoUpgrade
-SkipGitHubLogin
-SkipCodexLogin
-AllowDirtyWorktree
-SkipCodex
-NonInteractivePackages
```

No token, API key, password, cookie, private-key or authorization-header parameter is allowed.

## 6. Installer execution order

The installer must perform stages in this exact logical order:

1. initialize strict/error behavior;
2. validate Windows and PowerShell version;
3. enable TLS 1.2 for the current process;
4. initialize a timestamped log outside the repository;
5. resolve repository/package paths;
6. validate the manifest schema;
7. verify all repository-owned package hashes;
8. validate target install path;
9. detect WinGet;
10. repair/install WinGet when missing or broken;
11. verify WinGet functionality;
12. install or upgrade Git;
13. install or upgrade GitHub CLI;
14. refresh process PATH;
15. verify Git and GitHub CLI commands;
16. verify or start GitHub authentication;
17. configure Git credential integration;
18. verify GitHub repository access and write permission;
19. validate local repository and remote identity;
20. verify synchronization/worktree state;
21. install or upgrade Codex unless skipped;
22. refresh process PATH;
23. verify Codex command;
24. verify or start Codex ChatGPT authentication unless skipped;
25. stage local helper installation;
26. verify staged hashes;
27. atomically install local helpers;
28. write non-secret local installation state;
29. execute readiness test;
30. print final capability matrix and exact launch command.

No component may be declared successful before its verification command succeeds.

## 7. Platform checks

The installer must fail closed unless all are true:

```text
OS platform = Windows NT
PowerShell major version >= 5
PowerShell minor version >= 1 when major = 5
64-bit operating system = true
architecture = x64 or ARM64
```

The installer may run from a 32-bit PowerShell process only if it relaunches itself in 64-bit Windows PowerShell and preserves safe parameters.

Windows Server is outside the approved target unless explicitly added later.

## 8. TLS and network behavior

The installer must enable TLS 1.2 for the current process before PowerShell Gallery or HTTPS downloads.

Allowed external sources:

```text
PowerShell Gallery / Microsoft.WinGet.Client
Microsoft WinGet source
Git for Windows package Git.Git
GitHub CLI package GitHub.cli
github.com and api.github.com
chatgpt.com/codex/install.ps1
OpenAI Codex release endpoints reached by the official installer
```

It must reject unexpected package identities and must not disable certificate validation.

## 9. Package integrity checks

Manifest path:

```text
tools/github-automation/automation-manifest.json
```

Required manifest shape:

```json
{
  "schemaVersion": 1,
  "minimumPowerShell": "5.1",
  "repositoryFullName": "ClaytonKinnane/ASU-VCH",
  "defaultRepositoryPath": "C:\\Project\\ASU-VCH",
  "defaultInstallPath": "C:\\Tools\\ASU-VCH",
  "files": [
    {
      "path": "tools/github-automation/Install-ASUVCHGitHubAutomation.ps1",
      "installName": "Install-ASUVCHGitHubAutomation.ps1",
      "sha256": "<64 lowercase hex>",
      "install": true
    }
  ]
}
```

Required manifest rules:

- `schemaVersion == 1`;
- every path is repository-relative;
- every path is inside the approved package paths;
- no `..` traversal;
- no duplicate source paths;
- no duplicate install names;
- every SHA-256 is 64 lowercase hexadecimal characters;
- the manifest must not hash itself;
- the manifest must not contain credentials;
- every listed file must exist and match its SHA-256 before any installation action.

The installer and installed readiness test must both validate the manifest.

## 10. WinGet detection and repair

The installer first invokes a safe WinGet probe such as:

```powershell
winget --info
```

When WinGet is unavailable or nonfunctional, it must use Microsoft's supported PowerShell repair flow:

```powershell
Install-PackageProvider -Name NuGet -Force
Install-Module -Name Microsoft.WinGet.Client -Force -Repository PSGallery -Scope CurrentUser
Import-Module Microsoft.WinGet.Client
Repair-WinGetPackageManager -Force -Latest
```

Requirements:

- run in the current-user scope where supported;
- avoid permanently trusting PSGallery unless necessary;
- restore any temporary repository trust change;
- refresh PATH after repair;
- verify `winget --info` afterward;
- stop fail-closed if verification fails;
- log only module/package versions and status.

## 11. Git installation

Package id:

```text
Git.Git
```

Canonical command shape:

```powershell
winget install --id Git.Git -e --source winget --accept-source-agreements --accept-package-agreements
```

The installer must:

- detect an existing `git` command;
- install when absent;
- upgrade unless `-NoUpgrade` is set;
- allow official installer UAC prompts;
- refresh PATH;
- verify `git --version`;
- verify the executable resolves from a normal installation path;
- not modify global Git identity automatically.

## 12. GitHub CLI installation

Package id:

```text
GitHub.cli
```

Canonical command shape:

```powershell
winget install --id GitHub.cli -e --source winget --accept-source-agreements --accept-package-agreements
```

The installer must:

- detect an existing `gh` command;
- install when absent;
- upgrade unless `-NoUpgrade` is set;
- refresh PATH;
- verify `gh --version`.

## 13. GitHub authentication

Authentication probe:

```powershell
gh auth status --hostname github.com --active
```

When authentication is absent and login is not skipped, invoke:

```powershell
gh auth login --hostname github.com --git-protocol https --web
gh auth setup-git --hostname github.com
```

Afterward verify authentication again.

The installer must:

- keep browser/device authentication visible;
- not use `--with-token`;
- not invoke `gh auth token`;
- not read GitHub credential files;
- not log auth command output verbatim when it could reveal sensitive metadata;
- report only account login and PASS/FAIL status.

## 14. GitHub repository permission verification

Using authenticated `gh api`, verify:

```text
repository full_name == ClaytonKinnane/ASU-VCH
default_branch == main
permissions.push == true
```

The installer may read metadata, branches, PRs, Actions runs and repository contents.

It must not:

- modify branch protection;
- modify required checks;
- modify Actions settings;
- modify repository settings;
- create/delete a branch during installation;
- create a PR during installation;
- perform Merge during installation.

## 15. Local repository validation

The installer must derive the repository root from its own location and confirm it equals or is contained by the supplied `RepositoryPath`.

Required checks:

```text
RepositoryPath exists
.git worktree is valid after Git is available
remote origin exists
origin URL matches ClaytonKinnane/ASU-VCH
GitHub default branch is main
local HEAD is a commit
current branch is not detached
working tree clean unless -AllowDirtyWorktree
no unresolved merge/rebase/cherry-pick state
```

Synchronization checks:

1. run `git fetch --prune origin`;
2. resolve current local branch;
3. verify its remote branch exists when applicable;
4. verify local HEAD equals remote branch HEAD;
5. report divergence fail-closed when ahead or behind unexpectedly.

Allowed execution branches during pre-merge acceptance:

```text
tools/github-local-automation-bootstrap
```

Allowed execution branch after merge:

```text
main
```

Other branches require an explicit override parameter and must be reported.

## 16. Codex installation

Official installer URL:

```text
https://chatgpt.com/codex/install.ps1
```

The installer must:

1. download the upstream installer into a unique temporary directory;
2. ensure HTTPS and expected source host;
3. compute and log the downloaded script SHA-256;
4. execute it using Windows PowerShell without injecting secrets;
5. refresh PATH;
6. verify `codex --version`;
7. remove the temporary installer after execution when practical.

It must not use an unofficial package named only `Codex` from an ambiguous package source.

## 17. Codex authentication

Authentication probe:

```powershell
codex login status
```

When not authenticated and login is not skipped, invoke:

```powershell
codex login
```

Then verify `codex login status` again.

Requirements:

- prefer ChatGPT account sign-in;
- never request an API key;
- never read `%USERPROFILE%\.codex\auth.json`;
- never print or log tokens;
- fail closed if authentication remains absent;
- allow the user to cancel and rerun installer `Repair` later.

## 18. Local helper installation

Install destination:

```text
C:\Tools\ASU-VCH
```

Managed files:

```text
Install-ASUVCHGitHubAutomation.ps1
Test-ASUVCHGitHubAutomation.ps1
Invoke-ASUVCHBranchCleanup.ps1
Start-ASUVCHCodex.ps1
automation-manifest.json
README.md
installation-state.json
```

Installation procedure:

1. create staging directory outside the repository;
2. copy files listed by manifest;
3. verify copied hashes;
4. preserve the previous installation as a temporary backup;
5. replace the managed installation atomically where practical;
6. execute the installed readiness test;
7. remove backup only after PASS;
8. restore backup when installation validation fails.

The installer must not copy secrets from the repository or user profile.

## 19. Local installation state

Generated path:

```text
C:\Tools\ASU-VCH\installation-state.json
```

Allowed fields:

```text
schemaVersion
installedAt
repositoryFullName
repositoryPath
repositoryCommit
installerVersion
PowerShellVersion
WinGetVersion
GitVersion
GitHubCliVersion
CodexVersion
manifestSha256
capabilityStatus
```

Prohibited fields:

```text
username/email unless explicitly required
OAuth token
API key
cookie
device code
credential path contents
private machine secret
```

## 20. Readiness test interface

Path:

```text
tools/github-automation/Test-ASUVCHGitHubAutomation.ps1
```

Modes:

```text
Quick
Full
```

Default:

```text
Full
```

Required output includes:

```text
POWERSHELL_CHECK
MANIFEST_CHECK
WINGET_CHECK
GIT_CHECK
GH_CHECK
GH_AUTH_CHECK
GH_REPOSITORY_ACCESS_CHECK
GH_WRITE_PERMISSION_CHECK
LOCAL_REPOSITORY_CHECK
SYNC_CHECK
WORKTREE_CHECK
CODEX_CHECK
CODEX_AUTH_CHECK
AGENTS_MD_CHECK
LOCAL_HELPER_CHECK
OVERALL_STATUS
```

Exit codes:

```text
0 = PASS
1 = FAIL
2 = PASS WITH USER ACTION REQUIRED
```

The test must not change GitHub or repository state.

## 21. Codex launcher interface

Path:

```text
tools/github-automation/Start-ASUVCHCodex.ps1
```

Defaults:

```text
RepositoryPath=C:\Project\ASU-VCH
RequireCleanWorktree=true
RequireSynchronizedBranch=true
```

The launcher must:

- run the Quick readiness test;
- verify `AGENTS.md` exists;
- change directory to the repository root;
- invoke `codex` interactively;
- preserve Codex sandbox and approval defaults;
- never pass `--dangerously-bypass-approvals-and-sandbox` or equivalent unsafe flags;
- never include credentials on the command line.

## 22. Branch cleanup tool interface

Path:

```text
tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
```

Required declaration:

```powershell
#requires -Version 5.1
[CmdletBinding(SupportsShouldProcess = $true, ConfirmImpact = 'High')]
```

Modes:

```text
Doctor
Verify
Delete
```

Required Delete inputs:

```powershell
-PullRequestNumber <positive integer>
-BranchName '<exact branch>'
-ExpectedMainSha '<40-character SHA>'
-ExpectedPrHeadSha '<40-character SHA>'
-ExpectedMergeCommitSha '<40-character SHA>'
-PostMergeRunId <positive integer>
-ApprovalToken '<exact branch name>'
```

Required conditions:

```text
main == ExpectedMainSha
PR state == closed
PR merged_at != null
PR base == main
PR head branch == BranchName
PR head SHA == ExpectedPrHeadSha
PR merge commit == ExpectedMergeCommitSha
post-merge run event == push
post-merge run branch == main
post-merge run head SHA == ExpectedMainSha
post-merge run conclusion == success
required job conclusion == success
required steps conclusion == success
canonical PR comment contains POST_MERGE_VERIFICATION_STATUS=PASS
remote branch SHA == ExpectedPrHeadSha
branch ahead of main == 0
unique unmerged commits == 0
ApprovalToken case-sensitive equals BranchName
```

Protected identities:

```text
main
master
GitHub default branch
empty/invalid refs
refs outside refs/heads
branch identity mismatch
```

The only destructive repository command is:

```powershell
git push origin --delete <BranchName>
```

Post-delete checks:

```text
branch absent
main unchanged
PR still merged
merge commit unchanged
FEATURE_BRANCH_DELETION_STATUS=PASS
```

The tool must not delete a local branch.

## 23. Logging

Default log directory:

```text
%LOCALAPPDATA%\ASU-VCH\Logs
```

Each command creates a timestamped UTF-8 log.

Logging functions must redact values matching token/key patterns and avoid capturing interactive authentication output.

Logs must remain outside the repository.

## 24. Idempotency and repair

Repeated `Install` or `Repair` execution must:

- revalidate package integrity;
- detect already installed components;
- avoid duplicate local files;
- upgrade allowed components unless `-NoUpgrade`;
- preserve authentication when valid;
- rerun readiness checks;
- repair missing/corrupt managed helper files;
- not create repository changes.

`Doctor` mode must perform read-only diagnostics and install nothing.

## 25. Uninstall boundary

The package may document a local-helper uninstall command that removes only:

```text
C:\Tools\ASU-VCH
```

It must not automatically uninstall Git, GitHub CLI, Codex, WinGet or user credentials because they may be shared with other projects.

## 26. Repository/static validation

Before target-machine acceptance, Validation must include:

1. exact changed-path allowlist check;
2. PowerShell parser checks for all four scripts;
3. Windows PowerShell 5.1 syntax review;
4. strict-mode and terminating-error checks;
5. forbidden unsafe Codex flag checks;
6. forbidden secret-reading/logging checks;
7. forbidden repository-settings mutation checks;
8. manifest JSON schema checks;
9. manifest SHA-256 verification;
10. path traversal rejection checks;
11. installer state-machine review;
12. WinGet exact package-id checks;
13. official Codex installer URL check;
14. GitHub auth web-flow checks;
15. root `AGENTS.md` size/content check;
16. branch cleanup fail-closed checks;
17. `-WhatIf`/ShouldProcess checks;
18. no runtime/DB/migration/workflow/theme/deploy changes;
19. no credentials or personal data;
20. clean repository tree after tests.

## 27. Target-machine acceptance

The owner must synchronize the exact implementation branch/head and run the canonical one-command installer.

Acceptance evidence must include:

```text
installer exit code
sanitized log
component versions
GitHub auth PASS
repository write permission PASS
Codex auth PASS
manifest PASS
local helper PASS
full readiness OVERALL_STATUS=PASS
```

Target-machine acceptance must not perform an actual branch deletion unless separately authorized.

## 28. Process gates

Implementation is forbidden until owner approval of:

- Architecture;
- this Specification;
- Formal Review;
- exact 14-path allowlist;
- exact reviewed branch head.

After Implementation and repository/static Validation, stop before Pull Request creation.

Pull Request, Final PR Review, Merge and branch deletion require separate explicit permissions.
