# ASU-VCH GitHub Local Automation

Этот пакет подготавливает локальную Windows-среду для работы с проектом АСУ-ВЧ через Codex CLI, Git и GitHub CLI.

Он расширяет возможности **локального Codex-сеанса**. Обычный браузерный чат не получает прямой доступ к вашему компьютеру.

## Предварительное условие

Репозиторий должен быть синхронизирован в:

```text
C:\Project\ASU-VCH
```

Для установки ветка должна быть `main`, рабочее дерево — чистым, а локальный `HEAD` — совпадать с `origin/main`.

## Одна команда

Запустите Windows PowerShell 5.1:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1'
```

Сценарий:

1. проверяет Windows и PowerShell;
2. проверяет репозиторий и синхронизацию;
3. проверяет WinGet;
4. устанавливает или проверяет Git;
5. устанавливает или проверяет GitHub CLI;
6. выполняет безопасный браузерный вход GitHub при необходимости;
7. проверяет write-доступ к `ClaytonKinnane/ASU-VCH`;
8. устанавливает Codex официальным Windows installer;
9. запускает вход через ChatGPT при необходимости;
10. проверяет manifest и SHA-256 helper-файлов;
11. устанавливает helpers в `C:\Tools\ASU-VCH`;
12. запускает cleanup tool в режиме `Doctor`;
13. выводит capability matrix и следующие команды.

UAC и браузерные входы остаются интерактивными. Сценарий не пытается обходить системные или account-security controls.

## Повторная проверка

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File `
  'C:\Project\ASU-VCH\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1' `
  -Mode Doctor
```

## Восстановление или повторная установка

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File `
  'C:\Project\ASU-VCH\tools\github-automation\Install-ASUVCHGitHubAutomation.ps1' `
  -Mode Repair
```

## Запуск локального Codex

```powershell
Set-Location 'C:\Project\ASU-VCH'
codex
```

Проектные инструкции после установки находятся в:

```text
C:\Tools\ASU-VCH\CODEX-INSTRUCTIONS.md
```

В начале нового Codex-сеанса укажите, что этот файл является обязательной инструкцией проекта, либо перенесите его содержимое в утверждённый `AGENTS.md` отдельным документированным инкрементом.

## Branch cleanup — только проверка

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

## Branch cleanup — удаление

Допустимо только после отдельного owner approval:

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

Для предварительного просмотра добавьте `-WhatIf`.

## Логи

```text
%LOCALAPPDATA%\ASU-VCH\Logs
```

Логи не должны содержать токены, API-ключи, cookies, device codes или private keys.

## WinGet отсутствует

Installer откроет официальную страницу Microsoft App Installer и остановится. После установки App Installer повторите ту же one-command команду.

## Выходные коды installer

```text
0 = готово
1 = fail-closed ошибка
2 = установка завершена, но требуется интерактивное действие
```

## Что installer не делает

- не выполняет Merge;
- не создаёт и не удаляет ветки;
- не создаёт Pull Request;
- не изменяет branch protection или required checks;
- не изменяет repository settings;
- не меняет PHP runtime, БД, migrations, themes или deploy;
- не принимает токены и API-ключи как параметры.

## Hash mode

Manifest использует `utf8-lf-normalized`: перед SHA-256 текст декодируется как UTF-8 и переводы строк нормализуются к LF. Это исключает ложные mismatch после Windows Git checkout с CRLF.

## Удаление локальных helpers

После завершения всех активных операций:

```powershell
Remove-Item -LiteralPath 'C:\Tools\ASU-VCH' -Recurse -Force
```

Это удаляет только локальные копии helper-файлов. Git, GitHub CLI, Codex и репозиторий не удаляются.
