# Test Attempt 04: фактическая организационная структура v1

## Статус

```text
DATE: 2026-07-28
RESULT: FAILED / FIX REQUIRED
TESTED HEAD: 72c21cf8e175243b9acf11f6d9a900dded2bdabc
TARGET: Windows 10 / Open Server Panel 6.5.1 / PHP 8.5 / MySQL 8.4.8
PR GATE: CLOSED
```

## Пройденные этапы

- GitHub feature-ветка синхронизирована, divergence `0 0`, рабочее дерево чистое.
- Migration compatibility checker прошёл, включая UTF-8 BOM и ThemeRegistry path checks.
- Deploy-конфигурация сохранена: SHA-256 до и после deploy совпал.
- Создана резервная копия:
  - файл: `C:\OSPanel\backups\asu-vch\asu_vch-20260728-210910.sql`;
  - размер: `121429` байт;
  - SHA-256: `B51CEB106FB472427FF92BBB84904A04E53FB64A5D4B59B3B953981A537FA9B9`.
- Утверждённый `deploy\Deploy-Local.ps1` завершился успешно.
- PHP lint deploy-копии: `101` файл, ошибок нет.
- Оба запуска installer подтвердили `9` migrations и отсутствие новых migrations.
- Интеграционный checker организационной структуры: `PASS 58 / FAIL 0`.
- Security RBAC, approval и required-password-change regression прошли.

## Блокирующий дефект

`database/check-security-user-rejection.php` завершился ошибкой:

```text
Ожидалось 19 системных разрешений, найдено 25.
```

Migration 009 штатно добавила шесть organization permissions, поэтому точное историческое глобальное значение `19` больше не является корректным regression-инвариантом. Аналогичное жёсткое условие найдено ещё в трёх исторических checker-файлах:

- `database/check-security-user-archive-restore.php`;
- `tools/check-military-ranks-directory-core.php`;
- `tools/check-organizational-elements-directory-core.php`.

Предметные permissions и точное итоговое значение `25` уже проверяются organization checker. Исторические regression-checker должны подтверждать сохранение своей базовой security-модели — не менее `19` системных permissions — и продолжать выполнять все прежние сценарии.

## Исправление

В feature-ветку добавлены:

- узкий permission-baseline adapter с whitelist четырёх checker-файлов;
- обязательная проверка ровно одной смысловой замены;
- поддержка фиксированного и уже динамического вывода permission count;
- гарантированное удаление временного checker-файла через `finally`;
- подключение adapter в fail-fast runner;
- static self-test формата всех четырёх исторических checker-файлов.

Adapter изменяет только устаревший глобальный инвариант `=== 19` на `>= 19`. Остальной исходный acceptance-сценарий выполняется без изменений.

## Ограничения результата

- Themes/directories regression после точки отказа не выполнялся.
- HTTP smoke и post-test integrity в этой попытке не выполнялись.
- Browser, RBAC acceptance и desktop visual acceptance не выполнялись.
- Mobile testing не входит в утверждённую область и не заявляется.
- Статус `AUTOMATED_TESTING_STATUS=PASS` отсутствует.

Следующая попытка обязана начаться с нового backup и полного запуска fail-fast runner. До полного PASS Pull Request не создаётся.
