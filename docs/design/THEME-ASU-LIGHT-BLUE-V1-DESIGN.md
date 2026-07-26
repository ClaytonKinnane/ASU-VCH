# Theme Management System & ASU Light Blue Theme v1 — Architecture / Specification

## 1. Статус документа

- Инкремент: `Theme Management System & ASU Light Blue Theme v1`
- Проект: `АСУ-ВЧ`
- Ветка проектирования: `feature/theme-asu-light-blue`
- Базовая ветка: `main`
- Базовый commit: `4e1d692807fbac83d86ec1be431df4563bcfacd5`
- Статус: переработано после разрешения заказчика на расширение scope
- Реализация: не начата
- Предыдущий scope config-only activation: заменён настоящей системой управления темами

## 2. Источник визуального направления

Заказчик предоставил HTML-файл `index0004.html` как визуальный референс для второй темы.

Из источника принимаются:

- белый фон;
- основной синий `#086ad5`;
- hover-оттенок `#054f9e`;
- тонкие синие границы;
- белые карточки;
- радиусы около `8px`;
- минимальные мягкие тени;
- лёгкий подъём карточек при hover;
- плоские синие кнопки;
- светлые поля ввода;
- лаконичные header/footer;
- спокойная минималистичная композиция.

Не переносятся буквально:

- `MySite`;
- статический год `2024`;
- одновременные вкладки входа и публичной регистрации;
- inline CSS и inline JavaScript;
- обработчики `onclick`;
- формы без CSRF;
- поля и маршруты, отсутствующие в АСУ-ВЧ;
- исходная логика, противоречащая server-side install/login flow.

После установки АСУ-ВЧ публичная регистрация отключается. До установки показывается создание первого владельца, после установки — вход. Эти состояния взаимоисключающие.

## 3. Цель

Реализовать единый инкремент, который:

1. добавляет вторую полноценную тему `asu-light-blue`;
2. превращает существующий конфигурационный параметр темы в рабочий runtime-механизм;
3. добавляет безопасный реестр установленных тем;
4. хранит глобальную активную тему в `system_settings`;
5. предоставляет административный интерфейс просмотра и активации тем;
6. сохраняет `asu-blue` безопасной темой по умолчанию;
7. не копирует PHP-шаблоны между темами;
8. сохраняет существующие RBAC, CSRF, session guards и бизнес-логику;
9. стилизует весь текущий desktop-интерфейс, а не только страницу входа;
10. проходит автоматическую регрессию и ручную desktop-приёмку обеих тем.

## 4. Наименование и каталог

### 4.1 Текущая тема

- Slug: `asu-blue`
- Отображаемое имя: `АСУ Синяя`
- Тип: тёмная
- Статус: встроенная, fallback, активна до явного переключения

### 4.2 Новая тема

- Slug: `asu-light-blue`
- Отображаемое имя: `АСУ Светлая синяя`
- Тип: светлая
- Каталог: `themes/asu-light-blue`
- Визуальный источник: предоставленный HTML-файл

### 4.3 Имя инкремента

```text
Theme Management System & ASU Light Blue Theme v1
```

## 5. Текущее состояние

`config/app.php` содержит:

```php
'theme' => 'asu-blue',
```

Но исполняемые PHP-страницы подключают URL вида:

```text
/themes/asu-blue/assets/css/theme.css
```

Следовательно:

- параметр `theme` сейчас диагностический;
- переключение темы не работает;
- operation-result modal также привязан к `asu-blue`;
- модуль «Темы оформления» на странице настроек отмечен `В разработке`;
- `system_settings` уже существует и подходит для хранения значения;
- `system_settings` пока не хранит субъекта изменения.

## 6. Архитектурные принципы

### 6.1 Тема — только представление

Тема может содержать:

- CSS;
- статические изображения и иконки, если они добавлены разработчиком;
- metadata в доверенном PHP-реестре.

Тема не содержит:

- PHP;
- SQL;
- исполняемый пользовательский JavaScript;
- серверные маршруты;
- собственные копии бизнес-логики;
- произвольные внешние URL.

