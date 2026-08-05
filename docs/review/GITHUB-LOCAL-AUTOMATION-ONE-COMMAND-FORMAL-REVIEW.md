# GitHub Local Automation One-Command Bootstrap — Formal Review

Status: `PASS FOR OWNER APPROVAL`

Date: `2026-08-05`

Baseline:

```text
main: d9c7db67ee2df8204281d67a4c134528a499573b
branch: tools/github-local-automation-bootstrap
```

Reviewed documents:

- `docs/architecture/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-ARCHITECTURE.md`;
- `docs/specification/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-SPECIFICATION.md`.

## 1. Review scope

The review checked:

- compliance with the mandatory АСУ-ВЧ lifecycle;
- one-command usability after local synchronization;
- Windows PowerShell 5.1 compatibility intent;
- repository trust and synchronization gates;
- Git/WinGet/GitHub CLI/Codex installation boundaries;
- interactive authentication safety;
- local helper integrity model;
- branch deletion isolation;
- secret handling and logging;
- exact changed-path allowlist;
- testability and fail-closed behavior;
- terminal documentation model compliance.

## 2. Architecture review

### 2.1 One-command model

The design avoids mutable raw-download bootstrap and instead runs the installer already synchronized from merged `main`:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

This is simpler, auditable and avoids the previous commit-self-reference problem.

Result: `PASS`.

### 2.2 Repository identity and synchronization

The installer must verify canonical `origin`, clean worktree, current `main`, `git fetch --prune` and exact equality `HEAD == origin/main` before installation.

It is prohibited from checkout/reset/merge/rebase/force-update operations.

Result: `PASS`.

### 2.3 Installation boundaries

Git and GitHub CLI are installed through exact WinGet package IDs. Codex uses the official Windows installer endpoint, downloaded to a temporary file before execution. Authentication remains interactive.

Result: `PASS`.

### 2.4 Browser/local capability boundary

The documentation correctly states that browser ChatGPT does not gain local terminal access. Expanded operational capability is available only through the locally installed Codex/terminal environment.

Result: `PASS`.

### 2.5 Branch cleanup safety

Branch deletion remains in a separate tool and separate `Delete` mode, with exact SHA/run/comment evidence, zero unique commits, exact case-sensitive approval token, `ShouldProcess` and `-WhatIf`.

Result: `PASS`.

## 3. Specification review

### 3.1 Exact allowlist

The proposed allowlist contains exactly 12 paths:

```text
process Markdown: 7
tooling Markdown: 2
PowerShell: 2
JSON: 1
total: 12
```

No runtime, DB, migration, workflow, Action SHA, theme, deploy or existing checker path is included.

Result: `PASS`.

### 3.2 PowerShell 5.1 feasibility

The required installer behaviour can be implemented with Windows PowerShell 5.1-compatible cmdlets and external executables. PowerShell 7 is not required.

Result: `PASS`.

### 3.3 WinGet recovery

The design does not silently install an unofficial WinGet bundle. When WinGet is absent, it stops with official App Installer recovery instructions.

Result: `PASS`.

### 3.4 Authentication safety

The interface prohibits token/API-key/password parameters. GitHub and ChatGPT authentication remain interactive, and credential output must not be written to logs.

Result: `PASS`.

### 3.5 Integrity model

The manifest hashes only copied helper files, not itself and not the installer. Repository synchronization and exact `HEAD == origin/main` bind the local source; manifest SHA-256 values bind copied helpers.

No cryptographic self-reference exists.

Result: `PASS`.

### 3.6 Idempotency and repair

Install/Repair/Doctor modes permit initial setup, safe rerun and diagnostics. Atomic staging and preservation of the previous local helper installation are required where practical.

Result: `PASS`.

### 3.7 Validation split

Repository/static validation can be completed before PR. Actual Windows installation, elevation, GitHub login, Codex login and idempotency must be performed by the user and reported as target-machine acceptance; they must not be falsely claimed by remote validation.

Result: `PASS`.

## 4. Required implementation controls

```text
RUN_FROM_SYNCED_REPOSITORY=REQUIRED
LOCAL_HEAD_EQUALS_ORIGIN_MAIN=REQUIRED
MUTABLE_RAW_BOOTSTRAP=NOT_USED
POWERSHELL_5_1=REQUIRED
WINGET_EXACT_PACKAGE_IDS=REQUIRED
GH_AUTH=INTERACTIVE_BROWSER_FLOW
CODEX_AUTH=INTERACTIVE_CHATGPT_FLOW
SECRETS_IN_ARGUMENTS=PROHIBITED
SECRETS_IN_LOGS=PROHIBITED
CHECKOUT_RESET_MERGE_REBASE_FORCE_UPDATE=PROHIBITED
SETTINGS_MUTATION=PROHIBITED
BRANCH_DELETE_VERIFY_FIRST=REQUIRED
BRANCH_DELETE_SEPARATE_APPROVAL=REQUIRED
UNIQUE_UNMERGED_COMMITS_ALLOWED=0
POST_MERGE_MARKDOWN_CLOSURE_REQUIRED=NO
```

## 5. Findings

```text
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS_OPEN=0
OPEN_FINDINGS=0
```

## 6. Verdict

```text
ARCHITECTURE_REVIEW=PASS
SPECIFICATION_REVIEW=PASS
ONE_COMMAND_MODEL_REVIEW=PASS
POWERSHELL_5_1_REVIEW=PASS
REPOSITORY_SYNC_GATE_REVIEW=PASS
GITHUB_AUTH_REVIEW=PASS
CODEX_INSTALL_REVIEW=PASS
MANIFEST_INTEGRITY_REVIEW=PASS
BRANCH_DELETION_SAFETY_REVIEW=PASS
ALLOWLIST_REVIEW=PASS
PROCESS_REVIEW=PASS

FORMAL_REVIEW_STATUS=PASS FOR OWNER APPROVAL
```

## 7. Process gate

The branch is stopped before Implementation.

No installer, cleanup tool, manifest, tooling guide, Codex instruction file, Approval record, Implementation record or Validation record has been created.

Implementation requires separate owner approval of:

- Architecture;
- Specification;
- this Formal Review;
- exact 12-path allowlist;
- implementation on the exact reviewed branch head.
