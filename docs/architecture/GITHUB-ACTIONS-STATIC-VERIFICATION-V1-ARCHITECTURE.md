# GitHub Actions Static Verification v1 — Architecture

**Статус:** Approved for Implementation
**Дата утверждения:** 2026-08-03
**Утверждённый baseline:** `main @ feac7230616d3a8df98acb48f43a0b60f89f2255`

## 1. Назначение

Инкремент добавляет один GitHub Actions workflow для автоматической статической проверки Pull Request в `main`, push в `main` и ручной диагностики после появления workflow в default branch.

CI является дополнительным ранним gate. Он не заменяет локальные проверки MySQL, migrations, installer, deploy, source/deploy parity, HTTP smoke, browser runtime и visual acceptance.

## 2. Границы

В scope входят:

- `.github/workflows/static-verification.yml`;
- PHP 8.5 static verification;
- event-aware `git diff --check`;
- lint всех tracked PHP-файлов;
- запуск только доказанно автономных checker’ов;
- immutable action SHA;
- минимальные permissions;
- timeout и concurrency;
- документация Architecture, Specification, Review, Approval, Implementation, Test Report и Final PR Review.

Вне scope:

- MySQL service;
- `database/install.php`;
- DB integration и migration execution;
- Windows/self-hosted runners;
- Open Server Panel;
- deploy и source/deploy parity;
- HTTP/browser/visual testing;
- изменение business logic, DB schema, RBAC, UI или themes;
- branch protection mutation;
- production deployment;
- mobile testing.

Mobile: `OUT OF SCOPE / NOT RUN`.

## 3. Проверенный baseline

Перед созданием feature-ветки повторно подтверждено:

- repository: `ClaytonKinnane/ASU-VCH`;
- default branch: `main`;
- actual `main` HEAD: `feac7230616d3a8df98acb48f43a0b60f89f2255`;
- ожидаемый и фактический SHA совпадают;
- feature-ветка отсутствовала;
- `.github/workflows` отсутствовал;
- `composer.json` и `composer.lock` отсутствовали.

Feature-ветка создаётся только от exact approved baseline:

`feature/github-actions-static-verification-v1`.

## 4. Workflow identity

- Path: `.github/workflows/static-verification.yml`
- Workflow name: `ASU-VCH Static Verification`
- Job ID: `asu-vch-static-verification`
- Job name: `asu-vch-static-verification`

Job ID и отображаемое имя намеренно стабильны для возможного будущего required status check. Их изменение после Stage B будет breaking operational change.

## 5. Runtime

- GitHub-hosted runner: `ubuntu-24.04`;
- PHP: `8.5.x`;
- coverage: none;
- Composer: not installed by workflow;
- job timeout: 10 minutes;
- no service containers;
- no cache;
- no artifacts.

## 6. Events

Workflow запускается на:

- `pull_request` в `main`: `opened`, `synchronize`, `reopened`, `ready_for_review`;
- `push` в `main`;
- `workflow_dispatch`.

`pull_request_target` запрещён.

Для Pull Request `git diff --check` использует exact payload SHA:

- `github.event.pull_request.base.sha`;
- `github.event.pull_request.head.sha`.

Для push используются:

- `github.event.before`;
- `github.sha`.

Для zero-before push и root commit применяется empty-tree fallback. Для manual run проверяется `HEAD^..HEAD`, либо empty tree для root commit.

## 7. Security envelope

Workflow выполняет недоверенный PR-код. Поэтому обязательны:

- `permissions: contents: read`;
- отсутствие secrets и environments;
- отсутствие write permissions;
- отсутствие `pull_request_target`;
- `persist-credentials: false`;
- `fetch-depth: 0`;
- immutable action SHA;
- отсутствие deploy, DB и network-dependent repository commands;
- final worktree integrity check, включая untracked files.

Закреплённые Actions:

- `actions/checkout@11d5960a326750d5838078e36cf38b85af677262`;
- `shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240`.

## 8. Concurrency

Group:

`asu-vch-static-verification-${{ github.event_name }}-${{ github.event.pull_request.number || github.ref }}`

`cancel-in-progress: true`.

Это отменяет устаревший run того же PR, но не смешивает PR, push и manual runs.

## 9. Verification pipeline

Порядок шагов:

1. Checkout.
2. Setup PHP 8.5 без Composer и coverage.
3. Проверка версии PHP и initial clean checkout.
4. Event-aware `git diff --check`.
5. NUL-safe lint tracked `*.php`.
6. Explicit allowlist из девяти CI-safe checker’ов.
7. Final clean-worktree check.

Все shell steps используют `set -euo pipefail`.

## 10. PHP lint

Список формируется только из Git index:

`git ls-files -z -- '*.php'`

Отсутствие tracked PHP считается ошибкой. Каждый файл передаётся отдельному `php -l`. Untracked и generated files в lint scope не входят.

## 11. CI-safe checker allowlist

Workflow запускает ровно:

1. `database/check-theme-asset-failure.php`
2. `tools/check-all-theme-directory-assets.php`
3. `tools/check-organizational-structure-migration-compatibility.php`
4. `tools/check-organizational-structure-ui-polish.php`
5. `tools/check-military-occupational-specialties-ui.php`
6. `tools/check-military-rank-compatibility-service.php`
7. `tools/check-military-rank-v2-loader.php`
8. `tools/check-military-ranks-directory-v2-source.php`
9. `tools/check-military-ranks-directory-v2-ui-layout.php`

Discovery по glob запрещён: новый checker не получает автоматического доверия.

## 12. Исключённые классы

Не запускаются:

- security и Organization integration checker’ы с MySQL;
- checker’ы, требующие `config/local.php`;
- hybrid checker’ы, которые без DB завершаются успешным `SKIP`;
- `run-permission-baseline-compatible-checker.php`, создающий временный файл в checkout;
- PowerShell test runners;
- deploy, installer, HTTP smoke и backup.

## 13. Failure semantics

Job завершается failure при:

- unsupported event;
- неполном или недоступном Git range;
- whitespace error;
- PHP не `8.5.x`;
- отсутствии tracked PHP;
- PHP syntax error;
- отсутствующем checker-файле;
- ненулевом exit code checker’а;
- изменении tracked или untracked repository state;
- timeout или Action failure.

`continue-on-error` запрещён.

## 14. Stage separation

Stage A:

- добавить workflow;
- проверить его в PR;
- выполнить Final PR Review;
- merge только после отдельного разрешения;
- проверить push и manual run после merge.

Stage B — отдельное утверждение:

- required status check `asu-vch-static-verification`;
- require conversation resolution;
- любые изменения branch protection.

## 15. Approved changed-path allowlist

1. `.github/workflows/static-verification.yml`
2. `docs/architecture/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-ARCHITECTURE.md`
3. `docs/specification/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-SPECIFICATION.md`
4. `docs/review/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-FORMAL-REVIEW.md`
5. `docs/decisions/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-APPROVAL.md`
6. `docs/implementation/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-IMPLEMENTATION.md`
7. `docs/testing/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-TEST-REPORT.md`
8. `docs/review/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-PR-FINAL-REVIEW.md`

Расширение allowlist требует отдельного разрешения.