### 6.2 Один HTML/class contract

Обе темы используют одинаковую PHP-разметку и существующие class names. Тема меняет визуальное представление, но не DOM-семантику операций.

### 6.3 Глобальная настройка

Активная тема едина для всей установки. Per-user theme в v1 отсутствует.

### 6.4 Безопасный fallback

При любой ошибке чтения БД, неизвестном slug или недоступности обязательных assets применяется `asu-blue`.

### 6.5 Запрет HTTP override

Нельзя переключить или предварительно просмотреть тему через `GET`, cookie или произвольный query-параметр.

## 7. Реестр доверенных тем

Добавляется:

```text
config/themes.php
```

Пример контракта:

```php
<?php

declare(strict_types=1);

return [
    'default' => 'asu-blue',
    'themes' => [
        'asu-blue' => [
            'name' => 'АСУ Синяя',
            'description' => 'Тёмная сине-бирюзовая тема АСУ-ВЧ.',
            'appearance' => 'dark',
            'preview_colors' => ['#131e30', '#18acea', '#17a58b'],
            'required_assets' => [
                'css/theme.css',
                'css/auth.css',
                'css/users.css',
                'css/operation-result-modal.css',
            ],
        ],
        'asu-light-blue' => [
            'name' => 'АСУ Светлая синяя',
            'description' => 'Светлая минималистичная тема с синими контурами.',
            'appearance' => 'light',
            'preview_colors' => ['#ffffff', '#086ad5', '#054f9e'],
            'required_assets' => [
                'css/theme.css',
                'css/auth.css',
                'css/users.css',
                'css/operation-result-modal.css',
            ],
        ],
    ],
];
```

Требования:

- slug соответствует `\A[a-z0-9][a-z0-9-]{1,63}\z`;
- `default` обязательно присутствует в `themes`;
- имя и описание непустые;
- `appearance` только `dark` или `light`;
- `preview_colors` содержит три валидных hex-цвета;
- required assets — статические относительные пути;
- реестр не формируется из БД, directory scan или HTTP input;
- изменение списка тем выполняется разработчиком через репозиторий.

## 8. Серверные компоненты

Добавляются:

```text
app/Theme/ThemeRegistry.php
app/Theme/ThemeSettingsRepository.php
app/Theme/ThemeActivationService.php
```

### 8.1 `ThemeRegistry`

Ответственность:

- загрузить и валидировать `config/themes.php`;
- вернуть default slug;
- вернуть метаданные зарегистрированной темы;
- определить, зарегистрирован ли slug;
- проверить наличие обязательных assets;
- вернуть список тем с runtime availability;
- сформировать безопасный asset URL.

Не выполняет:

- запросы к БД;
- HTTP redirects;
- mutation;
- чтение GET/POST.

### 8.2 `ThemeSettingsRepository`

Ответственность:

- читать `ui.active_theme` из `system_settings`;
- записывать значение, `updated_at`, `updated_by`;
- работать через prepared statements;
- не принимать решение о допустимости slug.

### 8.3 `ThemeActivationService`

Ответственность:

1. проверить actor id;
2. проверить slug через `ThemeRegistry`;
3. проверить доступность обязательных assets;
4. начать транзакцию;
5. заблокировать строку setting через `SELECT ... FOR UPDATE` при её наличии;
6. выполнить `INSERT ... ON DUPLICATE KEY UPDATE`;
7. записать `updated_by` и `updated_at`;
8. commit;
9. rollback при исключении.

Сервис не проверяет permission: permission проверяется HTTP boundary до вызова сервиса. Сервис всё равно не доверяет slug.

## 9. Migration 006

Добавляется:

```text
database/migrations/006_theme_management.sql
```

### 9.1 Изменение `system_settings`

Добавляется:

```sql
updated_by BIGINT UNSIGNED NULL
```

с FK:

```text
updated_by → users.id
ON UPDATE RESTRICT
ON DELETE SET NULL
```

Существующие строки получают `NULL`.

### 9.2 Начальная настройка

Migration вставляет:

```text
setting_key = ui.active_theme
setting_value = asu-blue
updated_by = NULL
```

