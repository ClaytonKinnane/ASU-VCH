# Implementation — Post-PR20 Baseline Refresh

## Статус

```text
DATE: 2026-08-01
STATUS: IMPLEMENTED / VALIDATION IN PROGRESS
BASELINE: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
BRANCH: docs/post-pr20-baseline-refresh
CLASSIFICATION: DOCUMENTATION ONLY
APPROVED_PATH_COUNT: 22
RUNTIME_CHANGE: NONE
DATABASE_CHANGE: NONE
GIT_REF_DELETION: NONE
PR: NOT CREATED
MERGE: NOT AUTHORIZED
```

## Выполненный scope

Обновлены 13 living documents:

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
```

Добавлены process records:

```text
docs/architecture/POST-PR20-BASELINE-REFRESH-ARCHITECTURE.md
docs/specification/POST-PR20-BASELINE-REFRESH-SPECIFICATION.md
docs/review/POST-PR20-BASELINE-REFRESH-FORMAL-REVIEW.md
docs/decisions/POST-PR20-BASELINE-REFRESH-APPROVAL.md
docs/implementation/POST-PR20-BASELINE-REFRESH-IMPLEMENTATION.md
docs/testing/POST-PR20-BASELINE-REFRESH-VALIDATION.md
```

Обновлены current operational records VUS:

```text
docs/implementation/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-IMPLEMENTATION.md
docs/testing/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-LOCAL-RUNBOOK.md
docs/review/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-FORMAL-REVIEW.md
```

## Зафиксированный baseline

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
mobile testing: OUT OF SCOPE / NOT RUN
```

## Functional facts documented

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

## Documentation model

Implementation разделяет:

- living current-state documentation;
- historical gate/test artifacts;
- current operational records с post-merge closure.

Exact current `main` определяется через `origin/main`. Exact SHA не используется как постоянно актуальное self-reference field. Documentation-only head не объявляется runtime-протестированным.

## Repository cleanup boundary

На этапе implementation ветки не удалялись. Текущий remote set после создания docs-ветки должен проверяться заново перед cleanup. Cleanup остаётся отдельным post-refresh административным workflow с отдельным owner approval.

## Testing classification

```text
PHP lint: NOT REQUIRED
DEPLOY: NOT REQUIRED
INSTALLER: NOT REQUIRED
DATABASE TESTING: NOT REQUIRED
HTTP/BROWSER TESTING: NOT REQUIRED
RUNTIME RETEST: NOT RUN / NOT REQUIRED
DOCUMENTATION VALIDATION: REQUIRED
MOBILE TESTING: OUT OF SCOPE / NOT RUN
```

Основание: diff ограничен утверждёнными Markdown paths и не изменяет runtime/config/database/themes/tools/Git refs.
