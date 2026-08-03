# Final PR Review — Составы военнослужащих и воинские звания v2

Дата: **2026-08-03**  
Pull Request: **#24**  
Base: `main` @ `5f97ed4237cca6fed314952e0c19716d98e7f459`  
Runtime/manual acceptance head: `b44aed14ee1a54be213cbc939322ba21b02e7a58`  
Remediation recheck head: `fe893e8315f7add80ed4d0501b41d8bc39b4b0e8`

## 1. Итог

```text
FINAL_PR_REVIEW=PASS
BLOCKING_FINDINGS_OPEN=0
MAJOR_FINDINGS_OPEN=0
MINOR_FINDINGS_OPEN=0
MERGE_AUTHORIZED=NO
```

PR готов к отдельному явному разрешению владельца на merge.

## 2. Проверенная область

Final PR Review охватил:

- полный diff PR относительно `main`;
- version lifecycle и migration compatibility loader;
- DDL, publication transaction и 18 triggers;
- published-state exact anchors;
- building-state recovery и fail-closed contract;
- version-scoped composition semantics и source evidence;
- Reference-owned compatibility service;
- repository historical/current behavior;
- owner-only read-only UI;
- три theme assets и UI-remediation;
- automated/manual test evidence;
- отсутствие Staffing schema, Organization bindings и increment B.

## 3. Первоначальный blocking finding

При первой проверке PR обнаружено:

```text
FINDING=BUILDING_RECOVERY_SOURCE_ANCHORS_TOO_BROAD
SEVERITY=BLOCKING
```

Building-state recovery строго проверял metadata, compositions, ranks и semantics, но существующие version-source и composition-source rows проверялись только по широкому whitelist. Противоречивая связка «состав → источник → роль» могла считаться recoverable и быть удалена/пересоздана вместо fail-closed.

Это расходилось с утверждённым recovery contract.

## 4. Remediation

В PR добавлено:

- единый exact-anchor contract для двух version sources;
- единый exact-anchor contract для восьми composition sources;
- строгая проверка composition/source/role/order/note;
- fail-closed для неверного source, order, role, pairing и derived note;
- чистые matcher-функции для воспроизводимого unit regression;
- loader checker с положительными и отрицательными сценариями.

Изменены только:

```text
database/MilitaryRankDirectoryV2/Definitions.php
database/MilitaryRankDirectoryV2/Recovery.php
tools/check-military-rank-v2-loader.php
```

Published v2, UI и существующая БД не изменялись.

## 5. Локальный remediation recheck

Пользователь выполнил точный PowerShell 5.1 блок на Windows/Open Server/MySQL.

Результат: **PASS**.

### PHP и source contract

```text
Definitions.php lint = PASS
Recovery.php lint = PASS
check-military-rank-v2-loader.php lint = PASS
MILITARY RANKS DIRECTORY V2 SOURCE CHECK PASSED
```

### Exact recovery anchors

```text
v1 composition anchors: 6
v2 composition anchors: 8
v2 rank anchors: 20
v2 version source anchors: 2
v2 composition source anchors: 8

exact primary version source accepted
exact equivalence version source accepted
contradictory version source rejected
wrong version source order rejected
exact derived composition source accepted
exact officer composition source accepted
contradictory composition/source pairing rejected
contradictory derived note rejected
contradictory composition source role rejected
recovery uses exact version source matcher
recovery uses exact composition source matcher

MILITARY RANK V2 LOADER CHECK PASSED
```

### Runtime regression

```text
MILITARY RANK COMPATIBILITY SERVICE CHECK PASSED
```

### Deploy parity

```text
Definitions.php source/deploy SHA-256 = MATCH
Recovery.php source/deploy SHA-256 = MATCH
```

### Installer и DB regression

```text
Применено миграций: 12
Новых миграций нет.
MILITARY RANKS DIRECTORY CHECK PASSED
```

### Repository state

```text
HEAD=fe893e8315f7add80ed4d0501b41d8bc39b4b0e8
Dirty paths=0
FINAL PR REVIEW REMEDIATION RECHECK PASSED
```

## 6. GitHub-side review

До документационного evidence commit подтверждено:

- PR state: open;
- draft: false;
- mergeable: true;
- branch behind `main`: 0;
- review threads: 0;
- changed scope соответствует утверждённому Reference v2 increment;
- GitHub Actions workflow runs для PR head отсутствуют.

Отсутствие workflow runs не заменяет локальное тестирование; фактическое evidence приведено в Test Report и Manual Desktop Acceptance.

## 7. Manual desktop acceptance

Результат: **PASS**.

- три темы;
- `1920×1080` и `1366×768`;
- current v2 и historical v1;
- version switching;
- v1/v2 filter counts;
- search и empty state;
- read-only UI;
- official source links;
- non-owner HTTP 403;
- console errors: 0;
- HTTP/asset 404: 0;
- defects: NONE.

## 8. Backup deviation

Предмиграционная backup не была создана из-за остановки первого preflight блока. Отклонение явно отражено в Test Report и PR description; оно не скрывается.

Post-migration backup:

```text
BACKUP_STATUS=PASS
BACKUP_FILE=C:\Project\ASU-VCH-backups\asu_vch-20260803-095418.sql
BACKUP_SIZE_BYTES=436258
BACKUP_SHA256=C392283F93212B1DD88DF9261C26FB741765F3E27C8B67E1F646B3F79065B7AB
```

## 9. Scope boundaries

Подтверждено отсутствие:

- Staffing tables/slots/routes;
- Organization bindings;
- actual unit/personnel/Excel data;
- mutation UI/routes;
- новых permissions;
- increment B implementation.

```text
MOBILE=OUT_OF_SCOPE / NOT_RUN
```

Mobile PASS не заявляется.

## 10. Merge gate

Final PR Review завершён: **PASS**.

Merge не выполнять без отдельного явного разрешения владельца. После merge требуется post-merge verification. Feature-ветку не удалять без ещё одного отдельного явного разрешения.