через idempotent `INSERT ... ON DUPLICATE KEY UPDATE`, который не должен перезаписать уже существующее допустимое пользовательское значение при повторном install.

### 9.3 Почему используется существующая таблица

`system_settings` уже предназначена для глобальных настроек. Отдельная таблица только для одной темы создала бы лишнюю сущность.

### 9.4 Аудит v1

Хранится последний субъект и время изменения глобальной настройки. Полная история всех переключений — отдельный будущий инкремент.

## 10. Приоритет источников активной темы

Функция runtime resolution использует следующий порядок:

1. default из `config/themes.php`;
2. bootstrap fallback из `app_config('theme', default)` только до доступности БД или при аварии БД;
3. значение `system_settings.ui.active_theme`, когда БД доступна;
4. проверка регистрации slug;
5. проверка обязательных assets;
6. fallback на registry default при любой невалидности.

После migration 006 нормальный runtime source-of-truth:

```text
system_settings.ui.active_theme
```

`config/app.php['theme']` сохраняется для pre-install/bootstrap fallback и обратной совместимости.

Ни POST, ни GET не имеют приоритета над БД.

## 11. Runtime helpers

В `app/bootstrap.php` регистрируются фабрики и helpers:

```php
theme_registry_service(): ThemeRegistry
theme_settings_repository(): ThemeSettingsRepository
theme_activation_service(): ThemeActivationService
active_theme(): string
active_theme_name(): string
theme_asset(string $asset): string
installed_themes(): array
```

### 11.1 `active_theme()`

- кэширует результат в рамках запроса;
- безопасно работает до установки БД;
- не вызывает recursive installation checks;
- ловит DB/runtime exceptions и использует fallback;
- не выводит exception пользователю;
- пишет нейтральный diagnostic log без паролей, DSN и PII;
- возвращает только зарегистрированный slug.

### 11.2 `theme_asset()`

- принимает только относительный path внутри `assets`;
- запрещает пустую строку;
- запрещает `..`, `\`, NUL, `://`, leading `/` и `//`;
- допускает ASCII segments `[A-Za-z0-9._-]+`;
- проверяет наличие выбранного asset в active theme;
- если optional asset отсутствует в active theme, может использовать такой же asset default theme только при явном разрешённом contract;
- обязательные assets проверяются до признания темы доступной;
- возвращает `/themes/{slug}/assets/{path}`.

В v1 все вызовы `theme_asset()` задаются статическими литералами из кода.

## 12. Shared operation modal JavaScript

Поведение modal не является частью темы.

Файл переносится в:

```text
public/assets/js/operation-result-modal.js
```

Каждая тема предоставляет только:

```text
css/operation-result-modal.css
```

Bootstrap operation result emitter подключает:

```php
theme_asset('css/operation-result-modal.css')
```

и общий:

```text
/assets/js/operation-result-modal.js
```

Удаление старого:

```text
themes/asu-blue/assets/js/operation-result-modal.js
```

допускается только после repo-wide проверки отсутствия ссылок.

## 13. RBAC

Новые permission codes не добавляются.

Используются существующие:

```text
system.settings.view
system.settings.update
```

### 13.1 Просмотр

Страницы:

```text
/admin/settings.php
/admin/settings/themes.php
```

требуют:

```text
system.settings.view
```

### 13.2 Изменение

Маршрут:

```text
POST /admin/settings/themes/activate.php
```

требует:

```text
system.settings.update
```

### 13.3 Роли

- `system_owner` получает доступ через `system.*.*`;
- `administrator` уже имеет `system.settings.view` и `system.settings.update`;
- `operator` и `viewer` не имеют доступа;
- системное количество permissions не изменяется.

### 13.4 Изменение текущей settings boundary

Текущий `public/admin/settings.php` использует owner-only guard. Он будет заменён на `require_permission('system.settings.view')`, чтобы фактический HTTP boundary соответствовал существующей RBAC-матрице.

Это не расширяет права ролей: administrator уже получил permission в migration 002.

## 14. HTTP/UI

