# АСУ-ВЧ — постоянный handoff для нового чата

## 1. Обязательный порядок начала нового чата

Перед любыми material-действиями ассистент должен:

1. прочитать `docs/PROJECT-WORKING-RULES.md`;
2. прочитать этот документ полностью;
3. самостоятельно проверить live GitHub state: `main`, remote branches, open PR, open Issues, Actions и reviews активного scope;
4. сопоставить GitHub state с этим snapshot;
5. продолжить с текущего незавершенного stage;
6. не повторять уже полученные разрешения;
7. не начинать implementation без утвержденных Architecture, Specification, Review и Approval.

GitHub/Git остается canonical source для mutable lifecycle. Записанные SHA являются exact historical/current anchors только на момент соответствующей проверки и всегда перепроверяются.

## 2. Репозиторий и локальная среда

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
local repository: C:\Project\ASU-VCH
deploy: C:\OSPanel\home\asu-vch.local
domain: https://asu-vch.local
Open Server Panel: 6.5.1
web server: Apache
PHP: 8.5.4
MySQL: 8.4
local shell: Windows PowerShell 5.1
```

Локальная машина используется для sync, deploy, MySQL/migrations, runtime, HTTP/browser и visual desktop acceptance. GitHub operations выполняются ассистентом через доступные инструменты. Длинные PowerShell-сценарии добавляются файлами в репозиторий и должны быть совместимы с Windows PowerShell 5.1.

## 3. Постоянный lifecycle проекта

```text
Research
→ Analysis
→ Architecture
→ Specification
→ Review
→ Approval
→ Implementation
→ Testing/Validation
→ Commit
→ Push
→ Pull Request
→ exact-head GitHub Actions
→ Final PR Review
→ отдельное Merge approval
→ Merge
→ Post-merge verification
→ отдельное Branch deletion approval
→ Branch deletion
```

Основные ограничения:

- отдельная ветка для каждого material scope;
- exact base/head/merge-base/path allowlist;
- fail closed при несовпадении anchors;
- никаких скрытых remediation и scope expansion;
- обычные PR, Final PR Review, merge и branch deletion требуют отдельных разрешений;
- repository settings, branch protection и required checks меняются только отдельным утвержденным инкрементом;
- static CI не заменяет MySQL, migrations, deploy, HTTP/browser, visual и mobile acceptance;
- mobile testing не входит в обычный scope;
- documentation-only commit не объявляется runtime-tested.

## 4. Постоянное разрешение на governance-документы

Владелец предоставил standing authorization без повторных permission prompts на поддержание только:

```text
docs/PROJECT-WORKING-RULES.md
docs/CHAT-HANDOFF.md
```

Разрешены отдельная documentation branch, commits, PR, exact-head Actions, Final PR Review, merge после PASS, post-merge verification и branch cleanup. Любой третий путь запрещен без отдельного обычного gate.

Standing authorization не распространяется на runtime, config, DB, migrations, workflows, themes, deploy, automation tools, integrity manifest, repository settings, branch protection, required checks или иные Markdown-файлы.

## 5. Последний подтвержденный stable baseline

### Main и governance

```text
verified main HEAD before current research: 7ae5bcf77826870d6beee7293f101f679a521c56
provenance: merge commit Pull Request #32
PR #32: merged
PR #32 exact reviewed head: d219f91dcd65026df4f8729353fdfc2ffd381072
PR #32 post-merge workflow: 31070334183 / SUCCESS
PR #32 documentation branch cleanup: PASS
remote inventory after cleanup: main only
open findings after PR #32: 0
```

PR #32 создал постоянные документы правил работы и handoff. Его lifecycle считается terminal complete; отдельный recursive PR только ради копирования его merge SHA или cleanup запрещен.

### Functional runtime baseline

```text
Pull Request: #24
migration: 012
merge commit: feac7230616d3a8df98acb48f43a0b60f89f2255
runtime/manual acceptance head: b44aed14ee1a54be213cbc939322ba21b02e7a58
```

### Static CI baseline

```text
Pull Request: #25
workflow: ASU-VCH Static Verification
merge commit: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
required status check: not enabled
branch protection Stage B: not implemented
```

### Documentation governance baseline

```text
Pull Request: #28
GitHub/Git: canonical for mutable lifecycle
historical gate records: immutable snapshots
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
recursive lifecycle-only documentation closure: prohibited
```

### Local automation baseline

```text
foundation: Pull Request #29
PowerShell 5.1 corrected baseline: Pull Request #30
PR #30 implementation head: fede2aa8c9c7b896f142075caa69b35219d4016d
PR #30 merge commit: 35abf7e29395bc662eeb2cecf5b1ea5a30fd7c77
native regression: 58 PASS / 0 FAIL
```

## 6. Реализованные области

### Platform и security

- bootstrap первого владельца;
- authentication, sessions и CSRF;
- public registration отключается после владельца;
- 4 system roles;
- 25 system permissions;
- полный user lifecycle;
- required password change;
- rejection audit;
- archive/restore.

### Themes

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`;
- 10 required CSS assets на тему.

