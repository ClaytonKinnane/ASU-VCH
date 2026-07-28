# Темы оформления

## Назначение

АСУ-ВЧ поддерживает глобальные доверенные темы для публичной части и административной панели. Активная тема едина для всей установки.

## Реестр

Установленные темы перечислены в `config/themes.php`. Реестр является статическим allow-list и не формируется из HTTP-параметров или сканирования каталогов.

В ветке `feature/theme-evgeniya-rostova` зарегистрированы:

- **АСУ Синяя** — `asu-blue`, тёмная сине-бирюзовая тема и безопасный fallback;
- **АСУ Светлая синяя** — `asu-light-blue`, светлая минималистичная тема с синими контурами;
- **Евгения Ростова** — `asu-evgeniya-rostova`, светлая розово-лиловая тема с сердечками, воздушными шариками и мягкими игрушками.

Третья тема реализована в feature-ветке, но до завершения локального Testing, desktop/browser-приёмки, Pull Request и отдельного merge approval не считается частью стабильного `main`.

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

Все темы реализуют единый class contract и содержат:

```text
themes/{slug}/assets/css/theme.css
themes/{slug}/assets/css/auth.css
themes/{slug}/assets/css/account.css
themes/{slug}/assets/css/users.css
themes/{slug}/assets/css/theme-management.css
themes/{slug}/assets/css/directories.css
themes/{slug}/assets/css/operation-result-modal.css
```

Для `asu-evgeniya-rostova` дополнительно обязательны:

```text
themes/asu-evgeniya-rostova/assets/img/hearts-pattern.svg
themes/asu-evgeniya-rostova/assets/img/balloons.svg
themes/asu-evgeniya-rostova/assets/img/teddy-bear.svg
themes/asu-evgeniya-rostova/assets/img/plush-bunny.svg
```

Все четыре SVG перечислены в `required_assets`. Отсутствие любого из них делает тему недоступной для активации.

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
- загрузка ZIP, произвольного CSS/JS, удаление темы и browser-редактор отсутствуют;
- SVG новой темы локальны и не содержат script, event handlers, embedded HTML, внешние href или remote resources;
- отдельный JavaScript темы отсутствует.

## Проверка

`database/check-theme-management.php` проверяет реестр трёх тем, metadata, required assets, безопасные URL, SVG/CSS safety, persistence, audit и rollback.

`database/check-theme-asset-failure.php` в изолированном временном каталоге подтверждает, что отсутствие обязательного SVG делает `asu-evgeniya-rostova` недоступной и точно отражается в `missingAssets()`.

Справочные checker'ы через общий wrapper проверяют `css/directories.css` всех тем из текущего реестра, после чего выполняют прежнюю профильную DB/repository-регрессию.

Desktop-приёмка `asu-blue` и `asu-light-blue` завершена в стабильном baseline. Desktop/browser-приёмка `asu-evgeniya-rostova` ожидается. Mobile PASS для нового инкремента не заявляется.
