# АСУ-ВЧ — инструкции для локального Codex

## Репозиторий и окружение

```text
GitHub: ClaytonKinnane/ASU-VCH
локальный репозиторий: C:\Project\ASU-VCH
локальное развёртывание: C:\OSPanel\home\asu-vch.local
локальные инструменты: C:\Tools\ASU-VCH
домен: https://asu-vch.local
PowerShell: Windows PowerShell 5.1
```

## Обязательный процесс

```text
Architecture → Specification → Formal Review → Approval →
Implementation → Testing/Validation → Commit → Push → Pull Request →
Final PR Review → отдельное разрешение на Merge → Merge →
post-merge verification → отдельное разрешение на удаление ветки
```

Нельзя переходить к следующему destructive или repository-write этапу без требуемого явного разрешения владельца.

## Fail-closed правила

- Перед каждой записью повторно проверяй exact `main`, exact branch head, merge-base, divergence и утверждённый changed-path allowlist.
- При любом несовпадении останавливайся и сообщай фактические значения.
- Не выполняй Implementation до утверждения Architecture, Specification и Formal Review.
- Не создавай Pull Request без отдельного разрешения.
- Не выполняй Merge без отдельного exact разрешения с SHA-gate.
- Не удаляй ветку без отдельного разрешения и успешного `Verify`.
- Не изменяй branch protection, required checks, Actions settings или repository settings без отдельного разрешения.
- Не выполняй force push/reset/rebase опубликованной ветки без отдельного разрешения.
- Не раскрывай токены, API-ключи, cookies, device codes, private keys или credential-store content.
- Не заявляй PASS для тестов, которые фактически не запускались.
- Мобильное тестирование не заявлять выполненным без отдельного фактического прогона.

## Branch cleanup

Используй только:

```powershell
C:\Tools\ASU-VCH\Invoke-ASUVCHBranchCleanup.ps1
```

Сначала:

```powershell
-Mode Verify
```

`-Mode Delete` разрешён только после отдельного owner approval и с exact:

```text
PR number
branch name
main SHA
PR head SHA
merge commit SHA
post-merge push run ID
ApprovalToken = exact branch name
```

## Документационная terminal model

Living-документация содержит устойчивое текущее состояние.

Architecture, Specification, Formal Review, Approval, Implementation, Validation и Final PR Review являются историческими gate records.

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

Review, Merge, Actions runs и branch cleanup последнего документационного PR канонически хранятся в GitHub и не создают рекурсивный Markdown closure.

## Коммуникация с владельцем

Когда требуется действие или разрешение владельца, используй заголовок:

```text
Действия, требуемые от вас
```

Приводи точную формулировку разрешения и exact SHA/branch/run anchors.
