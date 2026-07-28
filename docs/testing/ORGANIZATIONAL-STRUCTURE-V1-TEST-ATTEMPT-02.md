# Organizational Structure v1 — Test Attempt 02

## Status

```text
RESULT: FAILED
STAGE: PowerShell test runner startup
BLOCKING DEFECT: UTF-8 without BOM under Windows PowerShell 5.1
AUTOMATED TESTING PASS: NOT CLAIMED
```

## Environment

```text
Windows PowerShell: 5.1
Feature branch: feature/organizational-structure-v1
Tested HEAD: 9e904b88469786f8e6d2df65e3e832df1fd67f73
```

## Successful pre-check

The migration compatibility checker completed successfully:

- seven prepared `CREATE TABLE` statements;
- sixteen prepared `CREATE TRIGGER` statements;
- unsupported MySQL 8.4 auto-increment `CHECK` removed;
- self-parent protection present in insert/update triggers;
- no `DELIMITER` command.

## Failure

`tools/Test-OrganizationalStructureV1.ps1` failed during parsing before any runner action was executed.

Observed parser symptoms:

- unexpected `}` token;
- mojibake in Russian messages;
- unterminated string diagnostics.

Root cause: the new PowerShell scripts were stored as UTF-8 without BOM. Windows PowerShell 5.1 interpreted them using the legacy ANSI code page, corrupting non-ASCII source text.

## Impact

The runner did not perform:

- backup;
- approved deploy;
- migration;
- integration checks;
- regression checks;
- smoke checks.

No PASS status is assigned to this attempt.

## Fix

The following files were rewritten as UTF-8 with BOM:

- `tools/Test-OrganizationalStructureV1.ps1`;
- `tools/Backup-Database.ps1`.

The PHP compatibility checker now verifies the BOM bytes for both scripts before the runner is started.

## Retest gate

Retest from the latest GitHub feature-branch HEAD. First run:

```powershell
php .\tools\check-organizational-structure-migration-compatibility.php
```

The output must include PASS for both Windows PowerShell BOM checks. Then run the fail-fast PowerShell runner.
