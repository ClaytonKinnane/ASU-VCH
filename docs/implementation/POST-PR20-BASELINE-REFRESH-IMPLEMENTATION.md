# Implementation — Post-PR20 Baseline Refresh

## Current outcome

```text
DATE: 2026-08-01
STATUS: COMPLETED / MERGED / POST-MERGE VERIFIED / CLEANUP VERIFIED
BASELINE: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
HISTORICAL_BRANCH: docs/post-pr20-baseline-refresh
TRACKING_PR: #21 CLOSED / MERGED
CLASSIFICATION: DOCUMENTATION ONLY
INITIAL_APPROVED_PATH_COUNT: 22
FINAL_APPROVED_PATH_COUNT: 25
ACTUAL_PATH_COUNT: 25
PR_CREATION_HEAD: 060ba1e71d8791dac0a85fd9dd257d9b2cf21cfe
INITIAL_REMEDIATION_CONTENT_HEAD: 454a4371461a79f7ef82b41ea6d964d9d4bff4d6
FINAL_REMEDIATION_CONTENT_HEAD: 7b7f9d4c945d4f2abb70b36b1b437908cef5ed17
VALIDATED_IMPLEMENTATION_HEAD: 092e09b10c5509ff9976782a0bc757ff597b0200
FINAL_PR_HEAD: 4d44874ef02ffb9381334acfabfa383eba3e4ead
MERGE_COMMIT: f5b53f2ee4453f293b58cbe486e0943ab602335b
RUNTIME_CHANGE: NONE
DATABASE_CHANGE: NONE
FINAL_PR_REVIEW: PASS
POST_MERGE_VERIFICATION: PASS
BRANCH_CLEANUP: PASS
```

`FINAL_REMEDIATION_CONTENT_HEAD` фиксирует последний substantive documentation commit remediation. Последующие Implementation/Validation commits являются evidence-only. Ни один documentation head не объявляется runtime-tested.

## Initial implementation

Initial scope содержал 22 Markdown-пути: 13 living documents, 6 process/evidence records и 3 VUS operational records. Initial Documentation Validation завершилась PASS, после чего был создан PR #21 на head `060ba1e...`.

## Final PR Review attempt 1

```text
REVIEW_ID: 4835099195
RESULT: CHANGES REQUIRED
BLOCKING_FINDINGS: 2
MINOR_FINDINGS: 1
```

Findings:

1. отсутствовал post-merge closure трёх operational records PR #19;
2. current-state documents продолжали утверждать `PR not created` после создания PR #21;
3. Implementation record не содержал фактический implementation/PR head.

## Owner-approved remediation

Allowlist расширен с 22 до 25 Markdown-путей добавлением:

```text
docs/implementation/MILITARY-POSITIONS-DIRECTORY-V1-IMPLEMENTATION.md
docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-LOCAL-RUNBOOK.md
docs/review/MILITARY-POSITIONS-DIRECTORY-V1-FORMAL-REVIEW.md
```

Также были разрешены current-state synchronization, process/PR metadata updates, repeat Validation и repeat Final PR Review. На этом этапе merge и branch deletion оставались не разрешены.

## Final scope

```text
README.md
docs/README.md
docs/PROJECT-STATUS.md
docs/PROJECT.md
docs/DEVELOPMENT.md
docs/ENVIRONMENT.md
docs/LOCAL-RUNBOOK.md
docs/DATABASE-CURRENT.md
docs/THEMES.md
docs/ACCESS.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/ARCHITECTURAL-PATTERNS.md
docs/architecture/POST-PR20-BASELINE-REFRESH-ARCHITECTURE.md
docs/specification/POST-PR20-BASELINE-REFRESH-SPECIFICATION.md
docs/review/POST-PR20-BASELINE-REFRESH-FORMAL-REVIEW.md
docs/decisions/POST-PR20-BASELINE-REFRESH-APPROVAL.md
docs/implementation/POST-PR20-BASELINE-REFRESH-IMPLEMENTATION.md
docs/testing/POST-PR20-BASELINE-REFRESH-VALIDATION.md
docs/implementation/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-IMPLEMENTATION.md
docs/testing/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-LOCAL-RUNBOOK.md
docs/review/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-FORMAL-REVIEW.md
docs/implementation/MILITARY-POSITIONS-DIRECTORY-V1-IMPLEMENTATION.md
docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-LOCAL-RUNBOOK.md
docs/review/MILITARY-POSITIONS-DIRECTORY-V1-FORMAL-REVIEW.md
```

