# GitHub Local Automation PowerShell 5.1 First-Run Correction — Approval

Status: `APPROVED FOR IMPLEMENTATION`

Date: `2026-08-05`

Repository: `ClaytonKinnane/ASU-VCH`

## Approved anchors

```text
main: 375f941be3f50f9f1f264da244f0dc31496e2a6f
branch: fix/github-local-automation-ps51-first-run
reviewed branch head: af2e2cc26e3cb84ea744a204a9acef2269c3fd95
merge-base: 375f941be3f50f9f1f264da244f0dc31496e2a6f
behind main: 0
pre-implementation changed paths: 3 / 3 approved
unapproved paths: 0
```

## Approved documents

- `docs/architecture/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-ARCHITECTURE.md`;
- `docs/specification/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-SPECIFICATION.md`;
- `docs/review/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-FORMAL-REVIEW.md`.

## Approved implementation scope

The owner approved correction of:

- native stdout/stderr/exit-code handling in Windows PowerShell 5.1;
- first-run GitHub authentication;
- Codex installation through the official npm provider with Node.js LTS through WinGet when needed;
- separate Codex `Auto`, `ChatGPT`, `ApiKey` and `Skip` authentication modes;
- secure API-key input through interactive secure input and native stdin only;
- `$null`, scalar and array handling;
- Cleanup Doctor on a clean worktree;
- manifest hashes and tooling documentation;
- a native Windows PowerShell 5.1 regression harness;
- Implementation and repository/static Validation records.

## Exact changed-path allowlist

1. `docs/architecture/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-ARCHITECTURE.md`
2. `docs/specification/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-SPECIFICATION.md`
3. `docs/review/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-FORMAL-REVIEW.md`
4. `docs/decisions/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-APPROVAL.md`
5. `docs/implementation/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-IMPLEMENTATION.md`
6. `docs/testing/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-VALIDATION.md`
7. `docs/review/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-PR-FINAL-REVIEW.md`
8. `tools/github-automation/Install-ASUVCHGitHubAutomation.ps1`
9. `tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1`
10. `tools/github-automation/Test-ASUVCHGitHubAutomation.ps1`
11. `tools/github-automation/automation-manifest.json`
12. `tools/github-automation/README.md`
13. `tools/github-automation/CODEX-INSTRUCTIONS.md`

```text
TOTAL_PATHS=13
MARKDOWN_PATHS=9
POWERSHELL_PATHS=3
JSON_PATHS=1
```

The Final PR Review path remains reserved and absent until separate PR authorization.

## Prohibitions

Implementation must not:

- accept secrets as parameters;
- place secrets in environment variables or logs;
- change runtime, database, migrations, workflow, Action SHA, themes, deployment or existing application checkers;
- change branch protection, required checks, Actions settings or repository settings;
- create a Pull Request;
- merge;
- delete branches.

## Required stop

After Implementation and repository/static Validation, work must stop and provide the exact implementation head plus one native Windows PowerShell 5.1 pre-PR validation command.