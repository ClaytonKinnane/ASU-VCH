# История изменений

## 2026-08-01

### Справочник типовых воинских должностей — PR #19

- добавлена migration `010_military_positions_directory.sql`;
- реализован owner-only read-only route `/admin/directories/military-positions.php`;
- создан whole-catalog versioned public catalog из 14 tables и 41 DB trigger;
- добавлены 34 canonical military position types и 35 normative variants;
- добавлены families, composition scopes и organizational-context evidence;
- автоматические связи с military ranks отсутствуют;
- кадровые назначения и personal data не реализованы;
- Automated Testing: PASS;
- Manual Desktop Acceptance во всех трёх темах и двух desktop-разрешениях: PASS;
- mobile testing: `OUT OF SCOPE / NOT RUN`;
- PR #19 merged commit: `99f9f283768ca418fb7ff86d55b7d73e7a6c3510`.

### Публичные сведения о ВУС — PR #20

- добавлена migration `011_public_military_occupational_specialties_directory.sql`;
- реализован owner-only read-only route `/admin/directories/military-occupational-specialties.php`;
- создан source-centric catalog из 9 tables и 26 DB triggers;
- добавлены 5 legal sources, 4 official snapshots, 2 normative examples, 4 organizations и 15 training programs;
- итоговый searchable set: 17 records;
- шестизначные identifiers программ не разбиваются без официального источника;
- отсутствуют relations к positions, ranks, equipment и personal data;
- добавлен обязательный VUS stylesheet во все три themes;
- Final PR Review выявил и закрыл organization-filter composition defect и runner exact-scope defect;
- Automated Testing после remediation: PASS на runtime HEAD `9db06c4a26066ca25dc36c627c1236089a3c1238`;
- Manual Desktop Acceptance: PASS;
- targeted manual desktop recheck: PASS;
- repeated Final PR Review: PASS;
- PR #20 merged commit: `3082ec6ecbeddb92bd65e1398f05a9339abb199b`;
- post-merge Git/GitHub verification: PASS;
- mobile testing: `OUT OF SCOPE / NOT RUN`.

### Post-PR20 Baseline Refresh

- создана branch `docs/post-pr20-baseline-refresh` от `main @ 3082ec6ecbeddb92bd65e1398f05a9339abb199b`;
- завершены Research, Analysis, Architecture, Specification и Formal Review;
- owner утвердил documentation-only Implementation и Validation;
- scope ограничен 22 Markdown paths;
- living documentation актуализируется до migrations 001–011 и PR #19/#20;
- historical process/test artifacts сохраняются без переписывания;
- runtime, database, deploy, config, themes, tools и Git refs не изменяются;
- PR creation, merge и branch deletion не разрешены этим approval.

## 2026-07-31

### Post-PR17 Branch Cleanup Closure

- PR #17 merged;
- fresh inventory подтвердил 18 remote non-main branches;
- owner отдельно утвердил exact cleanup batch;
- удалены 18 / 18 approved remote branches;
- terminal verification snapshot: `main only`;
- local branch set сохранён без изменений;
- создан immutable cleanup evidence;
- runtime, deploy и database не изменялись.

## 2026-07-30

### Post-Organizational-Structure Baseline Refresh и Repository Reconciliation

- PR #16 и PR #17 merged как documentation-only;
- living documentation переведена на dynamic `origin/main` pointer;
- разделены current repository pointer и tested runtime anchors;
- созданы repository audits 2026-07-29 и 2026-07-30.

## 2026-07-29

### Organizational Structure v1 — PR #15

- migration 009;
- 7 tables, 16 triggers и 6 permissions;
- structures, versions, draft tree, documents metadata, history и compare;
- Automated Testing и Manual Desktop Acceptance: PASS;
- mobile testing: OUT OF SCOPE / NOT RUN.

## Более ранние изменения

Подробные historical records ранних security, theme и directory increments находятся в `docs/design`, `docs/decisions`, `docs/testing` и merged PR #1–#14.
