# GitHub Local Automation One-Command Bootstrap — Specification

Status: `PROPOSED / PRE-IMPLEMENTATION`

Date: `2026-08-05`

Baseline:

```text
main: d9c7db67ee2df8204281d67a4c134528a499573b
branch: tools/github-local-automation-bootstrap
```

## 1. Scope

Create a repository-owned Windows PowerShell 5.1 package that allows the project owner to synchronize the merged repository and then run one command that performs environment verification, component installation, authentication guidance and local helper deployment.

Target command after merge:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

## 2. Exact changed-path allowlist

### Process records

1. `docs/architecture/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-ARCHITECTURE.md`
2. `docs/specification/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-SPECIFICATION.md`
3. `docs/review/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-FORMAL-REVIEW.md`
4. `docs/decisions/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-APPROVAL.md`
5. `docs/implementation/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-IMPLEMENTATION.md`
6. `docs/testing/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-VALIDATION.md`
7. `docs/review/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-PR-FINAL-REVIEW.md`

### Tooling package

8. `tools/github-automation/Install-ASUVCHGitHubAutomation.ps1`
9. `tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1`
10. `tools/github-automation/automation-manifest.json`
11. `tools/github-automation/README.md`
12. `tools/github-automation/CODEX-INSTRUCTIONS.md`

```text
TOTAL_PATHS=12
MARKDOWN_PATHS=9
POWERSHELL_PATHS=2
JSON_PATHS=1
```

No path outside this exact allowlist may be changed.

The Final PR Review path remains absent until separately authorized PR Final Review.

## 3. Installer interface

Path:

```text
tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
```

Required compatibility:

```text
Windows PowerShell 5.1
Windows 10/11 64-bit
```

Required parameters/defaults:

```powershell
-Mode Install|Repair|Doctor       # default Install
-RepositoryPath 'C:\Project\ASU-VCH'
-InstallPath 'C:\Tools\ASU-VCH'
-RepositoryFullName 'ClaytonKinnane/ASU-VCH'
-RemoteName 'origin'
-MainBranch 'main'
```

Optional switches:

```powershell
-SkipCodex
-SkipGitHubLogin
-NoUpgrade
-AllowDirtyWorktree              # Doctor only
-NonInteractivePackages
```

The installer must not accept tokens, API keys, passwords, cookies or private keys as parameters.

## 4. One-command execution requirements

The installer must be runnable from a synchronized repository using exactly:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

The script must resolve its own repository-relative companion paths. It must not depend on the caller's current directory after startup.

## 5. Ordered installation stages

1. Validate Windows and PowerShell version.
2. Enable TLS 1.2 for current process.
3. Initialize timestamped log under `%LOCALAPPDATA%\ASU-VCH\Logs`.
4. Resolve script root and repository root.
5. Verify repository path is a Git worktree.
6. Verify `origin` exactly targets `ClaytonKinnane/ASU-VCH` through HTTPS or SSH canonical forms.
7. Verify clean worktree.
8. Detect Git. When absent, require WinGet and install exact package `Git.Git`.
9. Refresh process PATH.
10. Run `git fetch --prune origin`.
11. Verify `origin/main` exists.
12. Verify local `HEAD == origin/main`.
13. Verify current branch is `main` for Install/Repair.
14. Detect GitHub CLI. When absent, install exact package `GitHub.cli` through WinGet.
15. Refresh process PATH and verify `gh --version`.
16. Check `gh auth status --hostname github.com --active` without logging sensitive output.
17. When required and not skipped, run interactive `gh auth login --hostname github.com --git-protocol https --web`.
18. Run `gh auth setup-git --hostname github.com`.
19. Verify GitHub repository metadata, default branch `main` and authenticated push/write permission.
20. Detect Codex.
21. When absent or repair requested, download official Windows installer from `https://chatgpt.com/codex/install.ps1` to a temporary file.
22. Record only public SHA-256 of the downloaded Codex installer.
23. Execute the downloaded installer in a child PowerShell process.
24. Refresh PATH and verify `codex --version`.
25. Check `codex login status` without logging credentials.
26. When unauthenticated, print or start the interactive ChatGPT login action; never request an API key.
27. Read and validate `automation-manifest.json` from the synchronized repository.
28. Verify SHA-256 of every listed helper source file.
29. Stage helper files in a temporary directory.
30. Atomically replace the local installation under `C:\Tools\ASU-VCH` while preserving the previous copy on failure where practical.
31. Execute cleanup tool in `Doctor` mode.
32. Print final PASS/PENDING summary and exact next commands.

## 6. WinGet requirements

When `winget` is missing, the installer must:

- explain that Microsoft App Installer is required;
- offer to open the official Microsoft Store App Installer page;
- stop before partial Git/GitHub CLI installation;
- print the exact command for resuming after WinGet installation.