## Baseline facts

```text
latest functional PR: #20
PR #19: MERGED
PR #19 merge: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #20: MERGED
PR #20 merge / functional refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime: 9db06c4a26066ca25dc36c627c1236089a3c1238
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
mobile testing: OUT OF SCOPE / NOT RUN
```

## Remediation outcome

- PR #19 и PR #20 operational records получили post-merge closures;
- increment runbooks отделены от stable runbook;
- living docs перестали хранить transient PR state как постоянно актуальное поле;
- exact PR states и heads остались в датированных evidence records;
- runtime/config/database/migrations/theme/tool/Git ref changes отсутствовали.

## Final PR Review attempt 2

Повторный Final PR Review выполнен на exact final head:

```text
HEAD: 4d44874ef02ffb9381334acfabfa383eba3e4ead
REVIEW_ID: 4835150606
RESULT: PASS
BLOCKING_FINDINGS: 0
MAJOR_FINDINGS: 0
MINOR_FINDINGS: 0
```

Все findings attempt 1 закрыты.

## Merge and post-merge closure

После отдельного owner approval PR #21 объединён методом merge commit.

```text
PR_STATE: CLOSED
PR_MERGED: TRUE
MERGE_METHOD: MERGE COMMIT
MERGE_COMMIT: f5b53f2ee4453f293b58cbe486e0943ab602335b
MAIN_EQUALS_MERGE_COMMIT: PASS
PR_HEAD_IS_MERGE_PARENT: PASS
FILE_TREE_PARITY: PASS
POST_MERGE_VERIFICATION_STATUS: PASS
```

Merge не изменял runtime-tested anchor PR #20.

## Branch cleanup closure

После отдельного owner approval выполнен remote-first cleanup:

```text
REMOTE_BRANCHES_APPROVED: 3
REMOTE_BRANCHES_DELETED: 3 / 3
LOCAL_BRANCHES_APPROVED: 13
LOCAL_BRANCHES_DELETED: 13 / 13
TERMINAL_REMOTE_BRANCH_COUNT: 1
TERMINAL_REMOTE_BRANCH: main
TERMINAL_LOCAL_BRANCH_COUNT: 1
TERMINAL_LOCAL_BRANCH: main
FINAL_LOCAL_MAIN: f5b53f2ee4453f293b58cbe486e0943ab602335b
FINAL_ORIGIN_MAIN: f5b53f2ee4453f293b58cbe486e0943ab602335b
WORKING_TREE: CLEAN
FORCE_DELETION_USED: NO
TERMINAL_VERIFICATION_STATUS: PASS
```

Historical branch `docs/post-pr20-baseline-refresh` удалена и не является operational dependency.

Evidence: [Post-PR21 Merge and Cleanup Closure 2026-08-01](../POST-PR21-MERGE-CLEANUP-CLOSURE-2026-08-01.md).

## Test classification

```text
PHP_LINT: NOT_REQUIRED
DEPLOY: NOT_REQUIRED
INSTALLER: NOT_REQUIRED
DATABASE_TESTING: NOT_REQUIRED
HTTP_BROWSER_TESTING: NOT_REQUIRED
RUNTIME_RETEST: NOT_RUN_NOT_REQUIRED
DOCUMENTATION_VALIDATION: PASS
MOBILE_TESTING: OUT_OF_SCOPE_NOT_RUN
```

## Closed gate

PR #21 Implementation workflow завершён. Его прежние pre-merge `NOT AUTHORIZED` markers были точными на момент записи и закрыты последующими отдельными approvals, merge и cleanup evidence.

Новых действий по PR #21 не требуется.
