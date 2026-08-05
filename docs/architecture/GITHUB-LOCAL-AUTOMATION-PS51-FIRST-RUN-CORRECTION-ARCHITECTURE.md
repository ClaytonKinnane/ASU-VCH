# GitHub Local Automation PowerShell 5.1 First-Run Correction — Architecture

Status: `PROPOSED / PRE-IMPLEMENTATION`

Date: `2026-08-05`

Repository: `ClaytonKinnane/ASU-VCH`

Baseline:

```text
main: 375f941be3f50f9f1f264da244f0dc31496e2a6f
branch: fix/github-local-automation-ps51-first-run
```

## 1. Context

The merged one-command bootstrap package passed repository/static validation but failed target-machine acceptance in native Windows PowerShell 5.1.

Observed target-machine evidence:

```text
OS shell: Windows PowerShell 5.1.28000.2525
Git: 2.55.0.windows.3
GitHub CLI: 2.97.0
Node.js: 24.18.0
npm: 11.16.0
Codex CLI: 0.146.0
main: 375f941be3f50f9f1f264da244f0dc31496e2a6f
```

Confirmed defects:

1. `gh auth status` writes unauthenticated diagnostics to the native stderr stream. Under Windows PowerShell 5.1 with `$ErrorActionPreference = 'Stop'`, the diagnostic becomes a terminating error before the installer can inspect the exit code and start browser login.
2. The official Windows Codex PowerShell installer endpoint returned HTTP `403` on the target machine, and no alternate official installation provider was available in the script.
3. `codex login status` can write a successful status message to stderr while returning exit code `0`. Windows PowerShell 5.1 converted that successful stderr output into a terminating error.
4. The installer reported `Codex ChatGPT login ready` for API-key authentication because authentication readiness and authentication mode were not modelled separately.
5. `Invoke-ASUVCHBranchCleanup.ps1` used `.Count` directly on output that can collapse to `$null` or a scalar in Windows PowerShell 5.1. A clean `git status --porcelain` therefore failed under `Set-StrictMode -Version 2.0`.
6. The repository validation did not contain a native Windows PowerShell 5.1 regression harness capable of simulating first-run stderr/exit-code behaviour and empty native output.

The corrective increment must fix these defects without weakening repository, authentication, integrity or branch-deletion gates.

## 2. Goal

After a user synchronizes merged `main`, the original one-command entry point must reliably complete or return a precise actionable state in native Windows PowerShell 5.1:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

The corrected package must:

- preserve exact repository synchronization checks;
- install or verify Git, GitHub CLI, Node.js/npm and Codex CLI;
- perform first-run GitHub browser authentication;
- distinguish ChatGPT authentication from API-key authentication;
- never treat native stderr alone as command failure;
- never treat empty or scalar native output as a collection error;
- deploy helpers atomically only after all required checks pass;
- run Cleanup Doctor successfully on a clean worktree;
- provide deterministic native Windows PowerShell 5.1 regression evidence before PR;
- preserve separate approval for branch deletion.

## 3. Boundary

This increment is corrective tooling-only work.

It must not modify:

- PHP/runtime application code;
- database code, data or migrations;
- themes or assets;
- deploy scripts or Open Server configuration;
- GitHub Actions workflow or pinned Action SHA;
- existing application checkers outside `tools/github-automation`;
- branch protection, required checks, Actions settings or repository settings;
- authentication secrets stored by GitHub CLI or Codex;
- browser ChatGPT capabilities.

Browser ChatGPT still does not receive direct local terminal access. The expanded capability exists only in the local Codex/PowerShell environment launched by the user.

## 4. Architectural principles

### 4.1 Exit code is authoritative

For native executables, success or failure is determined by an explicit process exit code, not by whether stdout or stderr contains text.

### 4.2 Output streams are data

Stdout and stderr are captured separately as data. Callers decide whether either stream may be displayed, parsed, suppressed or redacted.

### 4.3 Interactive and captured commands are separate

Commands that require a browser, device flow, secure prompt or visible console use a dedicated interactive process path. Status probes and machine-readable commands use a captured process path.

