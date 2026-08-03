# GitHub Actions Static Verification v1 — Specification

**Статус:** Approved for Implementation  
**Baseline:** `main @ feac7230616d3a8df98acb48f43a0b60f89f2255`

## 1. Repository contract

Implementation выполняется в:

`feature/github-actions-static-verification-v1`

Ветка должна быть создана от exact approved baseline. При изменении `main` до создания ветки операция останавливается fail-closed.

## 2. Workflow contract

- path: `.github/workflows/static-verification.yml`;
- workflow name: `ASU-VCH Static Verification`;
- job ID: `asu-vch-static-verification`;
- job name: `asu-vch-static-verification`;
- runner: `ubuntu-24.04`;
- timeout: 10 minutes.

## 3. Trigger contract

Обязательны:

- `pull_request` в `main`: `opened`, `synchronize`, `reopened`, `ready_for_review`;
- `push` в `main`;
- `workflow_dispatch`.

Запрещены `pull_request_target`, `schedule`, `repository_dispatch`, `workflow_run` и path filters.

## 4. Permission contract

Top-level:

```yaml
permissions:
  contents: read
```

Запрещены write permissions, secrets, environments и OIDC.

## 5. Concurrency contract

```yaml
concurrency:
  group: asu-vch-static-verification-${{ github.event_name }}-${{ github.event.pull_request.number || github.ref }}
  cancel-in-progress: true
```

Допустимо только эквивалентное выражение с разделением event type и PR/ref.

## 6. Immutable Actions

Checkout:

```yaml
uses: actions/checkout@11d5960a326750d5838078e36cf38b85af677262
with:
  fetch-depth: 0
  persist-credentials: false
```

PHP setup:

```yaml
uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240
with:
  php-version: '8.5'
  coverage: none
  tools: none
```

Floating refs запрещены.

## 7. Runtime verification

Workflow должен:

- вывести `php --version`;
- завершиться failure, если major/minor не равны `8.5`;
- подтвердить initial clean checkout;
- использовать Bash и `set -euo pipefail`.

Patch version не фиксируется.

## 8. Event-aware diff

Pull Request:

`git diff --check <pull_request.base.sha> <pull_request.head.sha>`

Push:

`git diff --check <event.before> <github.sha>`

При forty-zero `before` используется empty tree.

Manual:

- `HEAD^..HEAD`, если parent существует;
- empty tree..HEAD для root commit.

Unsupported event обязан завершаться failure.

## 9. Tracked PHP lint

Источник списка:

`git ls-files -z -- '*.php'`

Требования:

- NUL-safe обработка;
- минимум один tracked PHP-файл;
- каждый файл проверяется `php -l`;
- число файлов выводится в log;
- Composer и vendor setup отсутствуют.

## 10. Checker allowlist

Запускаются только:

- `database/check-theme-asset-failure.php`;
- `tools/check-all-theme-directory-assets.php`;
- `tools/check-organizational-structure-migration-compatibility.php`;
- `tools/check-organizational-structure-ui-polish.php`;
- `tools/check-military-occupational-specialties-ui.php`;
- `tools/check-military-rank-compatibility-service.php`;
- `tools/check-military-rank-v2-loader.php`;
- `tools/check-military-ranks-directory-v2-source.php`;
- `tools/check-military-ranks-directory-v2-ui-layout.php`.

Требования:

- explicit ordered array;
- имя каждого checker’а выводится;
- отсутствие файла — failure;
- ненулевой exit code — failure;
- output не подавляется;
- `continue-on-error` отсутствует.

## 11. Explicit exclusions

Workflow не должен запускать или использовать:

- `config/local.php`;
- `database/install.php`;
- MySQL;
- security DB checker’ы;
- `database/check-theme-management.php`;
- `database/check-organizational-structure.php`;
- directory core checker’ы с DB;
- hybrid military positions/VUS integration checker’ы;
- permission-baseline runtime adapter;
- `Deploy-Local.ps1`;
- PowerShell full test runners;
- HTTP smoke;
- backup;
- source/deploy parity.

## 12. No-mutation contract

Repository run steps не должны:

- создавать файлы внутри checkout;
- изменять tracked files;
- stage/commit/push;
- switch/merge/rebase/reset/clean;
- изменять Git config;
- deploy;
- изменять БД или repository settings.

Системный temp вне checkout допускается для изолированного checker’а.

## 13. Final integrity

В конце:

`git status --porcelain=v1 --untracked-files=all`

Любой вывод — failure. PASS marker разрешён только после фактической чистоты.

## 14. Forbidden features

Запрещены:

- services/containers;
- cache;
- artifact upload;
- dependency installation;
- Composer/npm;
- DB;
- deploy;
- HTTP/browser tests;
- coverage;
- reusable workflows;
- dynamic action refs;
- third-party reporting.

## 15. Acceptance criteria

- AC-01: branch создана от approved exact baseline.
- AC-02: изменены только восемь allowlisted paths.
- AC-03: runtime/DB/UI/theme files не изменены.
- AC-04: workflow path и names точны.
- AC-05: events соответствуют contract; `pull_request_target` отсутствует.
- AC-06: единственное permission — `contents: read`.
- AC-07: Actions закреплены approved full SHA.
- AC-08: checkout credentials не сохраняются.
- AC-09: runner `ubuntu-24.04`, timeout 10.
- AC-10: runtime подтверждает PHP `8.5.x`.
- AC-11: Composer и coverage отсутствуют.
- AC-12: event-aware `git diff --check` использует payload SHA.
- AC-13: все tracked PHP linted NUL-safe способом.
- AC-14: запущены ровно девять CI-safe checker’ов.
- AC-15: DB/Windows/deploy checker’ы не запускаются.
- AC-16: final repository state clean.
- AC-17: PR workflow run success.
- AC-18: Final PR Review выполнен на exact head.
- AC-19: merge не выполняется без отдельного разрешения.
- AC-20: post-merge push run success.
- AC-21: post-merge manual run success.
- AC-22: branch protection не изменена.
- AC-23: feature branch не удалена без отдельного разрешения.
- AC-24: CI не заявлен заменой DB/deploy/visual tests.
- AC-25: Mobile — `OUT OF SCOPE / NOT RUN`.

## 16. Testing sequence

1. Проверить exact branch/base/head и allowlist.
2. Проверить YAML и shell contracts.
3. Создать PR.
4. Дождаться Pull Request workflow run.
5. Проверить exact head, job name, runner, PHP, diff, lint, девять checker’ов и clean worktree.
6. Зафиксировать Test Report.
7. Выполнить Final PR Review на exact current head.
8. Остановиться перед merge.
