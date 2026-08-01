# Implementation — Public Military Occupational Specialties v1

## Current status

```text
INCREMENT_STATUS: IMPLEMENTED / TESTED / ACCEPTED / REVIEWED / MERGED
MIGRATION: 011_public_military_occupational_specialties_directory.sql
IMPLEMENTATION_PATHS_IN_PR: 25
PR: #20 CLOSED / MERGED
MERGE_COMMIT: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
TESTED_RUNTIME_HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
FINAL_FEATURE_HEAD: bea147505a85010b61fe938eb07ec474d76cdab5
AUTOMATED_TESTING_STATUS: PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS: PASS
TARGETED_MANUAL_DESKTOP_RECHECK_STATUS: PASS
FINAL_PR_REVIEW_STATUS: PASS
POST_MERGE_GIT_VERIFICATION_STATUS: PASS
MOBILE_TESTING_STATUS: OUT_OF_SCOPE_NOT_RUN
BRANCH_DELETION_STATUS: PENDING SEPARATE POST-REFRESH APPROVAL
```

Точный current `main` определяется через `origin/main`. `3082ec6...` является merge/refresh anchor, а `9db06c4...` — последним полностью runtime-протестированным head. Documentation-only commits после него не объявляются runtime-протестированными.

## Реализовано

- source-centric schema из 9 tables и 26 triggers;
- exact seed: 5 legal sources, 4 official snapshots, 3 code segments, 6 context domains, 3 personnel scopes, 2 direct disclosures, 4 training organizations, 15 training programs;
- 17 searchable records;
- read-only repository и bootstrap factory;
- owner-only GET route `/admin/directories/military-occupational-specialties.php`;
- search и filters по record type, identifier kind, personnel scope, organization, evidence level и status;
- testable result-composition policy для organization filter;
- static/integration/UI checker'ы;
- PowerShell 5.1 testing runner;
- VUS-specific stylesheet во всех трёх themes;
- отсутствие relations к positions, ranks, equipment и persons;
- обязательное предупреждение о неполноте public coverage.

## Migration packaging

Canonical SQL выполняется через compatibility loader из двух упорядоченных gzip/base64 parts.

```text
CANONICAL_SQL_BYTES: 88267
CANONICAL_SQL_SHA256: 26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9
GZIP_ARCHIVE_BYTES: 9472
GZIP_ARCHIVE_SHA256: 1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39
BASE64_PARTS: 2
```

Loader fail-closed проверяет archive hash, распаковывает canonical SQL и проверяет SQL hash до передачи installer.

## Historical implementation and testing chronology

Следующие записи сохраняют состояние соответствующего этапа. Их прежние `NOT AUTHORIZED`, `NOT CREATED` и `RECHECK REQUIRED` markers являются историческими и не заменяют current status выше.

### Local Testing attempt 1

```text
DATE: 2026-08-01
RESULT: PRE-EXECUTION PARSER FAILURE
BACKUP: NOT STARTED
DEPLOY: NOT STARTED
INSTALLER: NOT STARTED
DATABASE CHANGES: NONE
```

Windows PowerShell 5.1 неверно разобрал UTF-8 without BOM с кириллическими строками. Runner был переведён на ASCII-only policy.

```text
FIX COMMIT: fb28a8d071fb871c0a0f7bc39042bb7331b4771e
```

### Automated Testing attempt 2

```text
DATE: 2026-08-01
HEAD: 289b6f1c4e77843e5d650b46c480cd44aa6c8eae
RESULT: PASS
MIGRATION 011: APPLIED
REPEATED INSTALLER: PASS
PHP FILES LINTED: 112
SOURCE/DEPLOY PARITY: PASS
HTTP SMOKE: PASS
WORKING TREE: CLEAN
```

### Manual Desktop Acceptance attempt 1

```text
RESULT: FAIL / DEFECTS CONFIRMED
MOBILE: OUT OF SCOPE / NOT RUN
```

Зафиксированы шесть UI defects:

1. неполная русификация labels;
2. недостаточные интервалы между секциями;
3. технические fingerprints/hashes в пользовательском UI;
4. inconsistent interactive/static card elevation;
5. слишком узкая source column;
6. лишнее bottom whitespace.

### UI remediation

- visible labels локализованы;
- evidence fingerprints сохранены в DB/checker, но скрыты в UI;
- section spacing унифицирован;
- lift оставлен только linked cards;
- static cards не используют dashboard hover behavior;
- table proportions: 26% / 22% / 24% / 28%;
- source wrapping исправлен;
- bottom warning compact;
- VUS stylesheet зарегистрирован во всех themes;
- добавлен `check-military-occupational-specialties-ui.php`;
- runner расширен до 24 paths и 14 runtime parity paths.

### Automated Testing attempt 3

