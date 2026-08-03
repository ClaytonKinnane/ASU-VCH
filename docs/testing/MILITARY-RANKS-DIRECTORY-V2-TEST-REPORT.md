# Test Report — Составы военнослужащих и воинские звания v2

Дата завершения: **2026-08-03**  
Итог: **PASS**  
Ветка: `feature/military-ranks-directory-v2`  
Runtime/manual acceptance head: `b44aed14ee1a54be213cbc939322ba21b02e7a58`  
Final PR Review remediation head: `fe893e8315f7add80ed4d0501b41d8bc39b4b0e8`

## 1. Целевая среда

```text
Windows
Open Server Panel 6.5.1
Apache
PHP 8.5.4 / 8.5 branch
MySQL 8.4.8
PowerShell 5.1
Local URL: https://asu-vch.local
```

## 2. Static checks

Результат: **PASS**.

- PHP lint implementation/checker files: PASS;
- `check-military-ranks-directory-v2-source.php`: PASS;
- `check-military-rank-v2-loader.php`: PASS;
- `check-military-rank-compatibility-service.php`: PASS;
- SQL UTF-8/control-byte/damaged-token scan: PASS;
- 18 DROP TRIGGER declarations: PASS;
- 18 CREATE TRIGGER declarations: PASS;
- working tree после static stage: clean.

## 3. Migration и installer

Результат: **PASS**.

До migration 012:

- migrations: 11;
- latest: `011_public_military_occupational_specialties_directory.sql`;
- current v1 rows: 1;
- existing v2 rows: 0.

После применения:

- migration 012 зарегистрирована;
- всего migrations: 12;
- повторный installer: `Новых миграций нет`;
- v1 lifecycle: superseded;
- v2 lifecycle: published/current;
- duplicate publication отсутствует.

## 4. Backup evidence и process deviation

Предмиграционная резервная копия **не была создана**. Первый backup/preflight block остановился до вызова `mysqldump`, после чего migration была применена отдельными командами. Создать предмиграционную копию задним числом невозможно.

Отклонение не скрывается и считается зафиксированным process deviation локального тестового запуска. Откат не потребовался.

После migration создана проверенная post-migration backup:

```text
BACKUP_STATUS=PASS
DATABASE_NAME=asu_vch
MYSQLDUMP_VERSION=8.4.8
BACKUP_FILE=C:\Project\ASU-VCH-backups\asu_vch-20260803-095418.sql
BACKUP_SIZE_BYTES=436258
BACKUP_SHA256=C392283F93212B1DD88DF9261C26FB741765F3E27C8B67E1F646B3F79065B7AB
```

## 5. Military ranks integration

Результат: **PASS**.

`MILITARY RANKS DIRECTORY CHECK PASSED`.

Подтверждено:

- v1 и v2 найдены;
- v1: 6 compositions, 20 ranks, 0 Staffing semantics;
- v2: 8 compositions, 8 semantics, 20 ranks;
- v2: 2 version sources, 8 composition sources;
- selectable categories: soldiers, sergeants, warrant officers, officers;
- compatibility/incompatibility cases;
- ancestry compatibility для officers;
- v1 возвращает composition-not-selectable;
- repository current version = v2;
- filters `2 / 4 / 2 / 12 / 4 / 3 / 5`;
- все три theme assets зарегистрированы.

## 6. Regression

Полный automated regression stage на implementation head `ec8faab73db014a0205e2aaeea71e4608254c0b8`: **PASS**.

Подтверждено:

- feature checkers: PASS;
- organizational elements directory: PASS через regression adapter;
- military positions directory: PASS;
- military occupational specialties directory/UI: PASS;
- Security RBAC/password/approval/rejection/archive-restore: PASS;
- Theme management/missing-asset/all-directory-assets: PASS;
- Organization migration compatibility: PASS;
- Organization UI polish: 64 PASS / 0 FAIL;
- Organization integration: 58 PASS / 0 FAIL;
- source/deploy runtime parity: 24/24;
- HTTP smoke: PASS;
- final working tree: clean.

## 7. UI remediation

