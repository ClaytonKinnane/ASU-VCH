# GitHub Local Automation Bootstrap — Architecture

Status: `PROPOSED / PRE-IMPLEMENTATION`

Date: `2026-08-04`

Repository: `ClaytonKinnane/ASU-VCH`

Baseline:

```text
main: d9c7db67ee2df8204281d67a4c134528a499573b
branch: tools/github-local-automation-bootstrap
branch start: d9c7db67ee2df8204281d67a4c134528a499573b
```

## 1. Purpose

Create a repository-owned Windows PowerShell 5.1 bootstrap package for the project owner.

After synchronizing the repository locally, the owner runs one command:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File 'C:\Project\ASU-VCH\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

That command must orchestrate environment verification, installation, authentication, repository validation, local-tool deployment and final readiness checks.

## 2. Capability boundary

The package expands the capabilities of a **local Codex session** working in `C:\Project\ASU-VCH` by providing:

- local terminal access through Codex;
- Git and GitHub CLI access;
- authenticated GitHub repository read/write operations;
- repository-aware project instructions through root `AGENTS.md`;
- fail-closed branch cleanup after separate owner authorization;
- repeatable diagnostics and readiness reporting.

It does **not** grant the current browser ChatGPT conversation direct access to the user's computer or local terminal. Browser GitHub connector capabilities remain unchanged.

## 3. Architectural boundary

The increment is tooling and project-agent-instructions only.

It must not change:

- PHP/runtime behavior;
- database code, data or migrations;
- themes or public assets;
- deploy code;
- GitHub Actions workflow or pinned Action SHA;
- existing application checkers;
- branch protection, required checks or repository settings;
- production or local database state;
- Open Server Panel configuration;
- `main` outside the normal PR/Merge lifecycle.

The installer may modify the user's local Windows environment only after the user explicitly runs it.

## 4. Target environment

```text
OS: Windows 10/11 client, 64-bit
shell: Windows PowerShell 5.1
repository path: C:\Project\ASU-VCH
local tool path: C:\Tools\ASU-VCH
repository: ClaytonKinnane/ASU-VCH
remote: origin
base branch: main
GitHub host: github.com
```

Supported CPU architectures are `x64` and `ARM64` where the upstream installers support them.

## 5. User workflow

### 5.1 Repository synchronization

The owner synchronizes the approved branch or merged `main` into:

```text
C:\Project\ASU-VCH
```

### 5.2 One-command bootstrap

From any PowerShell location:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File 'C:\Project\ASU-VCH\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

### 5.3 Security interaction

The script performs all automatable steps, but the following operating-system and account security controls remain visible and interactive:

- UAC elevation requested by an official package installer;
- browser-based GitHub sign-in;
- browser-based ChatGPT/Codex sign-in;
- Windows package agreement or policy failures;
- owner authorization before destructive branch deletion.

The installer must orchestrate these interactions and continue afterward, but must not bypass them.

### 5.4 Local agent launch

After successful installation:

```powershell
& 'C:\Tools\ASU-VCH\Start-ASUVCHCodex.ps1'
```

The launcher starts Codex with `C:\Project\ASU-VCH` as the working directory, where Codex automatically discovers the repository root `AGENTS.md`.

## 6. Planned repository components

### 6.1 Root project instructions

Path:

```text
AGENTS.md
```

Responsibilities:

- define the mandatory ASU-VCH lifecycle;
- prohibit implementation before documented approval;
- require exact-SHA gates;
- require separate PR, Merge and branch-deletion approvals;
- prohibit settings mutations without separate permission;
- prohibit secret disclosure;
- prohibit unsupported mobile-test claims;
- define local paths and environment boundaries.

The file must remain concise and below the Codex project-document size limit.

### 6.2 Bootstrap installer

Path:

```text
tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
```

Responsibilities:

