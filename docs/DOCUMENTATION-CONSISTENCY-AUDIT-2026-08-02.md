# Аудит согласованности документации АСУ-ВЧ — 2026-08-02

## 1. Назначение

Датированный audit record фиксирует полный read-only анализ документации и утверждённую remediation относительно functional/repository baseline:

```text
repository: ClaytonKinnane/ASU-VCH
baseline main: 1eef56b50a8d2278a62c5b70a471663b12132354
latest functional PR: #20
completed documentation merges reviewed: through PR #22
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
date: 2026-08-02
```

Документ является immutable historical audit snapshot. Live HEAD, PR, Issue и branch state определяется через GitHub и Git, а не этим файлом.

## 2. Методика

Проверены Markdown-paths, появившиеся или изменявшиеся в merged PR #1–#22, включая:

- root и `docs` indexes;
- living current-state documentation;
- target architecture;
- domain, ERD и migration specifications;
- Architecture, Review, Approval, Implementation и Test Evidence;
- repository audit/cleanup records;
- increment-specific runbooks.

Документация сопоставлена с:

- live GitHub repository state;
- `docs/PROJECT-STATUS.md`;
- `docs/DATABASE-CURRENT.md`;
- executable migrations 001–011;
- `config/themes.php`;
- merged PR metadata;
- terminal verification, предоставленной владельцем после PR #22.

Historical `NOT AUTHORIZED`, `NOT CREATED`, `PENDING` и аналогичные markers не считались дефектами, если они корректно описывали состояние конкретного gate и не использовались как current-state assertion.

## 3. Source-of-truth matrix

| Область | Источник истины |
|---|---|
| Live repository HEAD, branches, PRs, Issues | GitHub / Git |
| Current functional baseline | `PROJECT-STATUS.md` |
| Current physical schema | `DATABASE-CURRENT.md`, executable migrations, installer |
| Theme registry и required assets | `config/themes.php` |
| Target database architecture | `DATABASE.md` |
| Current domain inventory | `domains/README.md` |
| Current migration inventory | `migrations/README.md` |
| Historical gate/test state | dated process and testing records |
| Cleanup terminal state | dated immutable cleanup records |

## 4. Подтверждённые findings

### F-01 — stale domain index — Major

`docs/domains/README.md` завершал functional inventory на PR #15 и перечислял только два специализированных Reference-каталога.

Фактически merged PR #19 и PR #20 добавили:

- типовые воинские должности;
- публичные сведения о военно-учётных специальностях.

Также `Positions and assignments` неоднозначно смешивал уже реализованный public position-types catalog с будущей кадровой/штатной моделью.

### F-02 — stale migration index — Major

`docs/migrations/README.md` объявлял текущими migrations 001–009, хотя functional baseline содержит 001–011.

Отсутствовали current-index сведения о:

- migration 010 и пяти gzip/base64 parts;
- migration 011 и двух gzip/base64 parts;
- archive/canonical SQL SHA-256 verification;
- неизменности system permission baseline 25.

### F-03 — credential terminology conflict — Major

`docs/LOCAL-RUNBOOK.md` запрещал публикацию любых credentials и temporary passwords без исключения, тогда как historical specification и current local seed публично содержат `Admin / 12315`.

Конфликт решён классификацией этой пары как public local-only development fixture, а не production или instance-specific credential.

### F-04 — broken textual reference — Minor

`docs/DATABASE.md` ссылался на отсутствующий `DOMAINS.md`. Актуальная точка входа — `domains/README.md`.

### F-05 — ambiguous historical specification — Minor

`docs/STARTER-ADMIN-SPEC.md` сохранял branch/forward-looking language завершённого initial-site increment без явного `HISTORICAL IMPLEMENTED SPECIFICATION` framing.

### F-06 — target architecture presented as current plan — Minor

Финальный раздел `docs/DATABASE.md` представлял первоначальную последовательность Review → ERD → migrations → Implementation как ещё предстоящую, хотя часть этой работы завершена последующими инкрементами.

### F-07 — shortened domain workflow — Minor

