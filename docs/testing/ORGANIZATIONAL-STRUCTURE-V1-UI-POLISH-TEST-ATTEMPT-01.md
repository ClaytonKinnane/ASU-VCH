# Test Attempt 01: Organizational Structure v1 UI Polish

## Статус

```text
DATE: 2026-07-28
RESULT: FAILED / TEST CHECKER FIX REQUIRED
TESTED HEAD: cfa369e965722073b95606bd303dfe8b21b4aafc
TARGET: Windows 10 / Open Server Panel 6.5.1 / PHP 8.5 / MySQL 8.4.8
PR GATE: CLOSED
```

## Пройденные этапы

- Локальная ветка обновлена до тестируемого HEAD.
- `origin/feature/organizational-structure-v1` и локальная ветка совпали, divergence `0 0`.
- Рабочее дерево было чистым.
- PHP lint изменённых views и UI checker прошёл.
- PowerShell runner успешно разобран Windows PowerShell 5.1.
- Migration compatibility checker прошёл.
- UI polish presentation checker в source-клоне прошёл: `PASS 31 / FAIL 0`.
- До deploy создана резервная копия:
  - файл: `C:\OSPanel\backups\asu-vch\asu_vch-20260728-231846.sql`;
  - размер: `125532` байта;
  - SHA-256: `F6B7FE1BEBCA6DBFB4E9CDD4EF368896A8B09B85216AA8268E2C54F888066E5F`.
- Утверждённый deploy завершился успешно.
- `config/local.php` сохранён: SHA-256 до и после deploy совпал.
- PHP lint deploy-копии: `104` файла, ошибок нет.

## Блокирующий дефект

UI polish checker на deploy-копии завершился ошибкой:

```text
Warning: file_get_contents(C:\OSPanel\home\asu-vch.local/themes/asu-blue/assets/css/organization.css):
Failed to open stream: No such file or directory

FAIL: Не удалось прочитать C:\OSPanel\home\asu-vch.local/themes/asu-blue/assets/css/organization.css.
```

Причина: checker использовал source-путь `$root/themes`, тогда как утверждённый deploy публикует темы в `$root/public/themes`.

Это дефект testing-инструмента, а не подтверждённый дефект UI implementation. Source-запуск checker прошёл полностью до deploy.

## Исправление

В feature-ветку добавлено:

- разрешение theme root через `public/themes` с fallback на source `themes`;
- чтение всех трёх `organization.css` через вычисленный theme root;
- static compatibility guard, который требует deploy-aware path в UI polish checker.

Бизнес-логика, views и theme CSS не изменялись этим исправлением.

## Не выполнено после точки отказа

- migration installer и idempotency в этой попытке;
- organization integration checker;
- security regression;
- themes/directories regression;
- HTTP smoke;
- post-test integrity;
- повторный desktop visual acceptance.

Строка `AUTOMATED_TESTING_STATUS=PASS` отсутствует. Pull Request не создаётся.

Следующая попытка обязана начаться с нового backup и полного fail-fast runner.