### 4.4 Collection shape is explicit

Every potentially empty or single-item result is normalized with `@(...)` or an equivalent helper before count/index operations. No production decision may depend on PowerShell pipeline scalar unrolling.

### 4.5 Authentication readiness and mode are separate

The package records:

```text
CODEX_AUTH_READY=YES|NO
CODEX_AUTH_MODE=CHATGPT|API_KEY|UNKNOWN|NONE
CODEX_CHATGPT_AUTH_READY=YES|NO
CODEX_API_KEY_AUTH_READY=YES|NO
```

An authenticated API key must never be labelled as ChatGPT-plan authentication.

### 4.6 Secrets never become command-line arguments or logs

API keys are accepted only through a secure interactive prompt and passed to Codex through redirected standard input. They are not accepted as script parameters, environment variables, command-line arguments or log content.

### 4.7 One-command production flow remains unchanged

The merged production entry point remains the original repository-owned command. Corrective test and validation commands are additional evidence tools, not a replacement for the user-facing installer.

## 5. Corrected component architecture

### 5.1 Native process adapter

Both PowerShell scripts must use a shared design for native process execution.

Captured execution returns a structured result:

```text
FilePath
Arguments
ExitCode
StdOutLines[]
StdErrLines[]
CombinedLines[]
```

Implementation characteristics:

- based on `System.Diagnostics.Process` or another Windows PowerShell 5.1-compatible API;
- `UseShellExecute = false`;
- explicit stdout/stderr redirection;
- asynchronous or deadlock-safe stream consumption;
- optional redirected standard input;
- no dependence on PowerShell native stderr conversion;
- caller-provided allowed exit codes;
- safe command display that excludes secret stdin;
- deterministic handling of zero, one and many output lines.

Interactive execution:

- inherits the console;
- does not redirect browser/device-flow prompts;
- waits for completion;
- returns the native exit code;
- does not treat stderr as a PowerShell exception;
- is used only for approved commands such as `gh auth login` and `codex login`.

### 5.2 GitHub authentication state machine

The installer uses these states:

```text
GH_COMMAND_MISSING
GH_UNAUTHENTICATED
GH_AUTHENTICATED
GH_REPOSITORY_ACCESS_READY
GH_REPOSITORY_WRITE_READY
```

Flow:

1. verify `gh --version`;
2. run captured `gh auth status --hostname github.com --active`;
3. when exit code is non-zero, classify as unauthenticated instead of throwing;
4. when login is allowed, run interactive:

```text
gh auth login --hostname github.com --git-protocol https --web
```

5. re-run the captured status probe;
6. run `gh auth setup-git --hostname github.com`;
7. verify repository identity, default branch and push/write permission through `gh api`;
8. do not log token lines or raw authentication status output.

### 5.3 Codex installation provider

The current official OpenAI documentation presents both the Windows PowerShell installer and npm installation for Codex CLI. The target machine demonstrated that the PowerShell endpoint can return HTTP `403`, while official npm installation succeeded.

The corrected Windows provider is:

```text
Node.js LTS package: OpenJS.NodeJS.LTS through WinGet
Codex package: npm install --global @openai/codex@latest
```

Rules:

- verify `node --version` and `npm --version` after installation;
- do not automatically upgrade npm solely because an update notice exists;
- install Codex only from the official `@openai/codex` package namespace;
- verify the actual resulting `codex --version`;
- log package/tool versions, not credentials;
- support Repair mode by reinstalling/upgrading the official Codex npm package;
- record the resolved Codex executable path;
- treat the official PowerShell installer as optional documentation/fallback only if explicitly retained and proven reachable; it must not remain the sole provider.

Official source basis:

- OpenAI Codex repository README: Windows PowerShell installer and npm are supported installation paths;
- OpenAI Codex documentation: `npm install -g @openai/codex` is an official installation method;
- OpenAI Codex authentication documentation: ChatGPT and API-key authentication are distinct supported modes.

### 5.4 Codex authentication state machine

