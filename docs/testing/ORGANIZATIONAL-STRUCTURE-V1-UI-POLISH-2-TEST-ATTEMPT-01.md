# Test Attempt 01: Organizational Structure v1 UI Polish 2

## Статус

```text
DATE: 2026-07-29
RESULT: FAILED / STATIC CHECKER FIX REQUIRED
TESTED HEAD: e0bf66415f76ea5df742f6fb7a5f191ae831855a
TARGET: Windows 10 / Open Server Panel 6.5.1 / PHP 8.5 / MySQL 8.4
PR GATE: CLOSED
```

## Пройденные этапы

- Локальная ветка `feature/organizational-structure-v1` обновлена до тестируемого HEAD.
- Локальная ветка и `origin/feature/organizational-structure-v1` совпали, divergence `0 0`.
- Рабочее дерево было чистым.
- PHP lint изменённых view/checker-файлов прошёл.
- Presentation contract нового `organization-ui-controls.js` прошёл.
- Migration compatibility checker прошёл.
- UI polish checker выполнил `42 PASS / 1 FAIL`.

## Точка отказа

Единственный отказ:

```text
FAIL: calendar trigger связан с уникальным date input
```

Checker ожидал по два появления статических идентификаторов:

- `organization-document-date-create`;
- `organization-effective-from`.

Корректная доступная связка содержит по три появления одного идентификатора:

1. `label[for]`;
2. `input[id]`;
3. `button[data-date-picker-target]`.

Разметка, JS contract, CSS height/pencil/calendar contracts и сохранность POST/CSRF/revision прошли остальные проверки.

## Исправление

`tools/check-organizational-structure-ui-polish.php` изменён так, чтобы проверять явные пары атрибутов:

- `label for`;
- `input id`;
- `data-date-picker-target`.

Для динамического ID редактируемого документа checker также требует ровно три использования одной переменной `$documentDateId`.

Views, CSS, JavaScript, server routes, БД, RBAC и business logic этим исправлением не изменялись.

## Не выполнено

Из-за fail-fast остановки до вызова полного runner не выполнялись:

- новый backup;
- deploy;
- PHP lint deploy-копии;
- UI checker на deploy-копии;
- migration installer/idempotency;
- organization integration checker;
- security regression;
- themes/directories regression;
- HTTP smoke;
- post-test integrity;
- desktop visual acceptance.

Маркеры `AUTOMATED_TESTING_STATUS=PASS` и `UI_POLISH_2_TEST_COMMAND_STATUS=PASS` отсутствуют.

Следующая попытка должна повторно запустить source checker и затем полный fail-fast runner с новым backup.