### 14.1 Страница списка тем

Добавляется:

```text
GET /admin/settings/themes.php
```

Показывает:

- заголовок «Темы оформления»;
- текущую активную тему;
- карточки зарегистрированных тем;
- отображаемое имя;
- описание;
- признак `Светлая` / `Тёмная`;
- три palette swatch;
- статус `Активна`;
- статус `Недоступна`, если отсутствуют обязательные assets;
- кнопку `Активировать`, если пользователь имеет update permission;
- отсутствие формы изменения для view-only пользователя.

### 14.2 Страница настроек

Карточка «Темы оформления» перестаёт быть `В разработке` и становится ссылкой:

```text
/admin/settings/themes.php
```

Остальные карточки остаются disabled.

Техническая информация показывает runtime active theme, а не только `app_config('theme')`.

### 14.3 Маршрут активации

```text
POST /admin/settings/themes/activate.php
```

Порядок:

1. `require_permission('system.settings.update')`;
2. GET и другие методы не выполняют mutation и перенаправляют к списку тем либо возвращают безопасный response;
3. `require_csrf()`;
4. получить scalar `theme`;
5. передать в `ThemeActivationService`;
6. PRG redirect;
7. показать themed operation result.

### 14.4 Результаты операций

В безопасный operation result catalog добавляются фиксированные состояния:

```text
theme_activation_success → Тема оформления активирована.
theme_activation_unavailable → Выбранная тема недоступна.
theme_activation_error → Не удалось активировать тему оформления.
```

Произвольное название темы из POST не вставляется в JavaScript или result message.

После успешного POST redirect-страница уже загружается с новой темой.

## 15. Замена hardcoded asset URLs

Все исполняемые PHP emitter'ы заменяют:

```text
/themes/asu-blue/assets/...
```

на:

```php
<?= e(theme_asset('css/theme.css')) ?>
<?= e(theme_asset('css/auth.css')) ?>
<?= e(theme_asset('css/users.css')) ?>
```

Включая:

- публичный вход/первичную настройку;
- admin dashboard;
- settings;
- users list/create/view;
- account password change;
- тематические 403 страницы;
- operation result modal.

Checker падает, если hardcoded `/themes/asu-blue/` остаётся в executable PHP.

Документация и сами CSS-файлы могут содержать slug как текст.

## 16. Структура новой темы

```text
themes/asu-light-blue/
└── assets/
    └── css/
        ├── theme.css
        ├── auth.css
        ├── users.css
        └── operation-result-modal.css
```

Новая тема использует текущий class contract и не требует копирования PHP.

## 17. Design tokens ASU Light Blue

```css
:root {
    --page-background: #ffffff;
    --surface-background: #ffffff;
    --tile-background: #ffffff;
    --tile-border: #086ad5;
    --tile-border-hover: #054f9e;
    --button-background: #086ad5;
    --button-background-hover: #054f9e;
    --button-border: #086ad5;
    --text-primary: #18324d;
    --text-heading: #086ad5;
    --text-secondary: #5f6f7f;
    --input-background: #ffffff;
    --input-border: #9dbfe3;
    --focus-color: #086ad5;
    --tile-radius: 8px;
    --control-radius: 6px;
    --soft-shadow: 0 0 12px rgba(8, 106, 213, 0.10);
    --soft-shadow-hover: 0 0 20px rgba(8, 106, 213, 0.15);
}
```

Основной синий `#086ad5` и hover `#054f9e` сохраняются как идентичность референса. Для длинного текста используется тёмный `#18324d`, чтобы не снижать читаемость.

## 18. Визуальная спецификация

### 18.1 Общие поверхности

- body белый;
- карточки белые;
- рамки 1px;
- тени синие, мягкие и малой интенсивности;
- `glass-tile` сохраняется как class contract, но визуально становится светлой bordered card;
- backdrop-filter не обязателен.

### 18.2 Header/footer

- header и footer сохраняют текущую структуру АСУ-ВЧ;
- белые поверхности;
- синие тонкие контуры;
- логотип остаётся `АСУ`;
- footer использует динамический текущий год.

