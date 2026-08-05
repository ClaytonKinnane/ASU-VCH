# GitHub Local Automation PowerShell 5.1 First-Run Correction — Formal Review

Status: `PASS FOR OWNER APPROVAL`

Date: `2026-08-05`

Baseline:

```text
main: 375f941be3f50f9f1f264da244f0dc31496e2a6f
branch: fix/github-local-automation-ps51-first-run
```

Reviewed documents:

- `docs/architecture/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-ARCHITECTURE.md`;
- `docs/specification/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-SPECIFICATION.md`.

## 1. Review scope

The review checked:

- fidelity to the observed native Windows PowerShell 5.1 failures;
- preservation of the mandatory АСУ-ВЧ lifecycle;
- native stdout/stderr/exit-code semantics;
- `.exe`, `.cmd` and `.bat` invocation on Windows;
- empty/scalar/array output normalization under StrictMode;
- first-run GitHub browser authentication;
- official Codex installation-provider design;
- ChatGPT versus API-key authentication separation;
- secret input and log handling;
- helper staging, Doctor and rollback ordering;
- branch cleanup Delete isolation;
- native Windows PowerShell 5.1 regression strategy;
- exact corrective changed-path allowlist;
- runtime/database/workflow/settings isolation;
- pre-PR and post-merge acceptance gates.

## 2. Evidence review

Target-machine evidence establishes the following facts:

```text
main synchronization = PASS
Git installation = PASS
GitHub CLI installation = PASS
GitHub browser login = PASS
repository write access = PASS
Node.js/npm availability = PASS
Codex npm installation = PASS
Codex API-key authentication = PASS
manifest source validation = PASS
helper acceptance = FAIL / rolled back or not accepted
```

Four concrete PowerShell 5.1 failure mechanisms were observed:

1. unauthenticated `gh auth status` native stderr became a terminating PowerShell error;
2. successful `codex login status` native stderr became a terminating PowerShell error;
3. the sole Codex PowerShell download provider returned HTTP `403`;
4. empty clean-worktree output collapsed to `$null`, and `.Count` failed under StrictMode.

The corrective design directly addresses all four mechanisms and adds regression coverage for their general classes.

Evidence alignment result: `PASS`.

## 3. Architecture review

### 3.1 Native process adapter

The design correctly makes the native exit code authoritative and treats stdout/stderr as captured data. This avoids the Windows PowerShell 5.1 `NativeCommandError` behaviour that caused false failures.

The split between captured, interactive and secret-stdin execution is necessary:

- captured probes require deterministic stream/exit handling;
- browser login must retain visible interaction;
- API keys must never be command-line arguments.

The requirement to invoke `.cmd`/`.bat` through `%ComSpec%` with safe quoting addresses the actual npm/Codex command form on Windows.

Result: `PASS`.

### 3.2 Collection normalization

The architecture does not patch only the observed `$status.Count` line. It establishes a general rule for all optional pipeline, JSON and process results.

This prevents equivalent failures in jobs, steps, comments, parser errors, branch lines and manifest results.

Result: `PASS`.

### 3.3 GitHub first-run authentication

The state machine distinguishes:

```text
process launch failure
unauthenticated status
successful authentication
repository access/write readiness
```

A non-zero status probe therefore reaches browser login instead of prematurely terminating.

Raw authentication output remains suppressed, protecting token details.

Result: `PASS`.

### 3.4 Codex installation provider

The design replaces the single-point dependency on the failing PowerShell endpoint with the official npm package path:

```text
OpenJS.NodeJS.LTS
npm install --global @openai/codex@latest
```

OpenAI's official Codex documentation lists npm installation as supported. The target machine has already demonstrated successful installation of `codex-cli 0.146.0` through this provider.

The design does not auto-update npm merely because npm advertises a newer version.

Result: `PASS`.

### 3.5 Codex authentication modes

The correction separates readiness from mode:

```text
CHATGPT
API_KEY
UNKNOWN
NONE
```

This resolves the false claim that API-key login was ChatGPT-plan login.

The API-key path uses secure prompt plus redirected stdin, while ChatGPT remains an interactive browser flow. Server-side phone/account verification is not bypassed.

Result: `PASS`.

### 3.6 Secrets boundary

The design prohibits secrets in:

- parameters;
- command-line arguments;
- environment variables;
- logs;
- summaries;
- raw authentication status output.

