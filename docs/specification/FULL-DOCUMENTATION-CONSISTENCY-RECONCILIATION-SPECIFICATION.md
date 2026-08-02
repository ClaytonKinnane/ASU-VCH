# Full Documentation Consistency Reconciliation — Specification

## 1. Статус

```text
stage: Specification
status: APPROVED
classification: documentation-only
repository: ClaytonKinnane/ASU-VCH
branch: docs/full-documentation-consistency-reconciliation
baseline main: 1eef56b50a8d2278a62c5b70a471663b12132354
date: 2026-08-02
```

## 2. Цель

Исправить все подтверждённые findings полного документационного аудита без изменения runtime, schema, configuration, themes, tools или repository governance.

## 3. Exact changed-path allowlist

### 3.1 Исправляемые документы

```text
docs/ARCHITECTURAL-PATTERNS.md
docs/CHANGELOG.md
docs/DATABASE.md
docs/LOCAL-RUNBOOK.md
docs/README.md
docs/STARTER-ADMIN-SPEC.md
docs/domains/README.md
docs/migrations/README.md
```

### 3.2 Immutable audit record

```text
docs/DOCUMENTATION-CONSISTENCY-AUDIT-2026-08-02.md
```

### 3.3 Process/evidence records

```text
docs/architecture/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-ARCHITECTURE.md
docs/specification/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-SPECIFICATION.md
docs/review/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-FORMAL-REVIEW.md
docs/decisions/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-APPROVAL.md
docs/implementation/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-IMPLEMENTATION.md
docs/testing/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-VALIDATION.md
```

```text
EXPECTED_PATH_COUNT=15
EXPECTED_MARKDOWN_PATH_COUNT=15
EXPECTED_NON_MARKDOWN_PATH_COUNT=0
```

## 4. Required changes

### `docs/ARCHITECTURAL-PATTERNS.md`

Добавить semantic classification rule, mixed-document rule и обязательное включение living indexes в baseline refresh.

### `docs/domains/README.md`

- обновить merged functional inventory до PR #20;
- отразить четыре реализованных read-only Reference catalogs;
- отделить будущие штатные позиции/назначения от реализованного каталога типов должностей;
- синхронизировать mandatory workflow.

### `docs/migrations/README.md`

- обновить current range до 001–011;
- добавить migrations 010 и 011;
- описать compatibility packaging и профильные evidence;
- подтвердить, что permission baseline остаётся 25.

### `docs/DATABASE.md`

- добавить `TARGET ARCHITECTURE` banner;
- указать current physical schema sources;
- исправить `DOMAINS.md` на `domains/README.md`;
- переименовать первоначальный «Следующий этап» в historical sequence.

### `docs/STARTER-ADMIN-SPEC.md`

- добавить `HISTORICAL IMPLEMENTED SPECIFICATION` banner;
- сослаться на PR #1 и current-state documents;
- классифицировать `Admin / 12315` как local-only development fixture;
- не переписывать исходные requirements задним числом.

### `docs/LOCAL-RUNBOOK.md`

Уточнить security boundary:

- production/instance credentials, реальные temporary passwords, sessions и `config/local.php` не публикуются;
- известный local-only fixture не является secret;
- production use запрещён;
- hardening отложен в отдельный Security increment.

### `docs/README.md`

- разделить living indexes, target architecture, historical implemented specifications и immutable audits;
- добавить новый audit/process records;
- не хранить transient state настоящего PR.

### `docs/CHANGELOG.md`

Добавить запись о documentation reconciliation и отсутствии runtime/schema/config changes.

### Audit record

Зафиксировать baseline, methodology, findings, source-of-truth matrix, remediation, validation и remaining Security debt.

## 5. Historical preservation

Запрещено:

- удалять исходные requirements исторических specifications;
- переписывать старые gate states как будто они были иными;
- заменять tested runtime anchors documentation-only heads;
- представлять target architecture как уже реализованный runtime.

Допускаются status banners, closure/addendum sections и исправления ссылок/классификации.

## 6. Validation contract

```text
BASELINE_SHA_STATUS=PASS
CHANGED_PATH_ALLOWLIST_STATUS=PASS
EXPECTED_PATH_COUNT=15
ACTUAL_PATH_COUNT=15
MARKDOWN_ONLY_STATUS=PASS
NON_MARKDOWN_DIFF=0
DOMAIN_INDEX_BASELINE_STATUS=PASS
MIGRATION_INDEX_001_011_STATUS=PASS
DOCUMENT_CLASSIFICATION_STATUS=PASS
TARGET_RUNTIME_SEPARATION_STATUS=PASS
BROKEN_REFERENCE_SCAN_STATUS=PASS
CREDENTIAL_TERMINOLOGY_STATUS=PASS
HISTORICAL_CONTENT_PRESERVATION_STATUS=PASS
TRANSIENT_PR_STATE_SCAN_STATUS=PASS
REMOVED_BRANCH_DEPENDENCY_SCAN_STATUS=PASS
MARKDOWN_LINK_VALIDATION_STATUS=PASS
SECRET_REVIEW_STATUS=PASS
MAIN_INTEGRITY_STATUS=PASS
```

## 7. Exclusions

```text
runtime password generation/prompt
seed-local-owner.php changes
new migration
schema changes
permission changes
theme changes
route changes
deploy changes
browser/mobile acceptance
```

## 8. Gates

Implementation разрешена только после owner approval. PR creation, merge и branch deletion требуют отдельных последующих approvals.
