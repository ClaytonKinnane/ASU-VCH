# GitHub Local Automation PowerShell 5.1 First-Run Correction — Implementation

Status: `IMPLEMENTED / PRE-PR NATIVE VALIDATION REQUIRED`

Date: `2026-08-05`

Repository: `ClaytonKinnane/ASU-VCH`

## 1. Anchors

```text
baseline main: 375f941be3f50f9f1f264da244f0dc31496e2a6f
branch: fix/github-local-automation-ps51-first-run
reviewed design head: af2e2cc26e3cb84ea744a204a9acef2269c3fd95
approval commit: 19e412874f77d895c11e47fabfecff136be3146d
tooling package commit: f7fcc677c8d70f1a7b1e81a8777254973eda347f
harness correction commit / implemented tooling head: 90cefd28d500da0861545caa782b6325ef1a2a62
merge-base: 375f941be3f50f9f1f264da244f0dc31496e2a6f
behind main: 0
```

## 2. Implemented paths

```text
tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
tools/github-automation/Test-ASUVCHGitHubAutomation.ps1
tools/github-automation/automation-manifest.json
tools/github-automation/README.md
tools/github-automation/CODEX-INSTRUCTIONS.md
```

Git blob identities at implemented tooling head:

```text
Install-ASUVCHGitHubAutomation.ps1: 129dbf71016c70432b3b7621073c374e5988b5b7
Invoke-ASUVCHBranchCleanup.ps1: df9aa408494cf1f5b715a5a306fc1e359aff5353
Test-ASUVCHGitHubAutomation.ps1: 92d2a0b597c62d95d39f6eeac576c4891b6c43b9
automation-manifest.json: 2d1c67f9b6972002371d34ccc57d524db53ec8f5
README.md: 6359665926102ce7a2fe4d363c4a693b78efb3b1
CODEX-INSTRUCTIONS.md: 9e77ef2acef8e29d44ae29feb35ca01c3b7f6865
```

## 3. Confirmed defects closed in source

### D1 — GitHub unauthenticated stderr

`gh auth status` is now executed by the captured native-process adapter. A non-zero exit code represents the unauthenticated state and reaches the visible browser-login state machine instead of becoming a Windows PowerShell terminating error.

### D2 — Codex HTTP 403 provider

The single `https://chatgpt.com/codex/install.ps1` dependency was removed. The implemented provider is:

```text
WinGet package: OpenJS.NodeJS.LTS
npm package: @openai/codex@latest
command: npm install --global @openai/codex@latest
```

### D3 — successful Codex status on stderr

`codex login status` stdout and stderr are captured as data. Exit code is authoritative. A successful status message on stderr no longer creates a false failure.

### D4 — authentication-mode mislabelling

Capability state now separates:

```text
CODEX_AUTH_READY
CODEX_AUTH_MODE=API_KEY|CHATGPT|UNKNOWN|NONE
CODEX_CHATGPT_AUTH_READY
CODEX_API_KEY_AUTH_READY
CODEX_API_BALANCE=NOT_TESTED
```

API-key authentication is not described as ChatGPT-plan authentication.

### D5 — null/scalar collection failures

Optional native, pipeline, JSON and parser outputs are normalized with `@(...)` before `.Count`, indexing or filtering. Cleanup Doctor now accepts an empty clean-worktree result and reports:

```text
DOCTOR_STATUS=PASS
WORKTREE=CLEAN
```

### D6 — missing native regression harness

Added:

```text
tools/github-automation/Test-ASUVCHGitHubAutomation.ps1
```

The harness uses temporary mock commands and tests first-run GitHub login, successful stderr, non-zero stderr, npm Codex installation, ChatGPT/API-key mode separation, secret non-echo, clean/dirty Doctor behaviour, parser invocation, manifest hashes, worktree preservation and PATH restoration.

## 4. Native execution model

Both production scripts use `System.Diagnostics.Process` for captured native commands.

Rules implemented:

```text
exit code = success/failure authority
stdout = data
stderr = data
.exe = direct ProcessStartInfo execution
.cmd/.bat = %ComSpec% /d /s /c execution
interactive login = visible non-redirected child process
API key = secure prompt + redirected stdin
```

Secrets are not accepted as script parameters and are not placed in environment variables.

## 5. Helper deployment ordering

The installer now performs:

```text
source manifest/hash verification
→ temporary staging
→ staged Cleanup Doctor
→ atomic replacement
→ installed hash verification
→ previous-install cleanup
```

If replacement fails, the previous accepted installation is restored where present.

## 6. Manifest integrity

```text
schemaVersion=1
minimumPowerShell=5.1
hashMode=utf8-lf-normalized
```

Normalized SHA-256 entries:

```text
Invoke-ASUVCHBranchCleanup.ps1=000e6d242498905dcd70b7839373bfa9170704c55dfa5fbc61b8295b5e166b55
CODEX-INSTRUCTIONS.md=7ff8162fa41123e633262390844dd001951de85db31965880c8d31f1dcaa8d6a
```

The manifest does not hash itself and contains no credentials.

## 7. Branch cleanup safety preserved

Verify/Delete still require exact main, PR head, merge commit, successful post-merge push run/job/steps, canonical PASS comment, remote branch identity, zero unique unmerged commits and exact case-sensitive approval token.

The only remote branch deletion invocation remains in Cleanup `Delete` mode:

```powershell
git push <remote> --delete <approved branch>
```

No local branch deletion was added.

## 8. Scope isolation

Implementation did not modify:

```text
runtime
PHP application code
database
migrations
GitHub Actions workflow
Action SHA
themes
deploy
existing application checkers
branch protection
required checks
Actions settings
repository settings
```

## 9. Validation boundary

Source/repository static checks are recorded separately. Native Windows PowerShell 5.1 parser/runtime execution is not claimed by this record and is a mandatory pre-PR owner-run gate on the exact final branch head.

## 10. Process state

```text
IMPLEMENTATION=COMPLETE
REPOSITORY_STATIC_VALIDATION=RECORDED SEPARATELY
NATIVE_WINDOWS_POWERSHELL_5_1_VALIDATION=PENDING
PULL_REQUEST=NOT CREATED
MERGE=NOT PERFORMED
BRANCH_DELETION=NOT PERFORMED
```
