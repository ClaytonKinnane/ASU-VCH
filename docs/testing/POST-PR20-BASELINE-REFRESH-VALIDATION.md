# Documentation Validation — Post-PR20 Baseline Refresh

## Статус

```text
DATE: 2026-08-01
STATUS: PASS PENDING FINAL SELF-INCLUSION CHECK
BASELINE: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
BRANCH: docs/post-pr20-baseline-refresh
CLASSIFICATION: DOCUMENTATION ONLY
EXPECTED_PATH_COUNT: 22
PR: NOT CREATED
MERGE: NOT AUTHORIZED
BRANCH_DELETION: NOT PERFORMED
```

## 1. Pre-validation repository state

До добавления настоящего validation report compare подтвердил:

```text
base: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
merge-base: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
branch status: ahead
commits ahead: 21
commits behind: 0
changed paths before validation report: 21
```

Все 21 path входили в approved allowlist и имели расширение `.md`.

## 2. Main integrity

Сравнение expected baseline с `main`:

```text
base: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
head: main
status: identical
ahead: 0
behind: 0
```

`main` во время Implementation не изменён.

## 3. Pull Request state

Search по head `docs/post-pr20-baseline-refresh` вернул 0 PR.

```text
PR_STATUS=NOT_CREATED
```

## 4. Branch inventory

Fresh GitHub inventory до финальной self-inclusion check:

```text
docs/post-pr20-baseline-refresh
feature/military-positions-directory
feature/public-military-occupational-specialties-directory
main
```

Ни одна branch не удалена и не перемещена настоящим implementation.

## 5. Scope validation

Approved final allowlist:

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

Настоящий файл является 22-м approved path. Финальная compare-проверка выполняется после его создания.

## 6. Baseline fact validation

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

## 7. Functional documentation validation

Military Positions facts:

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

VUS facts:

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

## 8. Current-state / historical-artifact validation

Подтверждено:

- living docs описывают merged baseline после PR #20;
- exact current HEAD определяется через `origin/main`;
- exact SHA используются как historical anchors;
- documentation-only commits не названы runtime-tested;
- VUS implementation/review/runbook получили post-merge closure;
- historical attempt markers сохранены и явно отделены от current status;
- датированные test evidence PR #19/#20 не переписывались.

## 9. Theme/access/environment validation

Подтверждено:

- theme CSS contract содержит 9 assets;
- `military-occupational-specialties.css` указан для всех themes;
- owner-only directory routes используют `system.*.*`;
- migrations 010/011 не добавляют permissions;
- installer baseline — 11 migrations;
- mobile testing остаётся `OUT OF SCOPE / NOT RUN`.

## 10. Safety validation

```text
RUNTIME_PATHS_CHANGED=0
CONFIG_PATHS_CHANGED=0
DATABASE_PATHS_CHANGED=0
THEME_ASSET_PATHS_CHANGED=0
TOOL_PATHS_CHANGED=0
GIT_REFS_DELETED=0
PR_CREATED=0
MERGE_PERFORMED=0
```

Runtime/deploy/database retest не выполнялся и не требовался из-за documentation-only classification.

## 11. Final self-inclusion check

После создания настоящего файла требуется повторно подтвердить:

```text
changed paths: 22
all paths match exact allowlist
all changed paths are Markdown
merge-base: exact baseline
behind: 0
main: unchanged
PR: absent
branches: unchanged
```

После этой проверки status обновляется до final `PASS`.
