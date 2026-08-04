# GitHub Local Automation Bootstrap — Formal Review

Status: `PASS FOR OWNER APPROVAL`

Date: `2026-08-04`

Baseline:

```text
main: d9c7db67ee2df8204281d67a4c134528a499573b
branch: tools/github-local-automation-bootstrap
branch start: d9c7db67ee2df8204281d67a4c134528a499573b
```

Reviewed documents:

- `docs/architecture/GITHUB-LOCAL-AUTOMATION-BOOTSTRAP-ARCHITECTURE.md`;
- `docs/specification/GITHUB-LOCAL-AUTOMATION-BOOTSTRAP-SPECIFICATION.md`.

## 1. Review objective

Determine whether the proposed design can safely deliver this owner workflow:

1. synchronize `C:\Project\ASU-VCH`;
2. execute one Windows PowerShell 5.1 command;
3. automatically verify and install required local components;
4. complete visible GitHub and ChatGPT authentication prompts;
5. obtain a local Codex agent ready to work in the repository;
6. gain a separately gated fail-closed remote-branch cleanup command.

## 2. Official-source review

Current upstream behavior was checked against primary sources.

### 2.1 WinGet

Microsoft documents:

- exact package matching with `--id` and `-e`;
- source disambiguation with `--source winget`;
- package/source agreement switches;
- Git installation example using `Git.Git`;
- supported WinGet repair using `Microsoft.WinGet.Client` and `Repair-WinGetPackageManager`.

The proposed design follows those mechanisms.

Result: `PASS`.

### 2.2 GitHub CLI

GitHub CLI documents:

- browser authentication through `gh auth login --web`;
- host selection through `--hostname github.com`;
- Git protocol selection through `--git-protocol https`;
- Git credential configuration through `gh auth setup-git --hostname github.com`;
- active-auth verification through `gh auth status`.

The proposed design does not request a token parameter and does not call `gh auth token`.

Result: `PASS`.

### 2.3 Codex

OpenAI's Codex repository documents:

- the official Windows standalone installer at `https://chatgpt.com/codex/install.ps1`;
- native Windows installation without requiring npm/Node.js;
- interactive ChatGPT sign-in;
- repository instruction discovery through `AGENTS.md`.

The design downloads the official installer to a temporary file, records its public SHA-256 and verifies the installed command.

Result: `PASS`.

## 3. One-command feasibility review

The canonical command starts one controlling PowerShell process:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File 'C:\Project\ASU-VCH\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

That process can sequence:

- integrity validation;
- WinGet repair;
- Git installation;
- GitHub CLI installation;
- GitHub browser login;
- repository permission checks;
- Codex installation;
- Codex browser login;
- local helper installation;
- readiness validation.

UAC and browser authentication remain interactive by operating-system/account design. Requiring the user to approve those prompts does not violate the one-command orchestration requirement because no second setup command is required.

Result: `PASS`.

## 4. Browser-versus-local capability review

The design correctly distinguishes:

```text
browser ChatGPT GitHub connector: unchanged
local Codex session: expanded by local Git/gh/terminal access
```

It does not claim that installing Codex grants this browser conversation direct access to `C:\Project\ASU-VCH`.

Result: `PASS`.

## 5. Scope review

The exact allowlist contains 14 paths:

```text
root project instruction: 1
process records: 7
tooling package: 6
Markdown: 9
PowerShell: 4
JSON: 1
total: 14
```

No runtime, database, migration, workflow, Action SHA, theme, deploy, application checker or repository-settings path is included.

Result: `PASS`.

## 6. `AGENTS.md` review

Adding root `AGENTS.md` is a material but appropriate project-agent configuration change.

It gives local Codex sessions durable access to:

- the mandatory lifecycle;
- exact-SHA gates;
- approval boundaries;
- local paths;
- testing-claim restrictions;
- secret-handling rules.

Required controls:

- concise content;
- repository-wide scope stated explicitly;
- no secret or user-specific credential content;
- no conflict with higher-priority user instructions;
- no attempt to authorize destructive actions permanently.

Result: `PASS`.

## 7. PowerShell 5.1 review

The specification requires:

- `#requires -Version 5.1`;
- strict mode;
- terminating errors;
- no PowerShell 7-only syntax;
- no ternary operator;
- no null-coalescing operators;
- no parallel pipeline dependency;
- `ConvertFrom-Json`/`Get-FileHash` behavior compatible with Windows PowerShell 5.1;
- explicit external-command exit-code handling.

Actual parsing under Windows PowerShell 5.1 remains a required validation step.

Result: `PASS`.

## 8. WinGet bootstrap review

The design does not assume WinGet already works.

It uses Microsoft's supported repair flow:

```powershell
Install-PackageProvider -Name NuGet -Force
Install-Module -Name Microsoft.WinGet.Client -Force -Repository PSGallery -Scope CurrentUser
Repair-WinGetPackageManager -Force -Latest
```

Risks and controls:

- PowerShell Gallery/network policy can block module installation: fail closed and print exact failure;
- enterprise policy can block WinGet: do not bypass policy;
- UAC may be required by downstream installers: preserve prompt;
- PATH may remain stale: rebuild process PATH and verify commands.

Result: `PASS`.

## 9. Package integrity review

The local package uses a non-self-referential manifest:

- manifest hashes package files;
- manifest does not hash itself;
- no Git commit SHA is embedded as a self-reference;
- user synchronization supplies repository identity;
- local Git state and origin checks supply repository provenance;
- SHA-256 checks detect accidental/corrupt package-file changes.

The installer validates integrity before installing external components.

Result: `PASS`.

