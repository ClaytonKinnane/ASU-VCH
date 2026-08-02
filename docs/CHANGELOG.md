# История изменений

## 2026-08-02

### Full Documentation Consistency Reconciliation

- выполнен полный read-only audit Markdown-документации относительно `main @ 1eef56b50a8d2278a62c5b70a471663b12132354` и merged PR #1–#22;
- подтверждено соответствие канонического functional baseline: latest functional PR #20, migrations 001–011, 4 system roles, 25 system permissions и 3 built-in themes;
- `docs/domains/README.md` актуализирован до PR #20 и четырёх реализованных специализированных Reference-каталогов;
- `docs/migrations/README.md` актуализирован до migrations 001–011, включая compatibility packaging migrations 010 и 011;
- введено правило semantic classification: current-state раздел обновляется вместе с baseline независимо от каталога файла;
- `docs/DATABASE.md` явно классифицирован как target architecture, broken reference `DOMAINS.md` исправлен на `domains/README.md`, а первоначальный next-step plan обозначен как historical sequence;
- `docs/STARTER-ADMIN-SPEC.md` обозначен как historical implemented specification functional PR #1;
- `Admin / 12315` согласованно классифицирован как публично известный local-only development fixture, а не production или instance-specific credential;
- production credentials, реальные temporary passwords, session data и `config/local.php` остаются запрещёнными к публикации;
- runtime password hardening отложен в отдельный будущий Security increment;
- создан immutable audit record `DOCUMENTATION-CONSISTENCY-AUDIT-2026-08-02.md`;
- runtime, database, migrations, config, themes, public routes, deploy и tools не изменялись;
- runtime/database/browser retesting не требовался;
- mobile testing: `OUT OF SCOPE / NOT RUN`.

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

- создан documentation-only workflow для актуализации baseline после PR #19/#20;
- initial scope: 22 Markdown paths;
- initial Documentation Implementation и Validation: PASS;
- первый Final PR Review: `CHANGES_REQUIRED`;
- findings: incomplete operational closure PR #19, stale post-PR markers и отсутствие implementation head;
- owner отдельно разрешил remediation и расширил allowlist с 22 до 25 Markdown-путей;
- добавлены post-merge closures operational records PR #19;
- living/current-state documents синхронизированы;
- repeat Documentation Validation: PASS;
- repeat Final PR Review attempt 2: PASS;
- Final PR Review ID: `4835150606`;
- owner отдельно разрешил merge;
- PR #21 merged методом merge commit;
- final PR head: `4d44874ef02ffb9381334acfabfa383eba3e4ead`;
- merge commit: `f5b53f2ee4453f293b58cbe486e0943ab602335b`;
- post-merge Git verification: PASS;
- runtime, database, deploy, config, themes и tools не изменялись.

### Post-PR21 branch cleanup

После отдельного owner approval выполнен remote-first cleanup:

- remote deletion set: 3 branches;
- remote branches deleted: 3 / 3;
- после remote cleanup на GitHub осталась только `main`;
- local deletion set: 13 merged feature branches;
- local branches deleted через `git branch -d`: 13 / 13;
- после local cleanup локально осталась только `main`;
- final local main и `origin/main`: `f5b53f2ee4453f293b58cbe486e0943ab602335b`;
- working tree: clean;
- force deletion: not used;
- terminal verification: PASS;
- незамерженные commits не потеряны.

Датированный evidence: [Post-PR21 Merge and Cleanup Closure 2026-08-01](POST-PR21-MERGE-CLEANUP-CLOSURE-2026-08-01.md).

### Documentation closure synchronization

- шесть living documents актуализированы до устойчивого post-PR21 состояния;
- три operational records PR #21 получили post-merge and cleanup closure sections;
- удалённая docs-ветка больше не используется как operational dependency;
- current PR/Issue/branch state определяется динамически;
- документационный diff не изменяет runtime-tested baseline;
- Mobile PASS не заявляется.

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
