# GitHub Local Automation One-Command Bootstrap — Repository/Static Validation

Status: `PASS / TARGET-MACHINE ACCEPTANCE PENDING`

Date: `2026-08-05`

## 1. Validation scope

Validation covers repository content and static safety only.

Validated implementation head before this record:

```text
ce05cd5df67b408d0d32a4500e2790f1da8b2cf9
```

Baseline:

```text
main: d9c7db67ee2df8204281d67a4c134528a499573b
branch: tools/github-local-automation-bootstrap
merge-base: d9c7db67ee2df8204281d67a4c134528a499573b
behind main: 0
```

## 2. Changed-path validation

Before adding this Validation record, the branch contained exactly ten approved changed paths:

```text
docs/architecture/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-ARCHITECTURE.md
docs/specification/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-SPECIFICATION.md
docs/review/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-FORMAL-REVIEW.md
docs/decisions/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-APPROVAL.md
docs/implementation/GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-IMPLEMENTATION.md
tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
tools/github-automation/automation-manifest.json
tools/github-automation/README.md
tools/github-automation/CODEX-INSTRUCTIONS.md
```

After this record, the expected pre-PR set is exactly eleven approved paths. The reserved Final PR Review path must remain absent until separately authorized PR Final Review.

```text
UNAPPROVED_CHANGED_PATHS=0
FINAL_PR_REVIEW_PATH_PRESENT=NO
RUNTIME_PATH_CHANGES=0
DATABASE_PATH_CHANGES=0
MIGRATION_PATH_CHANGES=0
WORKFLOW_PATH_CHANGES=0
ACTION_SHA_CHANGES=0
THEME_PATH_CHANGES=0
DEPLOY_PATH_CHANGES=0
EXISTING_CHECKER_PATH_CHANGES=0
SETTINGS_OR_PROTECTION_CHANGES=0
```

## 3. PowerShell structural validation

Files reviewed:

```text
tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
```

Checks:

- `#requires -Version 5.1` present;
- strict mode and terminating error behavior present;
- balanced parentheses, brackets and braces outside strings/comments;
- terminated single/double strings and block comments;
- no continuation backtick followed by trailing whitespace;
- no PowerShell 7-only null-coalescing, null-conditional, pipeline-chain, parallel or `Join-String` syntax;
- native target-machine PowerShell parser invocation is embedded for manifest-listed `.ps1` files.

```text
INSTALLER_STRUCTURAL_PARSE=PASS
CLEANUP_STRUCTURAL_PARSE=PASS
POWERSHELL_5_1_STATIC_COMPATIBILITY_REVIEW=PASS
NATIVE_WINDOWS_POWERSHELL_5_1_PARSE=NOT RUN
```

The native Windows parser was not available in the repository validation environment. Its execution remains part of the user-run installer acceptance and is not falsely claimed PASS.

## 4. Manifest validation

Manifest:

```text
schemaVersion=1
minimumPowerShell=5.1
repository=ClaytonKinnane/ASU-VCH
defaultInstallPath=C:\Tools\ASU-VCH
hashMode=utf8-lf-normalized
```

Validated hashes:

```text
Invoke-ASUVCHBranchCleanup.ps1
794a161ab528e8b33144186011405ab9fd4781b160ee05879b7afd621bd8ee89

CODEX-INSTRUCTIONS.md
808177da4c52d3b7bc99d4f97aa4aae886a64b2eb369d23b1618e35b3ddd176c
```

Git blob cross-checks:

```text
cleanup blob: 9fe555ffb378c568c6496d00f23b31f8bf84609f
Codex instructions blob: 49ea1d7eb317a59e0cffc43a7a005ca3d9503341
installer blob: e052b3326e85fbc21bc96fb952a70a42f8926961
manifest blob: 236c71fb5addbf286596c163fa0302e8165c73a7
```

```text
MANIFEST_JSON_PARSE=PASS
MANIFEST_SCHEMA=PASS
MANIFEST_PATH_RULES=PASS
MANIFEST_DUPLICATES=0
MANIFEST_SELF_HASH=0
MANIFEST_COMMIT_SELF_REFERENCE=0
MANIFEST_CLEANUP_HASH=PASS
MANIFEST_CODEX_INSTRUCTIONS_HASH=PASS
LINE_ENDING_NORMALIZATION_REVIEW=PASS
```

## 5. Secret and authentication safety

Static scans and manual review confirmed:

```text
TOKEN_PARAMETERS=0
API_KEY_PARAMETERS=0
PASSWORD_PARAMETERS=0
PRIVATE_KEY_PARAMETERS=0
GH_AUTH_TOKEN_COMMANDS=0
GH_WITH_TOKEN_USAGE=0
AUTHORIZATION_HEADER_LITERALS=0
GITHUB_PAT_LIKE_LITERALS=0
OPENAI_KEY_LIKE_LITERALS=0
CODEX_AUTH_FILE_READS=0
CREDENTIAL_STORE_DUMPS=0
```

Authentication is performed only through visible GitHub browser login and interactive Codex/ChatGPT login.