1. validate Windows and Windows PowerShell 5.1+;
2. enable TLS 1.2 for the current process;
3. initialize a timestamped log outside the repository;
4. resolve and validate the repository root from the script location;
5. verify the package manifest and SHA-256 of repository-owned files before installing anything;
6. detect or repair WinGet using Microsoft's supported PowerShell repair flow;
7. install or upgrade Git for Windows using package id `Git.Git`;
8. install or upgrade GitHub CLI using package id `GitHub.cli`;
9. refresh the current process `PATH`;
10. verify `git` and `gh` versions;
11. invoke browser-based GitHub authentication when absent;
12. invoke `gh auth setup-git --hostname github.com`;
13. verify repository access and push/write permission;
14. validate `origin`, default branch and local worktree state;
15. install Codex using OpenAI's official Windows standalone installer;
16. verify `codex --version`;
17. invoke interactive ChatGPT login when required;
18. verify `codex login status`;
19. atomically install the approved local helper files to `C:\Tools\ASU-VCH`;
20. execute the local readiness test;
21. print a capability matrix and exact next command.

The installer must be idempotent and support repair/recheck execution.

### 6.3 Readiness test

Path:

```text
tools/github-automation/Test-ASUVCHGitHubAutomation.ps1
```

Responsibilities:

- test PowerShell, WinGet, Git, GitHub CLI and Codex commands;
- test GitHub and Codex authentication without revealing secrets;
- test repository identity, remote and write permission;
- test local repository synchronization and clean worktree;
- test installed helper-file integrity;
- output a machine-readable and human-readable readiness summary;
- perform no destructive GitHub action.

### 6.4 Branch cleanup tool

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

The tool must perform a complete deletion preflight and allow deletion only after separate exact owner approval.

The only destructive repository command is:

```powershell
git push origin --delete <approved-branch>
```

It must prohibit deleting `main`, `master`, the GitHub default branch, an unexpected branch or a branch with unique unmerged commits.

### 6.5 Codex launcher

Path:

```text
tools/github-automation/Start-ASUVCHCodex.ps1
```

Responsibilities:

- verify that Codex is installed and authenticated;
- verify the project directory;
- warn or fail on an invalid repository state;
- set the working directory to `C:\Project\ASU-VCH`;
- launch Codex without supplying tokens or API keys;
- preserve normal Codex approval and sandbox behavior.

### 6.6 Integrity manifest

Path:

```text
tools/github-automation/automation-manifest.json
```

The manifest contains:

- schema version;
- minimum Windows PowerShell version;
- expected repository identity;
- expected local installation path;
- exact package file paths;
- install names;
- lowercase SHA-256 values.

The manifest may hash the installer and companion files but must not hash itself.

The manifest does not contain credentials, machine-specific secrets or a self-referential Git commit SHA.

### 6.7 User guide

Path:

```text
tools/github-automation/README.md
```

The guide contains:

- synchronization prerequisites;
- the one-command installer invocation;
- expected interactive prompts;
- Doctor/Repair usage;
- Codex launch command;
- branch cleanup Verify/Delete examples;
- logs and troubleshooting;
- uninstall instructions;
- the browser-versus-local capability boundary.

## 7. WinGet bootstrap model

The installer first checks `winget`.

When WinGet is unavailable or broken, it uses Microsoft's supported repair sequence in the current user context:

```powershell
Install-PackageProvider -Name NuGet -Force
Install-Module -Name Microsoft.WinGet.Client -Force -Repository PSGallery -Scope CurrentUser
Repair-WinGetPackageManager -Force -Latest
```

It then refreshes `PATH` and verifies `winget --info`.

Failure to obtain a functioning official WinGet client stops the installer before Git, GitHub CLI or Codex setup is claimed successful.

## 8. Package installation model

Git and GitHub CLI use exact WinGet package identifiers and the official `winget` source:

```powershell
winget install --id Git.Git -e --source winget --accept-source-agreements --accept-package-agreements
winget install --id GitHub.cli -e --source winget --accept-source-agreements --accept-package-agreements
```

Installed commands must be verified after PATH refresh. WinGet exit status alone is not sufficient evidence.

## 9. GitHub authentication model

When active GitHub CLI authentication is absent, the installer invokes:

```powershell
gh auth login --hostname github.com --git-protocol https --web
gh auth setup-git --hostname github.com
```

The installer then verifies:

- an active GitHub account;
- repository access;
- `permissions.push == true` for `ClaytonKinnane/ASU-VCH`;
- default branch `main`;
- local `origin` identity.

The installer must not accept a token parameter, invoke `gh auth token`, print credential-store contents or log authorization headers.

## 10. Codex installation model

The installer downloads OpenAI's official Windows installer script from:

```text
https://chatgpt.com/codex/install.ps1
```

It saves the script to a temporary file, records only its public SHA-256, executes it in a child Windows PowerShell process and verifies the resulting `codex` command.

When authentication is absent, it invokes interactive ChatGPT sign-in through `codex login`, then verifies:

```powershell
codex login status
```

The installer must not request an API key or read/log Codex credential files.

## 11. Repository integrity model

Before local-machine changes, the installer verifies:

- every package file required by the manifest exists;
- every file SHA-256 matches the manifest;
- the manifest path is inside the approved package directory;
- the script is running from the expected repository;
- no package path escapes the repository root.

After local installation, the readiness test verifies the installed copies against the same manifest.

## 12. Local installation model

Files are staged under a temporary directory first.

After all integrity checks pass, the installer atomically replaces the managed local installation under:

```text
C:\Tools\ASU-VCH
```

Managed local files may include:

- installer copy;
- readiness test;
- branch cleanup tool;
- Codex launcher;
- manifest;
- user guide;
- installation-state JSON generated locally.

A failed repair must preserve the previous working installation where practical.

## 13. Logging and secret handling

Logs are stored outside the repository:

```text
%LOCALAPPDATA%\ASU-VCH\Logs
```

Allowed log data:

- command names and versions;
- public repository name;
- public commit SHA;
- branch, PR, run and job identifiers;
- package ids;
- public installer SHA-256;
- PASS/WARN/FAIL results.

Prohibited log data:

- OAuth tokens;
- API keys;
- device codes;
- cookies;
- credential-store contents;
- private keys;
- Authorization headers;
- Codex auth-file contents.

## 14. Branch cleanup safety

Deletion requires all of the following:

```text
exact main SHA
PR CLOSED / MERGED
exact PR head SHA
exact merge commit SHA
successful exact post-merge push run
successful required job and steps
canonical post-merge verification PASS evidence
remote branch SHA match
branch ahead of main = 0
unique unmerged commits = 0
case-sensitive approval token = exact branch name
PowerShell ShouldProcess confirmation
```

After deletion the tool independently confirms:

```text
branch absent
main unchanged
PR still merged
merge commit unchanged
```

## 15. Validation boundary

Repository/static validation can verify:

- PowerShell parsing and static safety rules;
- manifest schema and hashes;
- exact changed-path allowlist;
- no prohibited repository changes;
- branch-cleanup dry-run behavior with mocks or read-only evidence.

Actual Windows package installation, UAC, GitHub login, Codex login and local Codex launch require user-executed acceptance on the target machine. They must not be claimed PASS before that evidence exists.

Actual branch deletion remains separately authorized and is never implied by installer execution.

## 16. Terminal lifecycle rule

The increment follows the established terminal documentation model.

Mutable PR/review/Merge/Actions/branch-cleanup evidence remains canonical in GitHub and must not create a recursive Markdown closure increment.

## 17. Implementation gate

Implementation is prohibited until the owner approves:

- this Architecture;
- the Specification;
- the Formal Review;
- the exact changed-path allowlist;
- implementation on an exact reviewed branch head.