### 18.3 Кнопки

- primary: синий фон, белый текст;
- hover: `#054f9e`;
- secondary: белый фон, синяя рамка и текст;
- danger: красный фон/рамка;
- disabled визуально и семантически отличается;
- focus-visible заметный.

### 18.4 Формы

- белый background;
- усиленная синяя граница по сравнению с исходным HTML;
- label тёмно-серый;
- focus border `#086ad5`;
- autofill остаётся читаемым на светлом фоне;
- validation state не зависит только от цвета.

### 18.5 Таблицы и статистика

- белый table surface;
- синяя рамка/разделители;
- header row с очень светлой синей заливкой;
- hover row умеренный;
- ссылки заметны;
- статусы success/warning/error/muted различимы.

### 18.6 Operation modal

- error: светлая/бордовая поверхность, красная рамка, затемнённый backdrop;
- success: светлая/зелёно-бирюзовая поверхность;
- сохраняются `<dialog>`, focus, Escape, close button и fixed safe message;
- native alert не возвращается.

### 18.7 Theme management cards

- карточки используют palette swatches;
- active theme получает явный badge и `aria-current`-подобную семантику;
- unavailable theme не имеет активной submit-кнопки;
- цветовые swatches дополняются текстовым именем и не являются единственным индикатором.

## 19. Security requirements

### 19.1 CSRF

Mutation только POST и только после `require_csrf()`.

### 19.2 Authorization

Просмотр и update проверяются на сервере. Скрытие кнопки не заменяет permission check.

### 19.3 Allow-list

Slug принимается только при точном совпадении с registry key.

### 19.4 Path traversal

Никакой HTTP input не конкатенируется в filesystem path или asset URL без registry validation.

### 19.5 SQL

Только prepared statements. Setting key фиксированный сервером:

```text
ui.active_theme
```

### 19.6 Error handling

Пользователь получает нейтральную themed-ошибку. Exception details не выводятся.

### 19.7 External resources

Новая тема не требует CDN, внешних шрифтов, remote CSS или remote JavaScript.

### 19.8 Stored value compromise

Даже если `setting_value` изменено напрямую на вредоносную строку, runtime не использует её как path, пока значение не совпало с registry slug.

## 20. Failure modes

### 20.1 БД недоступна

Используется config/default fallback. Страница не должна падать только из-за чтения темы, если остальная страница способна отобразиться.

### 20.2 Setting отсутствует

Используется fallback; service при первой активации создаёт строку.

### 20.3 Setting содержит неизвестный slug

Используется `asu-blue`, пишется нейтральный diagnostic log.

### 20.4 Registered theme не имеет asset

Тема отмечается unavailable; активировать её нельзя. Если она уже записана активной, runtime переключается на fallback без автоматической скрытой записи в БД.

### 20.5 Default theme повреждена

Checker должен блокировать deploy/acceptance. Runtime всё равно возвращает default slug, но не пытается использовать произвольную тему.

### 20.6 Одновременная активация

Транзакция и row lock обеспечивают last committed write. В интерфейсе показывается фактическое значение после redirect.

## 21. Scope v1

### 21.1 В scope

- registry двух доверенных тем;
- migration 006;
- `updated_by` для `system_settings`;
- DB-backed active theme;
- runtime resolver и asset helper;
- settings permissions вместо owner-only boundary;
- UI списка тем;
- POST activation route;
- themed operation results;
- shared modal JS;
- светлая тема;
- checker;
- regression обеих тем;
- desktop acceptance.

### 21.2 Вне scope

- browser ZIP upload;
- установка произвольной темы;
- удаление тем;
- CSS editor;
- custom CSS;
- внешние URL;
- marketplace;
- per-user theme;
- preview через query-параметр;
- automatic OS dark/light;
- scheduled switching;
- полная история переключений;
- mobile acceptance.

## 22. План файлов реализации

### 22.1 Новые

