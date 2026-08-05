# ASU-VCH GitHub Local Automation

Пакет подготавливает локальную Windows-среду для работы с проектом АСУ-ВЧ через Git, GitHub CLI и Codex CLI.

Он расширяет возможности только **локального Codex/PowerShell-сеанса**. Браузерный ChatGPT не получает прямой доступ к компьютеру.

## Предварительные условия

```text
Windows 10/11 64-bit
Windows PowerShell 5.1
repository: C:\Project\ASU-VCH
branch: main
worktree: clean
local HEAD == origin/main
```

## Одна команда

Откройте Windows PowerShell 5.1 и выполните:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

Installer:

1. проверяет Windows, PowerShell 5.1, репозиторий, `origin` и синхронизацию;
2. устанавливает или проверяет Git через WinGet package `Git.Git`;
3. устанавливает или проверяет GitHub CLI через `GitHub.cli`;
4. выполняет браузерный GitHub login при необходимости;
5. проверяет write-доступ к `ClaytonKinnane/ASU-VCH`;
6. устанавливает или проверяет Node.js LTS через `OpenJS.NodeJS.LTS`;
7. устанавливает Codex через официальный npm package `@openai/codex@latest`;
8. определяет фактический режим Codex authentication;
9. проверяет manifest и SHA-256 helper-файлов;
10. проверяет staged Cleanup Doctor до принятия установки;
11. атомарно устанавливает helpers в `C:\Tools\ASU-VCH`;
12. выводит capability matrix и exit code.

Native-команды обрабатываются через exit code. Вывод stderr сам по себе не считается ошибкой, что устраняет ложные сбои Windows PowerShell 5.1.

## Режимы Codex authentication

Параметр:

```powershell
-CodexAuthMode Auto|ChatGPT|ApiKey|Skip
```

### Auto

Если Codex уже авторизован, сохраняется обнаруженный режим. Если авторизации нет, installer предлагает:

```text
1 = ChatGPT, рекомендуется для подходящего плана ChatGPT
2 = API key, отдельная API-тарификация
3 = отложить
```

### ChatGPT

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File `
  '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1' `
  -CodexAuthMode ChatGPT
```

Запускается интерактивная команда `codex login`. Серверные требования OpenAI, включая возможную account verification, не обходятся.

### ApiKey

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File `
  '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1' `
  -CodexAuthMode ApiKey
```

Ключ запрашивается через `Read-Host -AsSecureString`, передаётся Codex только через stdin и не помещается в параметры, environment variables или логи.

API usage оплачивается отдельно от подписки ChatGPT. Installer не выполняет удалённый платный request test без отдельного разрешения и выводит:

```text
CODEX_REMOTE_REQUEST_READY=NOT_TESTED
```

### Skip

Codex authentication откладывается. Installer возвращает exit code `2` и не утверждает полную готовность.

## Capability matrix

Корректная матрица различает:

```text
CODEX_AUTH_READY=YES|NO
CODEX_AUTH_MODE=API_KEY|CHATGPT|UNKNOWN|NONE
CODEX_CHATGPT_AUTH_READY=YES|NO
CODEX_API_KEY_AUTH_READY=YES|NO
```

`API_KEY` не должен обозначаться как ChatGPT login.

## Doctor и Repair

Диагностика без установки или входа:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File `
  'C:\Project\ASU-VCH\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1' `
  -Mode Doctor
```

Повторная установка/обновление утверждённых компонентов и helpers:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File `
  'C:\Project\ASU-VCH\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1' `
  -Mode Repair
```

`-NoUpgrade` запрещает ненужную переустановку уже работающего Codex в Repair mode.

## Обязательная native Windows PowerShell 5.1 проверка

До Pull Request корректирующего инкремента выполните на exact implementation head:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Test-ASUVCHGitHubAutomation.ps1'
```

Требуется:

```text
WINDOWS_POWERSHELL_VERSION=5.1.x
PASS_COUNT>=20
FAIL_COUNT=0
REPOSITORY_WORKTREE_STATUS=PASS
USER_PATH_RESTORATION_STATUS=PASS
NATIVE_PS51_REGRESSION_STATUS=PASS
```

Harness использует только временные mock-команды и временные каталоги. Он не выполняет реальные GitHub/OpenAI запросы, package installation, Merge или branch deletion.

## Запуск локального Codex

```powershell
Set-Location 'C:\Project\ASU-VCH'
codex
```

Инструкции проекта:

```text
C:\Tools\ASU-VCH\CODEX-INSTRUCTIONS.md
```

## Branch cleanup — Doctor

```powershell
& 'C:\Tools\ASU-VCH\Invoke-ASUVCHBranchCleanup.ps1' -Mode Doctor
```

Ожидаемый clean-worktree результат:

```text
DOCTOR_STATUS=PASS
WORKTREE=CLEAN
```

## Branch cleanup — Verify

```powershell
& 'C:\Tools\ASU-VCH\Invoke-ASUVCHBranchCleanup.ps1' `
  -Mode Verify `
  -PullRequestNumber <PR_NUMBER> `
  -BranchName '<BRANCH_NAME>' `
  -ExpectedMainSha '<40_CHAR_MAIN_SHA>' `
  -ExpectedPrHeadSha '<40_CHAR_PR_HEAD_SHA>' `
  -ExpectedMergeCommitSha '<40_CHAR_MERGE_SHA>' `
  -PostMergeRunId <PUSH_RUN_ID>
```

## Branch cleanup — Delete

Только после отдельного owner approval:

```powershell
& 'C:\Tools\ASU-VCH\Invoke-ASUVCHBranchCleanup.ps1' `
  -Mode Delete `
  -PullRequestNumber <PR_NUMBER> `
  -BranchName '<BRANCH_NAME>' `
  -ExpectedMainSha '<40_CHAR_MAIN_SHA>' `
  -ExpectedPrHeadSha '<40_CHAR_PR_HEAD_SHA>' `
  -ExpectedMergeCommitSha '<40_CHAR_MERGE_SHA>' `
  -PostMergeRunId <PUSH_RUN_ID> `
  -ApprovalToken '<BRANCH_NAME>'
```

Для предварительного просмотра добавьте `-WhatIf`. Cleanup tool не удаляет локальные ветки.

## Логи

```text
%LOCALAPPDATA%\ASU-VCH\Logs
```

В логи не должны попадать токены, API-ключи, OAuth/device codes, cookies, passwords или private keys. Raw auth status output не логируется.

## Выходные коды

```text
0 = полная готовность
1 = fail-closed техническая или integrity ошибка
2 = требуется разрешённое интерактивное действие
```

## Что installer не делает

- не выполняет checkout/switch/reset/merge/rebase/cherry-pick/clean;
- не создаёт Pull Request;
- не выполняет Merge;
- не создаёт и не удаляет ветки;
- не меняет branch protection, required checks, Actions settings или repository settings;
- не меняет PHP/runtime, БД, migrations, themes или deploy;
- не принимает secrets как параметры;
- не помещает secrets в environment variables.

## Integrity model

Manifest использует `utf8-lf-normalized`: текст декодируется как UTF-8, переводы строк нормализуются к LF, затем вычисляется SHA-256. Проверяются source, staging и installed copies.

## Удаление локальных helpers

После завершения всех операций:

```powershell
Remove-Item -LiteralPath 'C:\Tools\ASU-VCH' -Recurse -Force
```

Команда удаляет только локальные helper-копии. Git, GitHub CLI, Node.js, npm, Codex и репозиторий остаются установленными.
