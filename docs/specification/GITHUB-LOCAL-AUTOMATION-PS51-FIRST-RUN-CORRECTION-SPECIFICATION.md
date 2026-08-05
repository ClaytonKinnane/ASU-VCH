# GitHub Local Automation PowerShell 5.1 First-Run Correction — Specification

Status: `PROPOSED / PRE-IMPLEMENTATION`

Date: `2026-08-05`

Baseline:

```text
main: 375f941be3f50f9f1f264da244f0dc31496e2a6f
branch: fix/github-local-automation-ps51-first-run
```

## 1. Scope

Correct the merged GitHub local automation package so that its first-run, authentication, Codex installation, helper deployment and Cleanup Doctor flows work reliably in native Windows PowerShell 5.1.

Production command after corrective Merge remains:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

The correction must preserve fail-closed repository and branch-deletion safety while eliminating PowerShell 5.1 native stderr and scalar-output failures.

## 2. Exact changed-path allowlist

### Process records

1. `docs/architecture/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-ARCHITECTURE.md`
2. `docs/specification/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-SPECIFICATION.md`
3. `docs/review/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-FORMAL-REVIEW.md`
4. `docs/decisions/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-APPROVAL.md`
5. `docs/implementation/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-IMPLEMENTATION.md`
6. `docs/testing/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-VALIDATION.md`
7. `docs/review/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-PR-FINAL-REVIEW.md`

### Corrected tooling package

8. `tools/github-automation/Install-ASUVCHGitHubAutomation.ps1`
9. `tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1`
10. `tools/github-automation/Test-ASUVCHGitHubAutomation.ps1`
11. `tools/github-automation/automation-manifest.json`
12. `tools/github-automation/README.md`
13. `tools/github-automation/CODEX-INSTRUCTIONS.md`

```text
TOTAL_PATHS=13
MARKDOWN_PATHS=9
POWERSHELL_PATHS=3
JSON_PATHS=1
```

No path outside this exact allowlist may be changed.

The Final PR Review path must remain absent until a separate authorization to create the PR and perform Final PR Review.

## 3. Confirmed defect requirements

Implementation must close every confirmed defect:

```text
D1 GH_UNAUTHENTICATED_STDERR_TERMINATES_PS51
D2 CODEX_WINDOWS_INSTALLER_HTTP_403_NO_FALLBACK
D3 CODEX_STATUS_SUCCESS_STDERR_TERMINATES_PS51
D4 API_KEY_AUTH_MISLABELLED_CHATGPT
D5 CLEAN_STATUS_NULL_COUNT_STRICTMODE_FAILURE
D6 NO_NATIVE_PS51_FIRST_RUN_REGRESSION_HARNESS
```

No defect may be marked closed solely by source inspection. D1, D3, D5 and D6 require native Windows PowerShell 5.1 execution evidence.

## 4. Installer interface

Path:

```text
tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
```

Required compatibility:

```text
Windows PowerShell 5.1
Windows 10/11 64-bit
x64 and ARM64 where package providers support the architecture
```

Existing parameters/defaults remain unless explicitly changed below:

```powershell
-Mode Install|Repair|Doctor       # default Install
-RepositoryPath 'C:\Project\ASU-VCH'
-InstallPath 'C:\Tools\ASU-VCH'
-RepositoryFullName 'ClaytonKinnane/ASU-VCH'
-RemoteName 'origin'
-MainBranch 'main'
-SkipGitHubLogin
-NoUpgrade
-AllowDirtyWorktree              # Doctor only
-NonInteractivePackages
```

Corrected Codex interface:

```powershell
-CodexAuthMode Auto|ChatGPT|ApiKey|Skip    # default Auto
-SkipCodex                                 # retained for compatibility; maps to Skip
```

Rules:

- `-SkipCodex` and a non-`Skip` explicit `-CodexAuthMode` combination must fail as contradictory.
- The installer must not accept a GitHub token, OpenAI API key, password, cookie, OAuth code or private key as a command-line parameter.
- API-key mode may prompt securely only after the user selects or explicitly requests that mode.
- `Doctor` must never initiate package installation or authentication.

