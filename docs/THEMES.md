# Темы оформления

## Назначение

АСУ-ВЧ поддерживает global trusted themes для public/admin interfaces. Active theme едина для установки.

## Registry

Source of truth: `config/themes.php`.

- **АСУ Синяя** — `asu-blue`, dark blue/turquoise default and fallback;
- **АСУ Светлая синяя** — `asu-light-blue`;
- **Евгения Ростова** — `asu-evgeniya-rostova`.

Все 3 themes merged и доступны в registry.

## Management

```text
/admin/settings/themes.php
```

View требует `system.settings.view`, activation — `system.settings.update`. Active slug хранится в `system_settings.ui.active_theme`; app config используется как bootstrap fallback.

## Required CSS contract

Каждая тема содержит **10 required CSS-assets**:

```text
themes/{slug}/assets/css/theme.css
themes/{slug}/assets/css/auth.css
themes/{slug}/assets/css/account.css
themes/{slug}/assets/css/users.css
themes/{slug}/assets/css/theme-management.css
themes/{slug}/assets/css/directories.css
themes/{slug}/assets/css/military-ranks-v2.css
themes/{slug}/assets/css/military-occupational-specialties.css
themes/{slug}/assets/css/organization.css
themes/{slug}/assets/css/operation-result-modal.css
```

Назначение profile assets:

- `directories.css` — landing и shared directory components;
- `military-ranks-v2.css` — current/historical version switch, lifecycle metadata, composition hierarchy, derived/staffing badges and layout;
- `military-occupational-specialties.css` — VUS filters/cards/table/boundary note;
- `organization.css` — Organizational Structure v1;
- `operation-result-modal.css` — themed operation result modal.

Отсутствие required asset делает theme unavailable for activation.

## Additional assets — Евгения Ростова

```text
themes/asu-evgeniya-rostova/assets/img/hearts-pattern.svg
themes/asu-evgeniya-rostova/assets/img/balloons.svg
themes/asu-evgeniya-rostova/assets/img/teddy-bear.svg
themes/asu-evgeniya-rostova/assets/img/plush-bunny.svg
```

## JavaScript

Theme-specific JavaScript отсутствует. Shared scripts включают operation result modal и Organization UI controls.

## Publication

Source themes находятся в `themes`; deploy публикует их в:

```text
C:\OSPanel\home\asu-vch.local\public\themes\{slug}
```

`ThemeRegistry` validates slug, required assets and safe URLs.

## Security

- static slug allowlist;
- asset existence check before activation;
- unknown/unavailable theme → fallback `asu-blue`;
- traversal, absolute paths, URL schemes, NUL/backslash rejected;
- activation POST-only + permission + CSRF + PRG;
- no ZIP upload, arbitrary CSS/JS, deletion or browser editor;
- local SVG without executable/external content.

## Verification

`database/check-theme-management.php` проверяет registry, metadata, required assets, safe URLs, persistence/audit and rollback.

`database/check-theme-asset-failure.php` проверяет fail-closed activation при missing asset.

Military Ranks v2 source/UI-layout and all-theme asset checker'ы подтверждают `military-ranks-v2.css` во всех themes.

Последние результаты PR #24:

```text
asu-blue desktop acceptance: PASS
asu-light-blue desktop acceptance: PASS
asu-evgeniya-rostova desktop acceptance: PASS
Military Ranks v2 layout: PASS
required theme assets: PASS
console errors: 0
asset/HTTP 404: 0
mobile: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```