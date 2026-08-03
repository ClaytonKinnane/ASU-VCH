# Test Report — Справочник воинских званий v2

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
- source checker: PASS;
- loader checker: PASS;
- compatibility-service checker: PASS;
- SQL UTF-8/control-byte/damaged-token scan: PASS;
- 18 DROP TRIGGER и 18 CREATE TRIGGER declarations: PASS;
- рабочее дерево: clean.

## 3. Migration и installer

Результат: **PASS**.

До migration 012: 11 migrations, current v1 = 1, v2 отсутствовала.

После применения:

- migration 012 зарегистрирована;
- всего migrations: 12;
- повторный installer: `Новых миграций нет`;
- v1: superseded;
- v2: published/current;
- duplicate publication отсутствует.

## 4. Backup evidence и process deviation

Предмиграционная резервная копия не была создана: первый backup/preflight block остановился до `mysqldump`, а migration была применена последующими командами. Создать такую копию задним числом невозможно. Отклонение явно зафиксировано; откат не потребовался.

Post-migration backup:

```text
BACKUP_STATUS=PASS
DATABASE_NAME=asu_vch
MYSQLDUMP_VERSION=8.4.8
BACKUP_FILE=C:\Project\ASU-VCH-backups\asu_vch-20260803-095418.sql
BACKUP_SIZE_BYTES=436258
BACKUP_SHA256=C392283F93212B1DD88DF9261C26FB741765F3E27C8B67E1F646B3F79065B7AB
```

## 5. Integration и regression

Результат: **PASS**.

Подтверждены:

- v1: 6 compositions, 20 ranks, 0 Staffing semantics;
- v2: 8 compositions, 8 semantics, 20 ranks;
- v2: 2 version sources, 8 composition sources;
- 4 selectable categories;
- compatibility/incompatibility и ancestry cases;
- утверждённые filter counts;
- все три theme assets;
- Reference, Security, Theme и Organization regressions;
- Organization UI polish: 64 PASS / 0 FAIL;
- Organization integration: 58 PASS / 0 FAIL;
- source/deploy parity: 24/24;
- HTTP smoke: PASS;
- рабочее дерево: clean.

## 6. UI remediation и manual desktop acceptance

Первичная проверка выявила blocking layout defect. После исправления подтверждены one-column start-aligned hierarchy, отсутствие растягивания карточек, parent/child grouping, connectors и короткие child labels.

Manual desktop acceptance: **PASS**.

- три темы;
- 1920×1080 и 1366×768;
- current v2 и historical v1;
- version switching;
- filter counts;
- search и empty state;
- read-only UI;
- official source links;
- non-owner HTTP 403;
- console errors: 0;
- HTTP/asset 404: 0;
- defects: NONE.

Подробное evidence: `docs/testing/MILITARY-RANKS-DIRECTORY-V2-MANUAL-DESKTOP-ACCEPTANCE-2026-08-03.md`.

## 7. Final PR Review remediation

Первый Final PR Review выявил один blocking finding: building recovery использовал широкую source whitelist вместо точных composition/source/role/order/note anchors.

Исправление на head `fe893e8315f7add80ed4d0501b41d8bc39b4b0e8` прошло локальный recheck:

```text
MILITARY RANKS DIRECTORY V2 SOURCE CHECK PASSED
MILITARY RANK V2 LOADER CHECK PASSED
MILITARY RANK COMPATIBILITY SERVICE CHECK PASSED
Применено миграций: 12
Новых миграций нет.
MILITARY RANKS DIRECTORY CHECK PASSED
FINAL PR REVIEW REMEDIATION RECHECK PASSED
```

Negative recovery scenarios отклоняются: неверные source, order, pairing, derived note и role.

Подробное evidence: `docs/review/MILITARY-RANKS-DIRECTORY-V2-PR-FINAL-REVIEW.md`.

## 8. Mobile scope

```text
MOBILE=OUT_OF_SCOPE / NOT_RUN
```

Mobile PASS не заявляется.

## 9. Финальный статус тестирования

```text
STATIC=PASS
MIGRATION_012=PASS
REPEAT_INSTALLER=PASS
DB_INTEGRATION=PASS
REGRESSION=PASS
SOURCE_DEPLOY_PARITY=PASS
HTTP_SMOKE=PASS
MANUAL_DESKTOP=PASS
FINAL_PR_REVIEW_REMEDIATION=PASS
BLOCKING_FINDINGS_OPEN=0
DEFECTS=NONE
FINAL_RESULT=PASS
```

Merge не выполнять без отдельного явного разрешения владельца. После merge требуется post-merge verification. Feature-ветку не удалять без отдельного разрешения.
