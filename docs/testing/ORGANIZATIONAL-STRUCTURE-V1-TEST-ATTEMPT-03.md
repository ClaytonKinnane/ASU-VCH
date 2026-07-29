# Organizational Structure v1 — Test Attempt 03

## Статус

```text
RESULT: FAILED / FIX REQUIRED
AUTOMATED PASS CLAIM: NONE
FEATURE HEAD: 50aa825c9525f5627b1a75ee0be3ac0e14455ecf
TARGET: Windows 10 / Open Server Panel 6.5.1 / PHP 8.5 / MySQL 8.4.8
```

## Выполнено успешно

- GitHub feature-ветка синхронизирована, divergence `0/0`, рабочее дерево чистое;
- deploy-конфигурация существовала и сохранила SHA-256 `D7361F9C9633AD645E29948F0A019718B67AD6FEF0792E999B3F89861E5CE6BB`;
- резервная копия БД создана:
  - файл `C:\OSPanel\backups\asu-vch\asu_vch-20260728-210346.sql`;
  - размер `84066` байт;
  - SHA-256 `9795B3015B6CC068452CEBFFFB37E5019D703FC37CAFB7494FEA3D92D5D6B869`;
- утверждённый `deploy\Deploy-Local.ps1` завершён;
- deploy PHP lint: `101` файл, `0` ошибок;
- migration `009_organizational_structure_v1.sql` применена;
- повторный installer сообщил `Новых миграций нет`;
- organization checker подтвердил:
  - 7 таблиц;
  - 6 permissions;
  - 4 системные роли и 25 системных permissions;
  - отсутствие автоматических назначений обычным системным ролям;
  - 16 triggers;
  - весь синтетический lifecycle и rollback;
  - `55` успешных assertions.

## Блокирующее отклонение

Три assertions ресурсов тем завершились ошибкой:

```text
FAIL: тема asu-blue содержит organization.css
FAIL: тема asu-light-blue содержит organization.css
FAIL: тема asu-evgeniya-rostova содержит organization.css
```

Файлы были фактически опубликованы утверждённым deploy в `public/themes/<slug>/assets/css/organization.css`, но schema checker проверял source-путь `themes/<slug>/assets/css/organization.css` относительно deploy-root, где source-каталог тем намеренно отсутствует.

## Root cause

Checker использовал собственную файловую convention вместо production `ThemeRegistry`, который корректно выбирает `public/themes` в deploy и `themes` в source checkout.

## Исправление

- schema checker переведён на `theme_registry_service()->assetUrl()`;
- проверяется реальный production URL `/themes/<slug>/assets/css/organization.css`;
- compatibility checker дополнен guard-проверками:
  - schema checker использует `ThemeRegistry`;
  - schema checker не содержит прямую проверку непубликуемого source theme path.

## Gate

Попытка не считается PASS. Regression и HTTP smoke после organization checker не запускались, поскольку fail-fast runner корректно остановился. Требуется повторный полный automated runner на исправленном GitHub HEAD.
