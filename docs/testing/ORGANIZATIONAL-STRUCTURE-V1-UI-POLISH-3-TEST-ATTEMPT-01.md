# Test Attempt 01: Organizational Structure v1 UI Polish 3

## Статус

```text
DATE: 2026-07-29
RESULT: AUTOMATED PASS / MANUAL DESKTOP VISUAL ACCEPTANCE REQUIRED
TESTED HEAD: 2275737a398b39d9a92d010bb52122fa51d817fb
TARGET: Windows 10 / Open Server Panel 6.5.1 / PHP 8.5 / MySQL 8.4.8
PR GATE: CLOSED UNTIL MANUAL VISUAL CONFIRMATION
```

## Предварительные проверки

- Локальная ветка `feature/organizational-structure-v1` обновлена до tested HEAD.
- Локальная ветка и `origin/feature/organizational-structure-v1` совпали, divergence `0 0`.
- Рабочее дерево было чистым.
- PHP lint `tree.php` и UI checker прошёл.
- Node action JavaScript presentation contract прошёл.
- Migration compatibility checker прошёл.
- UI polish checker в source-клоне прошёл: `PASS 55 / FAIL 0`.

## Backup и deploy

До deploy создана резервная копия:

- файл: `C:\OSPanel\backups\asu-vch\asu_vch-20260729-090449.sql`;
- размер: `125533` байта;
- SHA-256: `DA608A4F12B8E0CAD615FE16C5A99782732DEB1E62DADE5DB711B48466E82AA4`.

Утверждённый `deploy\Deploy-Local.ps1` завершился успешно.

`config/local.php` сохранён:

```text
SHA-256 BEFORE: D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
SHA-256 AFTER DEPLOY: D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
SHA-256 FINAL: D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
```

## Автоматические результаты

- PHP lint deploy-копии: `104` файла, ошибок нет.
- UI polish checker на deploy-копии: `PASS 55 / FAIL 0`.
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
UI_POLISH_3_TEST_COMMAND_STATUS=PASS
```

## Подтверждённые UI contracts

Автоматический checker подтвердил:

- дерево использует стабильные button triggers без `details`;
- action bar отделён от области panels;
- четыре trigger/panel binding согласованы через target, `aria-controls` и panel ID;
- `Выше` и `Ниже` сохраняют POST contract и имеют тематические стрелки;
- удаление разделено на trigger `Удалить` и финальное действие `Подтвердить удаление`;
- JS синхронизирует `hidden` и `aria-expanded` только внутри текущего узла;
- toggle logic не отправляет формы;
- единый height contract применён во всех трёх темах;
- прежние calendar, autofill и pencil contracts сохранены;
- CSRF, revision и POST endpoints не изменены;
- structural `organization.css` идентичен во всех трёх темах.

## Оставшийся gate

Требуется manual desktop visual acceptance во всех трёх темах:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

Проверить:

1. все шесть node action buttons имеют одинаковую высоту;
2. `Выше` и `Ниже` показывают понятные тематические стрелки;
3. открытие `Переместить`, `Изменить`, `Добавить дочерний` и `Удалить` не меняет положение кнопок;
4. открывается только одна panel текущего узла;
5. `Удалить` открывает confirmation panel, а реальное удаление выполняется только кнопкой `Подтвердить удаление`;
6. клавиатурные Enter/Space, focus-visible и Tab-порядок работают;
7. console errors и asset 404 отсутствуют.

Mobile testing не входит в утверждённую область и не заявляется.

Pull Request не создаётся до явного подтверждения manual desktop visual acceptance пользователем.