```text
config/themes.php
app/Theme/ThemeRegistry.php
app/Theme/ThemeSettingsRepository.php
app/Theme/ThemeActivationService.php
database/migrations/006_theme_management.sql
database/check-theme-management.php
public/admin/settings/themes.php
public/admin/settings/themes/activate.php
public/assets/js/operation-result-modal.js
themes/asu-light-blue/assets/css/theme.css
themes/asu-light-blue/assets/css/auth.css
themes/asu-light-blue/assets/css/users.css
themes/asu-light-blue/assets/css/operation-result-modal.css
```

### 22.2 Изменяемые

```text
app/bootstrap.php
public/index.php
public/admin/index.php
public/admin/settings.php
public/admin/users.php
public/admin/users/create.php
public/admin/users/view.php
public/account/change-password.php
и другие PHP-emitter'ы с hardcoded theme path
themes/asu-blue/assets/css/theme.css — только при необходимости class contract
themes/asu-blue/assets/css/users.css — только при необходимости management UI
deploy/Deploy-Local.ps1 — только если текущий deploy не копирует новые пути
```

### 22.3 Возможное удаление

```text
themes/asu-blue/assets/js/operation-result-modal.js
```

только после подтверждённого переноса.

## 23. Автоматические проверки

Обязательны:

1. PHP syntax всех PHP-файлов;
2. migration 006 применена;
3. повторный install не создаёт новых миграций;
4. `system_settings.updated_by` и FK существуют;
5. `ui.active_theme` существует;
6. default theme `asu-blue` доступна;
7. `asu-light-blue` доступна;
8. unknown slug отклоняется;
9. missing asset theme отклоняется;
10. traversal paths отклоняются;
11. пустой asset path отклоняется;
12. DB setting меняется при успешной активации;
13. `updated_by` записан;
14. повторная активация безопасна;
15. system permissions count не меняется;
16. administrator settings permissions сохраняются;
17. viewer/operator denied;
18. CSRF 419 без mutation;
19. GET без mutation;
20. hardcoded `/themes/asu-blue/` отсутствует в executable PHP;
21. shared modal JS существует и используется;
22. old modal JS не используется;
23. deploy сохраняет `config/local.php`;
24. smoke с `asu-blue`;
25. smoke с `asu-light-blue`;
26. archive/restore checker;
27. чистый Git status.

## 24. Ручная desktop-приёмка

### 24.1 Управление темами

Проверяются:

- settings доступна владельцу и administrator;
- viewer/operator получают themed 403;
- обе темы показаны;
- active badge верен;
- palette swatches видны;
- activate button отсутствует у view-only роли;
- переключение применяется после redirect;
- возврат на `asu-blue` работает;
- unknown/modified request не меняет setting;
- modal success/error стилизован текущей темой.

### 24.2 Светлая тема

Контрольные страницы:

1. login;
2. initial setup — при наличии отдельной тестовой БД либо статической проверки;
3. dashboard;
4. settings;
5. themes management;
6. users list;
7. create user;
8. active user detail;
9. pending/rejected/archived states;
10. red modal;
11. green modal;
12. themed 403;
13. required password change.

### 24.3 Тёмная регрессия

После возврата на `asu-blue` проверяются:

- login;
- dashboard;
- settings/themes;
- users list/detail;
- operation modal;
- smoke.

Мобильные screenshots не требуются.

## 25. Критерии завершения

```text
Architecture/Specification approved: PASS
Formal Review approved: PASS
Migration 006: PASS
Theme registry: PASS
DB-backed activation: PASS
RBAC: PASS
CSRF: PASS
Safe fallback: PASS
No hardcoded theme URLs: PASS
ASU Light Blue desktop UI: PASS
ASU Blue regression: PASS
Operation modal both themes: PASS
Automated regression: PASS
Blocking findings: 0
Mobile acceptance: OUT OF SCOPE
```

## 26. Порядок работ

```text
Architecture
→ Specification
→ Formal Review
→ Approval
→ Implementation
→ Automated Testing
→ Desktop Acceptance
→ Test Report
→ Commit/Push
→ PR Final Review
→ Pull Request
→ Merge authorization
→ Merge
```

Реализация не начинается без отдельного явного утверждения этого переработанного документа и Formal Review.