## 5. Native command execution contract

### 5.1 Captured result type

Every captured native execution returns one object with at least:

```text
FilePath: string
DisplayName: string
ExitCode: int
StdOutLines: string[]
StdErrLines: string[]
CombinedLines: string[]
```

All line collections must be arrays even when empty or containing one line.

### 5.2 Native executable resolution

The implementation must resolve the actual command path before execution.

Supported executable forms:

```text
.exe
.com
.cmd
.bat
```

For `.exe`/`.com`, the process may execute the resolved file directly.

For `.cmd`/`.bat`, captured and interactive execution must use `%ComSpec%` with safe Windows quoting, for example an equivalent of:

```text
cmd.exe /d /s /c call "<resolved-command>" <arguments>
```

The implementation must handle paths containing spaces, including:

```text
C:\Program Files\GitHub CLI\gh.exe
C:\Program Files\nodejs\npm.cmd
C:\Users\Admin\AppData\Roaming\npm\codex.cmd
```

Arguments containing NUL or newline characters must be rejected.

### 5.3 Captured process requirements

Captured execution must:

- use a Windows PowerShell 5.1-compatible process API;
- set `UseShellExecute = false`;
- redirect stdout and stderr separately;
- optionally redirect stdin;
- consume stdout and stderr without deadlock;
- wait for completion;
- expose the actual exit code;
- avoid PowerShell native stderr conversion;
- not write raw output automatically;
- throw only when the caller explicitly requires an allowed-exit-code assertion.

### 5.4 Interactive process requirements

Interactive execution must:

- inherit the visible console;
- allow browser/device-login prompts;
- wait for completion;
- return the native exit code;
- not use PowerShell stderr as an exception signal;
- never redirect or record secret prompts.

Required interactive commands include only approved flows such as:

```text
gh auth login --hostname github.com --git-protocol https --web
codex login
```

### 5.5 Standard-input secret execution

API-key login requires a dedicated captured process function capable of writing secret data to stdin.

It must:

- not add the key to the argument list;
- not add the key to environment variables;
- not echo stdin;
- not include stdin in errors or logs;
- close stdin after writing;
- zero unmanaged buffers where practical;
- clear temporary variables in `finally`.

## 6. Collection-shape contract

No direct `.Count` access is permitted on a value that can originate from a pipeline, external process, JSON property or optional result unless it is first normalized.

Required examples:

```powershell
$lines = @(Invoke-Native...)
$count = @($lines).Count

$jobs = @($jobsResponse.jobs | Where-Object { ... })
$comments = @($commentsResponse)
$errors = @($parseErrors)
```

Required safe first-line behaviour:

```text
input null -> result null
input empty array -> result null
input scalar -> trimmed scalar string
input array -> trimmed first item
```

Unsafe patterns to remove or prove impossible include:

```powershell
$value.Count
$value[0]
```

when `$value` can be `$null` or a scalar under PowerShell pipeline unrolling.

## 7. Ordered installer stages

1. Validate Windows and 64-bit OS.
2. Validate Windows PowerShell 5.1+.
3. Enable TLS 1.2 for the current process.
4. Initialize a timestamped redacted log under `%LOCALAPPDATA%\ASU-VCH\Logs`.
5. Resolve repository/package paths.
6. Verify repository directory and `.git` metadata.
7. resolve/install/verify Git.
8. verify canonical `origin` identity.
9. normalize and verify clean worktree output.
10. run `git fetch --prune origin` for Install/Repair.
11. verify current branch `main` for Install/Repair.
12. verify exact `HEAD == origin/main`.
13. resolve/install/verify GitHub CLI.
14. classify GitHub authentication by native exit code.
15. when unauthenticated and permitted, run browser login interactively.
16. re-verify GitHub authentication.
17. run `gh auth setup-git --hostname github.com`.
18. verify repository identity, default branch and push/write permission.
19. resolve/install/verify Node.js LTS and npm when Codex is enabled.
20. resolve/install/repair Codex through official npm package.
21. verify `codex --version`.
22. classify existing Codex authentication and mode without logging raw status output.
23. perform selected interactive authentication when required.
24. re-verify Codex authentication and mode.
25. validate manifest schema and normalized hashes.
26. parse every PowerShell helper source.
27. stage helper files.
28. validate staged hashes.
29. run Cleanup Doctor against the staged cleanup script.
30. atomically replace the installed helper directory.
31. validate installed hashes.
32. print corrected capability matrix.
33. print exact next commands and log path.
34. return exit `0`, `1` or `2` according to the specified exit model.