## 6. Installer mutation isolation

The installer was checked for prohibited repository operations:

```text
GIT_CHECKOUT=0
GIT_RESET=0
GIT_REBASE=0
GIT_FORCE_PUSH=0
BRANCH_CREATE=0
BRANCH_DELETE=0
PULL_REQUEST_CREATE=0
PULL_REQUEST_MERGE=0
BRANCH_PROTECTION_MUTATION=0
REQUIRED_CHECK_MUTATION=0
ACTIONS_SETTINGS_MUTATION=0
REPOSITORY_SETTINGS_MUTATION=0
```

Allowed repository-affecting operations are limited to read/fetch and Git credential setup:

```text
git fetch --prune origin
gh auth setup-git --hostname github.com
```

The installer does not modify tracked repository content.

## 7. Cleanup safety validation

Static and manual checks confirmed:

```text
MODES=Doctor,Verify,Delete
SUPPORTS_SHOULD_PROCESS=YES
WHAT_IF_SUPPORTED=YES
MAIN_DELETE_PROHIBITED=YES
MASTER_DELETE_PROHIBITED=YES
DEFAULT_BRANCH_DELETE_PROHIBITED=YES
INVALID_REF_REJECTED=YES
EXACT_MAIN_SHA_REQUIRED=YES
EXACT_PR_HEAD_SHA_REQUIRED=YES
EXACT_MERGE_SHA_REQUIRED=YES
EXACT_PUSH_RUN_REQUIRED=YES
REQUIRED_JOB_AND_STEPS_REQUIRED=YES
CANONICAL_POST_MERGE_PASS_REQUIRED=YES
UNIQUE_UNMERGED_COMMITS_ALLOWED=0
EXACT_CASE_SENSITIVE_APPROVAL_TOKEN_REQUIRED=YES
LOCAL_BRANCH_DELETE_COMMANDS=0
REMOTE_BRANCH_DELETE_COMMANDS=1
```

The sole destructive command is isolated inside the `Delete` path:

```powershell
git push origin --delete <BranchName>
```

No actual branch deletion was executed during validation.

## 8. Installer behavior review

Verified static control flow:

- Windows 64-bit and PowerShell 5.1+ gates;
- TLS 1.2 activation;
- redacted log outside repository;
- script-location and repository identity gates;
- canonical Git remote validation;
- clean-worktree and main synchronization gates;
- exact WinGet package ids `Git.Git` and `GitHub.cli`;
- executable verification after installation/PATH refresh;
- interactive GitHub login and write-permission check;
- official Codex installer URL and public SHA-256 logging;
- Codex version/login-status checks;
- manifest verification before helper deployment;
- staging, backup and rollback flow;
- cleanup Doctor invocation;
- capability matrix and exit codes `0/1/2`.

```text
ONE_COMMAND_INTERFACE_REVIEW=PASS
FAIL_CLOSED_FLOW_REVIEW=PASS
IDEMPOTENCY_DESIGN_REVIEW=PASS
ATOMIC_INSTALL_DESIGN_REVIEW=PASS
ROLLBACK_DESIGN_REVIEW=PASS
CAPABILITY_BOUNDARY_REVIEW=PASS
```

## 9. Target-machine acceptance not run

The following require the owner's Windows machine after Merge:

```text
NATIVE_WINDOWS_POWERSHELL_5_1_EXECUTION=NOT RUN
WINGET_DETECTION_OR_INSTALL=NOT RUN
GIT_INSTALL_OR_UPGRADE=NOT RUN
GITHUB_CLI_INSTALL_OR_UPGRADE=NOT RUN
UAC_FLOW=NOT RUN
GITHUB_BROWSER_LOGIN=NOT RUN
GITHUB_REPOSITORY_WRITE_ACCESS_RUNTIME_CHECK=NOT RUN
CODEX_INSTALLATION=NOT RUN
CODEX_VERSION_RUNTIME_CHECK=NOT RUN
CODEX_CHATGPT_LOGIN=NOT RUN
LOCAL_HELPER_ATOMIC_INSTALL=NOT RUN
CLEANUP_DOCTOR_RUNTIME=NOT RUN
SECOND_INSTALLER_RUN_IDEMPOTENCY=NOT RUN
LOCAL_CODEX_LAUNCH=NOT RUN
ACTUAL_BRANCH_DELETION=NOT RUN
```

No item above is claimed PASS.

## 10. Findings

```text
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS_OPEN=0
OPEN_FINDINGS=0
```

## 11. Verdict and process gate

```text
REPOSITORY_STATIC_VALIDATION_STATUS=PASS
TARGET_MACHINE_ACCEPTANCE_STATUS=PENDING AFTER MERGE
IMPLEMENTATION_READY_FOR_PR_GATE=YES
PR_CREATION_AUTHORIZED=NO
FINAL_PR_REVIEW_AUTHORIZED=NO
MERGE_AUTHORIZED=NO
BRANCH_DELETION_AUTHORIZED=NO
```

The branch must stop before Pull Request creation and await separate owner authorization.
