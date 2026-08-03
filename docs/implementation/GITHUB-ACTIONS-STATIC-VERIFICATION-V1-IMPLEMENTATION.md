# GitHub Actions Static Verification v1 — Implementation

## Historical implementation gate

```text
date: 2026-08-03
base: main @ feac7230616d3a8df98acb48f43a0b60f89f2255
branch: feature/github-actions-static-verification-v1
status at implementation gate: IMPLEMENTED / PR VERIFICATION PENDING
```

The pending status above was correct before PR evidence. Current closure is recorded below.

## Implemented workflow

Path: `.github/workflows/static-verification.yml`.

```text
workflow: ASU-VCH Static Verification
job: asu-vch-static-verification
runner: ubuntu-24.04
PHP: 8.5.x
timeout: 10 minutes
coverage: none
Composer tools: none
```

Triggers:

- Pull Request to `main`;
- push to `main`;
- `workflow_dispatch`.

## Security implementation

- `permissions: contents: read`;
- no secrets, environments, OIDC or write permissions;
- no `pull_request_target`;
- immutable action SHA;
- `fetch-depth: 0`;
- `persist-credentials: false`;
- no cache/artifacts/services;
- no DB/deploy/network-dependent repository commands;
- final tracked/untracked worktree verification.

## Verification pipeline

1. PHP 8.5 runtime and initial clean checkout;
2. event-aware `git diff --check`;
3. NUL-safe lint of tracked PHP;
4. 9 explicit CI-safe checker entrypoints;
5. final clean-worktree guard.

PR uses payload base/head SHA; push uses `before`/current SHA with zero-before fallback; manual run uses parent/current or empty-tree root fallback.

## Checker allowlist

- theme asset failure;
- all-theme directory assets;
- Organization migration compatibility;
- Organization UI polish;
- VUS UI;
- Military Rank compatibility service;
- Military Rank v2 loader;
- Military Ranks v2 source;
- Military Ranks v2 UI layout.

DB, hybrid, Windows, deploy and repository-mutating adapters are excluded.

## Historical remaining gates

At the implementation record stage, PR workflow, Final PR Review, merge and branch deletion were not yet complete and were correctly prohibited without their gates.

## Post-merge and branch-lifecycle closure

```text
PR: #25 CLOSED / MERGED
EXACT_FINAL_PR_HEAD=0c6f7338f912e8797868d02d54fc015df7533ad6
MERGE_COMMIT=c567429b3aa4d629a4e7c11fec7e3dbae907d92e
FINAL_PR_REVIEW=PASS
PUSH_RUN=30837637886 / SUCCESS
WORKFLOW_DISPATCH_RUN=30839122892 / SUCCESS
RUNNER=Ubuntu 24.04.4
PHP=8.5.9
TRACKED_PHP=124 / 0 ERRORS
CI_SAFE_CHECKERS=9 / PASS
ORGANIZATION_UI=64 PASS / 0 FAIL
FINAL_WORKTREE=PASS
BRANCH_PROTECTION_CHANGED=NO
REQUIRED_STATUS_CHECK_ENABLED=NO
REPOSITORY_SETTINGS_CHANGED=NO
FEATURE_BRANCH=DELETED AFTER SEPARATE APPROVAL
DB_DEPLOY_BROWSER_VISUAL_MOBILE=OUT OF SCOPE / NOT RUN
```

Merge occurred only after separate exact-head owner approval. Push and manual runs verified the merge commit. Branch deletion occurred later under a separate approval.

The Node.js 20 deprecation annotation for pinned checkout was non-blocking; any action SHA refresh is a separate maintenance increment.

```text
CURRENT_INCREMENT_OUTCOME=IMPLEMENTED / PR_VERIFIED / FINAL_REVIEWED / MERGED / POST_MERGE_VERIFIED / BRANCH_CLEANED
STAGE_B_REQUIRED_CHECK=NOT ENABLED
```