Secure input still exists in process memory for the minimum interval required by Codex. The specification requires best-effort buffer clearing and `finally` cleanup; it does not make an unrealistic zero-memory-residue guarantee.

Result: `PASS`.

### 3.7 Helper staging and Doctor ordering

Running Doctor against the staged helper before replacing the accepted installation closes the observed failure mode where a syntactically valid but runtime-broken cleanup script reached the deployment stage.

Backup restoration remains fail closed and does not modify repository history.

Result: `PASS`.

### 3.8 Branch cleanup safety

The correction changes process/output handling but does not reduce Delete evidence gates.

The only destructive command remains remote branch deletion after exact evidence and approval. No local branch deletion, settings mutation or default-branch deletion path is introduced.

Result: `PASS`.

## 4. Specification review

### 4.1 Exact allowlist

The proposed allowlist contains exactly 13 paths:

```text
process Markdown: 7
tooling Markdown: 2
PowerShell: 3
JSON: 1
total: 13
```

The additional PowerShell path is a native regression harness. This is justified by the failure of static review to detect Windows PowerShell 5.1 runtime semantics.

No runtime, database, migration, workflow, Action SHA, theme, deploy or existing checker path is included.

Result: `PASS`.

### 4.2 Backward-compatible production command

The user-facing production command is unchanged.

The installer gains explicit Codex authentication-mode support while retaining `-SkipCodex` as a compatibility mapping. Contradictory arguments must fail closed.

Result: `PASS`.

### 4.3 Native process contract feasibility

`System.Diagnostics.Process`, `%ComSpec%`, redirected streams and redirected stdin are available in Windows PowerShell 5.1/.NET Framework.

The contract is implementable without PowerShell 7-only syntax.

The specification explicitly requires safe handling of paths with spaces and `.cmd` wrappers, reducing implementation ambiguity.

Result: `PASS`.

### 4.4 Mutable official package version

`@openai/codex@latest` is mutable by design. The correction mitigates this by:

- using the official scoped package only;
- recording/verifying the resulting executable and version;
- not embedding a stale package version in durable documentation;
- requiring native regression and target-machine acceptance.

A separate reproducible third-party package lock policy is outside this corrective increment.

Result: `PASS WITH DOCUMENTED BOUNDARY`.

### 4.5 Authentication wording variability

Future Codex versions may change `login status` wording. The specification treats exit code `0` as authentication readiness and uses text only to classify mode.

Unknown successful wording becomes `UNKNOWN`, not false failure or false ChatGPT attribution.

Result: `PASS`.

### 4.6 API billing/quota boundary

The specification correctly states that API-key authentication does not prove API balance or quota. A real remote model request is not silently performed or claimed.

Result: `PASS`.

### 4.7 Regression harness adequacy

The harness covers the exact defect classes with fake native commands and isolated temporary paths. It also checks repository/PATH restoration and proves no branch deletion was executed.

The harness is not treated as a substitute for real post-merge target-machine acceptance.

Result: `PASS`.

### 4.8 Native pre-PR gate

The corrective increment requires native Windows PowerShell 5.1 regression evidence on the exact implementation head before PR authorization.

This is stronger and more appropriate than repeating a static-only gate that already proved insufficient.

Result: `PASS`.

### 4.9 Post-merge idempotency gate

The same one-command installer must pass twice after Merge. The second run verifies stable helper hashes, clean worktree and idempotency.

Actual branch deletion remains outside acceptance.

Result: `PASS`.

## 5. Required implementation controls

