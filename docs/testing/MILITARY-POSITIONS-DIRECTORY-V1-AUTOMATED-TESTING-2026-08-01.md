# Automated Testing Evidence: Справочник типов воинских должностей ВС РФ v1

## Статус

```text
DATE: 2026-08-01
BRANCH: feature/military-positions-directory
TESTED HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
BASE / MERGE-BASE: 8cc604eec7e973c2917ea0b1f9b08b976b673f41
AUTOMATED_TESTING_STATUS: PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS: NOT RUN
MOBILE_TESTING_STATUS: OUT OF SCOPE / NOT RUN
PR: NOT CREATED
MERGE: NOT AUTHORIZED
```

## Repository preflight

```text
current branch: feature/military-positions-directory
local HEAD == origin feature HEAD: true
divergence: 0 0
merge-base matches approved baseline: true
implementation file count: 21
working tree before testing: clean
IMPLEMENTATION_SCOPE_STATUS=PASS
```

## Backup and deploy

```text
BACKUP_STATUS=PASS
DATABASE_NAME=asu_vch
MYSQLDUMP_VERSION=8.4.8
BACKUP_FILE=C:\OSPanel\backups\asu-vch\asu_vch-20260801-071408.sql
BACKUP_SIZE_BYTES=295871
BACKUP_SHA256=F99BDF9BD39498E24E3BF4B9CCACF45650B79B5F5639C0C44C6226546B42EEDF
DEPLOY_ROOT=C:\OSPanel\home\asu-vch.local
DEPLOY_CONFIG_LOCAL_SHA256_BEFORE=D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
DEPLOY_CONFIG_LOCAL_SHA256_AFTER_DEPLOY=D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
```

## PHP and installer

```text
PHP files linted: 108
PHP lint errors: 0
applied migrations: 10
migration 010: already applied and registered
repeat installer: no new migrations
```

## Military positions checker

```text
migration archive and canonical SQL hashes: PASS
tables declared / present: 14 / 14
triggers declared / present: 41 / 41
published catalog versions: 1
version sources: 4
source entries: 24
source-entry evidence: 28
families: 4
canonical types: 34
type-family relations: 34
variants: 35
composition scopes: 2
composition scope members: 3
type-composition relations: 34
composition evidence: 35
organizational relations: 29
organizational evidence: 29
system permissions: 25
rank relation tables: 0
MILITARY_POSITIONS_DIRECTORY_CHECK=PASS
```

Rejection tests passed:

- published child insert rejected;
- backward lifecycle transition rejected;
- tariff grade outside 1–50 rejected;
- cross-version composition evidence rejected;
- cross-version organizational evidence rejected.

## Regressions

```text
all theme directory assets: PASS
military ranks directory regression: PASS
organizational elements directory regression: PASS
security RBAC: PASS
user approval: PASS
required password change: PASS
user rejection: PASS
archive/restore: PASS
theme management: PASS
theme missing-asset behavior: PASS
organization regression: 58 PASS / 0 FAIL
```

## Source/deploy parity and HTTP smoke

```text
SOURCE_DEPLOY_PARITY_STATUS=PASS
/            HTTP 200
/health.php  HTTP 200
/admin/      HTTP 302 anonymous
```

## Post-test integrity

```text
DEPLOY_CONFIG_LOCAL_SHA256_FINAL=D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
FINAL_HEAD=0455f0120c881bb9ba6e9df8f80ea0af89819be9
FINAL_ORIGIN_FEATURE_DIVERGENCE=0 0
FINAL_WORKING_TREE_CLEAN=True
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=NOT_RUN
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
COMMIT_PUSH_PR_STATUS=IMPLEMENTATION_COMMIT_ALREADY_ON_GITHUB_PR_NOT_CREATED
```

## Следующий gate

До PR обязательна ручная desktop-приёмка owner-only read-only справочника в трёх встроенных темах. Mobile testing остаётся вне scope и не заявляется как PASS.