## 8. Git and repository requirements

The existing repository gates remain mandatory:

```text
repository worktree = valid
origin = ClaytonKinnane/ASU-VCH
worktree = clean unless Doctor explicitly allows dirty
origin/main = available
local HEAD = origin/main for Install/Repair
current branch = main for Install/Repair
GitHub default branch = main
GitHub authenticated access = PASS
GitHub push/write permission = PASS
```

The installer must not execute:

```text
git checkout
git switch
git reset
git merge
git rebase
git cherry-pick
git clean
git push --force
git update-ref
branch deletion
```

Read-only/fetch operations remain allowed.

## 9. GitHub authentication requirements

### 9.1 Probe

Run captured:

```text
gh auth status --hostname github.com --active
```

Classification:

```text
exit 0 -> authenticated
non-zero -> unauthenticated/pending, not a technical exception by itself
process launch failure -> technical error
```

Raw probe output must not be logged or repeated in the capability matrix.

### 9.2 First-run login

When unauthenticated, Install/Repair and login not skipped:

```text
gh auth login --hostname github.com --git-protocol https --web
```

must run interactively.

After login:

- status probe must return exit `0`;
- `gh auth setup-git --hostname github.com` must return exit `0`;
- `gh api repos/ClaytonKinnane/ASU-VCH` must identify the expected repository;
- `default_branch` must equal `main`;
- `permissions.push` must be true.

### 9.3 Skip state

If login is skipped and authentication is absent, the installer must not deploy helpers or claim complete readiness. It returns exit `2` with an exact command for resuming.

## 10. Node.js/npm requirements

When Codex is enabled:

1. detect `node` and `npm`;
2. when absent, install exact WinGet package:

```text
OpenJS.NodeJS.LTS
```

3. refresh process PATH;
4. verify `node --version` and `npm --version`;
5. record safe versions in the capability matrix/log;
6. do not automatically update npm based solely on an npm notice;
7. fail closed if npm remains unavailable.

Capability keys:

```text
NODEJS_READY=YES|NO
NPM_READY=YES|NO
```

## 11. Codex installation requirements

Official package command:

```text
npm install --global @openai/codex@latest
```

Rules:

- use the resolved `npm.cmd`/`npm` path;
- install only the official scoped package `@openai/codex`;
- verify actual command availability after PATH refresh;
- verify `codex --version` exit `0`;
- record the resolved executable path and version safely;
- Repair mode may reinstall/upgrade;
- `-NoUpgrade` prevents unnecessary reinstall when Codex already works;
- do not treat the PowerShell installer endpoint as the only installation provider;
- do not silently download or execute an unofficial installer;
- do not pin a stale Codex version in durable documentation unless a separate release policy is approved.

The official Windows PowerShell installer URL may remain documented as an official alternative, but a failure or `403` from that endpoint must not prevent the approved npm provider from being used.

## 12. Codex authentication requirements

### 12.1 Probe

Run captured:

```text
codex login status
```

Classification:

```text
exit 0 -> authenticated
non-zero -> unauthenticated/pending
process launch failure -> technical error
```

Stdout versus stderr location must not affect classification.

Raw output must not be logged or printed by the installer.

### 12.2 Mode classification

Safe internal classification:

```text
API_KEY
CHATGPT
UNKNOWN
NONE
```

The implementation may inspect captured output only for known mode phrases. It must discard the raw text after classification.

Required capability output:

```text
CODEX_AUTH_READY=YES|NO
CODEX_AUTH_MODE=API_KEY|CHATGPT|UNKNOWN|NONE
CODEX_CHATGPT_AUTH_READY=YES|NO
CODEX_API_KEY_AUTH_READY=YES|NO
```

