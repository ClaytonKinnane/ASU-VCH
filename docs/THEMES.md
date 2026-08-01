# Темы оформления

## Назначение

АСУ-ВЧ поддерживает глобальные доверенные темы для публичной части и административной панели. Активная тема едина для всей установки.

## Реестр

Темы перечислены в статическом allowlist `config/themes.php`:

- **АСУ Синяя** — `asu-blue`, тёмная сине-бирюзовая тема и safe fallback;
- **АСУ Светлая синяя** — `asu-light-blue`, светлая минималистичная тема;
- **Евгения Ростова** — `asu-evgeniya-rostova`, светлая розово-лиловая тема.

Все три темы merged, доступны в registry и прошли desktop acceptance затронутых интерфейсов. Default/fallback — `asu-blue`.

## Управление

```text
/admin/settings/themes.php
```

Просмотр требует `system.settings.view`, активация — `system.settings.update`.

Активная тема хранится в `system_settings.ui.active_theme`; `config/app.php['theme']` используется как bootstrap/pre-install fallback.

## Обязательный CSS contract

Каждая тема содержит девять обязательных CSS-assets:

```text
themes/{slug}/assets/css/theme.css
themes/{slug}/assets/css/auth.css
themes/{slug}/assets/css/account.css
themes/{slug}/assets/css/users.css
themes/{slug}/assets/css/theme-management.css
themes/{slug}/assets/css/directories.css
themes/{slug}/assets/css/military-occupational-specialties.css
themes/{slug}/assets/css/organization.css
themes/{slug}/assets/css/operation-result-modal.css
```

Назначение профильных assets:

- `directories.css` — landing и общие directory components;
- `military-occupational-specialties.css` — VUS-specific filters, cards, table proportions и boundary note;
- `organization.css` — Organizational Structure v1;
- `operation-result-modal.css` — themed result modal.

Справочник типовых воинских должностей использует общий directory contract. VUS использует общий contract и отдельный обязательный stylesheet.

## Дополнительные assets темы «Евгения Ростова»

```text
themes/asu-evgeniya-rostova/assets/img/hearts-pattern.svg
themes/asu-evgeniya-rostova/assets/img/balloons.svg
themes/asu-evgeniya-rostova/assets/img/teddy-bear.svg
themes/asu-evgeniya-rostova/assets/img/plush-bunny.svg
```

Отсутствие любого required asset делает тему недоступной для активации.

## JavaScript

Общее поведение:

```text
public/assets/js/operation-result-modal.js
public/assets/js/organization-tree.js
public/assets/js/organization-ui-controls.js
```

Theme-specific JavaScript отсутствует.

## Публикация

Исходные темы находятся в `themes`. Deploy публикует их в:

```text
C:\OSPanel\home\asu-vch.local\public\themes\{slug}
```

`ThemeRegistry` проверяет slug, required assets и строит URL только для доступных файлов.

## Безопасность

- slug принимается только из static allowlist;
- required assets проверяются до activation;
- unknown/unavailable theme приводит к fallback `asu-blue`;
- traversal, absolute paths, URL schemes, NUL и backslash отклоняются;
- GET/cookie theme preview отсутствует;
- activation — POST-only, permission + CSRF + PRG;
- ZIP upload, arbitrary CSS/JS, theme deletion и browser editor отсутствуют;
- SVG локальны и не содержат executable/external content.

## Проверка

`database/check-theme-management.php` проверяет:

- registry трёх тем;
- metadata и required assets;
- safe URLs;
- CSS/SVG safety;
- persistence, audit и rollback;
- наличие `military-occupational-specialties.css` во всех темах.

`database/check-theme-asset-failure.php` подтверждает отказ activation при missing required asset.

Профильные directory/UI checker'ы проверяют publication и behavior CSS.

Последние результаты:

```text
asu-blue desktop acceptance: PASS
asu-light-blue desktop acceptance: PASS
asu-evgeniya-rostova desktop acceptance: PASS
VUS UI checker: PASS
console errors: 0
asset/HTTP 404: 0
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```
