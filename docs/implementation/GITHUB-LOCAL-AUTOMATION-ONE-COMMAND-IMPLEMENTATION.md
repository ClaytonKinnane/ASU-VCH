# GitHub Local Automation One-Command Bootstrap — Implementation

Status: `IMPLEMENTED / REPOSITORY PACKAGE COMPLETE`

Date: `2026-08-05`

## 1. Approved anchors

```text
main: d9c7db67ee2df8204281d67a4c134528a499573b
branch: tools/github-local-automation-bootstrap
approved implementation-start head: 6a1380ad6d40dc8924b37740e7f16db9183708a8
merge-base: d9c7db67ee2df8204281d67a4c134528a499573b
behind main at approval: 0
```

Implementation was performed only after owner approval of Architecture, Specification, Formal Review and the exact 12-path allowlist.

## 2. Implemented package

### 2.1 Approval record

```text
path: docs/decisions/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-APPROVAL.md
commit: 57fcb99e6997d1c4e7aa1b1e4274670e0681d457
```

### 2.2 Codex project instructions

```text
path: tools/github-automation/CODEX-INSTRUCTIONS.md
commit: 920df80ae54c8ba69fd2ae236a2d018cbb582565
Git blob: 49ea1d7eb317a59e0cffc43a7a005ca3d9503341
normalized SHA-256: 808177da4c52d3b7bc99d4f97aa4aae886a64b2eb369d23b1618e35b3ddd176c
```

The instructions preserve the mandatory ASU-VCH lifecycle, exact-SHA gates, separate PR/Merge/branch-deletion approvals, settings-mutation prohibition, secret-handling rules and truthful test reporting.

The file is installed to `C:\Tools\ASU-VCH`. It is not represented as automatically loaded by browser ChatGPT or Codex; the user guide explains how to reference it in a local Codex session.

### 2.3 Fail-closed branch cleanup

```text
path: tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
commit: 5b28112f4e5dcb71924ba6255267a834045df525
Git blob: 9fe555ffb378c568c6496d00f23b31f8bf84609f
normalized SHA-256: 794a161ab528e8b33144186011405ab9fd4781b160ee05879b7afd621bd8ee89
```

Implemented modes:

```text
Doctor
Verify
Delete
```

Delete is guarded by exact main/PR-head/merge SHAs, merged PR state, exact successful post-merge push run, successful job and required steps, canonical post-merge PASS comment, exact branch SHA, zero unique unmerged commits, exact case-sensitive approval token and PowerShell `ShouldProcess`.

The only destructive repository command in the tool is:

```powershell
git push origin --delete <approved branch>
```

The tool does not delete local branches and prohibits `main`, `master` and the repository default branch.

### 2.4 User guide

```text
path: tools/github-automation/README.md
commit: 89e5b6270a5a5c2d9f455f36ba8a0b1bf7734c7c
Git blob: 431c90478115f7c054e06d696c54cd58879cd7a3
```

The guide documents one-command installation, interactive security prompts, Doctor/Repair modes, local Codex launch, cleanup Verify/Delete templates, logs, security boundaries and local helper removal.

### 2.5 One-command installer

```text
path: tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
commit: d5972e21d82a5072b7270602afa97f1f3fcd4c3a
Git blob: e052b3326e85fbc21bc96fb952a70a42f8926961
```

Canonical post-merge invocation:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

Implemented behavior:

1. validate Windows, 64-bit OS and PowerShell 5.1+;
2. enable TLS 1.2 and initialize a redacted external log;
3. validate repository path, package location, Git worktree, canonical `origin`, clean tree and synchronization;
4. require `main` and `HEAD == origin/main` in Install/Repair;
5. detect or install exact WinGet packages `Git.Git` and `GitHub.cli`;
6. verify resulting commands after PATH refresh;
7. perform visible GitHub browser authentication when required;
8. configure `gh auth setup-git` and verify repository/default-branch/write permission;
9. download the official Codex Windows installer to a temporary file;
10. record only its public SHA-256 and execute it without secret arguments;
11. verify `codex --version` and interactive ChatGPT login state;
12. validate manifest schema and normalized SHA-256 values;
13. parse manifest-listed PowerShell files using the native PowerShell parser on the target machine;
14. stage and atomically install helpers under `C:\Tools\ASU-VCH` with rollback where practical;
15. run cleanup Doctor when GitHub authentication is available;
16. output a capability matrix and exact next commands.

Exit codes:

```text
0 = ready
1 = fail-closed failure
2 = user action still required
```

### 2.6 Integrity manifest

```text
path: tools/github-automation/automation-manifest.json
commit: a56505b1775531c8b3af7ea4eae3205d5258347a
Git blob: 236c71fb5addbf286596c163fa0302e8165c73a7
hash mode: utf8-lf-normalized
```

The manifest hashes the cleanup tool and Codex instructions. It does not hash itself and does not contain a self-referential commit SHA, credentials or machine-specific secrets.

Normalized hashing prevents false mismatches when Windows Git checks out text using CRLF.

## 3. Security and repository isolation

```text
TOKEN_OR_API_KEY_PARAMETERS=0
GH_AUTH_TOKEN_COMMAND=0
GH_WITH_TOKEN_USAGE=0
INSTALLER_BRANCH_DELETE_COMMANDS=0
INSTALLER_CHECKOUT_RESET_REBASE_COMMANDS=0
INSTALLER_PR_MERGE_COMMANDS=0
INSTALLER_REPOSITORY_SETTINGS_MUTATIONS=0
CLEANUP_REMOTE_BRANCH_DELETE_COMMANDS=1
CLEANUP_LOCAL_BRANCH_DELETE_COMMANDS=0
```

No runtime, database, migration, workflow, Action SHA, theme, deploy, existing checker, branch protection, required-check or repository-setting path was changed.

## 4. Capability boundary

The package expands the capabilities of a local Codex/terminal session after the owner installs and authenticates it.

It does not grant this browser ChatGPT conversation direct local-terminal access and does not change the browser GitHub connector action set.

## 5. Acceptance boundary

The following were not executed in the implementation environment and are not claimed PASS:

```text
NATIVE_WINDOWS_POWERSHELL_5_1_EXECUTION=NOT RUN
WINGET_INSTALL_OR_UPGRADE=NOT RUN
UAC_FLOW=NOT RUN
GITHUB_BROWSER_LOGIN=NOT RUN
CODEX_INSTALLATION=NOT RUN
CODEX_CHATGPT_LOGIN=NOT RUN
LOCAL_ATOMIC_INSTALLATION=NOT RUN
SECOND_RUN_IDEMPOTENCY=NOT RUN
ACTUAL_BRANCH_DELETION=NOT RUN
```

These remain target-machine acceptance checks after Merge.

## 6. Process status

```text
IMPLEMENTATION_STATUS=COMPLETE
NEXT_GATE=REPOSITORY_STATIC_VALIDATION
PR_CREATION_AUTHORIZED=NO
MERGE_AUTHORIZED=NO
BRANCH_DELETION_AUTHORIZED=NO
```
