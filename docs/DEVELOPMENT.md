# Правила разработки

## Источник истины

Репозиторий `ClaytonKinnane/ASU-VCH` на GitHub является единственным источником истины для кода и документации проекта.

Изменения выполняются в отдельной GitHub-ветке. Локальный клон не используется для разработки, создания commit или push; он используется для синхронизации, deploy и тестирования.

## Обязательный процесс

Материальный инкремент проходит этапы:

```text
Research
→ Analysis
→ Architecture
→ Specification
→ Review
→ Approval
→ Implementation
→ Testing
→ Commit
→ Push
→ Pull Request
→ Final PR Review
→ separate merge approval
→ Merge
→ post-merge verification
→ separate branch deletion approval
```

Нельзя переходить к Implementation без утверждённых Architecture, Specification, Review и явного Approval владельца проекта.

Pull Request создаётся только после завершения утверждённого scope и требуемого Testing. Merge выполняется только после Final PR Review и отдельного явного разрешения. Удаление веток не входит автоматически ни в создание PR, ни в merge approval.

## Классификация изменений

### Runtime-инкремент

Требует применимых проверок:

- repository/scope preflight;
- backup перед schema/data migration;
- deploy с сохранением `config/local.php`;
- PHP lint;
- installer и repeat installer;
- профильные integration checker'ы и regressions;
- source/deploy parity;
- HTTP smoke;
- предусмотренную Specification manual acceptance.

### Documentation-only инкремент

Не требует deploy/runtime retest, если diff ограничен утверждёнными Markdown-путями. Обязательны:

- exact path allowlist;
- отсутствие runtime/config/database/theme/tool diff;
- проверка текущих baseline facts и historical anchors;
- Markdown link validation;
- stale-current-state scan;
- secret scan;
- Final PR Review перед merge.

Documentation-only commit не объявляется runtime-протестированным.

## Ветки

- `main` содержит стабильное объединённое состояние.
- Функциональность разрабатывается в `feature/...`.
- Исправления выполняются в `bugfix/...` либо в активной feature-ветке до merge.
- Документационные обновления выполняются в `docs/...`.
- Постоянной feature-ветки проекта нет.
- Завершённая ветка сохраняется до отдельного cleanup gate.

Перед удалением каждой remote или local ветки обязательны:

1. fresh inventory;
2. подтверждение, что tip достижим из актуального `origin/main` либо иным образом доказано отсутствие уникальных данных;
3. проверка связанного PR и post-merge состояния;
4. отдельное явное разрешение владельца;
5. безопасное удаление без force, когда это применимо;
6. итоговая проверка `main`, remote inventory и local branch set.

Техническая классификация `SAFE TO DELETE` не является разрешением на удаление.

## Локальный тестовый клон

```text
C:\Project\ASU-VCH
```

Допустимые операции:

- `git fetch --prune`;
- переключение на утверждённую ветку или `main`;
- проверка SHA, divergence и чистоты рабочего дерева;
- controlled deploy в Open Server Panel;
- installer, lint, CLI checker'ы, HTTP/browser testing;
- Git CLI cleanup только после отдельного утверждения точного набора веток.

Перед синхронизацией:

```powershell
Set-Location -LiteralPath 'C:\Project\ASU-VCH'
git status --short
git fetch --prune origin
git switch main
git pull --ff-only origin main
```

Локально запрещены без отдельного scope:

- ручное редактирование исходников и документации;
- `git add`, `git commit` и `git push` проектных изменений;
- раскрытие `config/local.php`, credentials, session data или временных паролей;
- `git branch -D` либо force-update refs для обычного cleanup.

## Разделение репозитория и web root

```text
Git clone:   C:\Project\ASU-VCH
Deploy root: C:\OSPanel\home\asu-vch.local
Apache root: C:\OSPanel\home\asu-vch.local\public
```

Deploy выполняется контролируемым PowerShell-сценарием. `config/local.php` сохраняется; перед schema/data migration создаётся SQL backup.

## Коммиты

Используются понятные префиксы:

- `feat:` — новая функциональность;
- `fix:` — исправление;
- `style:` — оформление;
- `docs:` — документация;
- `refactor:` — переработка без изменения назначения;
- `test:` — проверки;
- `chore:` — служебные изменения.

## Технологические ограничения

- Windows PowerShell 5.1;
- PHP 8.5.4;
- MySQL 8.4.x;
- сторонние зависимости только после обоснования и Approval;
- секреты и локальные параметры не хранятся в Git;
- архитектурные решения не вводятся скрыто в коде или migration;
- мобильная версия не объявляется проверенной без фактической мобильной приёмки.
