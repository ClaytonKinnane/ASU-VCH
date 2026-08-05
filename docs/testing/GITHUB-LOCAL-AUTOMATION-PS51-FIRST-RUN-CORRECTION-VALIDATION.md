# GitHub Local Automation PowerShell 5.1 First-Run Correction — Validation

Status: `REPOSITORY/STATIC PASS / NATIVE WINDOWS POWERSHELL 5.1 PENDING`

Date: `2026-08-05`

Repository: `ClaytonKinnane/ASU-VCH`

## 1. Validation anchors

```text
baseline main: 375f941be3f50f9f1f264da244f0dc31496e2a6f
branch: fix/github-local-automation-ps51-first-run
implemented tooling head: 90cefd28d500da0861545caa782b6325ef1a2a62
implementation record commit: 8b7533988b54061f67fad8e54d842b3aa83129ee
merge-base: 375f941be3f50f9f1f264da244f0dc31496e2a6f
behind main: 0
```

## 2. Changed-path validation before this record

```text
changed paths: 11 / 12 currently permitted pre-PR paths
unapproved paths: 0
reserved Final PR Review path: absent
```

The eleventh pre-record path is the Implementation record. This Validation record becomes the twelfth current path. The thirteenth allowlist path remains reserved for separately authorized Final PR Review.

## 3. Repository/static results

```text
EXACT_MAIN=PASS
MERGE_BASE=PASS
BEHIND_MAIN_0=PASS
CURRENT_PATHS_WITHIN_ALLOWLIST=PASS
UNAPPROVED_PATHS_0=PASS
FINAL_PR_REVIEW_ABSENT=PASS
RUNTIME_PATH_CHANGES_0=PASS
DATABASE_PATH_CHANGES_0=PASS
MIGRATION_PATH_CHANGES_0=PASS
WORKFLOW_PATH_CHANGES_0=PASS
ACTION_SHA_CHANGES_0=PASS
THEME_PATH_CHANGES_0=PASS
DEPLOY_PATH_CHANGES_0=PASS
EXISTING_APPLICATION_CHECKER_CHANGES_0=PASS
```

## 4. Installer static review

Confirmed in source:

```text
SYSTEM_DIAGNOSTICS_PROCESS_ADAPTER=PASS
EXIT_CODE_AUTHORITATIVE=PASS
STDOUT_CAPTURED_AS_DATA=PASS
STDERR_CAPTURED_AS_DATA=PASS
CMD_BAT_COMSPEC_PATH=PASS
INTERACTIVE_LOGIN_SEPARATE=PASS
SECRET_STDIN_PATH_SEPARATE=PASS
GH_FIRST_RUN_STATE_MACHINE=PASS
CODEX_NPM_PROVIDER=PASS
NODEJS_LTS_WINGET_PROVIDER=PASS
OLD_CODEX_POWERSHELL_ENDPOINT_ABSENT=PASS
CODEX_AUTH_MODES_AUTO_CHATGPT_APIKEY_SKIP=PASS
API_KEY_PARAMETER_COUNT=0
OPENAI_API_KEY_ENVIRONMENT_ASSIGNMENTS=0
API_KEY_COMMAND_LINE_ARGUMENTS=0
CAPABILITY_MODE_SEPARATION=PASS
STAGED_CLEANUP_DOCTOR_BEFORE_REPLACEMENT=PASS
ATOMIC_BACKUP_RESTORE_PATH=PASS
```

The installer does not invoke Git checkout/switch/reset/rebase/cherry-pick/clean, does not create or delete branches, does not create or merge Pull Requests and contains no repository-settings mutation path.

## 5. Cleanup static review

Confirmed in source:

```text
NATIVE_PROCESS_ADAPTER=PASS
EMPTY_OUTPUT_NORMALIZATION=PASS
SCALAR_ARRAY_NORMALIZATION=PASS
CLEAN_WORKTREE_COUNT_HANDLING=PASS
DOCTOR_CLEAN_OUTPUT=PASS
EXACT_MAIN_GATE=PASS
EXACT_PR_HEAD_GATE=PASS
EXACT_MERGE_COMMIT_GATE=PASS
POST_MERGE_PUSH_RUN_GATE=PASS
REQUIRED_JOB_STEPS_GATE=PASS
CANONICAL_PR_COMMENT_GATE=PASS
REMOTE_BRANCH_SHA_GATE=PASS
UNIQUE_UNMERGED_COMMITS_0_GATE=PASS
CASE_SENSITIVE_APPROVAL_TOKEN_GATE=PASS
SHOULD_PROCESS_WHATIF=PASS
REMOTE_DELETE_INVOCATIONS=1
LOCAL_BRANCH_DELETE_INVOCATIONS=0
```

