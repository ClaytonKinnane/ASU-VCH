# GitHub Local Automation PowerShell 5.1 First-Run Correction — Validation

Status: `REPOSITORY/STATIC PASS / NATIVE ATTEMPT 1 FAILED / RERUN PENDING`

Date: `2026-08-05`

Repository: `ClaytonKinnane/ASU-VCH`

## 1. Validation anchors

```text
baseline main: 375f941be3f50f9f1f264da244f0dc31496e2a6f
branch: fix/github-local-automation-ps51-first-run
initial pre-native branch head: 7e8a552bdb5a50d252624cf9591906473128d593
native-attempt-1 harness correction commit: d943ad1850225d483d01cdf2bc8f894f35d98a8e
PS5.1 constructor correction commit: 449cd1f92e5454b952d8378cb5d4ea3ba0cb861f
merge-base: 375f941be3f50f9f1f264da244f0dc31496e2a6f
behind main: 0
```

## 2. Native attempt 1 evidence

The owner executed the harness under native Windows PowerShell 5.1.

Observed:

```text
WINDOWS=PASS
POWERSHELL_MAJOR_5=PASS
POWERSHELL_MINOR_1_PLUS=PASS
MOCK_GIT_EXISTS=PASS
MOCK_GH_EXISTS=PASS
MOCK_CODEX_EXISTS=PASS
FIRST_RUN_EXIT_0=FAIL
GH_STDERR_NONZERO_REACHED_LOGIN=FAIL
STATE_CODEX_MODE=ABSENT
NATIVE_PS51_VALIDATION_EXIT_CODE=1
```

Result:

```text
NATIVE_ATTEMPT_1=FAIL
PRE_PR_GATE=FAIL
PR_ELIGIBILITY=NO
```

The user correctly stopped after the failed command.

## 3. Failure classification

The failure occurred before the intended mock GitHub first-run state machine could be exercised.

Root cause:

```text
HARNESS_PROCESS_PATH_MOCKING=INSUFFICIENT
INSTALLER_PATH_REFRESH_REMOVED_TEMP_MOCK_BIN=YES
REAL_GITHUB_OR_OPENAI_REQUEST_PROVEN=NO
PRODUCTION_AUTH_FAILURE_PROVEN=NO
CASCADED_MISSING_STATE_EXCEPTION=YES
```

The result is classified as a harness isolation defect, not a successful or failed production acceptance test.

## 4. Corrective static verification

Corrected harness commits:

```text
d943ad1850225d483d01cdf2bc8f894f35d98a8e
449cd1f92e5454b952d8378cb5d4ea3ba0cb861f
```

Corrected harness Git blob:

```text
739f89661fae310aa49569eac006821796551477
```

Verified from the committed source:

```text
REQUIRES_WINDOWS_POWERSHELL_5_1=PASS
PS51_REGEX_CONSTRUCTOR=PASS
GUID_SCOPED_TEMP_DIRECTORY=PASS
PRODUCTION_INSTALLER_TEST_HOOK_ASSERTION=PASS
TEMP_INSTALLER_COPY_ONLY=PASS
TEMP_COPY_PATH_SHIM=PASS
PROCESS_PATH_RESTORATION_CHECK=PASS
USER_PATH_UNCHANGED_CHECK=PASS
LOCALAPPDATA_RESTORATION_CHECK=PASS
GUARDED_STATE_FILE_READ=PASS
CASCADE_ON_MISSING_CODEX_MODE=REMOVED
FULL_SUMMARY_AFTER_ASSERTION_FAILURE=PASS
REAL_REPOSITORY_PRE_POST_STATUS_CHECK=PASS
REAL_NETWORK_REQUESTS=0
REAL_PACKAGE_INSTALLATIONS=0
REAL_MERGE_OPERATIONS=0
REAL_BRANCH_DELETIONS=0
MINIMUM_PASS_COUNT=25
REQUIRED_FAIL_COUNT=0
```

## 5. Production source integrity

The following production files were not changed by the attempt-1 correction:

```text
tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
tools/github-automation/automation-manifest.json
tools/github-automation/README.md
tools/github-automation/CODEX-INSTRUCTIONS.md
```

Their previously validated blob and normalized-hash evidence remains applicable.

## 6. Repository scope revalidation

```text
EXACT_MAIN=PASS
MERGE_BASE=PASS
BEHIND_MAIN_0=PASS
CURRENT_PATHS_WITHIN_13_PATH_ALLOWLIST=PASS
UNAPPROVED_PATHS_0=PASS
RESERVED_FINAL_PR_REVIEW_PATH_ABSENT=PASS
RUNTIME_PATH_CHANGES_0=PASS
DATABASE_PATH_CHANGES_0=PASS
MIGRATION_PATH_CHANGES_0=PASS
WORKFLOW_PATH_CHANGES_0=PASS
ACTION_SHA_CHANGES_0=PASS
THEME_PATH_CHANGES_0=PASS
DEPLOY_PATH_CHANGES_0=PASS
EXISTING_APPLICATION_CHECKER_CHANGES_0=PASS
```

## 7. Mandatory rerun gate

A fresh native Windows PowerShell 5.1 run is mandatory on the exact final branch head produced after this record.

Required final output:

```text
WINDOWS_POWERSHELL_VERSION=5.1.x
PASS_COUNT>=25
FAIL_COUNT=0
REPOSITORY_WORKTREE_STATUS=PASS
PROCESS_PATH_RESTORATION_STATUS=PASS
USER_PATH_RESTORATION_STATUS=PASS
LOCALAPPDATA_RESTORATION_STATUS=PASS
NATIVE_PS51_REGRESSION_STATUS=PASS
NATIVE_PS51_VALIDATION_EXIT_CODE=0
PRE_PR_NATIVE_VALIDATION=PASS
```

Until that exact-head rerun passes:

```text
NATIVE_WINDOWS_POWERSHELL_5_1_PRE_PR_VALIDATION=PENDING
PULL_REQUEST=NOT AUTHORIZED / NOT CREATED
MERGE=NOT AUTHORIZED / NOT PERFORMED
BRANCH_DELETION=NOT AUTHORIZED / NOT PERFORMED
```
