# GitHub Local Automation One-Command Bootstrap — Approval

Status: `APPROVED FOR IMPLEMENTATION`

Date: `2026-08-05`

## Approved anchors

```text
main: d9c7db67ee2df8204281d67a4c134528a499573b
branch: tools/github-local-automation-bootstrap
approved head: 6a1380ad6d40dc8924b37740e7f16db9183708a8
merge-base: d9c7db67ee2df8204281d67a4c134528a499573b
behind main: 0
```

## Approved design

The owner approved:

- `GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-ARCHITECTURE.md`;
- `GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-SPECIFICATION.md`;
- `GITHUB-LOCAL-AUTOMATION-ONE-COMMAND-FORMAL-REVIEW.md`;
- exact 12-path allowlist;
- PowerShell 5.1 one-command installer;
- fail-closed branch cleanup tool;
- integrity manifest;
- user guide;
- Codex project instructions;
- Implementation and repository/static Validation records.

Canonical post-merge command:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

## Safety limits

```text
TOKENS_OR_API_KEYS_AS_PARAMETERS=PROHIBITED
SECRETS_IN_LOGS=PROHIBITED
BRANCH_PROTECTION_MUTATION=PROHIBITED
REQUIRED_CHECKS_MUTATION=PROHIBITED
REPOSITORY_SETTINGS_MUTATION=PROHIBITED
INSTALLER_BRANCH_CREATION_OR_DELETION=PROHIBITED
MERGE_BY_INSTALLER=PROHIBITED
RUNTIME_DB_MIGRATION_WORKFLOW_THEME_DEPLOY_CHANGES=PROHIBITED
```

Actual Windows installation, UAC, GitHub login, Codex login and local idempotency acceptance remain user-executed after Merge.

Pull Request creation, Final PR Review, Merge and feature-branch deletion remain separately gated.