`docs/domains/README.md` не включал Analysis, Commit, Push, Final PR Review, post-merge verification и separate branch deletion approval.

### F-08 — structural root cause — Major

Предыдущая классификация опиралась преимущественно на каталог файла. Поэтому living current-state sections внутри domain/migration indexes не попали в refresh PR #21.

## 5. Root cause

Главная причина — отсутствие project-wide правила:

```text
semantic classification overrides directory classification
```

Файл может одновременно выполнять target и living-index функции. Любой раздел с current-state assertions обязан обновляться вместе с baseline независимо от каталога.

## 6. Утверждённая remediation

Владелец явно утвердил:

```text
APPROVE_FULL_DOCUMENTATION_CONSISTENCY_RECONCILIATION
APPROVE_DOCUMENTATION_ONLY_SCOPE
APPROVE_15_PATH_ALLOWLIST
APPROVE_LOCAL_FIXTURE_TERMINOLOGY
DEFER_RUNTIME_PASSWORD_HARDENING_TO_SEPARATE_INCREMENT
```

Выполнено:

1. domain index обновлён до PR #20 и четырёх Reference-каталогов;
2. migration index обновлён до 001–011 и дополнен packaging/integrity сведениями;
3. введено semantic classification rule;
4. target/current/historical classes разделены в documentation index;
5. `DATABASE.md` получил target banner, корректную ссылку и historical framing;
6. starter specification получил implemented historical banner;
7. local fixture terminology согласована в runbook, specification и architectural patterns;
8. mandatory domain workflow синхронизирован с project workflow;
9. changelog дополнен documentation-only reconciliation;
10. runtime/schema/config/theme/tool state оставлен неизменным.

## 7. Local fixture decision

Текущий baseline содержит:

```text
username: Admin
password: 12315
environment: local only
must_change_password: true
```

Это public development fixture для воспроизводимого локального bootstrap, а не secret конкретной установки.

Не публикуются:

- production credentials;
- instance-specific credentials;
- реальные временные пароли пользователей;
- session identifiers/data;
- private keys и tokens;
- содержимое `config/local.php`.

Fixture запрещён для production, не должен повторно использоваться и требует смены пароля при первом входе.

## 8. Deferred Security debt

Interactive password prompt, безопасная генерация или иное удаление фиксированного local fixture из `database/seed-local-owner.php` не выполнены этим инкрементом.

```text
DEFERRED_ITEM=LOCAL_FIXTURE_PASSWORD_HARDENING
CLASSIFICATION=FUTURE_SECURITY_INCREMENT
RUNTIME_CHANGE_IN_THIS_INCREMENT=NONE
```

Перед такой реализацией требуется отдельный Research → Analysis → Architecture → Specification → Review → Approval workflow и полноценное runtime/regression testing.

## 9. Exact approved changed-path set

```text
docs/ARCHITECTURAL-PATTERNS.md
docs/CHANGELOG.md
docs/DATABASE.md
docs/LOCAL-RUNBOOK.md
docs/README.md
docs/STARTER-ADMIN-SPEC.md
docs/domains/README.md
docs/migrations/README.md
docs/DOCUMENTATION-CONSISTENCY-AUDIT-2026-08-02.md
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

## 10. Validation authority

Финальный technical result и exact validated head фиксируются в:

`testing/FULL-DOCUMENTATION-CONSISTENCY-RECONCILIATION-VALIDATION.md`

Этот audit record не хранит transient PR number/state или branch lifecycle как постоянно актуальный project status.

## 11. Test classification

```text
PHP_LINT=NOT_REQUIRED
SQL_SCHEMA_TESTING=NOT_REQUIRED
INSTALLER=NOT_REQUIRED
DEPLOY=NOT_REQUIRED
DATABASE_RETEST=NOT_REQUIRED
BROWSER_TESTING=NOT_REQUIRED
MOBILE_TESTING=OUT_OF_SCOPE_NOT_RUN
```

Причина: approved diff является строго Markdown-only. Runtime, database, migrations, configuration, themes, public routes, deploy scripts и tools не изменяются.
