# GitHub Actions Static Verification v1 — Implementation

**Статус:** IMPLEMENTED / PR VERIFICATION PENDING  
**Дата:** 2026-08-03  
**Base:** `main @ feac7230616d3a8df98acb48f43a0b60f89f2255`  
**Branch:** `feature/github-actions-static-verification-v1`

## 1. Preflight

Перед Implementation:

- actual `main` повторно проверен;
- actual SHA совпал с approved baseline;
- target feature branch отсутствовала;
- ветка создана строго от approved SHA;
- branch protection и Actions settings не менялись.

## 2. Implemented workflow

Создан:

`.github/workflows/static-verification.yml`

Identity:

- workflow: `ASU-VCH Static Verification`;
- job ID/name: `asu-vch-static-verification`.

Runtime:

- `ubuntu-24.04`;
- PHP `8.5.x`;
- no coverage;
- no Composer;
- 10-minute timeout.

Events:

- `pull_request` to `main`;
- `push` to `main`;
- `workflow_dispatch`.

## 3. Security implementation

Реализовано:

- `permissions: contents: read`;
- no secrets;
- no environments;
- no `pull_request_target`;
- immutable full action SHA;
- checkout `fetch-depth: 0`;
- checkout `persist-credentials: false`;
- no cache/artifacts/services;
- no DB/deploy/network-dependent repository commands;
- final tracked/untracked worktree verification.

## 4. Static verification implementation

Workflow выполняет:

1. PHP runtime check `8.5.x`;
2. initial clean checkout check;
3. event-aware `git diff --check`;
4. NUL-safe lint всех tracked PHP;
5. девять explicit CI-safe checker’ов;
6. final clean-worktree check.

## 5. Event-aware diff

Pull Request использует exact payload base/head SHA.

Push использует `before` и current SHA; zero-before обрабатывается через empty tree.

Manual run проверяет parent/current commit либо empty tree для root commit.

## 6. Checker implementation

Explicit allowlist:

- `database/check-theme-asset-failure.php`;
- `tools/check-all-theme-directory-assets.php`;
- `tools/check-organizational-structure-migration-compatibility.php`;
- `tools/check-organizational-structure-ui-polish.php`;
- `tools/check-military-occupational-specialties-ui.php`;
- `tools/check-military-rank-compatibility-service.php`;
- `tools/check-military-rank-v2-loader.php`;
- `tools/check-military-ranks-directory-v2-source.php`;
- `tools/check-military-ranks-directory-v2-ui-layout.php`.

DB, hybrid, adapter, Windows и deploy checker’ы не включены.

## 7. Changed paths

Implementation ограничена утверждённым allowlist. Существующие runtime и checker files не изменены.

На текущем этапе созданы workflow и process documents. Test Report и Final PR Review заполняются только после фактических GitHub Actions evidence.

## 8. Testing status

До Pull Request доступны проверки структуры, YAML, shell contracts, exact changed paths и immutable references.

Фактический GitHub-hosted run будет зафиксирован в Test Report. До него нельзя заявлять:

- PR workflow PASS;
- push workflow PASS;
- manual workflow PASS.

DB/deploy/visual/mobile проверки не выполняются и не требуются для данного static-only increment.

Mobile: `OUT OF SCOPE / NOT RUN`.

## 9. Remaining gates

- push feature head;
- create PR;
- obtain successful `pull_request` workflow run;
- create Test Report;
- Final PR Review on exact head;
- stop before merge.

Merge и branch deletion не разрешены.
