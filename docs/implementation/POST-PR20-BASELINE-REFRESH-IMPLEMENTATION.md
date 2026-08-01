# Implementation — Post-PR20 Baseline Refresh

## Current status

```text
DATE: 2026-08-01
STATUS: IMPLEMENTED / PR REVIEW REMEDIATED
BASELINE: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
BRANCH: docs/post-pr20-baseline-refresh
PR: #21 OPEN
CLASSIFICATION: DOCUMENTATION ONLY
INITIAL_APPROVED_PATH_COUNT: 22
FINAL_APPROVED_PATH_COUNT: 25
ACTUAL_PATH_COUNT: 25
PR_CREATION_HEAD: 060ba1e71d8791dac0a85fd9dd257d9b2cf21cfe
REMEDIATION_CONTENT_HEAD: 454a4371461a79f7ef82b41ea6d964d9d4bff4d6
RUNTIME_CHANGE: NONE
DATABASE_CHANGE: NONE
GIT_REF_DELETION: NONE
MERGE: NOT AUTHORIZED
```

`REMEDIATION_CONTENT_HEAD` фиксирует последний commit с содержательными исправлениями. Этот Implementation record и последующий Validation record являются evidence-only commits и не создают самореферентного implementation SHA.

## Initial implementation

Initial scope содержал 22 Markdown-пути:

- 13 living documents;
- 6 process/evidence records refresh;
- 3 VUS operational records.

Initial Documentation Validation завершилась PASS, после чего был создан PR #21 на head `060ba1e...`.

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
3. Implementation record не содержал фактический implementation head.

## Owner-approved remediation

Allowlist расширен с 22 до 25 Markdown-путей добавлением:

```text
docs/implementation/MILITARY-POSITIONS-DIRECTORY-V1-IMPLEMENTATION.md
docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-LOCAL-RUNBOOK.md
docs/review/MILITARY-POSITIONS-DIRECTORY-V1-FORMAL-REVIEW.md
```

Разрешены синхронизация current-state документов, update process records/PR body, repeat Documentation Validation и repeat Final PR Review. Merge и branch deletion не разрешены.

## Final changed-path scope

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

## Baseline facts documented

```text
latest functional PR: #20
PR #19: MERGED
PR #19 merge: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #20: MERGED
PR #20 merge / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime: 9db06c4a26066ca25dc36c627c1236089a3c1238
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
mobile testing: OUT OF SCOPE / NOT RUN
```

## Operational closure

PR #19 и PR #20 operational records теперь разделяют current merged status и historical pre-merge evidence. Increment runbooks помечены как historical; stable operations направлены в `docs/LOCAL-RUNBOOK.md`.

## Test classification

```text
PHP_LINT: NOT_REQUIRED
DEPLOY: NOT_REQUIRED
INSTALLER: NOT_REQUIRED
DATABASE_TESTING: NOT_REQUIRED
HTTP_BROWSER_TESTING: NOT_REQUIRED
RUNTIME_RETEST: NOT_RUN_NOT_REQUIRED
DOCUMENTATION_VALIDATION: REQUIRED
MOBILE_TESTING: OUT_OF_SCOPE_NOT_RUN
```

## Next gate

```text
REPEAT_DOCUMENTATION_VALIDATION: REQUIRED
REPEAT_FINAL_PR_REVIEW: REQUIRED
MERGE_STATUS: NOT_AUTHORIZED
BRANCH_DELETION_STATUS: NOT_AUTHORIZED
```