### 12.3 Auto mode

When already authenticated, preserve the detected mode.

When unauthenticated and interactive:

- present a concise choice between ChatGPT, API key and defer;
- recommend ChatGPT because it can use an eligible ChatGPT plan;
- state that API usage is billed separately and requires API account readiness;
- never imply that API-key login uses the ChatGPT subscription.

When non-interactive, Auto must not prompt indefinitely; it returns exit `2` with exact commands/options.

### 12.4 ChatGPT mode

Run interactively:

```text
codex login
```

After completion, re-run status and require mode `CHATGPT` or `UNKNOWN` with exit `0`.

If browser/server policy requires phone verification or other account action, report:

```text
CODEX_AUTH_READY=NO
CODEX_AUTH_MODE=NONE
USER_ACTION_REQUIRED=Complete OpenAI account verification or choose ApiKey mode
```

Do not bypass account policy.

### 12.5 API-key mode

Secure prompt only:

```powershell
Read-Host -AsSecureString
```

Native command:

```text
codex login --with-api-key
```

The key must be written only to redirected stdin.

After login:

- status exit must be `0`;
- classified mode must be `API_KEY` or, if future CLI wording is unknown, `UNKNOWN` with an explicit warning;
- no full or masked key text may appear in logs or capability output;
- API balance/quota must remain `NOT_TESTED` unless a separately authorized remote request test is performed.

### 12.6 Skip mode

Codex installation may complete, but authentication remains pending. Installer returns exit `2` and does not claim `LOCAL_CODEX_AGENT_READY=YES`.

## 13. Manifest requirements

Path:

```text
tools/github-automation/automation-manifest.json
```

Existing schema remains version `1` unless implementation proves a schema change is necessary and documents it before coding.

Manifest continues to include installed helper files only:

```text
tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
tools/github-automation/CODEX-INSTRUCTIONS.md
```

The new regression harness is repository validation tooling and is not copied to `C:\Tools\ASU-VCH` unless separately approved.

Required rules:

- lowercase normalized SHA-256;
- UTF-8 LF-normalized hash mode;
- no manifest self-hash;
- no installer self-hash;
- no commit self-reference;
- no credentials;
- no target-machine-specific paths except the documented default install path;
- update cleanup and instructions hashes after approved changes;
- verify source, staged and installed hashes.

## 14. Helper deployment requirements

Installed files remain:

```text
C:\Tools\ASU-VCH\Invoke-ASUVCHBranchCleanup.ps1
C:\Tools\ASU-VCH\CODEX-INSTRUCTIONS.md
C:\Tools\ASU-VCH\automation-manifest.json
```

Required sequence:

1. validate source hashes;
2. parse helper PowerShell;
3. create staging directory under the install-path parent;
4. copy helpers and manifest;
5. validate staged hashes;
6. run staged Cleanup Doctor;
7. preserve existing installed directory as backup;
8. move staged directory into place;
9. validate installed hashes;
10. delete backup only after full success;
11. restore backup on failure.

The staged Doctor invocation must use the corrected native-command handling and must not modify the repository.

## 15. Cleanup tool requirements

Path:

```text
tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
```

Modes remain:

```text
Doctor
Verify
Delete
```

### 15.1 Doctor

Doctor must safely handle:

```text
clean worktree -> zero status lines
one changed path -> one status line
multiple changed paths -> multiple status lines
```

Default clean-worktree result:

```text
DOCTOR_STATUS=PASS
WORKTREE=CLEAN
```

Doctor must verify:

- Windows and PowerShell;
- repository path;
- git and gh availability;
- GitHub authentication by exit code, without raw token output;
- valid Git worktree;
- canonical remote;
- clean worktree unless explicitly allowed;
- expected repository metadata;
- default branch;
- push/write permission.

### 15.2 Verify/Delete safety