```text
HEAD: 2ec9eb1866ed59cdf3411bbed1e145abc7d12fc2
RESULT: TEST-CHECKER PATH DEFECT
BACKUP: PASS
DEPLOY: PASS
PHP FILES LINTED: 113
INSTALLER TWICE: PASS
CORE VUS CHECKER: PASS
UI CHECKER: FAIL BEFORE STYLESHEET ASSERTIONS
DATABASE CHANGES: NONE
```

UI checker искал CSS только в source `themes/...`, хотя deploy path — `public/themes/...`.

```text
FIX COMMIT: 09b032ba39c75d17f87aa003d1df13ddedcd5b2d
```

### Automated Testing attempt 4

```text
HEAD: ed73780fc3f34aa0e19cc7d168d366832d5dae79
RESULT: THEME REGRESSION EXPECTATION DEFECT
BACKUP: PASS
DEPLOY: PASS
PHP FILES LINTED: 113
INSTALLER TWICE: PASS
CORE VUS CHECKER: PASS
UI CHECKER: PASS
DIRECTORY REGRESSIONS: PASS
SECURITY REGRESSIONS BEFORE THEME CHECK: PASS
THEME MANAGEMENT CHECK: FAIL ON EXACT REQUIRED-ASSET LIST
DATABASE CHANGES: NONE
```

Theme regression expected прежний asset list без VUS stylesheet.

```text
FIX COMMIT: feaae262033468fc64459ae4b64d0f85be7e9040
```

### Automated Testing attempt 5

```text
HEAD: c71a2959f30c7aa570ca5120115aff81f9054625
RESULT: SOURCE/DEPLOY PARITY PATH DEFECT
BACKUP: PASS
DEPLOY: PASS
PHP FILES LINTED: 113
INSTALLER TWICE: PASS
CORE VUS CHECKER: PASS
UI CHECKER: PASS
DIRECTORY REGRESSIONS: PASS
SECURITY REGRESSIONS: PASS
THEME REGRESSIONS: PASS
ORGANIZATION REGRESSION: PASS / 58 OF 58
SOURCE/DEPLOY PARITY: FAIL ON FIRST THEME ASSET PATH
DATABASE CHANGES: NONE
```

Runner сопоставлял source `themes/...` с неверным deploy path вместо `public/themes/...`.

```text
FIX COMMIT: e9d865990756a70fbbf8d85ee5074b8e518a5a24
```

### Automated Testing attempt 6

```text
DATE: 2026-08-01
HEAD: e1bf5c85708cfa29d3a0356368938345eb2064e2
RESULT: PASS
IMPLEMENTATION PATHS: 24
BACKUP: PASS
BACKUP FILE: C:\OSPanel\backups\asu-vch\asu_vch-20260801-140604.sql
BACKUP SIZE BYTES: 389877
BACKUP SHA-256: 90C2368DB9C82F83C0A856D3238CD717D3F5591567D2BB967C6646588B534EA2
DEPLOY: PASS
PHP FILES LINTED: 113
INSTALLER TWICE: PASS / NO NEW MIGRATIONS
CORE VUS CHECKER: PASS
UI CHECKER: PASS
DIRECTORY REGRESSIONS: PASS
SECURITY REGRESSIONS: PASS
THEME REGRESSIONS: PASS
ORGANIZATION REGRESSION: PASS / 58 OF 58
SOURCE/DEPLOY PARITY: PASS / 14 PATHS
HTTP SMOKE: PASS
FINAL ORIGIN FEATURE DIVERGENCE: 0 0
FINAL WORKING TREE: CLEAN
```

Historical final markers:

```text
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=NOT_RUN
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
PR_STATUS=NOT_CREATED
```

### Manual Desktop Acceptance attempt 2

```text
DATE: 2026-08-01
RUNTIME_HEAD: e1bf5c85708cfa29d3a0356368938345eb2064e2
RESULT: PASS
THEMES: 3 OF 3
DESKTOP RESOLUTIONS: 1920x1080 AND 1366x768
CONSOLE ERRORS: 0
HTTP OR ASSET 404: 0
DEFECTS: NONE
MOBILE: OUT OF SCOPE / NOT RUN
```

Evidence: `docs/testing/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-MANUAL-DESKTOP-ACCEPTANCE-2026-08-01.md`.

### Pull Request creation

```text
PR: #20
STATE AT CREATION: OPEN
BASE: main
HEAD BEFORE FINAL REVIEW REMEDIATION: 42aa35bae08625595449697bbe684b962f052d4c
MERGE AT THAT GATE: NOT AUTHORIZED
BRANCH DELETION AT THAT GATE: NOT AUTHORIZED
```

### Final PR Review attempt 1

```text
DATE: 2026-08-01
RESULT: CHANGES REQUIRED
BLOCKING FINDINGS: 2
```

