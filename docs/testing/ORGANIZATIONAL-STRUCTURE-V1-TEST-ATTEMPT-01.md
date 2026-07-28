# Organizational Structure v1 — Test Attempt 01

## Status

```text
DATE: 2026-07-28
BRANCH: feature/organizational-structure-v1
TESTED HEAD: 062c1be3cd181c77446a83ff23fd02e471015b18
RESULT: FAILED / FIX REQUIRED
PULL REQUEST: NOT CREATED
MERGE: PROHIBITED
```

## Confirmed passes

- GitHub branch synchronization: PASS.
- Working tree integrity before and after the attempt: PASS.
- Deploy `config/local.php` SHA-256 preservation: PASS.
- Repository PHP lint: 97 files, 0 errors.
- Deploy PHP lint: 98 files, 0 errors.
- Static migration counts: 7 tables, 16 triggers, 6 permissions, no `DELIMITER`.
- Existing security regression checks: PASS.
- Military ranks directory regression: PASS.
- Organizational element types directory regression: PASS.
- HTTP smoke `/`, `/health.php`, `/admin/`: PASS.

## Blocking findings

### F-01 — backup wrapper was not compatible with Windows PowerShell 5.1

`tools/Backup-Database.ps1` failed during parsing near its control-character validation. No database backup was created in this attempt.

Resolution: replace the complex PowerShell implementation with a small PowerShell 5.1 wrapper around a PHP CLI backup tool. The PHP tool keeps the password out of process arguments, uses a temporary MySQL defaults file, removes temporary files, verifies the dump, and prints SHA-256.

### F-02 — migration 009 used an unsupported MySQL 8.4 check constraint

MySQL rejected:

```text
chk_org_structure_nodes_self_parent
Check constraint cannot refer to an auto-increment column
```

DDL before the failure was committed by MySQL, leaving a partial empty schema. Migration 009 was not recorded and organization permissions were not inserted.

Resolution: installer compatibility preparation removes the unsupported check for MySQL 8.4, refuses to continue if any unrecorded organization table contains rows, and injects equivalent self-parent rejection into the node insert/update triggers.

### F-03 — test deploy command did not use the approved deploy script

The ad hoc `robocopy` command supplied for this attempt copied theme sources to `themes/` but did not publish them to `public/themes/`. Consequently, theme availability checks failed for the newly required `css/organization.css` asset.

Resolution: use `deploy/Deploy-Local.ps1`, which publishes source themes to `public/themes`. The automated test runner uses this approved deploy path and publishes CLI test tools separately.

### F-04 — pasted interactive command sequence did not stop globally after failure

Although individual commands threw errors, the remaining lines were entered into an interactive PowerShell session and continued executing. Therefore the final printed line `AUTOMATED_TESTING_STATUS=PASS` is invalid and must not be used as a test result.

Resolution: add `tools/Test-OrganizationalStructureV1.ps1` as a single fail-fast runner. A non-zero child exit code terminates the runner before later stages.

## Data integrity

- Existing application data remained available.
- Deploy configuration hash remained unchanged.
- New organization tables observed after the failed migration were empty.
- System permission count remained 19.
- No organization feature acceptance or browser acceptance was performed.
- Mobile PASS is not claimed.

## Retest gate

A new attempt must run in this order:

1. pull the testing-fix commits;
2. execute the single automated runner;
3. require successful backup before migration continuation;
4. require migration and idempotency PASS;
5. require organization checker and all regressions PASS;
6. only then proceed to manual desktop acceptance.
