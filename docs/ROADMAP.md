# План разработки

## Стабильная контрольная точка

Функциональные PR #1–#9, #12, #15, #19 и #20 завершены и объединены в `main`. Documentation-only PR #10, #11, #13, #14, #16, #17 и #18 также merged и не создавали нового runtime baseline.

```text
current repository pointer: origin/main
latest functional PR: #20
PR #19 merge commit: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #20 merge / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
applied migrations: 001–011
stable built-in themes: 3
system roles: 4
system permissions: 25
```

Актуальный SHA `main` определяется через `origin/main`; exact SHA выше используются как исторические anchors.

## Завершённые этапы

- [x] базовый сайт, authentication, sessions и CSRF;
- [x] RBAC и управление пользователями;
- [x] required temporary-password change;
- [x] rejection audit;
- [x] archive/restore audit;
- [x] theme management и light-blue theme;
- [x] unified tile geometry и hover effects;
- [x] directories landing;
- [x] military ranks directory;
- [x] organizational element types directory;
- [x] Evgeniya Rostova theme;
- [x] Organizational Structure v1 и UI Polish 1–4;
- [x] repository/documentation reconciliation и cleanup 2026-07-31;
- [x] PR #19 — public military position types catalog;
- [x] Automated Testing и Manual Desktop Acceptance PR #19;
- [x] Final Review, merge approval, merge и post-merge state PR #19;
- [x] PR #20 — public military occupational specialties catalog;
- [x] UI remediation и Final PR Review remediation PR #20;
- [x] Automated Testing PASS после remediation;
- [x] Manual Desktop Acceptance и targeted recheck PASS;
- [x] repeated Final PR Review PASS;
- [x] separate merge approval и merge PR #20;
- [x] post-merge Git/GitHub verification PR #20;
- [ ] Post-PR20 Baseline Refresh documentation-only increment;
- [ ] PR, Final Review и merge Post-PR20 Baseline Refresh;
- [ ] fresh post-refresh branch inventory;
- [ ] separate owner decision for exact remote/local cleanup batch;
- [ ] branch cleanup and terminal verification.

## Последние функциональные инкременты

### Типовые воинские должности — PR #19

```text
migration: 010
tables: 14
triggers: 41
canonical types: 34
variants: 35
automated testing: PASS
manual desktop acceptance: PASS
mobile testing: OUT OF SCOPE / NOT RUN
```

### Публичные сведения о ВУС — PR #20

```text
migration: 011
tables: 9
triggers: 26
searchable records: 17
automated testing: PASS
manual desktop acceptance: PASS
targeted manual recheck: PASS
final PR review: PASS
post-merge verification: PASS
mobile testing: OUT OF SCOPE / NOT RUN
```

## Активный документационный инкремент

```text
increment: Post-PR20 Baseline Refresh
branch: docs/post-pr20-baseline-refresh
classification: documentation only
approved path allowlist: 22 Markdown paths
runtime/deploy/database changes: none
PR: not created
merge: not authorized
branch deletion: not authorized
```

Цель — привести living documentation к migrations 001–011 и merged PR #19/#20, сохранив исторические gate artifacts.

## Следующий функциональный инкремент

Не выбран и не утверждён. Возможные направления не являются задачами до отдельного Research / Analysis / Architecture / Approval:

- карточка военнослужащего;
- штатные структуры и кадровые назначения;
- развитие нормативных справочников;
- общий Documents domain и приказы;
- общий Audit domain;
- production/CI infrastructure;
- отдельный mobile verification increment.

## Постоянные ограничения

- Нельзя включать закрытые, ограниченные или фактические сведения без отдельного утверждения scope и защиты.
- Нельзя считать каталоги должностей/ВУС кадровым или персональным воинским учётом.
- Нельзя считать Documents domain реализованным из-за metadata внутри Organization.
- Нельзя считать mobile version проверенной без фактической acceptance.
- Нельзя выполнять PR creation, merge или branch deletion без соответствующего отдельного approval.
- `SAFE TO DELETE` не является deletion authorization.
- Исторический cleanup snapshot не разрешает автоматическое удаление будущих branches.