Findings:

1. `record_type=all + organization` сохранял unrelated direct-disclosure rows;
2. runner ожидал 24 paths при фактических 25.

Также требовалась metadata/body synchronization.

### Final PR Review remediation

- добавлена `shouldSearchPublicDisclosures(recordType, organization)`;
- organization исключает direct-disclosure rows;
- `direct-disclosure + organization` даёт empty result;
- checker покрывает пять combinations и repository filter against control SQL;
- runner exact scope обновлён до 25 paths;
- Specification, Test Plan, Runbook, Implementation и PR body синхронизированы.

Schema, seed, permissions и theme assets remediation не меняла.

### Automated Testing attempt 7 — remediation

```text
DATE: 2026-08-01
HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
RESULT: PASS
IMPLEMENTATION PATHS: 25
BACKUP: PASS
BACKUP FILE: C:\OSPanel\backups\asu-vch\asu_vch-20260801-153344.sql
BACKUP SIZE BYTES: 389878
BACKUP SHA-256: 8D757448B22CB66AC77EDF7E1B3A1E6EAFFCB2C41988BB3830967934582B386C
DEPLOY: PASS / 153 FILES
PHP FILES LINTED: 113 / 0 ERRORS
INSTALLER TWICE: PASS / NO NEW MIGRATIONS
CORE VUS CHECKER: PASS
ORGANIZATION FILTER POLICY REGRESSIONS: PASS
ORGANIZATION REPOSITORY FILTER: PASS
UI CHECKER: PASS
DIRECTORY REGRESSIONS: PASS
SECURITY REGRESSIONS: PASS
THEME REGRESSIONS: PASS
ORGANIZATION REGRESSION: PASS / 58 OF 58
SOURCE/DEPLOY PARITY: PASS / 14 PATHS
HTTP SMOKE: PASS
FINAL ORIGIN FEATURE DIVERGENCE: 0 0
FINAL WORKING TREE: CLEAN
```

Historical final markers at this gate:

```text
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=TARGETED_RECHECK_REQUIRED
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
PR_STATUS=OPEN_20_NOT_MERGED
```

### Targeted Manual Desktop Recheck

Результат: `PASS`.

```text
INITIAL_TOTAL_17=PASS
ALL_PLUS_FINANCIAL_UNIVERSITY_8=PASS
ALL_PLUS_MIIGAIK_4=PASS
ALL_PLUS_CHGU_2=PASS
ALL_PLUS_OSU_1=PASS
ONLY_SELECTED_ORGANIZATION_ROWS=PASS
NORMATIVE_EXAMPLES_EXCLUDED_WITH_ORGANIZATION=PASS
DIRECT_DISCLOSURE_PLUS_ORGANIZATION_EMPTY=PASS
TRAINING_PROGRAM_PLUS_ORGANIZATION=PASS
RESET_RETURNS_17=PASS
CONSOLE_ERRORS=0
HTTP_OR_ASSET_404=0
DEFECTS=NONE
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
```

### Final PR Review attempt 2

```text
RESULT: PASS
BLOCKING_FINDINGS: 0
MAJOR_FINDINGS: 0
MINOR_FINDINGS: 0
```

Final review evidence commit:

```text
bea147505a85010b61fe938eb07ec474d76cdab5
```

## Merge and post-merge closure

Отдельное owner approval разрешило merge PR #20 методом merge commit и потребовало post-merge verification без удаления ветки.

```text
PR_STATE: CLOSED
MERGED: YES
MERGE_COMMIT: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
MERGE_METHOD: MERGE COMMIT
POST_MERGE_GIT_VERIFICATION_STATUS: PASS
FEATURE_BRANCH_PRESERVED: YES
```

Post-merge Git/GitHub verification подтвердила:

- `main` идентичен merge commit;
- feature head `bea1475...` достижим из `main` и является parent merge commit;
- file trees совпадают;
- key runtime route присутствует в `main`;
- PR закрыт как merged;
- feature branch не удалена.

Локальный runtime/deploy retest непосредственно на merge commit не выполнялся и не заявляется. Runtime evidence остаётся привязанным к `9db06c4...`; последующие commits `63890e...`, `835321b...`, `bea1475...` были documentation-only.

## Current next gate

Post-PR20 Baseline Refresh актуализирует living documentation. После его собственного Validation → PR → Final Review → merge потребуется fresh branch inventory и отдельное точное approval на cleanup.

```text
PULL_REQUEST_FOR_BASELINE_REFRESH: NOT CREATED
MERGE_FOR_BASELINE_REFRESH: NOT AUTHORIZED
REMOTE_BRANCH_DELETION: NOT AUTHORIZED / NOT PERFORMED
LOCAL_BRANCH_DELETION: NOT AUTHORIZED / NOT PERFORMED
```
