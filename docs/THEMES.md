# Темы оформления

## Назначение

АСУ-ВЧ поддерживает глобальные доверенные темы для публичной части и административной панели. Активная тема едина для всей установки.

## Реестр

Установленные темы перечислены в `config/themes.php`. Реестр является статическим allow-list и не формируется из HTTP-параметров или сканирования каталогов.

В стабильном `main` зарегистрированы три встроенные темы:

- **АСУ Синяя** — `asu-blue`, тёмная сине-бирюзовая тема и безопасный fallback;
- **АСУ Светлая синяя** — `asu-light-blue`, светлая минималистичная тема с синими контурами;
- **Евгения Ростова** — `asu-evgeniya-rostova`, светлая розово-лиловая тема с сердечками, воздушными шариками и мягкими игрушками.

Все три темы объединены, доступны в реестре и прошли desktop-приёмку затронутых интерфейсов. Default/fallback остаётся `asu-blue`.

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

## Обязательный CSS contract

Все темы реализуют единый class contract и содержат восемь обязательных CSS-assets:

```text
themes/{slug}/assets/css/theme.css
themes/{slug}/assets/css/auth.css
themes/{slug}/assets/css/account.css
themes/{slug}/assets/css/users.css
themes/{slug}/assets/css/theme-management.css
themes/{slug}/assets/css/directories.css
themes/{slug}/assets/css/organization.css
themes/{slug}/assets/css/operation-result-modal.css
```

`organization.css` оформляет страницы Organizational Structure v1: список, карточку, версии, дерево, документы, историю и сравнение.

## Дополнительные assets темы «Евгения Ростова»

Для `asu-evgeniya-rostova` обязательны:

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

Общее поведение дерева Organizational Structure находится в:

```text
public/assets/js/organization-tree.js
public/assets/js/organization-ui-controls.js
```

Отдельный theme-specific JavaScript отсутствует.

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
- SVG новой темы локальны и не содержат script, event handlers, embedded HTML или external resources;
- theme-specific JavaScript отсутствует.

## Проверка

`database/check-theme-management.php` проверяет реестр трёх тем, metadata, required assets, безопасные URL, SVG/CSS safety, persistence, audit и rollback.

`database/check-theme-asset-failure.php` подтверждает в sandbox, что отсутствие обязательного SVG делает `asu-evgeniya-rostova` недоступной.

Directory checker'ы проверяют `directories.css` всех тем. Organizational Structure checker проверяет публикацию `organization.css` всех трёх тем.

Для Organizational Structure v1 выполнены:

```text
asu-blue desktop acceptance: PASS
asu-light-blue desktop acceptance: PASS
asu-evgeniya-rostova desktop acceptance: PASS
UI contract checks: 64 PASS / 0 FAIL
```

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```