The single remote deletion invocation remains reachable only after `Mode=Delete`, complete preflight and exact approval token.

## 6. Manifest validation

Manifest JSON parses structurally and contains exactly two helper entries.

```text
schemaVersion=1
minimumPowerShell=5.1
repository=ClaytonKinnane/ASU-VCH
defaultInstallPath=C:\Tools\ASU-VCH
hashMode=utf8-lf-normalized
manifest self-hash=absent
credentials=absent
```

Expected normalized SHA-256:

```text
Invoke-ASUVCHBranchCleanup.ps1=000e6d242498905dcd70b7839373bfa9170704c55dfa5fbc61b8295b5e166b55
CODEX-INSTRUCTIONS.md=7ff8162fa41123e633262390844dd001951de85db31965880c8d31f1dcaa8d6a
```

Source, staging and installed copies are checked through the same normalization algorithm.

## 7. Regression harness static review

The harness:

- requires Windows PowerShell 5.1;
- creates only GUID-scoped temporary directories;
- prepends temporary mock commands only to process `PATH`;
- restores `PATH` and `LOCALAPPDATA` in `finally`;
- checks repository worktree before and after;
- performs no real network request;
- performs no real package installation;
- performs no real Merge or branch deletion;
- tests unauthenticated `gh` stderr with exit `1` followed by login;
- tests authenticated stderr with exit `0`;
- tests ChatGPT and API-key Codex mode detection;
- verifies the test API key is not echoed;
- tests empty clean-worktree output and dirty-worktree failure;
- invokes the Windows PowerShell parser for all three `.ps1` files;
- verifies manifest hashes;
- requires at least twenty passing checks and zero failures.

A batch mock control-flow defect found during static review was corrected in commit:

```text
90cefd28d500da0861545caa782b6325ef1a2a62
```

## 8. Static scan summary

```text
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS_OPEN=0
OPEN_SOURCE_FINDINGS=0
REPOSITORY_STATIC_VALIDATION_STATUS=PASS
```

## 9. Mandatory native boundary

The current execution environment does not provide native Windows PowerShell 5.1. Therefore the following are not claimed PASS:

```text
WINDOWS_POWERSHELL_5_1_PARSER_EXECUTION=PENDING
WINDOWS_POWERSHELL_5_1_NATIVE_STDERR_REGRESSION=PENDING
WINDOWS_POWERSHELL_5_1_CMD_QUOTING=PENDING
FIRST_RUN_GITHUB_MOCK_FLOW=PENDING
CODEX_CHATGPT_MOCK_FLOW=PENDING
CODEX_API_KEY_STDIN_MOCK_FLOW=PENDING
CLEANUP_DOCTOR_NATIVE_RUNTIME=PENDING
WORKTREE_AND_PATH_RESTORATION_RUNTIME=PENDING
```

The branch is not eligible for Pull Request until the owner executes the harness on the exact final branch head and returns:

```text
WINDOWS_POWERSHELL_VERSION=5.1.x
PASS_COUNT>=20
FAIL_COUNT=0
REPOSITORY_WORKTREE_STATUS=PASS
USER_PATH_RESTORATION_STATUS=PASS
NATIVE_PS51_REGRESSION_STATUS=PASS
```

## 10. Target-machine installation boundary

The harness is a pre-PR regression test and does not replace post-merge real installation acceptance. After a future authorized Merge, the one-command installer must still run twice on the target machine to validate real execution and idempotency.

API-key authentication readiness does not prove API balance or quota. ChatGPT authentication remains subject to server-side account requirements.

## 11. Process result

```text
IMPLEMENTATION=COMPLETE
REPOSITORY_STATIC_VALIDATION=PASS
NATIVE_WINDOWS_POWERSHELL_5_1_PRE_PR_VALIDATION=PENDING
PULL_REQUEST=NOT AUTHORIZED / NOT CREATED
MERGE=NOT AUTHORIZED / NOT PERFORMED
BRANCH_DELETION=NOT AUTHORIZED / NOT PERFORMED
```
