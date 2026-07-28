# Test Attempt 06: фактическая организационная структура v1

## Статус

```text
DATE: 2026-07-28
RESULT: FAILED / FIX REQUIRED
TESTED HEAD: d604cae8bf1057366dbff6319b3dc1aaba50848e
TARGET: Windows 10 / Open Server Panel 6.5.1 / PHP 8.5 / MySQL 8.4.8
PR GATE: CLOSED
```

## Пройденные этапы

- Локальная ветка fast-forward обновлена до тестируемого HEAD и совпала с `origin/feature/organizational-structure-v1`, divergence `0 0`.
- Git HTTPS временно давал `unexpected eof while reading`, но повторный `fetch` завершился успешно без отключения проверки сертификатов.
- Compatibility checker прошёл, включая реальную подготовку четырёх legacy checker-файлов.
- Deploy-конфигурация сохранена: SHA-256 до и после deploy совпал.
- Создана резервная копия:
  - файл: `C:\OSPanel\backups\asu-vch\asu_vch-20260728-220413.sql`;
  - размер: `121432` байта;
  - SHA-256: `A57D2D7E58F5DB98DB979AB94F2F2CF0270F6356292A0EFC6AB6AC1010D7E686`.
- Утверждённый `deploy\Deploy-Local.ps1` завершился успешно.
- PHP lint deploy-копии: `103` файла, ошибок нет.
- Оба запуска installer подтвердили `9` migrations и отсутствие новых migrations.
- Интеграционный checker организационной структуры: `PASS 58 / FAIL 0`.
- Security regression полностью прошёл, включая rejection и archive/restore через compatibility adapter.

## Блокирующий дефект

`database/check-theme-management.php` завершился ошибкой:

```text
FAIL Evgeniya Rostova required assets registered
```

Реестр `config/themes.php` уже корректно регистрировал `css/organization.css` для всех трёх тем, включая `asu-evgeniya-rostova`. Исторический theme checker сравнивал `required_assets` с прежним точным массивом без нового организационного CSS.

При review оставшихся directory regression также обнаружен прямой legacy-путь `$root/themes/...` в двух core-checker-файлах. Утверждённый deploy публикует темы в `public/themes`, поэтому эти проверки гарантированно отказали бы после исправления текущего дефекта.

## Исправление

В feature-ветку добавлены:

- `css/organization.css` в точный ожидаемый массив theme management checker;
- static compatibility guard для ожидаемого организационного asset;
- whitelist-преобразование legacy theme path только для:
  - `tools/check-military-ranks-directory-core.php`;
  - `tools/check-organizational-elements-directory-core.php`;
- фактическая проверка сформированного `public/themes` пути в compatibility checker.

Предметная логика тем, справочников и acceptance-сценариев не изменяется.

## Ограничения результата

- `check-theme-asset-failure.php`, directory regression, HTTP smoke и post-test integrity после точки отказа не выполнялись.
- Browser, RBAC acceptance и desktop visual acceptance не выполнялись.
- Mobile testing не входит в утверждённую область и не заявляется.
- Напечатанная вручную после исключения строка `TEST_COMMAND_BLOCK_STATUS=PASS` не является результатом runner и недействительна.
- `AUTOMATED_TESTING_STATUS=PASS` отсутствует.

Следующая попытка обязана начаться с нового backup и полного запуска fail-fast runner. До полного автоматического PASS Pull Request не создаётся.
