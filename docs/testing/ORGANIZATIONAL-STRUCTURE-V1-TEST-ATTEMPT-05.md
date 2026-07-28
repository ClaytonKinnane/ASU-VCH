# Test Attempt 05: фактическая организационная структура v1

## Статус

```text
DATE: 2026-07-28
RESULT: FAILED / FIX REQUIRED
TESTED HEAD: c57fb1f8738cace675e495cfc77ca9da6e80ef84
TARGET: Windows 10 / Open Server Panel 6.5.1 / PHP 8.5 / MySQL 8.4.8
PR GATE: CLOSED
```

## Пройденные этапы

- GitHub feature-ветка синхронизирована, divergence `0 0`, рабочее дерево чистое.
- Migration compatibility checker прошёл все заявленные статические проверки.
- Deploy-конфигурация сохранена: SHA-256 до и после deploy совпал.
- Создана резервная копия:
  - файл: `C:\OSPanel\backups\asu-vch\asu_vch-20260728-215450.sql`;
  - размер: `121432` байта;
  - SHA-256: `34F5D94E1DEA793A7AD86315C5D64EBB383F3F7FC7F92CBE3ADB1B005C644626`.
- Утверждённый `deploy\Deploy-Local.ps1` завершился успешно.
- PHP lint deploy-копии: `102` файла, ошибок нет.
- Оба запуска installer подтвердили `9` migrations и отсутствие новых migrations.
- Интеграционный checker организационной структуры: `PASS 58 / FAIL 0`.
- Security RBAC, approval и required-password-change regression прошли.
- Legacy rejection-checker через permission adapter прошёл полностью и подтвердил `25` системных permissions.

## Блокирующий дефект

При подготовке `database/check-security-user-archive-restore.php` permission adapter завершился ошибкой:

```text
Warning: Undefined variable $outputReplacementCount
Fatal error: str_replace(): Argument #3 ($subject) must be of type array|string, null given
```

Причина: в ветке замены фиксированной строки вывода `str_replace` был вызван с тремя аргументами. Переменная счётчика ошибочно попала на позицию subject; исходный подготовленный checker не был передан.

## Исправление

В feature-ветку внесены:

- отдельная чистая функция `prepare_permission_baseline_compatible_checker`;
- единый whitelist четырёх legacy checker-файлов;
- корректный четырёхаргументный вызов `str_replace`;
- post-conditions: ровно одно совместимое условие `>= 19` и ровно один динамический вывод permission count;
- CLI adapter использует только протестированную функцию подготовки;
- migration compatibility checker реально выполняет подготовку всех четырёх legacy checker-файлов до backup и deploy.

Таким образом runtime-ошибки преобразования теперь должны выявляться на предварительном compatibility-этапе, до полного локального цикла.

## Ограничения результата

- Archive/restore regression не был выполнен.
- Themes/directories regression после точки отказа не выполнялся.
- HTTP smoke и post-test integrity в этой попытке не выполнялись.
- Browser, RBAC acceptance и desktop visual acceptance не выполнялись.
- Mobile testing не входит в утверждённую область и не заявляется.
- Статус `AUTOMATED_TESTING_STATUS=PASS` отсутствует.

Следующая попытка обязана начаться с нового backup и полного запуска fail-fast runner. До полного PASS Pull Request не создаётся.