Package commands must use:

```powershell
winget install --id Git.Git -e --source winget --accept-source-agreements --accept-package-agreements
winget install --id GitHub.cli -e --source winget --accept-source-agreements --accept-package-agreements
```

When upgrade is enabled and package already exists, the script may use equivalent exact-id `winget upgrade` commands. It must verify the resulting executable instead of trusting only the WinGet exit code.

## 7. Repository verification

The installer must fail closed unless all required conditions hold:

```text
repository worktree = valid
origin = ClaytonKinnane/ASU-VCH
worktree = clean
origin/main = available
local HEAD = origin/main
current branch = main for Install/Repair
GitHub default branch = main
GitHub authenticated access = PASS
GitHub push/write permission = PASS
```

The installer must not checkout, reset, merge, rebase or force-update any branch.

## 8. Codex boundary

Codex installation is intended to provide local agent/terminal capability. It does not extend the browser ChatGPT connector.

Required checks:

```text
codex command available
codex --version succeeds
codex login status checked
```

Authentication remains interactive through ChatGPT. Token or API-key collection is prohibited.

## 9. Manifest requirements

Path:

```text
tools/github-automation/automation-manifest.json
```

Required shape:

```json
{
  "schemaVersion": 1,
  "minimumPowerShell": "5.1",
  "repository": "ClaytonKinnane/ASU-VCH",
  "defaultInstallPath": "C:\\Tools\\ASU-VCH",
  "files": [
    {
      "path": "tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1",
      "installName": "Invoke-ASUVCHBranchCleanup.ps1",
      "sha256": "<64 lowercase hex>"
    },
    {
      "path": "tools/github-automation/CODEX-INSTRUCTIONS.md",
      "installName": "CODEX-INSTRUCTIONS.md",
      "sha256": "<64 lowercase hex>"
    }
  ]
}
```

The manifest must not contain:

- commit self-reference;
- hash of itself;
- credentials;
- machine-specific secrets.

## 10. Branch cleanup tool

Path:

```text
tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
```

Modes:

```text
Doctor
Verify
Delete
```

Delete requires:

```powershell
-PullRequestNumber <positive integer>
-BranchName '<exact branch>'
-ExpectedMainSha '<40-char SHA>'
-ExpectedPrHeadSha '<40-char SHA>'
-ExpectedMergeCommitSha '<40-char SHA>'
-PostMergeRunId <positive integer>
-ApprovalToken '<exact branch>'
```

Preflight requirements:

```text
main == ExpectedMainSha
PR state == closed
PR merged_at != null
PR base == main
PR head branch == BranchName
PR head SHA == ExpectedPrHeadSha
PR merge commit == ExpectedMergeCommitSha
push run event == push
push run branch == main
push run head SHA == ExpectedMainSha
run/job/required steps == success
canonical PR comment contains POST_MERGE_VERIFICATION_STATUS=PASS
remote branch SHA == ExpectedPrHeadSha
branch ahead of main == 0
unique unmerged commits == 0
ApprovalToken == BranchName case-sensitive
```

Protected identities:

```text
main
master
repository default branch
invalid refs
refs outside refs/heads/*
```

The only destructive command is:

```powershell
git push origin --delete <BranchName>
```

The script must support `SupportsShouldProcess` and `-WhatIf`, and must not delete local branches.

## 11. Local helper installation

Installed files:

```text
C:\Tools\ASU-VCH\Invoke-ASUVCHBranchCleanup.ps1
C:\Tools\ASU-VCH\CODEX-INSTRUCTIONS.md
C:\Tools\ASU-VCH\automation-manifest.json
```

The repository remains the source of truth. Repair mode recopies only after manifest validation.

## 12. Validation requirements

Repository/static validation must include:

1. exact 12-path allowlist check;
2. PowerShell parser check;
3. Windows PowerShell 5.1 syntax compatibility review;
4. manifest JSON schema and hash verification;
5. forbidden command scan;
6. secret-pattern scan;
7. proof that installer does not checkout/reset/merge/rebase/force-update;
8. proof that settings/protection mutation commands are absent;
9. proof that branch deletion exists only in cleanup Delete mode;
10. cleanup Doctor/Verify static path review;
11. no runtime/DB/migration/workflow/theme/deploy changes;
12. clean repository state after validation.

Target-machine acceptance remains user-executed and must include:

```text
one-command installer run
Git/GitHub CLI detection or installation
GitHub browser login when needed
repository/write access verification
Codex installation/version/login status
manifest deployment
cleanup Doctor PASS
second installer run for idempotency
```

Actual branch deletion is not part of installation acceptance and remains separately authorized.

## 13. Process gate

Implementation is prohibited until owner approval of Architecture, Specification, Formal Review and this exact 12-path allowlist.
