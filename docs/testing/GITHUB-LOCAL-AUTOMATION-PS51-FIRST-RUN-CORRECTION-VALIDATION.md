# GitHub Local Automation PowerShell 5.1 First-Run Correction — Validation

Status: `HISTORICAL VALIDATION RECORD / EXACT-HEAD ACCEPTANCE EVIDENCE IN PR #30`

Date: `2026-08-05`

Repository: `ClaytonKinnane/ASU-VCH`

## 1. Stable anchors

```text
baseline main: 375f941be3f50f9f1f264da244f0dc31496e2a6f
branch: fix/github-local-automation-ps51-first-run
pull request: #30
initial PR head: 9831fa58c0656c0c39d622e56e53296ced703be6
merge-base: 375f941be3f50f9f1f264da244f0dc31496e2a6f
behind main at initial PR review: 0
```

This file records durable validation history and acceptance rules. Mutable exact-head,
workflow-run and review evidence is recorded in PR #30 and GitHub Actions so this
document does not require a self-referential commit SHA.

## 2. Native validation history

### Attempt 1

Result: `FAIL / HARNESS ISOLATION DEFECT`.

The production state machine was not reached because the installer PATH refresh
removed the temporary mock directory.

### Attempt 2

Result: `FAIL / PRODUCTION POWERSHELL PARSER DEFECTS`.

The run exposed unmatched parentheses in the npm and Codex version log
expressions. Both production parser defects were corrected.

### Attempt 3

Result:

```text
PASS_COUNT=34
FAIL_COUNT=3
NPM_PROVIDER_CREATED_CODEX=FAIL
API_STDIN_LOGIN=FAIL
CAPABILITY_API_KEY=FAIL
```

Classification: `HARNESS PATH ISOLATION GAP`. A real user-level Codex command
remained eligible after the mock command was removed.

### Attempt 4

Result: `INCOMPLETE / HARNESS INTERACTION DEFECTS`.

The run exposed:

- LF-only temporary `.cmd` files, which broke batch labels;
- an API-key scenario that could block on `Read-Host -AsSecureString`;
- absence of a bounded child-process timeout.

These were corrected only in the regression harness.

### Attempt 5 — accepted pre-PR evidence

Exact head:

```text
9831fa58c0656c0c39d622e56e53296ced703be6
```

Native Windows PowerShell evidence:

```text
WINDOWS_POWERSHELL_VERSION=5.1.28000.2525
PASS_COUNT=41
FAIL_COUNT=0
REPOSITORY_WORKTREE_STATUS=PASS
PROCESS_PATH_RESTORATION_STATUS=PASS
USER_PATH_RESTORATION_STATUS=PASS
LOCALAPPDATA_RESTORATION_STATUS=PASS
NATIVE_PS51_REGRESSION_STATUS=PASS
NATIVE_PS51_VALIDATION_EXIT_CODE=0
EXACT_COMMIT_NATIVE_VALIDATION=PASS
PRE_PR_NATIVE_VALIDATION=PASS
```

The validated history was pushed and PR #30 was created from that exact head.

## 3. Initial Final PR Review

The first Final PR Review for PR #30 was completed on exact head
`9831fa58c0656c0c39d622e56e53296ced703be6`.

Result:

```text
FINAL_PR_REVIEW_STATUS=FAIL
CHANGES_REQUIRED=YES
MERGE_ELIGIBILITY=NO
```

Blocking findings:

1. Verify/Delete did not execute Cleanup Doctor before preflight.
2. Explicit `ChatGPT` and `ApiKey` requests did not reject a known opposite
   authenticated mode.
3. This validation document still described only attempt 1 and incorrectly
   stated that PR creation was not authorized.
4. The implementation emitted `CODEX_API_BALANCE` instead of the specified
   `CODEX_REMOTE_REQUEST_READY` capability.
5. Cleanup accepted PowerShell 5.0 although its contract requires PowerShell 5.1+.

## 4. Corrective validation requirements

The corrective implementation must prove all of the following:

```text
CLEANUP_VERIFY_DELETE_DOCTOR_GATE=PASS
CLEANUP_POWERSHELL_5_1_GATE=PASS
CODEX_EXPLICIT_MODE_GATE_PRESENT=PASS
CHATGPT_MISMATCH_EXIT_2=PASS
CHATGPT_MISMATCH_REJECTED=PASS
API_MISMATCH_EXIT_2=PASS
API_MISMATCH_REJECTED=PASS
CODEX_REMOTE_REQUEST_CAPABILITY_KEY=PASS
REMOTE_REQUEST_NOT_TESTED_CHATGPT=PASS
REMOTE_REQUEST_NOT_TESTED_API_KEY=PASS
MANIFEST_SOURCE_HASH_PASS=PASS
```

The existing native regression requirements remain mandatory:

```text
FAIL_COUNT=0
NATIVE_PS51_REGRESSION_STATUS=PASS
REPOSITORY_WORKTREE_STATUS=PASS
PROCESS_PATH_RESTORATION_STATUS=PASS
USER_PATH_RESTORATION_STATUS=PASS
LOCALAPPDATA_RESTORATION_STATUS=PASS
```

## 5. Exact-head evidence policy

For every corrective head after the initial PR head:

1. commit all approved code, harness, manifest and documentation changes;
2. require a clean worktree;
3. run the native Windows PowerShell 5.1 harness on that exact commit;
4. push only when the native run returns exit `0`;
5. require the pull-request workflow on the same exact head to succeed;
6. repeat Final PR Review against that exact head;
7. keep Merge and branch deletion blocked until separate owner approvals.

Canonical mutable evidence belongs in PR #30 reviews/comments and GitHub Actions.
This record must not be interpreted as Merge or branch-deletion authorization.