All existing exact evidence gates remain mandatory:

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
canonical PR comment contains post-merge PASS markers
remote branch SHA == ExpectedPrHeadSha when branch exists
branch unique unmerged commits == 0
ApprovalToken == BranchName case-sensitive
```

Protected identities remain:

```text
main
master
repository default branch
invalid refs
refs outside refs/heads/*
```

The only destructive command remains:

```text
git push origin --delete <BranchName>
```

No local branch deletion is permitted.

`SupportsShouldProcess`, `-WhatIf` and high-impact confirmation remain required.

## 16. Regression harness requirements

Path:

```text
tools/github-automation/Test-ASUVCHGitHubAutomation.ps1
```

Required interface:

```powershell
-RepositoryPath 'C:\Project\ASU-VCH'
-KeepTemporaryEvidence        # optional, default false
```

No network or destructive side effect is permitted by default.

The harness must use isolated temporary directories and fake native commands to cover at least:

```text
T01 CAPTURE_STDOUT_EXIT_0
T02 CAPTURE_STDERR_EXIT_0
T03 CAPTURE_STDERR_EXIT_1
T04 EMPTY_OUTPUT_ARRAY_COUNT_0
T05 SINGLE_OUTPUT_ARRAY_COUNT_1
T06 MULTI_OUTPUT_ARRAY_COUNT_N
T07 GH_UNAUTHENTICATED_REACHES_LOGIN_DECISION
T08 GH_AUTHENTICATED_REACHES_ACCESS_CHECK
T09 CODEX_API_KEY_STATUS_STDERR_EXIT_0
T10 CODEX_CHATGPT_STATUS_EXIT_0
T11 CODEX_UNAUTHENTICATED_NONZERO
T12 SECRET_STDIN_NOT_IN_ARGUMENTS
T13 SECRET_STDIN_NOT_IN_LOGS
T14 CLEANUP_DOCTOR_CLEAN_WORKTREE_PASS
T15 CLEANUP_DOCTOR_DIRTY_WORKTREE_FAIL_CLOSED
T16 MANIFEST_SOURCE_HASH_PASS
T17 POWERSHELL_5_1_PARSE_PASS
T18 REPOSITORY_WORKTREE_UNCHANGED
T19 USER_PATH_UNCHANGED
T20 NO_BRANCH_DELETE_EXECUTED
```

The harness must print:

```text
PASS_COUNT=<n>
FAIL_COUNT=0
NATIVE_PS51_REGRESSION_STATUS=PASS
REPOSITORY_WORKTREE_STATUS=PASS
USER_PATH_RESTORATION_STATUS=PASS
```

Any failed scenario returns non-zero.

Temporary fake commands must not receive real GitHub/OpenAI credentials.

## 17. Capability matrix

Required output keys:

```text
POWERSHELL_5_1_READY=YES|NO
WINGET_READY=YES|NO
GIT_READY=YES|NO
GITHUB_CLI_READY=YES|NO
GITHUB_AUTH_READY=YES|NO
GITHUB_REPOSITORY_WRITE_ACCESS=YES|NO
NODEJS_READY=YES|NO
NPM_READY=YES|NO
CODEX_READY=YES|NO
CODEX_AUTH_READY=YES|NO
CODEX_AUTH_MODE=CHATGPT|API_KEY|UNKNOWN|NONE
CODEX_CHATGPT_AUTH_READY=YES|NO
CODEX_API_KEY_AUTH_READY=YES|NO
ASU_VCH_REPOSITORY_READY=YES|NO
ASU_VCH_LOCAL_HELPERS_READY=YES|NO
LOCAL_CODEX_AGENT_READY=YES|NO
CODEX_REMOTE_REQUEST_READY=NOT_TESTED|PASS|FAIL
```

`CODEX_REMOTE_REQUEST_READY` defaults to `NOT_TESTED`.

No key may expose credential text.

## 18. Exit codes

```text
0 = selected local bootstrap mode is fully ready
1 = technical, integrity or safety failure
2 = user action required, authentication deferred or account policy incomplete
```

A non-zero native status probe does not automatically mean installer exit `1`; it must be classified according to the state machine.

## 19. Logging requirements

Logs remain under:

```text
%LOCALAPPDATA%\ASU-VCH\Logs
```

The redaction layer must remove or suppress:

- Authorization headers;
- GitHub tokens;
- OpenAI API keys;
- device codes;
- cookies;
- passwords;
- raw authentication status output;
- secret stdin;
- credential file content.

Logs may contain:

- timestamps;
- safe stage names;
- public tool versions;
- public SHAs;
- public repository/PR/run/job identifiers;
- manifest hashes;
- authentication mode label;
- PASS/FAIL/PENDING state;
- non-secret errors.

Errors originating from raw auth output must be replaced with a safe stage-specific message.

## 20. Documentation requirements

`tools/github-automation/README.md` must document:

- the one-command production entry point;
- GitHub first-run browser flow;
- Node.js/npm dependency for the approved Codex provider;
- ChatGPT versus API-key modes;
- separate API billing/quota boundary;
- exit codes;
- logs;
- Doctor/Repair behaviour;
- validation harness command;
- branch cleanup examples and separate deletion approval.

`tools/github-automation/CODEX-INSTRUCTIONS.md` must preserve:

- Architecture → Specification → Review → Approval → Implementation → Testing → Commit → Push → PR → Final PR Review → Merge;
- exact-SHA and exact-allowlist gates;
- no secrets in output;
- separate Merge approval;
- separate branch-deletion approval;
- no repository settings mutation;
- no false claim that API-key authentication is ChatGPT-plan authentication.

## 21. Repository/static validation

Required checks before requesting native acceptance:

1. exact 13-path allowlist;
2. only three design documents changed before approval;
3. PowerShell parser checks for all three scripts after implementation;
4. forbidden PowerShell 7-only syntax scan;
5. unsafe native invocation scan;
6. unsafe nullable/scalar `.Count`/index scan;
7. manifest JSON parse/schema/hash verification;
8. secret-pattern and raw-auth-output logging scan;
9. installer forbidden Git mutation scan;
10. cleanup destructive command count exactly one and Delete-only;
11. no local branch deletion command;
12. no repository settings/protection mutation;
13. no runtime/DB/migration/workflow/Action SHA/theme/deploy/checker changes;
14. clean repository state after validation.

## 22. Native Windows PowerShell 5.1 pre-PR validation

A PR must not be authorized until the owner runs on the exact implementation head:

```powershell
Set-Location 'C:\Project\ASU-VCH'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Test-ASUVCHGitHubAutomation.ps1'
```

Required evidence:

```text
WINDOWS_POWERSHELL_VERSION=5.1.x
EXACT_BRANCH=fix/github-local-automation-ps51-first-run
EXACT_HEAD=<approved implementation head>
PASS_COUNT>=20
FAIL_COUNT=0
NATIVE_PS51_REGRESSION_STATUS=PASS
REPOSITORY_WORKTREE_STATUS=PASS
USER_PATH_RESTORATION_STATUS=PASS
```

This is a mandatory gate. Remote/static validation alone is insufficient.

## 23. Post-merge target-machine acceptance

After corrective Merge and synchronization of exact `main`, the owner runs the unchanged one-command installer.

Required acceptance:

```text
Git ready
GitHub CLI ready
GitHub auth ready
repository write access ready
Node.js ready
npm ready
Codex ready
Codex auth mode correctly labelled
manifest validation PASS
Cleanup Doctor PASS
helper installation PASS
installer exit 0
repository remains clean
```

Then run the same production command a second time.

Second run must prove:

```text
IDEMPOTENCY_STATUS=PASS
INSTALLER_EXIT_CODE=0
HELPER_HASHES_UNCHANGED=YES
REPOSITORY_WORKTREE_STATUS=PASS
```

A real model/API request is not required and is not claimed unless separately authorized.

Actual branch deletion is not part of acceptance.

## 24. Process gate

Implementation is prohibited until owner approval of:

- Architecture;
- this Specification;
- Formal Review;
- exact 13-path allowlist;
- exact reviewed branch head.

After implementation and repository/static validation, the process must stop for native Windows PowerShell 5.1 pre-PR validation and separate PR authorization.

Merge, branch deletion and repository settings changes remain unauthorized.