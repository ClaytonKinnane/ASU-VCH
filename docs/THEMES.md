# Темы оформления

## Назначение

АСУ-ВЧ поддерживает глобальные доверенные темы для публичной части и административной панели. Активная тема едина для всей установки.

## Реестр

Установленные темы перечислены в `config/themes.php`. Реестр является allow-list и не формируется из HTTP-параметров или сканирования каталогов.

Встроенные темы:

- **АСУ Синяя** — `asu-blue`, тёмная тема и безопасный fallback;
- **АСУ Светлая синяя** — `asu-light-blue`, светлая минималистичная тема с основным цветом `#086ad5`.

## Управление

Страница управления:

```text
/admin/settings/themes.php
```

Просмотр требует `system.settings.view`, активация — `system.settings.update`.

После migration 006 активная тема хранится в:

```text
system_settings.ui.active_theme
```

`config/app.php['theme']` используется только как bootstrap/pre-install fallback.

## Состав темы

Каждая тема содержит CSS-assets с единым class contract:

```text
themes/{slug}/assets/css/theme.css
themes/{slug}/assets/css/auth.css
themes/{slug}/assets/css/account.css
themes/{slug}/assets/css/users.css
themes/{slug}/assets/css/theme-management.css
themes/{slug}/assets/css/operation-result-modal.css
```

Поведение operation-result modal является общим и находится в:

```text
public/assets/js/operation-result-modal.js
```

## Безопасность

- slug должен быть зарегистрирован;
- обязательные assets проверяются до активации;
- неизвестное или повреждённое значение приводит к fallback `asu-blue`;
- query, cookie и GET-preview не поддерживаются;
- загрузка ZIP, произвольного CSS/JS и редактирование темы из браузера отсутствуют.
