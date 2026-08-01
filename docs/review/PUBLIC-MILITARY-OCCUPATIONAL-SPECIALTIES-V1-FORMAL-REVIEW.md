# Formal Review — Public Military Occupational Specialties v1

## Specification review

`PASS AFTER CORRECTIONS`.

Specification v0.1 получила 8 blocking findings. В v0.2 устранены искусственная группировка программ, недоказанный разбор шестизначных обозначений, связи со званиями, пустые relation-таблицы, неполные version-aware FK, противоречивый publish procedure, невоспроизводимый HTML fingerprint и неполный source register.

Implementation выполнена по owner-approved Specification v0.2.

## Final PR Review attempt 1

```text
DATE: 2026-08-01
PR: #20
RESULT: CHANGES_REQUIRED
BLOCKING_FINDINGS: 2
```

Были выявлены:

1. фильтр организации при `record_type=all` не исключал нормативные direct-disclosure записи;
2. PowerShell runner ожидал 24 пути при фактическом составе PR из 25 путей.

Дополнительно требовалась синхронизация PR body и процессных метаданных.

## Remediation verification

Подтверждено:

- `MilitaryOccupationalSpecialtyCatalogRepository::shouldSearchPublicDisclosures()` задаёт проверяемую политику включения нормативных записей;
- `record_type=all + organization` возвращает только программы выбранной организации;
- `record_type=direct-disclosure + organization` возвращает пустое состояние;
- integration checker проверяет пять комбинаций `record_type/organization`;
- repository-фильтр организации сопоставляется с контрольным SQL-запросом;
- runner включает все 25 changed paths, включая Manual Desktop Acceptance evidence;
- ошибочное заявление о пользовательском фильтре `source` удалено из PR body;
- Specification, Test Plan, Runbook, Implementation, Manual Desktop Acceptance evidence и PR body отражают финальный scope и процессные ограничения.

## Automated Testing after remediation

```text
DATE: 2026-08-01
RUNTIME_HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
RESULT: PASS
IMPLEMENTATION_PATHS: 25
PHP_FILES_LINTED: 113
INSTALLER_TWICE: PASS
CORE_VUS_CHECKER: PASS
ORGANIZATION_FILTER_POLICY_REGRESSIONS: PASS
ORGANIZATION_REPOSITORY_FILTER: PASS
UI_CHECKER: PASS
DIRECTORY_SECURITY_THEME_REGRESSIONS: PASS
ORGANIZATION_REGRESSION: 58 PASS / 0 FAIL
SOURCE_DEPLOY_PARITY: 14 PATHS / PASS
HTTP_SMOKE: PASS
FINAL_DIVERGENCE: 0 0
FINAL_WORKING_TREE: CLEAN
```

Evidence-only commit `63890eaa588faa014c0a5bd4ede6ca804bcdcc90` не изменил runtime.

## Manual Desktop Acceptance

Полная Manual Desktop Acceptance после UI remediation: `PASS`.

Целевая перепроверка Final PR Review remediation: `PASS`.

Подтверждены:

- исходный результат — 17 записей;
- «Все + Финансовый университет» — 8;
- «Все + МИИГАиК» — 4;
- «Все + ЧГУ» — 2;
- «Все + ОГУ» — 1;
- отображаются только записи выбранной организации;
- нормативные примеры исключаются при выбранной организации;
- «Нормативные примеры + организация» — пустое состояние;
- «Программы подготовки + организация» — корректный результат;
- сброс возвращает 17 записей;
- Console errors — 0;
- HTTP/asset 404 — 0;
- defects — none.

Evidence-only commit `835321b21f9b037c74aa5632426868aa97aa8cc6` не изменил runtime.

## Final PR Review attempt 2

```text
DATE: 2026-08-01
PR: #20
RESULT: PASS
BLOCKING_FINDINGS: 0
MOBILE_TESTING_STATUS: OUT_OF_SCOPE_NOT_RUN
MERGE_STATUS: NOT_AUTHORIZED
BRANCH_DELETION_STATUS: NOT_AUTHORIZED
```

Проверены 25 changed paths, критические runtime-файлы, read-only и owner-only границы, repository queries, migration compatibility loader, schema/seed invariants, theme assets, integration/UI checkers, PowerShell runner, Automated Testing evidence, Manual Desktop Acceptance evidence, отсутствие relation-таблиц к должностям, званиям, ВВСТ и персональным данным, merge-base и состояние PR.

Оба blocking finding первого Final PR Review устранены и подтверждены автоматическими и ручными проверками. Новых blocking, major или minor findings не выявлено.

PR #20 был допущен к отдельному owner merge-approval gate. Этот исторический review сам по себе не разрешал merge и branch deletion.

## Post-merge closure

```text
DATE: 2026-08-01
PR: #20
PR_STATE: CLOSED
MERGED: YES
MERGE_METHOD: MERGE COMMIT
MERGE_COMMIT: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
FINAL_FEATURE_HEAD: bea147505a85010b61fe938eb07ec474d76cdab5
FINAL_PR_REVIEW_STATUS: PASS
POST_MERGE_GIT_VERIFICATION_STATUS: PASS
FEATURE_BRANCH_PRESERVED_AFTER_MERGE: YES
```

Отдельное owner merge approval было получено после Final PR Review PASS. Merge выполнен с expected-head guard. Post-merge verification подтвердила:

- PR закрыт как merged;
- `main` указывает на merge commit `3082ec6...`;
- feature head `bea1475...` является родителем merge commit;
- деревья feature head и merged `main` совпадают;
- ключевой VUS runtime route присутствует в `main`;
- feature-ветка не была удалена.

Post-merge verification была Git/GitHub-проверкой. Новый локальный runtime/deploy retest на merge commit не выполнялся и не заявляется; проверенным runtime anchor остаётся `9db06c4a26066ca25dc36c627c1236089a3c1238`.

## Current outcome

```text
INCREMENT_STATUS: IMPLEMENTED / TESTED / ACCEPTED / REVIEWED / MERGED
AUTOMATED_TESTING_STATUS: PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS: PASS
TARGETED_MANUAL_DESKTOP_RECHECK_STATUS: PASS
FINAL_PR_REVIEW_STATUS: PASS
POST_MERGE_GIT_VERIFICATION_STATUS: PASS
MOBILE_TESTING_STATUS: OUT_OF_SCOPE_NOT_RUN
BRANCH_DELETION_STATUS: PENDING SEPARATE POST-REFRESH APPROVAL
```

Удаление feature-ветки не входит в Post-PR20 Baseline Refresh и требует fresh inventory и отдельного owner approval после завершения документационного цикла.