```text
EXACT_BASE_MAIN=375f941be3f50f9f1f264da244f0dc31496e2a6f
RUN_FROM_SYNCED_REPOSITORY=REQUIRED
LOCAL_HEAD_EQUALS_ORIGIN_MAIN=REQUIRED_FOR_INSTALL_REPAIR
NATIVE_EXIT_CODE_AUTHORITATIVE=REQUIRED
NATIVE_STDERR_AS_DATA=REQUIRED
CMD_BAT_VIA_COMSPEC=REQUIRED
EMPTY_SCALAR_ARRAY_NORMALIZATION=REQUIRED
GH_FIRST_RUN_BROWSER_LOGIN=REQUIRED
CODEX_NPM_PROVIDER=REQUIRED
NODEJS_LTS_EXACT_WINGET_ID=REQUIRED
CODEX_AUTH_MODE_SEPARATION=REQUIRED
API_KEY_ONLY_VIA_SECURE_STDIN=REQUIRED
RAW_AUTH_OUTPUT_LOGGING=PROHIBITED
SECRETS_IN_ARGUMENTS=PROHIBITED
SECRETS_IN_ENVIRONMENT=PROHIBITED
STAGED_CLEANUP_DOCTOR=REQUIRED
HELPER_ROLLBACK=REQUIRED
BRANCH_DELETE_VERIFY_FIRST=REQUIRED
BRANCH_DELETE_SEPARATE_APPROVAL=REQUIRED
LOCAL_BRANCH_DELETE=PROHIBITED
SETTINGS_MUTATION=PROHIBITED
NATIVE_PS51_PRE_PR_GATE=REQUIRED
POST_MERGE_SECOND_RUN=REQUIRED
REMOTE_CODEX_REQUEST_TEST=NOT_REQUIRED / NOT_CLAIMED
```

## 6. Design risks and mitigations

### R1. Windows command-line quoting

Risk: `.cmd` wrappers and paths with spaces can be misquoted.

Mitigation: one centralized argument/command resolver plus regression cases using real path shapes.

Status: `CONTROLLED`.

### R2. stdout/stderr deadlock

Risk: synchronous reading of one redirected stream can deadlock while the other fills.

Mitigation: asynchronous or deadlock-safe dual-stream consumption required by specification.

Status: `CONTROLLED`.

### R3. secret exposure through errors

Risk: a failed API-key login could include stdin or raw auth output in an exception.

Mitigation: secret stdin is never included in display strings; raw status output is discarded after classification; logs use safe stage messages.

Status: `CONTROLLED`.

### R4. future Codex status wording

Risk: mode parser may not recognize new wording.

Mitigation: successful unknown wording maps to `UNKNOWN`, preserving readiness without false mode attribution.

Status: `CONTROLLED`.

### R5. test doubles differ from real tools

Risk: fake native commands cannot model every GitHub/Codex behaviour.

Mitigation: native harness is mandatory before PR, and real one-command plus idempotency acceptance is mandatory after Merge.

Status: `CONTROLLED`.

### R6. API-key account has no balance

Risk: authentication passes but actual requests fail for billing/quota reasons.

Mitigation: capability matrix reports remote request readiness as `NOT_TESTED`; no false operational claim.

Status: `DOCUMENTED EXTERNAL CONDITION`.

## 7. Process review

The branch currently contains design documentation only.

Implementation, package changes, Approval record, Implementation record and Validation record remain prohibited pending owner approval.

PR creation, Final PR Review, Merge and branch deletion remain separately gated.

The existing branch `tools/github-local-automation-bootstrap` remains undeleted.

Result: `PASS`.

## 8. Findings

```text
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS_OPEN=0
OPEN_FINDINGS=0
```

Documented boundaries are not open findings:

- official package `@latest` remains mutable;
- API billing/quota is not tested by login status;
- server-side OpenAI phone/account verification cannot be bypassed;
- post-merge real-machine acceptance remains required.

## 9. Verdict

```text
EVIDENCE_ALIGNMENT_REVIEW=PASS
ARCHITECTURE_REVIEW=PASS
SPECIFICATION_REVIEW=PASS
NATIVE_PROCESS_MODEL_REVIEW=PASS
POWERSHELL_5_1_FEASIBILITY_REVIEW=PASS
SCALAR_ARRAY_NORMALIZATION_REVIEW=PASS
GITHUB_FIRST_RUN_AUTH_REVIEW=PASS
CODEX_INSTALL_PROVIDER_REVIEW=PASS
CODEX_AUTH_MODE_REVIEW=PASS
SECRET_HANDLING_REVIEW=PASS
HELPER_ROLLBACK_REVIEW=PASS
BRANCH_DELETION_SAFETY_REVIEW=PASS
NATIVE_TEST_STRATEGY_REVIEW=PASS
ALLOWLIST_REVIEW=PASS
PROCESS_REVIEW=PASS

FORMAL_REVIEW_STATUS=PASS FOR OWNER APPROVAL
```

## 10. Process gate

The branch must stop before Implementation.

Implementation requires separate owner approval of:

- Architecture;
- Specification;
- this Formal Review;
- exact 13-path allowlist;
- exact reviewed branch head after final design-state verification.

No Pull Request, Merge or branch deletion is authorized by this review.