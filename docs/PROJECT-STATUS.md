# Текущее состояние проекта АСУ-ВЧ

Дата актуализации functional baseline и governance closure: `2026-08-01`.

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
completed baseline refresh PR: #21
PR #19 merge commit: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #20 merge / functional refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
PR #21 final head: 4d44874ef02ffb9381334acfabfa383eba3e4ead
PR #21 merge commit: f5b53f2ee4453f293b58cbe486e0943ab602335b
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
active documentation increment after closure: none
```

Documentation-only commits после tested runtime не объявляются runtime-tested. Точный current-main SHA и live repository state определяются динамически.

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

Каталог owner-only/read-only; штатные позиции, кадровые назначения, personal data и automatic rank relations не реализованы.

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

Каталог не связан с positions, ranks, equipment или personal data и не заявляется как полный персональный воинский учёт.

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

## Завершённый documentation baseline refresh — PR #21

```text
classification: documentation only
initial allowlist: 22 Markdown paths
final approved allowlist: 25 Markdown paths
Final PR Review attempt 1: CHANGES REQUIRED
owner-approved remediation: COMPLETE
repeat Documentation Validation: PASS
Final PR Review attempt 2: PASS
review ID: 4835150606
merge method: merge commit
merge commit: f5b53f2ee4453f293b58cbe486e0943ab602335b
post-merge Git verification: PASS
runtime change: none
```

PR #21 закрыт как merged. Его operational records сохраняют pre-merge snapshots и отдельный post-merge closure.

## Post-PR21 branch cleanup

После отдельного owner approval выполнен remote-first cleanup.

```text
approved remote branches: 3
remote branches deleted: 3 / 3
approved local branches: 13
local branches deleted: 13 / 13
terminal remote branch count: 1
terminal remote branch: main
terminal local branch count: 1
terminal local branch: main
final local main: f5b53f2ee4453f293b58cbe486e0943ab602335b
final origin/main: f5b53f2ee4453f293b58cbe486e0943ab602335b
working tree: clean
force deletion used: no
terminal verification: PASS
```

Это датированный terminal snapshot 2026-08-01, а не бессрочное утверждение о будущих ветках.

Подробный evidence record: [Post-PR21 Merge and Cleanup Closure 2026-08-01](POST-PR21-MERGE-CLEANUP-CLOSURE-2026-08-01.md).

## Current repository checks

Open PRs, open Issues и branches проверяются динамически:

```powershell
git fetch --prune origin
git ls-remote --heads origin
git branch -vv
git status --short
```

GitHub является источником истины для PR/Issue state. Living docs не хранят transient state нового workflow как постоянно актуальное поле.

## Следующий инкремент

Не выбран и не утверждён. Любой новый functional или technical scope начинается с Research → Analysis → Architecture → Specification → Review → Approval.

## Постоянные gates

```text
merge: separate explicit owner approval required
branch deletion: separate post-merge owner approval required
mobile PASS: not claimed
```
