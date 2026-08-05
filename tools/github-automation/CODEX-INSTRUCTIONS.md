# АСУ-ВЧ — обязательные инструкции для локального Codex

## 1. Репозиторий и среда

```text
Repository: ClaytonKinnane/ASU-VCH
Local path: C:\Project\ASU-VCH
Deployment: C:\OSPanel\home\asu-vch.local
Default branch: main
Shell: Windows PowerShell 5.1
```

Перед любой работой проверить:

```powershell
Set-Location 'C:\Project\ASU-VCH'
git status --short
git branch --show-current
git fetch --prune origin
git rev-parse HEAD
git rev-parse origin/main
```

Нельзя предполагать clean worktree, текущую ветку, exact SHA или состояние GitHub без проверки.

## 2. Обязательный lifecycle

```text
Architecture
→ Specification
→ Formal Review
→ Owner Approval
→ Implementation
→ Testing / Validation
→ Commit
→ Push
→ Pull Request
→ Final PR Review
→ separate Merge approval
→ Merge
→ post-merge verification
→ separate branch-deletion approval
```

Не переходить к следующему gate без явного разрешения владельца.

## 3. Exact gates

Для каждого изменения фиксировать:

```text
exact main SHA
exact branch head SHA
merge-base
branch ahead/behind
exact changed-path allowlist
unapproved paths count
```

Перед PR, Final PR Review, Merge и branch deletion повторять fail-closed проверку exact anchors.

## 4. Запрещённые действия без отдельного разрешения

- создание Pull Request;
- Merge;
- удаление локальной или remote ветки;
- изменение branch protection;
- изменение required checks;
- изменение GitHub Actions settings;
- изменение repository settings;
- force push;
- reset/clean/rebase чужих или незафиксированных изменений;
- изменение runtime, БД, migrations, workflow, Action SHA, themes или deploy вне утверждённого allowlist.

## 5. Документация

Living documentation содержит только durable current state.

Mutable PR/SHA/run/branch evidence хранится в GitHub и Git. Historical gate records остаются историческими и не считаются открытыми задачами только из-за слов `PENDING`, `NEXT GATE` или `NOT AUTHORIZED` внутри старого контекста.

После Merge не создавать рекурсивную Markdown closure без отдельного утверждения. Post-merge verification фиксировать в GitHub PR.

## 6. Секреты

Никогда не выводить, не логировать, не коммитить и не отправлять:

- GitHub tokens;
- OpenAI API keys;
- passwords;
- OAuth/device codes;
- cookies;
- private keys;
- credential-store contents;
- Authorization headers.

API-key authentication Codex и ChatGPT-plan authentication — разные режимы:

```text
CODEX_AUTH_MODE=API_KEY
CODEX_AUTH_MODE=CHATGPT
```

Не называть API-key режим входом через ChatGPT. API usage оплачивается отдельно и API balance не считается проверенным без отдельного разрешённого request test.

## 7. Windows PowerShell 5.1 native commands

Для automation scripts:

- native exit code является источником истины;
- stderr сам по себе не означает failure;
- stdout/stderr захватывать как данные через `System.Diagnostics.Process`;
- `.cmd`/`.bat` запускать через `%ComSpec%` с безопасным quoting;
- interactive browser/device-login commands запускать с видимой консолью;
- secrets передавать только через redirected stdin;
- optional/pipeline/native output нормализовать через `@(...)` до `.Count` или indexing;
- clean empty output должен оставаться допустимым empty collection.

## 8. Codex installation and authentication

Утверждённый provider:

```text
WinGet: OpenJS.NodeJS.LTS
npm: npm install --global @openai/codex@latest
```

Не использовать unofficial installer. Не обновлять npm только из-за notice.

Authentication modes:

```text
Auto
ChatGPT
ApiKey
Skip
```

- ChatGPT: `codex login`, account policy не обходить.
- ApiKey: secure prompt, stdin only, no args/env/logs.
- Skip: incomplete readiness, exit 2.

## 9. Branch cleanup

Cleanup tool modes:

```text
Doctor
Verify
Delete
```

`Delete` разрешён только после отдельного owner approval и только если все exact gates PASS:

```text
main exact SHA
merged PR exact head and merge commit
successful post-merge push run/job/steps
canonical post-merge PASS comment
remote branch exact SHA
branch ahead of main = 0
unique unmerged commits = 0
ApprovalToken == BranchName case-sensitive
```

Единственная разрешённая destructive-команда cleanup tool:

```text
git push origin --delete <approved-branch>
```

Локальные ветки tool не удаляет.

## 10. Validation

Repository/static PASS не заменяет native Windows PowerShell 5.1 acceptance.

Для corrected GitHub automation package до PR выполнить:

```powershell
Set-Location 'C:\Project\ASU-VCH'; powershell.exe -NoProfile -ExecutionPolicy Bypass -File '.\tools\github-automation\Test-ASUVCHGitHubAutomation.ps1'
```

Требуется:

```text
PASS_COUNT>=20
FAIL_COUNT=0
NATIVE_PS51_REGRESSION_STATUS=PASS
REPOSITORY_WORKTREE_STATUS=PASS
USER_PATH_RESTORATION_STATUS=PASS
```

После Merge one-command installer запускается дважды для target-machine execution и idempotency acceptance.

## 11. Отчёт пользователю

Когда требуются действия пользователя, всегда создавать раздел:

```text
Действия, требуемые от вас
```

Давать одну точную команду или точные UI-шаги. Не утверждать PASS для непроведённых native, browser, UAC, billing или account-verification проверок.
