# Test Attempt 01: Organizational Structure v1 UI Polish 4

## Статус

```text
DATE: 2026-07-29
RESULT: AUTOMATED PASS / MANUAL DESKTOP VISUAL ACCEPTANCE REQUIRED
TESTED HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
TARGET: Windows 10 / Open Server Panel 6.5.1 / PHP 8.5 / MySQL 8.4.8
PR GATE: CLOSED UNTIL MANUAL VISUAL CONFIRMATION
```

## Предварительные проверки

- Ветка `feature/organizational-structure-v1` обновлена до tested HEAD.
- Локальная ветка и `origin/feature/organizational-structure-v1` совпали, divergence `0 0`.
- Рабочее дерево было чистым.
- `organization-tree.js` не изменялся относительно approved baseline.
- PHP lint `tree.php` и UI checker прошёл.
- Structural `organization.css` идентичен во всех трёх темах.
- Presentation contract UI Polish 4 прошёл.
- Migration compatibility checker прошёл.
- UI polish checker в source-клоне прошёл: `PASS 64 / FAIL 0`.

## Backup и deploy

До deploy создана резервная копия:

- файл: `C:\OSPanel\backups\asu-vch\asu_vch-20260729-110320.sql`;
- размер: `125533` байта;
- SHA-256: `7727B6FAF804695D87B57ABB896889139439F045A7FA7F9CE0B271979AF7E0F6`.

Утверждённый `deploy\Deploy-Local.ps1` завершился успешно.

`config/local.php` сохранён:

```text
SHA-256 BEFORE: D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
SHA-256 AFTER DEPLOY: D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
SHA-256 FINAL: D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
```

## Автоматические результаты

- PHP lint deploy-копии: `104` файла, ошибок нет.
- UI polish checker на deploy-копии: `PASS 64 / FAIL 0`.
- Migration 009: применено migrations `9`, новых migrations нет.
- Повторный installer подтвердил idempotency.
- Organization integration checker: `PASS 58 / FAIL 0`.
- Security regression полностью прошёл.
- Theme management и theme missing-asset regression прошли.
- Military ranks и organizational element types directory regression прошли.
- HTTP smoke прошёл:
  - `/` — HTTP 200;
  - `/health.php` — HTTP 200;
  - `/admin/` — HTTP 302.
- Post-test integrity:
  - deploy config сохранён;
  - final HEAD не изменился;
  - рабочее дерево чистое;
  - final divergence `0 0`.

Итоговые маркеры:

```text
AUTOMATED_TESTING_STATUS=PASS
UI_POLISH_4_TEST_COMMAND_STATUS=PASS
```

## Подтверждённые UI contracts

Автоматический checker подтвердил:

- поиск дерева использует общий tools-контейнер с сохранёнными `data-tree-*` contracts;
- search input выровнен по внешним границам button group;
- node edit icon использует единый наклонный карандаш;
- прежнее объединённое reset-правило для edit/add отсутствует;
- tree toggle сохраняет native markup, glyph и `aria-expanded` behavior;
- tree toggle имеет заметный theme-aware indicator;
- `organization-tree.js` и `organization-ui-controls.js` не изменены;
- прежние UI Polish 1–3, calendar, autofill, CSRF, revision и POST contracts сохранены;
- structural `organization.css` идентичен во всех трёх темах.

## Оставшийся gate

Требуется manual desktop visual acceptance во всех трёх темах:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

Проверить:

1. поле поиска совпадает по внешней ширине с группой кнопок `Раскрыть всё` / `Свернуть всё`;
2. node-action `Изменить` показывает тот же наклонный карандаш, что и `Изменить карточку`;
3. индикатор уровня заметен, использует цвета темы и ясно различает состояния `▾` / `▸`;
4. поиск, раскрытие/сворачивание, focus-visible и клавиатурное управление работают без регрессий;
5. console errors и asset 404 отсутствуют.

Mobile testing не входит в утверждённую область и не заявляется.

Pull Request не создаётся до явного подтверждения manual desktop visual acceptance пользователем.
