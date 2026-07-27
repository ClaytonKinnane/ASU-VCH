# Темы оформления

## Назначение

АСУ-ВЧ поддерживает глобальные доверенные темы для публичной части и административной панели. Активная тема едина для всей установки.

## Реестр

Установленные темы перечислены в `config/themes.php`. Реестр является статическим allow-list и не формируется из HTTP-параметров или сканирования каталогов.

Встроенные темы:

- **АСУ Синяя** — `asu-blue`, тёмная сине-бирюзовая тема и безопасный fallback;
- **АСУ Светлая синяя** — `asu-light-blue`, светлая минималистичная тема с синими контурами.

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

`config/app.php['theme']` используется как bootstrap/pre-install fallback.

## Обязательные assets

Обе темы реализуют единый class contract и содержат:

```text
themes/{slug}/assets/css/theme.css
themes/{slug}/assets/css/auth.css
themes/{slug}/assets/css/account.css
themes/{slug}/assets/css/users.css
themes/{slug}/assets/css/theme-management.css
themes/{slug}/assets/css/directories.css
themes/{slug}/assets/css/operation-result-modal.css
```

Общее поведение operation-result modal находится в:

```text
public/assets/js/operation-result-modal.js
```

## Публикация

Исходные темы находятся в корневом каталоге `themes`. Apache обслуживает только `public`, поэтому deploy публикует темы в:

```text
C:\OSPanel\home\asu-vch.local\public\themes\{slug}
```

`ThemeRegistry` проверяет регистрацию slug, наличие обязательных файлов и строит URL только для доступных assets.

## Безопасность

- slug должен присутствовать в `config/themes.php`;
- обязательные assets проверяются до активации;
- неизвестное или повреждённое значение приводит к fallback `asu-blue`;
- path traversal, абсолютные пути, URL-схемы, NUL и backslash в имени asset отклоняются;
- query-, cookie- и GET-preview темы не поддерживаются;
- активация выполняется POST-only с permission, CSRF и PRG;
- загрузка ZIP, произвольного CSS/JS, удаление темы и browser-редактор отсутствуют.

## Проверка

Integration checker управления темами проверяет реестр, доступность assets, persistence, audit и fallback. Справочные checker'ы дополнительно вызывают `ThemeRegistry::assetUrl()` для `css/directories.css` обеих тем.

Desktop-приёмка тем `asu-blue` и `asu-light-blue` завершена. Мобильная приёмка последних справочных инкрементов не выполнялась.
