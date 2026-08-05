# GitHub Local Automation PowerShell 5.1 First-Run Correction — Implementation

Status: `IMPLEMENTED / NATIVE WINDOWS POWERSHELL 5.1 RERUN REQUIRED`

Date: `2026-08-05`

Repository: `ClaytonKinnane/ASU-VCH`

## 1. Immutable anchors

```text
baseline main: 375f941be3f50f9f1f264da244f0dc31496e2a6f
branch: fix/github-local-automation-ps51-first-run
reviewed design head: af2e2cc26e3cb84ea744a204a9acef2269c3fd95
approval commit: 19e412874f77d895c11e47fabfecff136be3146d
initial tooling package commit: f7fcc677c8d70f1a7b1e81a8777254973eda347f
initial harness correction commit: 90cefd28d500da0861545caa782b6325ef1a2a62
initial pre-native branch head: 7e8a552bdb5a50d252624cf9591906473128d593
native-attempt-1 harness correction commit: d943ad1850225d483d01cdf2bc8f894f35d98a8e
merge-base: 375f941be3f50f9f1f264da244f0dc31496e2a6f
behind main: 0
```

## 2. Implemented production tooling

```text
tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
tools/github-automation/automation-manifest.json
tools/github-automation/README.md
tools/github-automation/CODEX-INSTRUCTIONS.md
```

Implemented production behaviour remains:

- native stdout and stderr are captured as data;
- native exit code is authoritative;
- unauthenticated GitHub status reaches the interactive browser-login state;
- Node.js LTS is resolved through WinGet package `OpenJS.NodeJS.LTS`;
- Codex is installed through official npm package `@openai/codex@latest`;
- Codex authentication modes are `Auto`, `ChatGPT`, `ApiKey` and `Skip`;
- API-key input is obtained through `Read-Host -AsSecureString`;
- API-key material is passed to Codex only through stdin;
- API-key material is not accepted as a parameter, placed in environment variables or logged;
- optional results are normalized before count/index operations;
- Cleanup Doctor accepts an empty clean-worktree result;
- helper installation validates source, staged and installed normalized hashes;
- staged Cleanup Doctor must pass before installed helpers are replaced;
- backup restoration is fail-closed.

## 3. Unchanged production identities after native attempt 1

Native attempt 1 did not require a production installer, cleanup helper, manifest or documentation-tooling change.

```text
Install-ASUVCHGitHubAutomation.ps1 git blob:
129dbf71016c70432b3b7621073c374e5988b5b7

Invoke-ASUVCHBranchCleanup.ps1 git blob:
df9aa408494cf1f5b715a5a306fc1e359aff5353

automation-manifest.json git blob:
2d1c67f9b6972002371d34ccc57d524db53ec8f5

README.md git blob:
6359665926102ce7a2fe4d363c4a693b78efb3b1

CODEX-INSTRUCTIONS.md git blob:
9e77ef2acef8e29d44ae29feb35ca01c3b7f6865
```

Manifest normalized SHA-256 values remain:

```text
Invoke-ASUVCHBranchCleanup.ps1:
000e6d242498905dcd70b7839373bfa9170704c55dfa5fbc61b8295b5e166b55

CODEX-INSTRUCTIONS.md:
7ff8162fa41123e633262390844dd001951de85db31965880c8d31f1dcaa8d6a
```

## 4. Native Windows PowerShell 5.1 attempt 1

The owner executed the mandatory harness on exact branch head:

```text
7e8a552bdb5a50d252624cf9591906473128d593
```

Observed relevant output:

```text
PASS WINDOWS
PASS POWERSHELL_MAJOR_5
PASS POWERSHELL_MINOR_1_PLUS
PASS MOCK_GIT_EXISTS
PASS MOCK_GH_EXISTS
PASS MOCK_CODEX_EXISTS
FAIL FIRST_RUN_EXIT_0
FAIL GH_STDERR_NONZERO_REACHED_LOGIN
Get-Content: state\codex.mode not found
NATIVE_PS51_VALIDATION_EXIT_CODE=1
```

The pre-PR gate correctly remained failed.

## 5. Root-cause classification

The first failure was traced to the regression fixture:

1. the harness prepended mock commands only to process `PATH`;
2. the production installer intentionally refreshed process `PATH` from Machine/User values;
3. the GUID-scoped mock directory was therefore removed from command resolution;
4. the test no longer exercised mock `gh`;
5. the absent `codex.mode` state then caused a cascading `Get-Content` exception.

This output is valid evidence that the original harness isolation was insufficient. It is not evidence that production GitHub browser authentication failed on a real installation path.

## 6. Harness correction

Path:

```text
tools/github-automation/Test-ASUVCHGitHubAutomation.ps1
```

Correction commit:

```text
d943ad1850225d483d01cdf2bc8f894f35d98a8e
```

Corrected Git blob:

```text
a7eb812a3cb7e1916e5f3790ecf6a0855568cc9e
```

The corrected harness now:

- copies the production installer into a GUID-scoped temporary repository;
- asserts that the production installer contains no test hook;
- applies a PATH-preservation shim only to the temporary installer copy;
- keeps mock commands selected after temporary-copy PATH refresh;
- does not modify Machine or User PATH;
- restores process `PATH` and `LOCALAPPDATA`;
- verifies persistent User PATH remains unchanged;
- reads optional state files through a guarded helper;
- records missing state as a failed assertion instead of terminating diagnostics;
- reports infrastructure errors and the complete final status block;
- requires at least twenty-five passes and zero failures;
- checks the real repository is clean before and after;
- performs no real network request, package installation, Merge or branch deletion.

## 7. Secret boundary

The corrected harness uses only a clearly non-secret synthetic stdin value.

Production constraints remain:

```text
secret parameters: 0
secret environment assignments: 0
secret logging paths: 0
API-key command-line arguments: 0
API-key transport: secure interactive read -> native stdin only
```

## 8. Scope boundary

No changes were made to:

```text
application runtime
database
migrations
workflow files
Action SHA
themes
deployment
existing application checkers
branch protection
required checks
Actions settings
repository settings
```

## 9. Current implementation result

```text
PRODUCTION_IMPLEMENTATION=COMPLETE
HARNESS_FIXTURE_CORRECTION=COMPLETE
REPOSITORY_STATIC_REVALIDATION=REQUIRED
NATIVE_WINDOWS_POWERSHELL_5_1_RERUN=REQUIRED
PULL_REQUEST=NOT CREATED
MERGE=NOT PERFORMED
BRANCH_DELETION=NOT PERFORMED
```
