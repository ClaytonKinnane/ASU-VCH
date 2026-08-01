# История изменений

## 2026-08-01

### Справочник типовых воинских должностей — PR #19

- добавлена migration `010_military_positions_directory.sql`;
- реализован owner-only read-only route `/admin/directories/military-positions.php`;
- 14 tables, 41 DB trigger, 34 canonical types и 35 variants;
- automatic rank relations, кадровые назначения и personal data отсутствуют;
- Automated Testing: PASS;
- Manual Desktop Acceptance: PASS;
- PR #19 merged commit: `99f9f283768ca418fb7ff86d55b7d73e7a6c3510`;
- mobile testing: `OUT OF SCOPE / NOT RUN`.

### Публичные сведения о ВУС — PR #20

- добавлена migration `011_public_military_occupational_specialties_directory.sql`;
- реализован owner-only read-only route `/admin/directories/military-occupational-specialties.php`;
- 9 tables, 26 triggers, 5 legal sources, 4 official snapshots, 4 organizations, 15 training programs и 17 searchable records;
- relations к positions, ranks, equipment и personal data отсутствуют;
- Final PR Review remediation закрыла organization-filter composition и runner exact-scope defects;
- Automated Testing: PASS на runtime head `9db06c4a26066ca25dc36c627c1236089a3c1238`;
- Manual Desktop Acceptance и targeted recheck: PASS;
- repeated Final PR Review: PASS;
- PR #20 merged commit: `3082ec6ecbeddb92bd65e1398f05a9339abb199b`;
- post-merge Git verification: PASS;
- mobile testing: `OUT OF SCOPE / NOT RUN`.

### Post-PR20 Baseline Refresh — PR #21

- создана ветка `docs/post-pr20-baseline-refresh` от `main @ 3082ec6ecbeddb92bd65e1398f05a9339abb199b`;
- завершены Research, Analysis, Architecture, Specification, Formal Review и owner Approval;
- initial scope: 22 Markdown paths;
- initial Documentation Implementation и Validation: PASS;
- создан PR #21 `docs: refresh baseline after PR #19 and PR #20`;
- первый Final PR Review PR #21: `CHANGES REQUIRED`;
- findings: incomplete operational closure PR #19, stale post-PR markers и отсутствие implementation head;
- owner отдельно разрешил remediation и расширил allowlist с 22 до 25 Markdown-путей;
- добавлены post-merge closures для operational records PR #19;
- current-state documents синхронизированы с PR #21;
- runtime, database, deploy, config, themes, tools и Git refs не изменялись;
- repeat Documentation Validation и Final PR Review выполняются до отдельного merge gate;
- merge и branch deletion не разрешены.

## 2026-07-31

### Repository cleanup closure

- merged documentation reconciliation;
- отдельно утверждён и удалён batch из 18 remote non-main branches;
- terminal snapshot: `main only` на дату проверки;
- runtime, deploy и database не изменялись.

## 2026-07-29

### Organizational Structure v1 — PR #15

- migration 009;
- 7 tables, 16 triggers и 6 permissions;
- structures, versions, draft tree, documents metadata, history и compare;
- Automated Testing и Manual Desktop Acceptance: PASS;
- mobile testing: OUT OF SCOPE / NOT RUN.

## Более ранние изменения

Подробные historical records ранних security, theme и directory increments находятся в `docs/design`, `docs/decisions`, `docs/testing` и merged PR #1–#18.
