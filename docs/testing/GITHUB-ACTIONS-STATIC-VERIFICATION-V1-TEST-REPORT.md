# GitHub Actions Static Verification v1 — Test Report

**Статус:** PR WORKFLOW PASS
**Дата:** 2026-08-03
**PR:** #25
**Base:** `feac7230616d3a8df98acb48f43a0b60f89f2255`
**Validated implementation/remediation head:** `7bc170d4673b1143e4b7d149738a4c081e2af476`

## 1. Scope

Проверялись только утверждённые static CI capabilities:

- workflow registration и `pull_request` trigger;
- runner и PHP runtime;
- token permissions;
- immutable Action references;
- event-aware `git diff --check`;
- tracked PHP lint;
- девять CI-safe checker’ов;
- final repository integrity.

DB, migrations, installer, deploy, HTTP, browser, visual и mobile testing не выполнялись и не входят в scope.

## 2. Pre-PR validation

До записи workflow выполнены:

- YAML parsing: PASS;
- `bash -n` для пяти shell steps: PASS;
- review команды PHP lint: первоначальная форма `php -l -- <file>` отклонена до commit, поскольку локальная проверка показала зависание; реализована корректная форма `php -l <file>`;
- changed-path allowlist review: PASS;
- immutable SHA review: PASS.

## 3. Attempt 1 — expected fail-closed evidence

Workflow run:

- run ID: `30836352719`;
- run number: `1`;
- head: `973d952a3d2310aa307117310cec530854e63760`;
- job: `asu-vch-static-verification`;
- result: `FAILURE`.

Успешно до failure:

- runner: `ubuntu-24.04`;
- token permissions: `contents: read`, metadata read;
- checkout immutable SHA: PASS;
- setup-php immutable SHA: PASS;
- PHP installed: `8.5.9`;
- coverage disabled: PASS;
- Composer tools disabled: PASS;
- initial checkout clean: PASS.

Failure step:

`Event-aware git diff check`

Причина:

`git diff --check` обнаружил Markdown hard-break trailing spaces в пяти документах.

Это подтвердило fail-closed behavior до PHP lint и checker execution.

## 4. Remediation

Исправлены только trailing spaces в:

- Architecture;
- Specification;
- Formal Review;
- Approval;
- Implementation.

Workflow logic, runtime files и checker files не изменялись.

Remediation выполнена отдельными fast-forward commits. Не применялись force-push, rebase или squash.

## 5. Successful Pull Request run

Workflow run:

- run ID: `30836630576`;
- run number: `6`;
- job ID: `91763264596`;
- workflow: `ASU-VCH Static Verification`;
- job: `asu-vch-static-verification`;
- validated head: `7bc170d4673b1143e4b7d149738a4c081e2af476`;
- merge test ref: `5beb8a5f9f3ac9bcdc0472c496644234eabe2edc`;
- conclusion: `SUCCESS`.

## 6. Environment evidence

- runner image: `ubuntu-24.04`;
- OS: Ubuntu 24.04.4 LTS;
- PHP: `8.5.9`;
- coverage: none;
- tools: none;
- GitHub token: contents read, metadata read;
- checkout credentials removed after checkout;
- secrets used by repository commands: none.

## 7. Step results

- Checkout: PASS
- Setup PHP 8.5: PASS
- Verify PHP runtime and clean checkout: PASS
- Event-aware git diff check: PASS
- Lint tracked PHP files: PASS
- Run CI-safe checkers: PASS
- Verify final repository integrity: PASS
- Post Checkout: PASS

## 8. Event-aware diff evidence

- event: `pull_request`;
- exact base SHA: `feac7230616d3a8df98acb48f43a0b60f89f2255`;
- exact head SHA: `7bc170d4673b1143e4b7d149738a4c081e2af476`;
- result marker: `GIT_DIFF_CHECK_STATUS=PASS`.

## 9. PHP lint evidence

- source: tracked Git files only;
- NUL-safe enumeration: PASS;
- PHP files checked: `124`;
- syntax errors: `0`;
- result marker: `PHP_LINT_STATUS=PASS`.

## 10. CI-safe checker evidence

Executed count: `9`.

1. `database/check-theme-asset-failure.php` — PASS
2. `tools/check-all-theme-directory-assets.php` — PASS
3. `tools/check-organizational-structure-migration-compatibility.php` — PASS
4. `tools/check-organizational-structure-ui-polish.php` — PASS, 64 PASS / 0 FAIL
5. `tools/check-military-occupational-specialties-ui.php` — PASS
6. `tools/check-military-rank-compatibility-service.php` — PASS
7. `tools/check-military-rank-v2-loader.php` — PASS
8. `tools/check-military-ranks-directory-v2-source.php` — PASS
9. `tools/check-military-ranks-directory-v2-ui-layout.php` — PASS

Result marker: `CI_SAFE_CHECKERS_STATUS=PASS`.

## 11. Repository integrity

Final command checked tracked and untracked state.

Result marker:

`REPOSITORY_WORKTREE_STATUS=PASS`

No checker left repository modifications.

## 12. Warnings

GitHub runner emitted a non-blocking platform warning that `actions/checkout` targets Node.js 20 and was forced to run on Node.js 24 by the current runner platform.

Assessment:

- action was pinned to the approved immutable SHA;
- checkout completed successfully;
- warning did not affect workflow result;
- no workflow change is required in this increment.

## 13. Documentation synchronization note

This Test Report is a documentation-only commit after the validated implementation/remediation head. Therefore its resulting PR head must receive a fresh successful `pull_request` run before Final PR Review.

## 14. Boundaries

- DB testing: OUT OF SCOPE / NOT RUN
- Deploy testing: OUT OF SCOPE / NOT RUN
- HTTP/browser testing: OUT OF SCOPE / NOT RUN
- Visual desktop acceptance: OUT OF SCOPE / NOT RUN
- Mobile testing: OUT OF SCOPE / NOT RUN
- Branch protection mutation: NOT PERFORMED
- Merge: NOT PERFORMED

## 15. Verdict

`PULL_REQUEST_WORKFLOW_TESTING_STATUS=PASS`

Final PR Review remains pending until the documentation synchronization run on the current PR head succeeds.
