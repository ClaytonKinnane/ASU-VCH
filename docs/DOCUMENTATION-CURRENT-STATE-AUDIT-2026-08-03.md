# Аудит текущего состояния документации АСУ-ВЧ — 2026-08-03

## 1. Классификация

```text
record: immutable read-only audit snapshot
repository: ClaytonKinnane/ASU-VCH
baseline main: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
date: 2026-08-03
result: CHANGES_REQUIRED
blocking findings: 0
major findings: 6
minor findings: 1
```

Документ фиксирует состояние до remediation. Live HEAD, branches, Pull Requests и Issues определяются через GitHub/Git, а не этим snapshot.

## 2. Методика

Проверены:

- living documentation и living indexes;
- operational records PR #24 и PR #25;
- merged PR metadata и post-merge evidence;
- executable migration marker 012 и compatibility implementation;
- `config/themes.php`;
- `.github/workflows/static-verification.yml`;
- documentation classification rules после reconciliation PR #23;
- branch lifecycle PR #24/#25;
- relative links, stale assertions, historical anchors, mobile claims и secret boundaries.

Historical `PENDING`, `NOT AUTHORIZED` и `NOT PERFORMED` не считались ошибкой, если они корректно описывали конкретный gate. Ошибкой считалось отсутствие отдельного closure либо использование такого marker как current status.

## 3. Source-of-truth matrix

| Область | Источник истины |
|---|---|
| Live Git state | GitHub / Git |
| Functional capability | merged PR #24, current runtime files, Test Report и post-merge evidence |
| Physical schema | `DATABASE-CURRENT.md`, executable migrations, installer, compatibility loaders |
| Theme assets | `config/themes.php` |
| Static CI | `.github/workflows/static-verification.yml` и workflow run evidence |
| Historical gates | dated Architecture, Specification, Approval, Review и Test records |

## 4. Findings

### M-01 — stale canonical functional baseline — Major

Living documents завершались functional PR #20 и migrations 001–011, хотя PR #24 merged и migration 012 применена.

### M-02 — stale physical schema and migration index — Major

`DATABASE-CURRENT.md`, migration index и runbooks ожидали 11 migrations и не описывали v1/v2 lifecycle, semantics/source tables и 18 v2 triggers.

### M-03 — incorrect theme asset count — Major

Living theme documentation указывала 9 required CSS-assets, тогда как registry содержит 10, включая `css/military-ranks-v2.css`.

### M-04 — CI capability classified as absent/future — Major

Living project/roadmap documents не отражали merged GitHub Actions Static Verification v1 и успешные push/manual post-merge runs.

### M-05 — incomplete PR #25 operational status — Major

Implementation, Test Report и Final PR Review сохраняли незакрытые pre-merge status fields без additive post-merge/branch-lifecycle closure.

### M-06 — changelog gap — Major

Changelog не содержал material outcomes PR #24 и PR #25.

### m-01 — incomplete PR #24 operational closure — Minor

Исторические gate statements были корректны, но operational records не содержали полного post-merge и branch-deletion outcome.

## 5. Canonical remediation facts

```text
latest functional PR: #24
latest technical PR: #25
PR #24 merge: feac7230616d3a8df98acb48f43a0b60f89f2255
PR #25 merge: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
migrations: 001–012
roles: 4
permissions: 25
themes: 3
required CSS assets per theme: 10
static CI: implemented
required status check: not enabled
branch protection changed by PR #25: no
```

## 6. PR #24 evidence

```text
runtime/manual acceptance head: b44aed14ee1a54be213cbc939322ba21b02e7a58
Final PR Review remediation head: fe893e8315f7add80ed4d0501b41d8bc39b4b0e8
final feature head: 2e996849ec51be4d83676aa779bf7e797e35932e
merge commit: feac7230616d3a8df98acb48f43a0b60f89f2255
post-merge verification: PASS
repeat installer: 12 / no new migrations
database regression: PASS
deploy/source parity: PASS
HTTP smoke: PASS
feature branch: deleted after separate approval
mobile: OUT OF SCOPE / NOT RUN
```

## 7. PR #25 evidence

```text
validated implementation/remediation head: 7bc170d4673b1143e4b7d149738a4c081e2af476
exact Final PR Review head: 0c6f7338f912e8797868d02d54fc015df7533ad6
merge commit: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
push run: 30837637886 / SUCCESS
workflow_dispatch run: 30839122892 / SUCCESS
tracked PHP lint evidence: 124 / 0 errors
CI-safe checkers: 9 / PASS
branch protection changed: no
required status check enabled: no
feature branch: deleted after separate approval
DB/deploy/browser/mobile: OUT OF SCOPE / NOT RUN
```

## 8. Approved remediation

Владелец утвердил Architecture, Specification, Formal Review и exact 29-path Markdown allowlist. Runtime, database, migration, workflow, theme, deploy, tool и settings changes запрещены.

Remediation применяется additive способом:

- living/current-state sections обновляются;
- historical gate evidence сохраняется;
- operational records получают отдельные closure sections;
- current `main` SHA не хранится как самореферентное living field;
- ветки и PR inventory остаются dynamic.

## 9. Test classification

```text
semantic documentation validation: REQUIRED
relative link validation: REQUIRED
stale assertion scan: REQUIRED
historical anchor review: REQUIRED
secret boundary review: REQUIRED
git diff check: REQUIRED
runtime/DB/deploy/browser retest: NOT REQUIRED
mobile: OUT OF SCOPE / NOT RUN
```

## 10. Remediation authority

Architecture, Specification, Formal Review, Approval, Implementation и Validation этого reconciliation являются process records. Этот audit snapshot не должен переписываться как будто findings никогда не существовали.
