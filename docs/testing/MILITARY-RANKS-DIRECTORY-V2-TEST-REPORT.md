# Test Report — Справочник воинских званий v2

## Historical testing result

```text
date: 2026-08-03
result: PASS
branch at test gate: feature/military-ranks-directory-v2
runtime/manual acceptance head: b44aed14ee1a54be213cbc939322ba21b02e7a58
Final PR Review remediation head: fe893e8315f7add80ed4d0501b41d8bc39b4b0e8
```

## Target environment

```text
Windows / Open Server Panel 6.5.1 / Apache
PHP 8.5.4 / MySQL 8.4.8 / PowerShell 5.1
https://asu-vch.local
```

## Static and source checks

PASS:

- PHP lint affected implementation/checkers;
- source checker;
- loader checker;
- compatibility-service checker;
- UTF-8/control-byte/damaged-token scan;
- exact 18 DROP/CREATE trigger declarations;
- clean worktree.

## Migration and installer

Before migration 012: 11 migrations and current v1. After application:

```text
migration 012: registered
applied migrations: 12
repeat installer: no new migrations
v1: superseded
v2: published/current
duplicate publication: absent
```

## Backup evidence and deviation

Pre-migration backup was not created because the first preflight block stopped before `mysqldump`; later commands applied migration 012. This cannot be reconstructed retrospectively and remains an explicit process deviation.

Post-migration backup:

```text
DATABASE_NAME=asu_vch
MYSQLDUMP_VERSION=8.4.8
BACKUP_FILE=C:\Project\ASU-VCH-backups\asu_vch-20260803-095418.sql
BACKUP_SIZE_BYTES=436258
BACKUP_SHA256=C392283F93212B1DD88DF9261C26FB741765F3E27C8B67E1F646B3F79065B7AB
```

## Integration and regression

PASS confirmed:

- v1: 6 compositions, 20 ranks, no Staffing semantics;
- v2: 8 compositions, 8 semantics, 20 ranks;
- v2 sources: 2 version / 8 composition;
- compatibility/incompatibility and ancestry cases;
- filter counts;
- three theme assets;
- Reference/Security/Theme/Organization regressions;
- Organization UI: 64 PASS / 0 FAIL;
- Organization integration: 58 PASS / 0 FAIL;
- source/deploy parity: 24/24;
- HTTP smoke and clean worktree.

## UI remediation and manual desktop acceptance

Initial blocking layout defect was remediated. One-column hierarchy, parent/child grouping, connectors and concise labels were accepted in all three themes at 1920×1080 and 1366×768.

```text
current v2: PASS
historical v1: PASS
version switching: PASS
search/filters/empty state: PASS
read-only UI: PASS
official links: PASS
non-owner HTTP 403: PASS
console errors: 0
HTTP/asset 404: 0
defects: NONE
```

## Final PR Review remediation

Initial Final PR Review found overly broad recovery source validation. Exact version/composition source matchers and negative scenarios were added.

```text
source checker: PASS
loader checker: PASS
compatibility service: PASS
contradictory source/order/pairing/note/role: REJECTED
repeat installer: 12 / no new migrations
DB regression: PASS
worktree: clean
```

## Historical merge gate

At the test-report gate, merge remained prohibited without separate permission and branch deletion required a later separate permission. This statement remains historical evidence.

## Post-merge and branch-lifecycle closure

```text
PR: #24 CLOSED / MERGED
FINAL_FEATURE_HEAD=2e996849ec51be4d83676aa779bf7e797e35932e
MERGE_COMMIT=feac7230616d3a8df98acb48f43a0b60f89f2255
POST_MERGE_VERIFICATION=PASS
FEATURE_HEAD_INCLUDED_IN_MAIN=PASS
MERGE_TREE_EQUALS_FEATURE_TREE=PASS
STATIC_CHECKS=PASS
DEPLOY=PASS
SOURCE_DEPLOY_PARITY=PASS
REPEAT_INSTALLER=12 / NO NEW MIGRATIONS
DATABASE_REGRESSION=PASS
HTTP_SMOKE=PASS
WORKTREE_CLEAN=PASS
FEATURE_BRANCH=DELETED AFTER SEPARATE APPROVAL
MOBILE=OUT OF SCOPE / NOT RUN
```

The merge commit is not relabeled as the original runtime/manual acceptance head. Post-merge verification is separate evidence.

```text
FINAL_TEST_AND_CLOSURE_RESULT=PASS
BLOCKING_FINDINGS_OPEN=0
DEFECTS=NONE
```