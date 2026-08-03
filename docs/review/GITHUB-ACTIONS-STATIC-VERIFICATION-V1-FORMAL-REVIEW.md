# GitHub Actions Static Verification v1 — Formal Review

**Статус:** PASS / Approved for Implementation
**Reviewed baseline:** `main @ feac7230616d3a8df98acb48f43a0b60f89f2255`
**Дата:** 2026-08-03

## 1. Review scope

Проверены:

- актуальный GitHub baseline;
- отсутствие existing workflows;
- отсутствие Composer manifests;
- inventory всех checker/test scripts;
- CI-safety classification;
- Architecture;
- Specification;
- untrusted PR security model;
- immutable Actions;
- acceptance criteria;
- separation Stage A / Stage B.

## 2. Inventory result

Reviewed artifacts:

- 8 top-level database checker’ов;
- 2 organization helper-модуля;
- 17 PHP checker/adapter-файлов в `tools`;
- 5 PowerShell validation scripts;
- всего 32.

Classification:

- 9 CI-safe entrypoints;
- 16 DB-dependent entrypoints/helpers;
- 2 manual-only adapter/support files;
- 1 Windows-dependent script;
- 4 deploy-dependent scripts.

## 3. Approved CI-safe set

1. `database/check-theme-asset-failure.php`
2. `tools/check-all-theme-directory-assets.php`
3. `tools/check-organizational-structure-migration-compatibility.php`
4. `tools/check-organizational-structure-ui-polish.php`
5. `tools/check-military-occupational-specialties-ui.php`
6. `tools/check-military-rank-compatibility-service.php`
7. `tools/check-military-rank-v2-loader.php`
8. `tools/check-military-ranks-directory-v2-source.php`
9. `tools/check-military-ranks-directory-v2-ui-layout.php`

Для каждого подтверждено отсутствие MySQL, `config/local.php`, Windows, Open Server и deploy dependency; отсутствие repository mutation; детерминированность относительно checkout.

## 4. Findings and resolutions

### FR-01 — Hybrid checker false-positive risk

`check-military-positions-directory.php` и `check-military-occupational-specialties-directory.php` без `config/local.php` возвращают exit 0 после DB skip.

**Resolution:** классифицированы DB-dependent и исключены.

### FR-02 — Repository-local temporary checker

`run-permission-baseline-compatible-checker.php` создаёт временный PHP-файл внутри checkout.

**Resolution:** adapter не включён.

### FR-03 — Checkout credential persistence

Default checkout может сохранить credentials.

**Resolution:** `persist-credentials: false` и `contents: read`.

### FR-04 — Floating action refs

Floating refs допускают supply-chain substitution.

**Resolution:** approved full SHA для checkout и setup-php.

### FR-05 — Unneeded Composer

`setup-php` может устанавливать Composer по умолчанию.

**Resolution:** `tools: none`.

### FR-06 — Ambiguous diff range

`HEAD^` или текущий `origin/main` не гарантируют exact PR range.

**Resolution:** exact event payload SHA для PR и push.

### FR-07 — Manual dispatch before merge

Workflow ещё отсутствует в default branch.

**Resolution:** PR event проверяется pre-merge; push и `workflow_dispatch` — post-merge.

### FR-08 — Checker side effects

Checker может оставить generated file.

**Resolution:** final `git status --porcelain=v1 --untracked-files=all`.

### FR-09 — Required check name drift

Разные job ID/name осложняют Stage B.

**Resolution:** оба равны `asu-vch-static-verification`.

Все findings закрыты.

## 5. Review results

- Architecture Review: PASS
- Specification Review: PASS
- Inventory Review: PASS
- CI-safety Review: PASS
- Security Review: PASS
- Acceptance Criteria Review: PASS
- Blocking findings: 0
- Major findings: 4 identified / 4 resolved
- Minor findings: 5 identified / 5 resolved
- Open findings: 0

## 6. Residual risks

- immutable third-party Actions остаются внешним кодом;
- exact-source checker’ы могут потребовать синхронного обновления при безопасном refactoring;
- static CI не обнаруживает DB/deploy/browser defects.

Risks приняты в рамках Stage A благодаря read-only token, отсутствию secrets, ephemeral runner и явной границе CI.

## 7. Verdict

`FORMAL REVIEW RESULT: PASS FOR OWNER APPROVAL`

Owner approval получено 2026-08-03. Review разрешает Implementation только в утверждённом allowlist. Merge, branch protection changes и branch deletion не разрешены.
