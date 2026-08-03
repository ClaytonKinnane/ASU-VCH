# GitHub Actions Static Verification v1 — Test Report

## Historical PR test gate

```text
date: 2026-08-03
PR: #25
base: feac7230616d3a8df98acb48f43a0b60f89f2255
validated implementation head: 7bc170d4673b1143e4b7d149738a4c081e2af476
status then: PR WORKFLOW PASS / FINAL REVIEW PENDING
```

The pending marker was correct at that gate. Later outcomes are recorded separately below.

## Scope

Tested static workflow registration, triggers, runner/PHP, read-only permissions, immutable Action references, event-aware diff check, tracked PHP lint, 9 CI-safe checker entrypoints and final repository integrity.

Database, installer, deploy, HTTP/browser, visual and mobile checks were outside this static-only increment.

## Attempt history

```text
attempt 1 run: 30836352719
attempt 1 result: FAILURE
cause: Markdown trailing whitespace detected by git diff --check
assessment: correct fail-closed behavior
```

Only trailing whitespace was remediated.

```text
successful run: 30836630576
job: 91763264596
head: 7bc170d4673b1143e4b7d149738a4c081e2af476
result: SUCCESS
runner: Ubuntu 24.04.4
PHP: 8.5.9
tracked PHP: 124 / 0 errors
CI-safe checkers: 9 / PASS
final worktree: PASS
```

A later synchronization run also completed successfully before Final PR Review.

## Non-blocking platform annotation

GitHub reported that the pinned checkout revision targeted Node.js 20 and executed it on Node.js 24. Checkout and all workflow steps succeeded. Updating the pinned revision is a separate maintenance increment.

## Final PR Review and merge closure

```text
EXACT_FINAL_PR_HEAD=0c6f7338f912e8797868d02d54fc015df7533ad6
FINAL_EXACT_HEAD_RUN=30836965091 / SUCCESS
FINAL_PR_REVIEW=PASS
PR_STATE=CLOSED / MERGED
MERGE_COMMIT=c567429b3aa4d629a4e7c11fec7e3dbae907d92e
```

## Post-merge verification

```text
PUSH_RUN=30837637886 / SUCCESS
WORKFLOW_DISPATCH_RUN=30839122892 / SUCCESS
POST_MERGE_VERIFICATION=PASS
RUNNER=Ubuntu 24.04.4
PHP=8.5.9
PERMISSIONS=contents read
DIFF_BASE=feac7230616d3a8df98acb48f43a0b60f89f2255
DIFF_HEAD=c567429b3aa4d629a4e7c11fec7e3dbae907d92e
GIT_DIFF_CHECK=PASS
TRACKED_PHP=124 / 0 ERRORS
CI_SAFE_CHECKERS=9 / PASS
ORGANIZATION_UI=64 PASS / 0 FAIL
FINAL_WORKTREE=PASS
ALL_STEPS=SUCCESS
```

## Settings and branch lifecycle

```text
BRANCH_PROTECTION_CHANGED=NO
REQUIRED_STATUS_CHECK_ENABLED=NO
REPOSITORY_SETTINGS_CHANGED=NO
FEATURE_BRANCH=DELETED AFTER SEPARATE APPROVAL
DATABASE_DEPLOY_BROWSER_VISUAL_MOBILE=OUT OF SCOPE / NOT RUN
```

No unperformed functional check is claimed as PASS.

```text
PULL_REQUEST_WORKFLOW_TESTING_STATUS=PASS
POST_MERGE_VERIFICATION_STATUS=PASS
CURRENT_CLOSURE_STATUS=COMPLETE
```