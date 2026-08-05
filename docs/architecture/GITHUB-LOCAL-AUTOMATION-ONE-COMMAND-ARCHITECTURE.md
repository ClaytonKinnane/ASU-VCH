# GitHub Local Automation One-Command Bootstrap — Architecture

Status: `PROPOSED / PRE-IMPLEMENTATION`

Date: `2026-08-05`

Repository: `ClaytonKinnane/ASU-VCH`

Baseline:

```text
main: d9c7db67ee2df8204281d67a4c134528a499573b
branch: tools/github-local-automation-bootstrap
```

## 1. Goal

После синхронизации локального репозитория пользователь должен выполнить одну команду Windows PowerShell 5.1 из `C:\Project\ASU-VCH`. Эта команда запускает repository-owned bootstrap script, который проверяет окружение, устанавливает необходимые компоненты, проводит интерактивную безопасную авторизацию и разворачивает локальные инструменты для работы над АСУ-ВЧ.

Целевая команда после merge:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

Команда не скачивает исполняемый скрипт из mutable URL: она запускает файл, уже синхронизированный из merged `main`.

## 2. Boundary

Increment является tooling-only.

Он не изменяет:

- PHP/runtime;
- database code, data или migrations;
- themes/assets;
- deploy scripts;
- GitHub Actions workflow и Action SHA;
- существующие checkers/tools вне нового каталога;
- branch protection, required checks и repository settings;
- local Open Server configuration;
- `main` вне обычного PR/Merge lifecycle.

Browser ChatGPT не получает прямой доступ к локальному терминалу. Расширение возможностей появляется только в локальной Codex/terminal среде, которую пользователь запускает на своём компьютере.

## 3. Target environment

```text
OS: Windows 10/11 64-bit
shell: Windows PowerShell 5.1
repository path: C:\Project\ASU-VCH
local installed tools: C:\Tools\ASU-VCH
GitHub repository: ClaytonKinnane/ASU-VCH
remote: origin
default branch: main
```

## 4. Components

### 4.1 One-command installer

Path:

```text
tools/github-automation/Install-ASUVCHGitHubAutomation.ps1
```

Responsibilities:

1. verify Windows and PowerShell 5.1+;
2. enable TLS 1.2 for current process;
3. initialize logs under `%LOCALAPPDATA%\ASU-VCH\Logs`;
4. verify the script is running from the expected repository;
5. verify local repository path, `origin`, default branch and clean worktree;
6. verify local branch is `main` or explicitly allowed diagnostic state;
7. verify local `HEAD` equals `origin/main` after `git fetch --prune`;
8. detect WinGet;
9. install/upgrade Git for Windows using exact WinGet package `Git.Git`;
10. install/upgrade GitHub CLI using exact WinGet package `GitHub.cli`;
11. refresh process PATH;
12. verify `git` and `gh` versions;
13. start browser GitHub authentication when required;
14. run `gh auth setup-git --hostname github.com`;
15. verify repository access and push/write permission;
16. install Codex CLI using the current official Windows installer;
17. verify `codex --version` and `codex login status`;
18. guide or start interactive ChatGPT sign-in when required;
19. copy approved local helper files atomically to `C:\Tools\ASU-VCH`;
20. verify copied file SHA-256 against repository manifest;
21. execute Doctor checks;
22. print exact next commands for Codex and branch cleanup.

### 4.2 Branch cleanup tool

Path:

```text
tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1
```

Modes:

```text
Doctor
Verify
Delete
```

Delete remains separately gated and checks:

- exact main SHA;
- exact PR head SHA;
- exact merge commit SHA;
- PR merged state;
- exact successful post-merge push run;
- successful required job steps;
- canonical post-merge PR comment PASS;
- exact remote branch SHA;
- branch ahead of main = 0;
- unique unmerged commits = 0;
- case-sensitive approval token equal to branch name.

The only destructive repository command is:

```powershell
git push origin --delete <approved-branch>
```

### 4.3 Local package manifest

Path:

```text
tools/github-automation/automation-manifest.json
```

The manifest contains:

- schema version;
- minimum PowerShell version;
- expected repository identity;
- default install path;
- exact helper file paths;
- install names;
- SHA-256 for copied helper files.

The installer itself is not copied through the manifest and does not require a self-hash. This avoids self-reference.

### 4.4 User guide

Path:

```text
tools/github-automation/README.md
```

It documents one local command, expected prompts, Doctor/Repair behaviour, branch cleanup examples, logs and limitations.

### 4.5 Codex project instructions

Path:

```text
tools/github-automation/CODEX-INSTRUCTIONS.md
```

They preserve the full АСУ-ВЧ lifecycle, exact-SHA gates, separate Merge approval, separate branch-deletion approval, no settings mutation and no secret disclosure.

## 5. Installation model

The user first synchronizes merged `main` locally. Then one command launches the installer from the repository.

No package commit SHA must be embedded into the package. Trust is based on:

```text
local repository synced to origin/main
+ verified origin repository identity
+ exact local HEAD == origin/main
+ GitHub access verification
+ manifest hashes for copied helpers
```

This model is simpler and more appropriate than downloading the installer from a raw branch URL.

## 6. Interaction model

The installer automates all safe checks and package installation, but security-sensitive interactions remain visible:

- Windows elevation requested by WinGet/package installers;
- Microsoft Store recovery when WinGet is absent;
- GitHub browser login;
- ChatGPT/Codex login;
- separate explicit approval before branch deletion.

The installer must not bypass these controls.

## 7. Failure model

All critical checks fail closed with non-zero exit:

- unsupported OS/PowerShell;
- wrong repository or remote;
- dirty worktree;
- local HEAD not equal to `origin/main`;
- missing WinGet after guided recovery;
- package install failure;
- GitHub auth/access/write-permission failure;
- Codex command verification failure;
- manifest/schema/hash mismatch;
- helper installation failure;
- unsafe branch-cleanup evidence.

## 8. Secrets and logging

The scripts must not accept or log:

- GitHub tokens;
- API keys;
- OAuth/device codes;
- cookies;
- private keys;
- Authorization headers;
- credential-store content.

Allowed log data includes command versions, public SHAs, PR/run/job identifiers and PASS/FAIL results.

## 9. Terminal lifecycle rule

Mutable PR/review/Merge/Actions/branch-cleanup evidence remains canonical in GitHub and does not require recursive Markdown closure.

## 10. Implementation gate

Implementation is prohibited until owner approval of this Architecture, the Specification, Formal Review and exact allowlist.
