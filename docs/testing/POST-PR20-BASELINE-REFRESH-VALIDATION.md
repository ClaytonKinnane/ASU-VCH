# Documentation Validation — Post-PR20 Baseline Refresh

## Финальный статус

```text
DATE: 2026-08-01
DOCUMENTATION_VALIDATION_STATUS: PASS
BASELINE: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
BRANCH: docs/post-pr20-baseline-refresh
CLASSIFICATION: DOCUMENTATION ONLY
EXPECTED_PATH_COUNT: 22
ACTUAL_PATH_COUNT: 22
COMMITS_BEHIND_BASELINE: 0
MERGE_BASE_STATUS: EXACT
MAIN_INTEGRITY_STATUS: PASS
PR_STATUS: NOT_CREATED
MERGE_STATUS: NOT_AUTHORIZED_NOT_PERFORMED
BRANCH_DELETION_STATUS: NOT_AUTHORIZED_NOT_PERFORMED
```

## Repository и scope

Финальный compare branch к baseline подтвердил:

```text
base: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
merge-base: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
branch status: ahead
commits behind: 0
changed paths: 22
```

Все 22 changed paths:

- входят в утверждённый exact allowlist;
- являются Markdown-файлами;
- не затрагивают runtime, config, database, migrations, themes, tools или Git refs.

Allowlist:

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
```

## Main integrity

Сравнение baseline с `main` завершилось:

```text
status: identical
ahead: 0
behind: 0
```

`main` не изменён настоящим инкрементом.

## Pull Request и branch inventory

Search по head `docs/post-pr20-baseline-refresh` вернул 0 Pull Request.

Fresh GitHub inventory:

```text
docs/post-pr20-baseline-refresh
feature/military-positions-directory
feature/public-military-occupational-specialties-directory
main
```

Удаление, перемещение или force-update refs не выполнялись.

## Baseline facts

Living documentation согласованно отражает:

```text
latest functional PR: #20
PR #19: MERGED
PR #19 merge: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #19 final feature head: 5424cefe2f1a6bdc2fa706612040a3985c88f04f
PR #20: MERGED
PR #20 merge / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime: 9db06c4a26066ca25dc36c627c1236089a3c1238
PR #20 final feature head: bea147505a85010b61fe938eb07ec474d76cdab5
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
```

## Functional facts

Military Positions:

```text
migration: 010
tables: 14
triggers: 41
canonical types: 34
variants: 35
rank relation tables: 0
Automated Testing: PASS
Manual Desktop Acceptance: PASS
```

Public Military Occupational Specialties:

```text
migration: 011
tables: 9
triggers: 26
legal sources: 5
official snapshots: 4
training organizations: 4
training programs: 15
searchable records: 17
Automated Testing: PASS
Manual Desktop Acceptance: PASS
Targeted Manual Desktop Recheck: PASS
Final PR Review: PASS
Post-merge Git verification: PASS
```

## Current-state и historical evidence

Подтверждено:

- living docs описывают merged baseline после PR #20;
- current HEAD определяется через `origin/main`;
- exact SHA используются как historical merge/test/refresh anchors;
- documentation-only head не назван runtime-tested;
- VUS implementation, review и runbook содержат post-merge closure;
- historical attempt markers отделены от current status;
- датированные test evidence PR #19/#20 не переписывались.

## Theme, access и environment

Подтверждено:

- theme CSS contract содержит 9 assets;
- `military-occupational-specialties.css` указан для всех трёх themes;
- новые owner-only routes используют `system.*.*`;
- migrations 010 и 011 не добавили permissions;
- installer baseline — 11 migrations;
- Mobile testing остаётся `OUT OF SCOPE / NOT RUN`.

## Test classification

```text
PHP_LINT=NOT_REQUIRED
DEPLOY=NOT_REQUIRED
INSTALLER=NOT_REQUIRED
DATABASE_TESTING=NOT_REQUIRED
HTTP_BROWSER_TESTING=NOT_REQUIRED
RUNTIME_RETEST=NOT_RUN_NOT_REQUIRED
MOBILE_TESTING=OUT_OF_SCOPE_NOT_RUN
```

Причина: diff строго documentation-only.

## Итоговые markers

```text
DOCUMENTATION_IMPLEMENTATION_STATUS=PASS
DOCUMENTATION_VALIDATION_STATUS=PASS
CHANGED_PATH_ALLOWLIST_STATUS=PASS
MARKDOWN_ONLY_STATUS=PASS
BASELINE_FACTS_STATUS=PASS
HISTORICAL_EVIDENCE_PRESERVATION_STATUS=PASS
MAIN_INTEGRITY_STATUS=PASS
PR_STATUS=NOT_CREATED
MERGE_STATUS=NOT_AUTHORIZED_NOT_PERFORMED
BRANCH_DELETION_STATUS=NOT_AUTHORIZED_NOT_PERFORMED
```
