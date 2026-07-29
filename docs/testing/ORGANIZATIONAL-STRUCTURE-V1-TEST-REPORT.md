# Test Report: Organizational Structure v1

## Статус

```text
DATE: 2026-07-29
RESULT: PASS
FEATURE BRANCH: feature/organizational-structure-v1
TESTED CODE HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
AUTOMATED TESTING: PASS
MANUAL DESKTOP ACCEPTANCE: PASS
MOBILE TESTING: OUT OF SCOPE / NOT RUN
PR GATE: READY FOR EXPLICIT USER AUTHORIZATION
MERGE GATE: CLOSED UNTIL SEPARATE EXPLICIT USER AUTHORIZATION
```

## Область отчёта

Отчёт закрывает тестирование Organizational Structure v1 и последующих presentation-only UI Polish 1–4 в утверждённой области.

Проверены:

- схема и migration 009;
- доменная логика организационных структур;
- версии, документы-основания и дерево элементов;
- lifecycle approve / activate / cancel / archive / restore;
- optimistic revision contract;
- RBAC и HTTP 403;
- три зарегистрированные темы;
- desktop presentation и accessibility contracts;
- regressions существующих модулей.

## Автоматическое тестирование

Полный Windows / Open Server fail-fast runner завершился успешно на tested code HEAD.

### Предварительные проверки

- Ветка и `origin/feature/organizational-structure-v1` совпали, divergence `0 0`.
- Рабочее дерево было чистым.
- PHP lint изменённых файлов прошёл.
- Migration compatibility checker прошёл.
- UI polish checker в source-клоне: `PASS 64 / FAIL 0`.
- `organization-tree.js` не изменялся в UI Polish 4.
- Structural `organization.css` идентичен во всех трёх темах.

### Backup и deploy

До deploy создана резервная копия:

```text
FILE: C:\OSPanel\backups\asu-vch\asu_vch-20260729-110320.sql
SIZE: 125533 bytes
SHA-256: 7727B6FAF804695D87B57ABB896889139439F045A7FA7F9CE0B271979AF7E0F6
```

Утверждённый `deploy\Deploy-Local.ps1` завершился успешно.

`config/local.php` сохранён до deploy, после deploy и после полного тестирования:

```text
D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB
```

### Результаты полного runner

- PHP lint deploy-копии: `104` файла, ошибок нет.
- UI polish checker на deploy-копии: `PASS 64 / FAIL 0`.
- Migration 009: применено migrations `9`, новых migrations нет.
- Повторный installer подтвердил idempotency.
- Organization integration checker: `PASS 58 / FAIL 0`.
- Security regression полностью прошёл.
- Theme management и missing-asset regression прошли.
- Military ranks directory regression прошёл.
- Organizational element types directory regression прошёл.
- HTTP smoke:
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

## Ручное функциональное desktop acceptance

Пользователь подтвердил:

1. создание тестовой структуры и единственного корня;
2. редактирование карточки структуры;
3. добавление, редактирование, перемещение, сортировку и удаление узлов;
4. подтверждение удаления поддерева;
5. добавление документов-оснований и назначение одного основного документа;
6. утверждение и ввод версии в действие;
7. создание нового черновика, сравнение версий и отмену черновика;
8. архивирование и восстановление с обязательным основанием;
9. просмотр истории изменений;
10. права `view`, `update`, `publish`, `history`, включая пользователя без `view`;
11. корректную страницу HTTP 403;
12. работу тем `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova`;
13. отсутствие asset 404;
14. длинные значения, клавиатурный focus и отображение форм.

## Финальное desktop visual acceptance

После UI Polish 1–4 пользователь подтвердил во всех трёх темах:

- поле поиска совпадает по внешним границам с группой `Раскрыть всё` / `Свернуть всё`;
- длинный поисковый текст не расширяет и не сдвигает tools-блок;
- node-action `Изменить` использует наклонный карандаш, совпадающий с `Изменить карточку`;
- наклон карандаша не меняется при открытии и закрытии panel;
- tree-toggle имеет заметный theme-aware indicator;
- состояния `▾` и `▸` отображаются корректно;
- `Раскрыть всё`, `Свернуть всё` и поиск работают без регрессий;
- `Tab`, клавиатурное управление и `focus-visible` работают корректно;
- console errors и asset 404 отсутствуют.

Ранее в UI Polish 1–3 также были подтверждены после исправлений:

- единая высота action-controls;
- стабильная строка node actions;
- отдельные panels ниже строки действий;
- тематические стрелки `Выше` / `Ниже`;
- разделение trigger `Удалить` и финального действия `Подтвердить удаление`;
- функциональные calendar buttons для native date picker;
- исправление Chromium autofill;
- единая presentation-геометрия edit/add/danger controls.

## Ограничения

- Тестирование выполнено на desktop в локальной среде Windows / Open Server Panel.
- Мобильное тестирование не входило в утверждённую область и не выполнялось.
- Документационные commits после tested code HEAD не изменяют deployed application code.
- Pull Request не создавался в рамках данного отчёта.
- Merge не выполнялся.

## Заключение

```text
ARCHITECTURE: COMPLETE
SPECIFICATION: COMPLETE
REVIEW: COMPLETE
IMPLEMENTATION: COMPLETE
AUTOMATED TESTING: PASS
MANUAL DESKTOP ACCEPTANCE: PASS
FINAL FEATURE STATUS: ACCEPTED
PR READINESS: READY FOR EXPLICIT USER AUTHORIZATION
```

Feature-ветка готова к созданию Pull Request после отдельного явного разрешения пользователя. Merge допускается только после отдельного явного разрешения после review PR.