## 10. Authentication and secret review

The design prohibits:

- token/password/API-key parameters;
- `gh auth token`;
- `--with-token`;
- reading GitHub credential stores;
- reading Codex auth files;
- logging OAuth/device codes;
- logging Authorization headers;
- passing secrets to child-process arguments.

Authentication remains interactive and is verified through supported status commands.

Result: `PASS`.

## 11. Repository permission review

The installer reads repository metadata and verifies `permissions.push == true`.

It explicitly prohibits during setup:

- branch creation/deletion;
- PR creation;
- Merge;
- workflow reruns;
- branch-protection mutation;
- required-check mutation;
- Actions-setting mutation;
- repository-setting mutation.

Result: `PASS`.

## 12. Branch cleanup review

The cleanup design requires:

- exact `main`, PR head and merge commit SHAs;
- merged PR evidence;
- exact successful post-merge push run;
- successful job/required steps;
- canonical post-merge PASS comment;
- exact branch identity;
- zero unique unmerged commits;
- case-sensitive approval token;
- `ShouldProcess` confirmation;
- post-delete independent verification.

The installer itself does not delete any branch.

Result: `PASS`.

## 13. Codex safety review

The launcher must not use unsafe bypass flags such as:

```text
--dangerously-bypass-approvals-and-sandbox
```

It starts normal interactive Codex from the repository root so root `AGENTS.md` is discoverable.

The owner retains Codex approval and sandbox controls.

Result: `PASS`.

## 14. Idempotency and rollback review

The design requires:

- detection of existing components;
- optional upgrade suppression;
- package revalidation on every run;
- staging before local-tool replacement;
- backup/restore of the previous managed installation;
- readiness check after replacement;
- no repository file mutation during installer execution.

Result: `PASS`.

## 15. Validation feasibility

Repository/static validation can be completed before target-machine execution.

Target-machine acceptance remains necessary for:

- WinGet repair behavior;
- Git/GitHub CLI installation;
- UAC interaction;
- GitHub browser login;
- Codex installer behavior;
- ChatGPT/Codex login;
- actual command/path resolution;
- local Codex launch.

The design explicitly prohibits claiming those checks PASS before owner-run evidence exists.

Result: `PASS`.

## 16. Findings

```text
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_OPEN_FINDINGS=0
OPEN_FINDINGS=0
```

Design clarifications resolved during review:

1. The package uses a local manifest without a self commit SHA or self hash.
2. The single command may invoke visible UAC/browser prompts but must resume verification afterward.
3. The capability expansion applies to local Codex, not the browser connector.
4. Root `AGENTS.md` replaces a separate copied instruction file and is automatically repository-scoped.

## 17. Required implementation controls

```text
ONE_COMMAND_ENTRYPOINT=REQUIRED
WINDOWS_POWERSHELL_5_1=REQUIRED
INTEGRITY_CHECK_BEFORE_INSTALL=REQUIRED
WINGET_REPAIR_FLOW=OFFICIAL_MICROSOFT
GIT_PACKAGE_ID=Git.Git
GH_PACKAGE_ID=GitHub.cli
GH_AUTH=INTERACTIVE_WEB
CODEX_INSTALLER=OFFICIAL_CHATGPT_URL
CODEX_AUTH=INTERACTIVE_CHATGPT
AGENTS_MD_ROOT_SCOPE=REQUIRED
BROWSER_LOCAL_ACCESS_CLAIM=PROHIBITED
SECRETS_IN_ARGUMENTS=PROHIBITED
SECRETS_IN_LOGS=PROHIBITED
UNSAFE_CODEX_BYPASS_FLAGS=PROHIBITED
REPOSITORY_SETTINGS_MUTATION=PROHIBITED
INSTALLER_BRANCH_DELETION=PROHIBITED
BRANCH_DELETE_VERIFY_FIRST=REQUIRED
BRANCH_DELETE_SEPARATE_APPROVAL=REQUIRED
UNIQUE_UNMERGED_COMMITS_ALLOWED=0
TARGET_MACHINE_PASS_WITHOUT_EVIDENCE=PROHIBITED
POST_MERGE_MARKDOWN_CLOSURE_REQUIRED=NO
```

## 18. Review verdict

```text
ARCHITECTURE_REVIEW=PASS
SPECIFICATION_REVIEW=PASS
OFFICIAL_SOURCE_REVIEW=PASS
ONE_COMMAND_FEASIBILITY_REVIEW=PASS
POWERSHELL_5_1_REVIEW=PASS
WINGET_BOOTSTRAP_REVIEW=PASS
GIT_INSTALL_REVIEW=PASS
GITHUB_CLI_REVIEW=PASS
GITHUB_AUTH_REVIEW=PASS
CODEX_INSTALL_REVIEW=PASS
CODEX_AUTH_REVIEW=PASS
AGENTS_MD_REVIEW=PASS
PACKAGE_INTEGRITY_REVIEW=PASS
SECRET_HANDLING_REVIEW=PASS
BRANCH_CLEANUP_SAFETY_REVIEW=PASS
ALLOWLIST_REVIEW=PASS
PROCESS_REVIEW=PASS

FORMAL_REVIEW_STATUS=PASS FOR OWNER APPROVAL
```

## 19. Process gate

The branch is stopped before Implementation.

No root `AGENTS.md`, installer, readiness test, cleanup tool, Codex launcher, manifest, tooling guide, Approval record, Implementation record or Validation record has been created yet.

Implementation requires separate owner approval of:

- Architecture;
- Specification;
- this Formal Review;
- exact 14-path allowlist;
- implementation on the exact reviewed branch head.
