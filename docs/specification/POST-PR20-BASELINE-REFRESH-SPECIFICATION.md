# Specification — Post-PR20 Baseline Refresh

## Статус

```text
DATE: 2026-08-01
VERSION: 0.2
STATUS: APPROVED / IMPLEMENTED / PR OPEN
BASELINE: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
BRANCH: docs/post-pr20-baseline-refresh
PR: #21 OPEN
CLASSIFICATION: DOCUMENTATION ONLY
```

## 1. Назначение

Актуализировать документацию АСУ-ВЧ после merge функциональных PR #19 и #20, не изменяя runtime, database, deploy, themes, tools или Git refs.

## 2. Exact changed-path allowlist

После замечаний первого Final PR Review PR #21 и отдельного owner approval финальный scope содержит ровно 25 Markdown-путей:

```text
README.md
docs/README.md
docs/PROJECT-STATUS.md
docs/PROJECT.md
docs/DEVELOPMENT.md
docs/ENVIRONMENT.md
docs/LOCAL-RUNBOOK.md
docs/DATABASE-CURRENT.md
docs/THEMES.md
docs/ACCESS.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/ARCHITECTURAL-PATTERNS.md
docs/architecture/POST-PR20-BASELINE-REFRESH-ARCHITECTURE.md
docs/specification/POST-PR20-BASELINE-REFRESH-SPECIFICATION.md
docs/review/POST-PR20-BASELINE-REFRESH-FORMAL-REVIEW.md
docs/decisions/POST-PR20-BASELINE-REFRESH-APPROVAL.md
docs/implementation/POST-PR20-BASELINE-REFRESH-IMPLEMENTATION.md
docs/testing/POST-PR20-BASELINE-REFRESH-VALIDATION.md
docs/implementation/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-IMPLEMENTATION.md
docs/testing/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-LOCAL-RUNBOOK.md
docs/review/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-FORMAL-REVIEW.md
docs/implementation/MILITARY-POSITIONS-DIRECTORY-V1-IMPLEMENTATION.md
docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-LOCAL-RUNBOOK.md
docs/review/MILITARY-POSITIONS-DIRECTORY-V1-FORMAL-REVIEW.md
```

Любой дополнительный путь требует нового Review и Approval.

## 3. Required baseline facts

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
repository pointer: origin/main
refresh baseline / PR #20 merge: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #19: MERGED
PR #19 merge: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #19 final feature head: 5424cefe2f1a6bdc2fa706612040a3985c88f04f
PR #20: MERGED
PR #20 merge: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime: 9db06c4a26066ca25dc36c627c1236089a3c1238
PR #20 final feature head: bea147505a85010b61fe938eb07ec474d76cdab5
latest functional PR: #20
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
active documentation PR: #21
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```

Current `main` определяется через `origin/main`; exact SHA являются historical anchors.

## 4. Living-document requirements

Living documents должны:

- отражать PR #19/#20 как merged и PR #21 как текущий open documentation PR;
- содержать migrations 001–011, 4 роли, 25 permissions и 3 темы;
- фиксировать 9 CSS-assets каждой темы;
- отделять tested runtime heads от documentation-only heads;
- не содержать current assertions `PR not created` для refresh;
- сохранять cleanup как отдельный post-merge gate.

Затрагиваемые living/current-state файлы:

```text
README.md
docs/README.md
docs/PROJECT-STATUS.md
docs/PROJECT.md
docs/DEVELOPMENT.md
docs/ENVIRONMENT.md
docs/LOCAL-RUNBOOK.md
docs/DATABASE-CURRENT.md
docs/THEMES.md
docs/ACCESS.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/ARCHITECTURAL-PATTERNS.md
```

## 5. Operational closure requirements

### 5.1 PR #20 / VUS

Current status должен быть merged/post-merge verified, historical attempts сохраняются, branch deletion остаётся pending separate approval.

### 5.2 PR #19 / Military Positions

Три operational record должны получить current-state closure:

- Implementation: `IMPLEMENTED / TESTED / ACCEPTED / MERGED`, PR #19 closed/merged, merge commit и tested runtime head;
- Local Runbook: historical pre-merge runner instructions должны быть явно отделены от current stable runbook;
- Formal Review: сохранить исходный review verdict и добавить post-merge closure без изображения merge как части исходного review.

Датированные Automated Testing и Manual Desktop Acceptance evidence PR #19 не изменяются.

## 6. Process records

Architecture, Specification, Formal Review, Approval, Implementation и Validation текущего refresh должны отражать:

- initial approved scope 22;
- первый Final PR Review PR #21 с `CHANGES REQUIRED`;
- расширение scope до 25 по отдельному approval;
- PR #21 open;
- remediation implementation head;
- повторную Documentation Validation;
- отсутствие merge и branch deletion.

## 7. Validation requirements

1. base и merge-base = `3082ec6...`;
2. branch behind `main` = 0;
3. exact changed-path set = 25;
4. все changed files имеют расширение `.md`;
5. non-Markdown diff = 0;
6. runtime/config/database/migrations/themes/tools/Git refs diff = 0;
7. living/current documents не содержат устаревших утверждений `PR #21 not created`;
8. operational records PR #19 и PR #20 имеют post-merge closure;
9. PR #19/#20 anchors согласованы;
10. migrations 010/011 и counts согласованы с evidence;
11. roles 4 / permissions 25 / themes 3;
12. theme CSS contract = 9;
13. relative Markdown links разрешаются;
14. secret scan PASS;
15. historical evidence preservation PASS;
16. PR #21 open / not merged;
17. branch deletion не выполнялась;
18. Mobile PASS не заявляется.

## 8. Test classification

```text
PHP LINT: NOT REQUIRED
SQL / INSTALLER: NOT REQUIRED
DEPLOY: NOT REQUIRED
DATABASE TESTING: NOT REQUIRED
HTTP / BROWSER: NOT REQUIRED
DOCUMENTATION VALIDATION: REQUIRED
MOBILE TESTING: OUT OF SCOPE / NOT RUN
```

## 9. Branch cleanup boundary

После merge PR #21 и post-merge verification требуется fresh remote/local inventory, exact cleanup batch и отдельное owner approval. Remote deletion выполняется первой, затем `fetch --prune` и approved local deletion через `git branch -d`.

## 10. Merge gate

Merge PR #21 разрешён только после повторного Documentation Validation PASS, повторного Final PR Review PASS и отдельного явного owner merge approval.