Первичная manual desktop review выявила blocking composition-layout defect.

Исправлено:

- one-column start-aligned hierarchy;
- no stretched cards;
- parent/child visual grouping;
- child connectors;
- concise child labels;
- identical behavior in three themes;
- dedicated UI-layout checker.

После remediation runtime/manual acceptance head: `b44aed14ee1a54be213cbc939322ba21b02e7a58`.

Targeted UI checks и deploy были выполнены перед повторной визуальной проверкой. Видимые deployed changes и итоговая manual acceptance подтверждают актуальность runtime head.

## 8. HTTP и assets

Результат: **PASS**.

```text
/ = 200
/health.php = 200
/admin/ = 302 без сессии
/admin/directories/military-ranks.php = 302 без сессии
/themes/asu-blue/assets/css/military-ranks-v2.css = 200
/themes/asu-light-blue/assets/css/military-ranks-v2.css = 200
/themes/asu-evgeniya-rostova/assets/css/military-ranks-v2.css = 200
```

Manual DevTools result:

- console errors: 0;
- HTTP/asset 404: 0.

## 9. Manual desktop acceptance

Результат: **PASS**.

Подтверждены:

- `asu-blue`: 1920×1080 и 1366×768;
- `asu-light-blue`: 1920×1080 и 1366×768;
- `asu-evgeniya-rostova`: 1920×1080 и 1366×768;
- current v2;
- historical v1;
- version switching;
- v2 composition counts;
- v1 composition counts;
- search и empty state;
- read-only UI;
- official source links;
- non-owner HTTP 403;
- defects: NONE.

Подробное evidence находится в:

`docs/testing/MILITARY-RANKS-DIRECTORY-V2-MANUAL-DESKTOP-ACCEPTANCE-2026-08-03.md`.

## 10. Mobile scope

```text
MOBILE=OUT_OF_SCOPE / NOT_RUN
```

Mobile PASS не заявляется.

## 11. Final PR Review remediation

Первая Final PR Review выявила один blocking finding: building-state recovery проверял source links по широкому whitelist, а не по точным composition/source/role/order/note anchors.

Исправлено на head `fe893e8315f7add80ed4d0501b41d8bc39b4b0e8`.

Локальный remediation recheck: **PASS**.

```text
Definitions.php lint = PASS
Recovery.php lint = PASS
check-military-rank-v2-loader.php lint = PASS
MILITARY RANKS DIRECTORY V2 SOURCE CHECK PASSED
MILITARY RANK V2 LOADER CHECK PASSED
MILITARY RANK COMPATIBILITY SERVICE CHECK PASSED
```

Negative recovery scenarios:

```text
contradictory version source rejected
wrong version source order rejected
contradictory composition/source pairing rejected
contradictory derived note rejected
contradictory composition source role rejected
```

Source/deploy parity:

```text
Definitions.php = MATCH
Recovery.php = MATCH
```

Повторный installer и DB regression:

```text
Применено миграций: 12
Новых миграций нет.
MILITARY RANKS DIRECTORY CHECK PASSED
```

Final remediation repository state:

```text
HEAD=fe893e8315f7add80ed4d0501b41d8bc39b4b0e8
Dirty paths=0
FINAL PR REVIEW REMEDIATION RECHECK PASSED
```

Подробное review evidence:

`docs/review/MILITARY-RANKS-DIRECTORY-V2-PR-FINAL-REVIEW.md`.

## 12. Финальный статус

```text
STATIC=PASS
MIGRATION_012=PASS
REPEAT_INSTALLER=PASS
DB_INTEGRATION=PASS
REGRESSION=PASS
SOURCE_DEPLOY_PARITY=PASS
HTTP_SMOKE=PASS
MANUAL_DESKTOP=PASS
FINAL_PR_REVIEW=PASS
BLOCKING_FINDINGS_OPEN=0
DEFECTS=NONE
FINAL_RESULT=PASS
```

Инкремент готов к отдельному явному разрешению владельца на merge. Merge не выполнять без такого разрешения. После merge требуется post-merge verification. Feature-ветку не удалять без отдельного разрешения.
