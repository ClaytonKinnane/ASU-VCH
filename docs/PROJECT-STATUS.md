# Текущее состояние проекта АСУ-ВЧ

Дата актуализации: `2026-08-01`.

## Репозиторий и functional anchors

Актуальный stable HEAD определяется через `origin/main`:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
latest functional PR: #20
PR #19 merge commit: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #19 final feature HEAD: 5424cefe2f1a6bdc2fa706612040a3985c88f04f
PR #20 merge / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
PR #20 final feature HEAD: bea147505a85010b61fe938eb07ec474d76cdab5
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
```

Documentation-only commits после tested runtime не объявляются runtime-tested.

## Последние functional increments

### PR #19 — Типовые воинские должности

```text
status: MERGED
migration: 010
tables: 14
triggers: 41
canonical types: 34
variants: 35
automated testing: PASS
manual desktop acceptance: PASS
mobile testing: OUT OF SCOPE / NOT RUN
```

Owner-only read-only catalog не создаёт штатные позиции, кадровые назначения или automatic rank relations.

### PR #20 — Публичные сведения о ВУС

```text
status: MERGED
migration: 011
tables: 9
triggers: 26
searchable records: 17
automated testing: PASS
manual desktop acceptance: PASS
targeted manual desktop recheck: PASS
final PR review: PASS
post-merge Git verification: PASS
mobile testing: OUT OF SCOPE / NOT RUN
```

Каталог не связан с должностями, званиями, ВВСТ или personal data и не заявляется как полный персональный воинский учёт.

## Реализованные области

- bootstrap owner, authentication, protected sessions и CSRF;
- 4 system roles и 25 permissions;
- полный user lifecycle и required password change;
- 3 trusted themes;
- owner-only read-only directories: ranks, organizational element types, military positions, public VUS;
- Organizational Structure v1;
- migrations 001–011.

## Последний проверенный runtime baseline

```text
runtime head: 9db06c4a26066ca25dc36c627c1236089a3c1238
PHP lint: 113 files / 0 errors
applied migrations: 11
repeat installer: no new migrations
VUS integration checker: PASS
VUS UI checker: PASS
organization regression: 58 PASS / 0 FAIL
source/deploy parity: 14 paths / PASS
HTTP smoke: PASS
manual desktop acceptance: PASS
targeted manual recheck: PASS
```

## Активный documentation increment

```text
increment: Post-PR20 Baseline Refresh
branch: docs/post-pr20-baseline-refresh
PR: #21 OPEN
classification: documentation only
initial allowlist: 22 Markdown paths
final approved allowlist: 25 Markdown paths
Final PR Review attempt 1: CHANGES REQUIRED
remediation: IMPLEMENTED / REVALIDATION IN PROGRESS
merge: NOT AUTHORIZED
branch deletion: NOT AUTHORIZED
```

Первый Final PR Review потребовал закрыть operational records PR #19 и синхронизировать current-state markers с уже созданным PR #21. Владелец отдельно утвердил расширение allowlist до 25 путей.

## Repository cleanup status

Исторический cleanup 2026-07-31 завершён. Текущий cleanup не выполнялся. После merge PR #21 требуются post-merge verification, fresh remote/local inventory, exact cleanup batch и отдельное owner approval.

## Текущий gate

```text
repeat Documentation Validation: REQUIRED
repeat Final PR Review: REQUIRED
merge approval: NOT GRANTED
branch deletion approval: NOT GRANTED
```
