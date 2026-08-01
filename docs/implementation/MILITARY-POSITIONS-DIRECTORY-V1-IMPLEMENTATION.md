# Implementation — Military Positions Directory v1

## Current status

```text
DATE: 2026-08-01
INCREMENT_STATUS: IMPLEMENTED / TESTED / ACCEPTED / MERGED
MIGRATION: 010_military_positions_directory.sql
PR: #19 CLOSED / MERGED
MERGE_COMMIT: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
TESTED_RUNTIME_HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
FINAL_FEATURE_HEAD: 5424cefe2f1a6bdc2fa706612040a3985c88f04f
AUTOMATED_TESTING_STATUS: PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS: PASS
MOBILE_TESTING_STATUS: OUT_OF_SCOPE_NOT_RUN
BRANCH_DELETION_STATUS: PENDING SEPARATE POST-REFRESH APPROVAL
```

Current `main` определяется через `origin/main`. Merge commit и tested runtime head являются historical anchors. Documentation-only commits после tested runtime не объявляются runtime-tested.

## Реализованный scope

- migration 010 с compatibility loader и SHA-256 verification canonical SQL/archive;
- 14 tables и 41 DB trigger;
- whole-catalog lifecycle `building → published → superseded`;
- immutable published data;
- 4 version sources, 24 source entries и 28 evidence records;
- 4 families;
- 34 canonical types и 35 normative variants;
- 2 composition scopes;
- 29 organizational relations;
- no automatic military-rank relation tables;
- owner-only read-only route `/admin/directories/military-positions.php`;
- search и filters;
- integration checker, regressions, source/deploy parity и PowerShell 5.1 runner.

Каталог хранит публичные нормативные типы, а не штатные позиции, кадровые назначения или персональные данные.

## Migration packaging

```text
ARCHIVE_SHA256: af617b754e4a8a5b453d6856f5c20540edb72d839fb162e61f9c160493c6fb82
CANONICAL_SQL_SHA256: 3ebb00dc2d89027eea7f3619deb29adfdcdea7b67b9a221b4ab0cd159d96ac78
BASE64_PARTS: 5
```

Loader fail-closed проверяет parts, archive hash, decompression и canonical SQL hash до выполнения migration.

## Testing outcome

Automated Testing на `0455f012...` подтвердил:

```text
implementation scope: PASS
backup: PASS
deploy/config preservation: PASS
PHP lint: 108 files / 0 errors
applied migrations: 10
repeat installer: no new migrations
military positions checker: PASS
security/theme/directory regressions: PASS
organization regression: 58 PASS / 0 FAIL
source/deploy parity: PASS
HTTP smoke: PASS
working tree: clean
```

Manual Desktop Acceptance подтвердил owner access, ordinary-role HTTP 403, default count 34, search/filters, normative variants, official links, three themes, desktop 1920×1080 и 1366×768, console errors 0 и asset/HTTP 404 0.

Подробные historical evidence:

- `docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-AUTOMATED-TESTING-2026-08-01.md`;
- `docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-MANUAL-DESKTOP-ACCEPTANCE-2026-08-01.md`.

Их pre-PR markers сохраняют состояние соответствующего момента и не заменяют current status выше.

## Post-merge closure

```text
PR_STATE: CLOSED
MERGED: YES
MERGE_METHOD: MERGE COMMIT
MERGE_COMMIT: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
FINAL_FEATURE_HEAD: 5424cefe2f1a6bdc2fa706612040a3985c88f04f
RUNTIME_RETEST_ON_MERGE_COMMIT: NOT RUN / NOT CLAIMED
```

PR #19 был объединён после отдельного merge approval. Merge не изменяет значение tested runtime head: им остаётся `0455f012...`.

## Current next gate

Feature-ветка сохраняется до завершения Post-PR20 Baseline Refresh, fresh inventory и отдельного owner approval exact cleanup batch.