### Справочники и организация

- owner-only read-only military ranks;
- organizational element types;
- public military positions;
- public VUS;
- Organizational Structure v1;
- Military Ranks Directory v2;
- current v2 / historical v1;
- version switching, search/filtering, source cards и badges;
- migrations 001–012.

## 7. Не реализовано

- штатные документы и штатные slots;
- персональные карточки военнослужащих;
- назначения person → staffing slot;
- реальные personnel/unit data;
- документы и фото военнослужащих;
- кадрово-служебная история;
- отдельный контур воинского учета граждан;
- ГАР/ФИАС subsystem;
- reference import governance;
- reconciliation/data quality;
- statutory forms/reporting engine;
- график отпусков;
- production deployment infrastructure;
- branch protection Stage B / required status check;
- mobile acceptance.

Наличие пункта в списке не означает разрешение на реализацию.

## 8. Активный scope: Military Accounting Order 700 Research

### Разрешение владельца

Владелец поставил задачу исследовать приказ Министра обороны Российской Федерации от 22.11.2021 № 700 в действующей редакции, все его ссылки и связанные официальные документы, сформировать целевое представление о воинском учете и предложения по модернизации «АСУ-ВЧ». Разрешено создать одну или несколько отдельных веток.

### Exact anchors

```text
scope name: Military Accounting Order 700 Research
classification: research / analysis / documentation-only
base branch: main
base SHA: 7ae5bcf77826870d6beee7293f101f679a521c56
research branch: research/military-accounting-order-700
research head SHA: b148c7c28d43c9bba08666fc593b75477e0d40b8
merge base: 7ae5bcf77826870d6beee7293f101f679a521c56
ahead: 1
behind: 0
changed paths: 5
unexpected paths: 0
runtime/config/DB/workflow diff: 0
```

### Exact research allowlist

```text
docs/research/military-accounting-order-700/README.md
docs/research/military-accounting-order-700/OFFICIAL-SOURCE-REGISTER.md
docs/research/military-accounting-order-700/LEGAL-AND-PROCESS-ANALYSIS.md
docs/research/military-accounting-order-700/TARGET-ACCOUNTING-MODEL.md
docs/research/military-accounting-order-700/ASU-VCH-MODERNIZATION-ROADMAP.md
```

### Research conclusions

1. Приказ № 700 регулирует преимущественно государственную систему воинского учета граждан: документы, первичный учет, учет в организациях, сверки, контроль качества и выписки Реестра.
2. Действующие военнослужащие не относятся к обычному контингенту воинского учета по Положению № 719; кадрово-служебный учет военнослужащих должен быть отдельным доменом.
3. «АСУ-ВЧ» должна разделить `CitizenMilitaryAccounting` и `PersonnelServiceAccounting`, связав их контролируемыми юридическими событиями.
4. Публичные акты не устанавливают детальную внутреннюю иерархию действующих частей для прямого hardcode. Предложено конфигурируемое оргдерево с ownership снизу и агрегированием вверх.
5. Первый рекомендуемый functional increment — `Lowest Unit Staffing Structure v1`, без персональных данных и назначений.
6. ГАР/ФИАС является целевым адресным эталоном; КЛАДР — legacy migration mapping.
7. Фото и документы требуют отдельного защищенного object/document vault, field-level access и immutable audit.
8. Отчетность должна быть versioned by legal edition и reproducible as-of.
9. График отпусков — отдельный кадровый процесс, утверждаемый командиром, а не автоматическое решение системы.
10. Секретные и ограниченные сведения не загружаются в общий контур без отдельной законной и аттестованной среды.

