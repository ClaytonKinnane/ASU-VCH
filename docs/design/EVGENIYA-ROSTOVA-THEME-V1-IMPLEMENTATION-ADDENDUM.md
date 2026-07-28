# Evgeniya Rostova Theme v1 — Implementation Addendum

## 1. Статус

- Проект: `АСУ-ВЧ`
- Инкремент: `Evgeniya Rostova Theme v1`
- Ветка: `feature/theme-evgeniya-rostova`
- База: `main @ 3a93ddf35c872d6710951c71a0044f81dbcacfd6`
- Approval commit: `62c6889c0cc0e40cb0045c9dae3562f8b56c5507`
- Стадия: Implementation
- Merge: запрещён до отдельного разрешения
- Удаление веток: запрещено до отдельного разрешения

## 2. Реализованное решение

Добавлена третья встроенная доверенная тема:

```text
slug: asu-evgeniya-rostova
name: Евгения Ростова
appearance: light
```

Тема использует существующий `ThemeRegistry`, глобальную настройку `system_settings.ui.active_theme`, существующий маршрут активации и текущий HTML/class contract.

Серверные маршруты, RBAC, бизнес-логика и схема БД не изменяются.

## 3. Состав темы

Добавлены семь CSS-файлов:

```text
theme.css
auth.css
account.css
users.css
theme-management.css
directories.css
operation-result-modal.css
```

Добавлены четыре локальных SVG-assets:

```text
hearts-pattern.svg
balloons.svg
teddy-bear.svg
plush-bunny.svg
```

Все одиннадцать файлов зарегистрированы в `config/themes.php` как `required_assets`. При отсутствии любого из них тема считается недоступной и не может быть активирована.

## 4. Визуальная реализация

Использована утверждённая розово-лиловая палитра:

```text
#fff7fb
#c12a70
#9a6bc4
```

Реализованы:

- светлый розоватый фон;
- белые карточки с розовыми контурами и мягкими тенями;
- насыщенные розовые primary controls;
- лиловые вторичные акценты;
- фоновые сердечки;
- композиция воздушных шариков в header;
- медвежонок на auth/theme-management surfaces;
- зайчик на footer, intro и directory surfaces;
- скрытие крупных декоративных элементов на узких экранах;
- сохранение `prefers-reduced-motion`.

Декоративные изображения подключаются только через CSS и не перекрывают controls.

## 5. SVG security

SVG созданы специально для проекта и не содержат:

- `<script>`;
- `foreignObject`;
- `<image>`;
- event-handler attributes;
- `javascript:`;
- `xlink:href`;
- внешних `href`;
- embedded HTML;
- пользовательских данных.

XML namespace `http://www.w3.org/2000/svg` является стандартным идентификатором формата SVG и не является внешней загрузкой ресурса.

## 6. Checker integration

### 6.1 Theme management checker

`database/check-theme-management.php` расширен для:

- точного списка трёх тем;
- проверки metadata и palette новой темы;
- проверки всех одиннадцати required assets;
- безопасных URL CSS/SVG;
- статической проверки SVG;
- запрета внешних CSS URL, data URI, `@import` и зависимостей от каталогов других тем;
- подтверждения отсутствия JavaScript в новой теме;
- транзакционного write/read с использованием новой темы.

### 6.2 Directory checker compatibility

Чтобы не переписывать крупные проверенные каталоговые checker'ы и не повышать риск регрессии их нормативной логики, применён совместимый wrapper-подход:

```text
check-all-theme-directory-assets.php
check-military-ranks-directory.php                — wrapper
check-military-ranks-directory-core.php           — неизменённый прежний checker
check-organizational-elements-directory.php       — wrapper
check-organizational-elements-directory-core.php  — неизменённый прежний checker
```

Wrapper сначала проверяет `css/directories.css` для всех тем, полученных динамически из `config/themes.php`, затем запускает прежний профильный checker без изменения его DB и repository assertions.

Это устраняет hardcoded coverage нового списка тем и сохраняет проверенную нормативную регрессию побайтно неизменной в core-файлах.

## 7. Статические проверки Implementation

В изолированной среде выполнены:

- PHP lint изменяемых PHP-файлов;
- XML parse четырёх SVG;
- проверка запрещённых SVG constructs;
- проверка баланса CSS braces;
- поиск внешних URL, data URI, `@import` и cross-theme references;
- фиксация размеров и SHA-256 подготовленных файлов.

Эти проверки не заменяют обязательные Open Server / MySQL / browser gates.

## 8. Оставшиеся gates

До открытия Pull Request обязательны:

1. синхронизация точного HEAD feature-ветки в локальный репозиторий;
2. проверка чистого рабочего дерева;
3. PHP lint в PHP 8.5.4;
4. controlled deploy с сохранением `config/local.php`;
5. installer и повторный installer с результатом «Новых миграций нет»;
6. theme management checker;
7. оба directory checker'а;
8. системные security regression checker'ы;
9. HTTP 200 для CSS и SVG новой темы;
10. desktop/browser-приёмка всех ключевых страниц новой темы;
11. проверка переключения между тремя темами;
12. возврат к согласованной активной теме после тестирования;
13. Final Test Report и Final Review.

Mobile acceptance остаётся вне scope и не заявляется как выполненная.
