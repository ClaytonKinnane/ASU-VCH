# АСУ-ВЧ

Автоматизированная система учёта военнослужащих «Войсковая часть».

## Текущий merged baseline

Стабильное состояние находится в `main`; live HEAD всегда проверяется через GitHub/Git, а не принимается из старого документа.

```text
latest merged functional increment: PR #36 / Military Positions Directory v1
previous functional increment: PR #35 / Lowest Unit Staffing Structure v1
migrations: 001–014
system roles: 4
system permissions: 35
built-in themes: 3
required CSS assets per theme: 10
active product implementation increment: none
mobile testing: NOT RUN / OUT OF SCOPE
production deployment: NOT PERFORMED
```

PR #36 merge commit: `a6cfceb421fac8d0985e409770bb26a62fac0b14`. Текущий `main` может содержать более поздние history-only commits; canonical current pointer определяется live. Содержимое current `main` после компенсирующих noop/revert commits соответствует tree PR #36.

## Реализовано

- bootstrap первого владельца, authentication, sessions и CSRF;
- RBAC: 4 system roles / 35 permissions;
- полный user lifecycle, required password change, rejection audit, archive/restore;
- 3 trusted themes;
- Military Ranks Directory v2: current v2 + historical v1;
- organizational element types;
- public VUS information;
- Organizational Structure v1;
- Lowest Unit Staffing Structure v1: registers, version lifecycle, documents metadata, individual slots, history/compare and catalog pinning;
- Managed Military Positions Directory v1: versioned canonical catalog, draft/publish/supersede/cancel lifecycle, logical archive/restore, stable identity and append-only history;
- GitHub Actions `ASU-VCH Static Verification`;
- Windows PowerShell 5.1 Git/GitHub/Codex local automation package.

Staffing v1 не хранит военнослужащих, personnel assignments или фактическую занятость/вакантность. Military Positions Directory не хранит ВУС, rank requirement, unit, person, equipment или occupancy как свойства канонической должности.

## Последняя functional validation

Military Positions Directory v1 проверен на exact runtime head `c647a933011873048866c75978d3f506634011fd`:

```text
exact increment inventory: 38/38
PHP lint: 171 files / PASS
migrations: 001–014
repeat initialization: PASS
DB/runtime checker: 167 PASS
HTTP smoke: 200 / 200 / expected 302
asu-blue desktop: PASS
asu-light-blue desktop: PASS
asu-evgeniya-rostova desktop: PASS
mutual exclusion: PASS
open findings: 0
real Staffing data mutation: NONE
mobile: NOT RUN / OUT OF SCOPE
```

Documentation-only или merge commits не объявляются runtime-tested head.

## Текущее планирование

Активного product implementation increment сейчас нет. Отдельная branch `research/military-accounting-order-700` содержит уникальный research по контуру `PersonnelServiceAccounting`; её содержимое не находится в `main` и не считается автоматически утверждённым implementation scope.

Следующий functional increment выбирается только через новый Research → Analysis → Architecture → Specification → Review → Approval cycle.

## Локальная среда

```text
repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
URL: https://asu-vch.local
Open Server Panel: 6.5.1
Apache
PHP: 8.5.4
MySQL: 8.4.x
Windows PowerShell: 5.1
```

Secrets и `config/local.php` в Git не помещаются.

## Процесс изменений

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing/Validation → Commit → Push → Pull Request
→ exact-head Actions → Final PR Review → Merge approval → Merge
→ post-merge verification → Branch deletion approval → Branch deletion
```

Обычные material increments следуют отдельным gates. Постоянные правила и исключение для routine governance maintenance определены в `docs/PROJECT-WORKING-RULES.md`. Branch deletion всегда требует отдельного явного разрешения.

## Документация

- [Постоянные правила работы](docs/PROJECT-WORKING-RULES.md)
- [Handoff для перехода в новый чат](docs/CHAT-HANDOFF.md)
- [Индекс документации](docs/README.md)
- [Текущее состояние проекта](docs/PROJECT-STATUS.md)
- [Текущее состояние БД](docs/DATABASE-CURRENT.md)
- [План разработки](docs/ROADMAP.md)
- [Локальный runbook](docs/LOCAL-RUNBOOK.md)
- [История изменений](docs/CHANGELOG.md)