Supported requested modes:

```text
Auto
ChatGPT
ApiKey
Skip
```

Default: `Auto`.

#### Existing authentication

The installer runs captured:

```text
codex login status
```

Exit code `0` means authenticated even when the success message is written to stderr.

Known safe status phrases may be parsed only to classify mode:

```text
Logged in using an API key -> API_KEY
Logged in using ChatGPT / ChatGPT account -> CHATGPT
otherwise with exit 0 -> UNKNOWN
```

Raw status output is not written to the installer log because it may contain a masked credential suffix or account detail.

#### ChatGPT mode

Interactive command:

```text
codex login
```

The user completes the browser flow. If account policy requires phone verification or another server-side condition, the installer must report an actionable `USER_ACTION_REQUIRED` state and must not claim ChatGPT readiness.

#### API-key mode

The script:

1. prompts with `Read-Host -AsSecureString`;
2. converts the key to plaintext only for the shortest required in-memory interval;
3. writes it only to redirected stdin of:

```text
codex login --with-api-key
```

4. zeroes/disposes unmanaged buffers where available;
5. removes temporary variables;
6. never echoes, logs, persists or passes the key as an argument;
7. verifies mode with `codex login status` without logging raw output.

API-key authentication does not prove API balance, billing readiness or quota. These remain separate operational conditions.

### 5.5 Scalar and collection normalization

The cleanup tool and installer must normalize all external and pipeline results before collection operations.

Required pattern:

```powershell
$items = @(Invoke-...)
if (@($items).Count -gt 0) { ... }
```

Applies to:

- `git status` lines;
- `git ls-remote` lines;
- `gh api` lists;
- workflow jobs and steps;
- PR comments;
- branch comparisons;
- parser errors;
- manifest entries;
- any result that can be empty or scalar.

`Get-FirstLine` must accept `$null`, scalar or array safely.

### 5.6 Cleanup Doctor and deletion isolation

Doctor must pass on a valid, authenticated, clean repository and report:

```text
DOCTOR_STATUS=PASS
REPOSITORY=<expected>
REMOTE=<canonical>
DEFAULT_BRANCH=main
WORKTREE=CLEAN
```

Verify/Delete retain all existing exact evidence gates.

The only destructive repository command remains:

```text
git push origin --delete <approved-branch>
```

Delete remains unreachable without:

- exact merged PR evidence;
- exact successful post-merge push run and required steps;
- canonical post-merge PASS comment;
- exact main/head/merge SHAs;
- zero unique unmerged commits;
- exact case-sensitive approval token;
- separate owner authorization.

No local branch deletion is introduced.

### 5.7 Helper deployment and rollback

Helper source files are validated before staging.

Deployment sequence:

1. validate manifest schema;
2. validate normalized source hashes;
3. parse PowerShell helper files;
4. stage files under the install-path parent;
5. verify staged hashes;
6. run Cleanup Doctor against the staged helper before replacing the current installation;
7. replace `C:\Tools\ASU-VCH` atomically where practical;
8. restore the prior installation on any failure;
9. verify installed hashes after replacement.

Running Doctor before final replacement prevents a known-broken helper from becoming the accepted installed version.

### 5.8 Native Windows PowerShell 5.1 regression harness

A new repository test tool is required:

```text
tools/github-automation/Test-ASUVCHGitHubAutomation.ps1
```

It must run under native Windows PowerShell 5.1 and use isolated temporary fake native commands to prove:

- stderr plus exit code `0` is success;
- stderr plus non-zero exit is handled as a status/failure according to caller policy;
- unauthenticated `gh auth status` reaches the login decision instead of terminating;
- authenticated `codex login status` on stderr is accepted;
- ChatGPT and API-key modes are classified separately;
- empty output produces a zero-length collection;
- one-line output produces a one-item collection;
- clean `git status --porcelain` does not fail under StrictMode;
- Cleanup Doctor passes in a controlled clean-repository scenario;
- secret stdin is absent from command strings and logs;
- manifest hashes remain valid;
- installer/cleanup scripts parse in Windows PowerShell 5.1;
- the harness leaves the repository and user PATH unchanged.

