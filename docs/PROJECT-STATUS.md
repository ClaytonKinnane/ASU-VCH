# Текущее состояние проекта АСУ-ВЧ

Дата актуализации functional baseline: `2026-08-01`.

## Репозиторий и anchors

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

Catalog owner-only/read-only; штатные позиции, кадровые назначения, personal data и automatic rank relations не реализованы.

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

Catalog не связан с positions, ranks, equipment или personal data и не заявляется как полный персональный воинский учёт.

## Реализованные области

- bootstrap owner, authentication, protected sessions и CSRF;
- 4 system roles и 25 permissions;
- full user lifecycle и required password change;
- 3 trusted themes;
- owner-only read-only directories: ranks, organizational element types, military positions, public VUS;
- Organizational Structure v1;
- migrations 001–011.

## Последний runtime-tested baseline

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

## Documentation baseline refresh workflow

Post-PR20 Baseline Refresh отслеживается PR #21 и веткой `docs/post-pr20-baseline-refresh`.

```text
classification: documentation only
initial allowlist: 22 Markdown paths
final approved allowlist: 25 Markdown paths
Final PR Review attempt 1: CHANGES REQUIRED
owner-approved remediation: COMPLETE
repeat Documentation Validation: PASS
```

Live state PR #21, reviews и merge status определяются в GitHub, а не хранятся в living document как постоянно актуальное поле. Exact pre-merge snapshot и heads зафиксированы в process/evidence records.

## Repository cleanup

Historical cleanup 2026-07-31 завершён. Следующий cleanup возможен только после завершения PR #21, post-merge verification, fresh remote/local inventory, exact cleanup batch и отдельного owner approval.

## Постоянные gates

```text
merge: separate explicit owner approval required
branch deletion: separate post-merge owner approval required
mobile PASS: not claimed
```