### Официальные источники

Исследование использует официальный портал правовой информации, банк документов Президента Российской Федерации, официальный сайт Правительства Российской Федерации и ФНС России. Коммерческие правовые системы допускались только как навигация, не как authoritative source.

Официальный 150-страничный PDF приказа был нестабилен/недоступен через используемые read-интерфейсы. Реквизиты и редакции подтверждены официальными карточками, а выводы — официальными системообразующими актами. Полнота ограничена открытыми официальными источниками; закрытые/секретные акты не реконструировались.

## 9. Текущий stage и разрешения

```text
Research: PREPARED
Analysis: PREPARED
Architecture: NOT STARTED
Specification: NOT STARTED
Review: NOT YET AUTHORIZED
Approval: NOT GRANTED
Implementation: NOT AUTHORIZED
runtime/DB/UI changes: NOT AUTHORIZED
research Pull Request: NOT AUTHORIZED
research merge: NOT AUTHORIZED
research branch deletion: NOT AUTHORIZED
open findings: 0
```

Создание research commit не является implementation и не разрешает PR/merge.

## 10. Следующий разрешенный шаг

1. Представить владельцу результаты исследования и предложения.
2. Получить решение: принять, скорректировать или отклонить исследовательскую модель.
3. При принятии — отдельно разрешить Review research head `b148c7c28d43c9bba08666fc593b75477e0d40b8`.
4. После Review определить точный scope первого functional increment.
5. Подготовить Architecture и Specification этого инкремента.
6. Implementation не начинать до отдельного Approval.

Рекомендуемый первый scope:

```text
NAME=Lowest Unit Staffing Structure v1
IN_SCOPE=organizational node, staffing document versions, individual staffing slots, vacancy state, links to approved public rank/position/VUS references, read-only desktop views, scoped permissions, audit
OUT_OF_SCOPE=real personnel, personal data, photos, files, assignments, orders, leave, medical data, external integrations, production deployment, mobile acceptance, classified/restricted data
```

## 11. Журнал текущего исследования

### 2026-08-06 — scope и branch

- подтвержден `main` на `7ae5bcf77826870d6beee7293f101f679a521c56`;
- remote inventory до research содержал только `main`;
- создана branch `research/military-accounting-order-700`;
- scope ограничен официальным исследованием и проектными Markdown-предложениями;
- implementation, PR и merge не разрешались.

### 2026-08-06 — official-source research

- подтверждены приказ № 700, изменение № 791 от 23.11.2023 и изменение № 438 от 09.07.2025;
- проанализированы постановления Правительства № 719 и № 506;
- проанализированы системообразующие акты о воинской обязанности, обороне, прохождении службы, статусе военнослужащих, военно-врачебной экспертизе, персональных данных и ГАР/ФИАС;
- сформирована граница между учетом граждан и кадрово-служебным учетом действующих военнослужащих;
- выявлено ограничение открытых источников для закрытых ведомственных сведений.

### 2026-08-06 — research commit

- создан commit `b148c7c28d43c9bba08666fc593b75477e0d40b8`;
- compare: `ahead=1`, `behind=0`;
- изменено ровно 5 allowlisted Markdown-файлов;
- runtime, config, DB, migrations, workflows, themes, deploy и repository settings не изменялись;
- research PR не создавался.

## 12. Checklist первого ответа в новом чате

```text
[ ] прочитан PROJECT-WORKING-RULES.md
[ ] прочитан CHAT-HANDOFF.md
[ ] проверен live main HEAD
[ ] проверены remote branches
[ ] проверены open PR и Issues
[ ] проверен live research head
[ ] compare research branch ↔ main соответствует anchors
[ ] прочитаны 5 research documents
[ ] определен текущий owner gate
[ ] implementation не начат без Approval
```