The harness must not perform package installation, login, network mutation, PR operations, Merge or branch deletion.

## 6. Capability model

Corrected capability matrix:

```text
POWERSHELL_5_1_READY
WINGET_READY
GIT_READY
GITHUB_CLI_READY
GITHUB_AUTH_READY
GITHUB_REPOSITORY_WRITE_ACCESS
NODEJS_READY
NPM_READY
CODEX_READY
CODEX_AUTH_READY
CODEX_AUTH_MODE
CODEX_CHATGPT_AUTH_READY
CODEX_API_KEY_AUTH_READY
ASU_VCH_REPOSITORY_READY
ASU_VCH_LOCAL_HELPERS_READY
LOCAL_CODEX_AGENT_READY
CODEX_REMOTE_REQUEST_READY=NOT_TESTED|PASS|FAIL
```

`LOCAL_CODEX_AGENT_READY=YES` requires local command, authentication and helper readiness. It does not imply paid API balance or available ChatGPT quota unless an explicit remote smoke test is separately authorized and performed.

## 7. Exit model

```text
0 = complete local bootstrap readiness for the selected authentication mode
1 = fail-closed technical or integrity error
2 = user action required or intentionally deferred authentication
```

Examples of exit `2`:

- user selected Skip;
- ChatGPT server-side account verification is incomplete;
- API key was not supplied after selecting API-key mode;
- browser login was cancelled without corrupting local state.

## 8. Secrets and logging

Prohibited in logs and console summaries:

- full or masked API keys;
- GitHub token output;
- OAuth/device codes;
- Authorization headers;
- cookies;
- private keys;
- credential-store content;
- raw `gh auth status` or `codex login status` output.

Allowed evidence:

- executable paths without secret-bearing arguments;
- public tool versions;
- public Git commit SHAs;
- public PR/run/job identifiers;
- authentication mode label only;
- PASS/FAIL/PENDING states;
- manifest hashes.

## 9. Validation architecture

### 9.1 Repository/static validation

Must include:

- exact changed-path allowlist;
- PowerShell parser checks;
- explicit PowerShell 5.1 syntax review;
- manifest schema/hash validation;
- forbidden mutation scan;
- secret-pattern scan;
- native wrapper design review;
- scalar/array unsafe-pattern scan;
- branch deletion isolation proof;
- no runtime/DB/migration/workflow/theme/deploy changes.

### 9.2 Native pre-PR validation

Before PR authorization, the owner must run the regression harness in native Windows PowerShell 5.1 on the exact implementation head and provide the complete PASS summary.

A remote/static-only PASS is insufficient for this corrective increment.

### 9.3 Post-merge target-machine acceptance

After Merge and synchronization of exact `main`:

1. run the unchanged one-command installer entry point;
2. verify the selected authentication mode is labelled correctly;
3. verify Cleanup Doctor PASS;
4. verify installed helper hashes;
5. run a second installer execution to prove idempotency;
6. verify clean repository state;
7. do not perform actual branch deletion without separate authorization.

## 10. Rollback

If corrected helper deployment fails, the previous installed helper directory is restored.

If package installation succeeds but later bootstrap stages fail, installed third-party packages are not automatically uninstalled; the summary reports partial component readiness accurately.

No corrective script may reset repository history, discard local files or delete branches as rollback.

## 11. Process boundary

The historical PR #29 remains merged evidence. This corrective increment does not rewrite its records.

Mutable PR/Merge/run evidence for the corrective increment remains canonical in GitHub. No recursive post-merge Markdown closure is required.

Existing branches, including `tools/github-local-automation-bootstrap`, remain present until separately authorized deletion.

## 12. Implementation gate

Implementation is prohibited until the owner separately approves:

- this Architecture;
- the corrective Specification;
- the corrective Formal Review;
- the exact corrective changed-path allowlist;
- the exact reviewed branch head.

PR creation, Merge and branch deletion remain separately gated.