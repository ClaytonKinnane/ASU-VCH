# Test Attempt 02: Organizational Structure v1 UI Polish 2

## Статус

```text
DATE: 2026-07-29
RESULT: AUTOMATED PASS / MANUAL DESKTOP VISUAL ACCEPTANCE REQUIRED
TESTED HEAD: 670920866e5a0cbcd910646b69e2d3de75fd2928
TARGET: Windows 10 / Open Server Panel 6.5.1 / PHP 8.5 / MySQL 8.4.8
PR GATE: CLOSED UNTIL MANUAL VISUAL CONFIRMATION
```

## Предварительные проверки

- Локальная ветка `feature/organizational-structure-v1` обновлена до tested HEAD.
- Локальная ветка и `origin/feature/organizational-structure-v1` совпали, divergence `0 0`.
- Рабочее дерево было чистым.
- PHP lint исправленного UI checker прошёл.
- Migration compatibility checker прошёл.
- UI polish checker в source-клоне прошёл: `PASS 43 / FAIL 0`.

## Backup и deploy

До deploy создана резервная копия:

- файл: `C:\OSPanel\backups\asu-vch\asu_vch-20260729-074553.sql`;
- размер: `125533` байта;
- SHA-256: `CD82276ADA0B3E95DEC2F8D4C48CD81D1A766BA57310847CDF325DF74A2F844E`.

Утверждённый `deploy\Deploy-Local.ps1` завершился успешно.

`config/local.php` сохранён:

```text
SHA-256 BEFORE: D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
SHA-256 AFTER DEPLOY: D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
SHA-256 FINAL: D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
```

## Автоматические результаты

- PHP lint deploy-копии: `104` файла, ошибок нет.
- UI polish checker на deploy-копии: `PASS 43 / FAIL 0`.
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
UI_POLISH_2_TEST_COMMAND_STATUS=PASS
```

## Подтверждённые UI contracts

Автоматический checker подтвердил:

- единый height contract action controls во всех трёх темах;
- однозначную CSS-геометрию карандаша для edit actions;
- три functional calendar button trigger;
- явную связь `label[for]`, `input[id]`, `button[data-date-picker-target]`;
- `showPicker()` и fallback `input.click()`;
- сохранение focus и отсутствие submit behavior;
- идентичность structural `organization.css` во всех трёх темах;
- сохранность CSRF, POST endpoints и revision fields.

## Оставшийся gate

Требуется manual desktop visual acceptance во всех трёх темах:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

Проверить:

1. соседние action controls имеют одинаковую высоту и не прыгают;
2. все edit actions показывают понятный карандаш;
3. календарная кнопка открывает native date picker мышью;
4. календарная кнопка работает клавишами Enter и Space;
5. label/input/date button визуально и функционально связаны;
6. console errors и asset 404 отсутствуют.

Mobile testing не входит в утверждённую область и не заявляется.

Pull Request не создаётся до явного подтверждения manual desktop visual acceptance пользователем.
