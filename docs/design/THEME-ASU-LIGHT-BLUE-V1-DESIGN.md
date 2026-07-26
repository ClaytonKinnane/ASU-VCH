# ASU Light Blue Theme v1 — Architecture / Specification

## 1. Статус документа

- Инкремент: `ASU Light Blue Theme v1`
- Проект: `АСУ-ВЧ`
- Ветка проектирования: `feature/theme-asu-light-blue`
- Базовая ветка: `main`
- Базовый commit: `4e1d692807fbac83d86ec1be431df4563bcfacd5`
- Статус: подготовлено к formal review и утверждению
- Реализация: не начата

## 2. Источник визуального направления

Заказчик предоставил самостоятельный HTML-файл `index0004.html` как визуальный референс.

Из источника принимаются следующие признаки:

- белый фон страницы;
- основной синий цвет `#086ad5`;
- более темный hover-цвет `#054f9e`;
- тонкие одноцветные рамки;
- белые карточки;
- компактные радиусы около `8px`;
- минимальные мягкие тени;
- легкий подъем карточек при hover;
- плоские синие кнопки;
- светлые поля ввода;
- спокойная минималистичная композиция;
- тонкие линии header/footer.

Не переносятся буквально:

- название `MySite`;
- год `2024`;
- одновременные вкладки входа и публичной регистрации;
- формы и поля, не соответствующие текущим PHP-маршрутам;
- inline CSS и inline JavaScript;
- обработчики `onclick`;
- исходные тексты placeholder;
- поведение форм без CSRF и серверной логики.

Причина: после установки АСУ-ВЧ публичная регистрация отключается, а первая регистрация владельца и обычный вход являются взаимоисключающими серверными состояниями.

## 3. Цель

Добавить вторую полноценную тему оформления, совместимую со всеми текущими страницами АСУ-ВЧ, без копирования PHP-шаблонов и без изменения бизнес-логики.

Тема должна:

1. сосуществовать с `asu-blue`;
2. использовать тот же HTML/class contract;
3. применяться ко всему интерфейсу, а не только к форме входа;
4. активироваться безопасно через конфигурацию;
5. не допускать path traversal через имя темы или asset path;
6. сохранять RBAC, CSRF, статусы, формы, таблицы и themed operation modal;
7. проходить desktop-приемку;
8. не требовать migration БД.

## 4. Наименование

- Отображаемое имя: `АСУ Светлая синяя`
- Slug: `asu-light-blue`
- Каталог: `themes/asu-light-blue`
- Текущая тема: `asu-blue`
- Тема по умолчанию после merge: `asu-blue`

Новая тема не должна автоматически менять внешний вид существующей установки после deploy.

## 5. Текущее состояние и проблема

`config/app.php` уже содержит параметр:

```php
'theme' => 'asu-blue',
```

Однако PHP-шаблоны подключают CSS через жесткие URL вида:

```text
/themes/asu-blue/assets/css/theme.css
```

Поэтому параметр конфигурации сейчас является только диагностическим и фактически не управляет assets.

Также themed operation modal подключает CSS/JS через путь конкретной темы.

Без общего безопасного asset resolver новая тема либо потребует дублирования PHP-страниц, либо не сможет быть активирована глобально.

## 6. Архитектура выбора темы

### 6.1 Реестр тем

Добавляется:

```text
config/themes.php
```

Он возвращает статический allow-list:

```php
return [
    'asu-blue' => [
        'name' => 'АСУ Синяя',
        'required_assets' => [
            'css/theme.css',
            'css/auth.css',
            'css/users.css',
            'css/operation-result-modal.css',
        ],
    ],
    'asu-light-blue' => [
        'name' => 'АСУ Светлая синяя',
        'required_assets' => [
            'css/theme.css',
            'css/auth.css',
            'css/users.css',
            'css/operation-result-modal.css',
        ],
    ],
];
```

Реестр не читается из GET/POST и не формируется из пользовательских данных.

### 6.2 Helper-функции

В bootstrap добавляются функции:

```php
theme_registry(): array
active_theme(): string
active_theme_name(): string
theme_asset(string $asset): string
```

#### `active_theme()`

- читает `app_config('theme', 'asu-blue')`;
- принимает только точный ключ из `config/themes.php`;
- при неизвестном значении:
  - пишет нейтральное сообщение в `error_log` без PII;
  - возвращает безопасный fallback `asu-blue`;
- не принимает имя темы из URL или формы.

#### `theme_asset()`

- принимает относительный путь внутри `assets`;
- запрещает пустую строку;
- запрещает `..`, обратный слеш, NUL и URL-схемы;
- допускает только сегменты `[a-zA-Z0-9._-]` и `/`;
- возвращает URL:

```text
/themes/{active_theme}/assets/{asset}
```

- выполняет URL-encoding по сегментам либо использует только заранее допустимые ASCII-пути;
- не проверяет путь, полученный от пользователя, потому что все вызовы задаются кодом.

### 6.3 Подключение assets

Все HTML-emitter'ы заменяют жесткие ссылки на:

```php
<link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
```

Для специализированных страниц:

```php
<?= e(theme_asset('css/auth.css')) ?>
<?= e(theme_asset('css/users.css')) ?>
```

403-страница в `require_permission()` также использует `theme_asset()`.

### 6.4 Общий JavaScript modal

Поведение operation modal не является визуальной темой. Поэтому JavaScript переносится в общий public asset:

```text
public/assets/js/operation-result-modal.js
```

Bootstrap подключает:

```text
/assets/js/operation-result-modal.js
```

Тема определяет только:

```text
theme_asset('css/operation-result-modal.css')
```

После проверки всех ссылок старый файл:

```text
themes/asu-blue/assets/js/operation-result-modal.js
```

может быть удален как дубликат. Удаление допустимо только если grep/checker подтверждает отсутствие ссылок.

## 7. Способ активации v1

В v1 тема выбирается через конфигурацию:

```php
'theme' => 'asu-light-blue',
```

Рекомендуемый локальный способ — переопределение ключа в `config/local.php`, чтобы не менять tracked `config/app.php`:

```php
return [
    // database и другие локальные параметры
    'theme' => 'asu-light-blue',
];
```

`array_replace_recursive` уже объединяет локальную конфигурацию с базовой.

### 7.1 Почему без UI-переключателя

Полноценный выбор темы в административной панели потребует:

- хранения системной настройки;
- POST-маршрута;
- CSRF;
- permission;
- валидации и audit;
- определения приоритета config/DB;
- отдельного жизненного цикла настройки.

Это отдельная функциональность и не входит в v1. Карточка `Темы оформления` в настройках остается `В разработке`.

## 8. Структура новой темы

```text
themes/asu-light-blue/
└── assets/
    └── css/
        ├── theme.css
        ├── auth.css
        ├── users.css
        └── operation-result-modal.css
```

PHP, SQL и JS внутри каталога темы не размещаются.

## 9. Class contract

Новая тема использует существующие классы, включая:

```text
body
.container
.glass-tile
.site-header
.header-content
.site-logo
.site-heading
.site-title
.site-description
.site-main
.site-footer
.footer-content
.auth-card
.auth-heading
.auth-description
.form-group
.form-label
.form-input
.primary-button
.secondary-button
.form-message
.admin-main
.admin-summary
.dashboard-grid
.dashboard-tile
.module-grid
.module-tile
.stats-grid
.stat-tile
.state-badge*
```

И классы users-модуля:

```text
.users-stats-grid
.security-section-grid
.security-section-card
.users-panel
.users-filters
.users-table
.user-detail-hero
.user-detail-grid
.user-detail-section
.role-badge
.archive-lifecycle-panel
```

Разметка PHP меняется только там, где требуется заменить asset URL. Структурные изменения страниц не планируются.

## 10. Design tokens новой темы

В `theme.css` задаются переменные:

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
    --input-border: #a9c8e8;
    --focus-color: #086ad5;
    --tile-radius: 8px;
    --control-radius: 6px;
    --soft-shadow: 0 0 12px rgba(8, 106, 213, 0.10);
    --soft-shadow-hover: 0 0 20px rgba(8, 106, 213, 0.15);
}
```

Допускается корректировка оттенков после desktop-приемки, но основной синий `#086ad5` и hover `#054f9e` сохраняются как источник идентичности темы.

## 11. Общая визуальная спецификация

### 11.1 Body

- фон `#ffffff`;
- основной текст темно-сине-серый, а не полностью синий, для читаемости длинных текстов;
- заголовки и action accents — `#086ad5`;
- без темных radial gradients.

### 11.2 Header/Footer

- белый фон;
- тонкая синяя нижняя/верхняя линия;
- без темной glass-заливки;
- логотип остается `АСУ`;
- заголовок продукта не заменяется на `MySite`;
- footer использует текущий динамический год.

Существующий `.glass-tile` в header/footer визуально становится светлой поверхностью.

### 11.3 Карточки

- белый фон;
- рамка `1px solid #086ad5`;
- радиус `8px`;
- мягкая синяя тень;
- hover: подъем на `2px` и усиление тени;
- не использовать стеклянную прозрачность как основной эффект.

Имя класса `.glass-tile` сохраняется для совместимости, хотя визуально это light outlined tile.

### 11.4 Кнопки

Primary:

- синий фон;
- белый текст;
- hover — `#054f9e`;
- небольшой transform без чрезмерного scale.

Secondary:

- белый фон;
- синяя рамка и текст;
- hover — очень светлый синий фон.

Danger:

- красный текст/рамка;
- светло-красный фон;
- высокий контраст.

### 11.5 Формы

- белый фон;
- рамка светло-синяя, но контрастнее исходного `rgba(..., .15)`;
- focus — `#086ad5` и мягкий halo;
- placeholder серо-синий;
- autofill адаптируется к белой теме;
- labels — темно-серые.

### 11.6 Таблицы

- белая поверхность;
- синие линии/акценты;
- header — очень светлый синий;
- row hover — `rgba(8, 106, 213, 0.04)`;
- ссылки пользователя — синие;
- состояния остаются семантически цветными.

### 11.7 Статусы

- success: зеленый текст/рамка на светло-зеленом фоне;
- error: красный на светло-красном;
- warning: янтарный на светло-желтом;
- muted: серо-синий;
- archived: muted;
- статус не кодируется только цветом: сохраняется текст и маркер.

### 11.8 Themed operation modal

Error modal:

- красная/бордовая поверхность;
- красная рамка;
- белый текст;
- затемненный backdrop;
- соответствует уже принятому UX.

Success modal:

- белая или очень светлая зелено-бирюзовая поверхность;
- зеленая рамка;
- темный текст;
- сохраняет классы и JS behavior.

### 11.9 Auth

Визуально следует предоставленному HTML:

- центрированная белая карточка;
- максимальная ширина около `400–460px`;
- синие контуры;
- плоская primary-кнопка;
- минимум декоративных эффектов.

Табы `Вход / Регистрация` не добавляются. Сервер продолжает показывать либо вход, либо первичную настройку.

## 12. Accessibility

Тема должна обеспечивать:

- читаемый контраст основного текста на белом фоне;
- видимый `:focus-visible`;
- отсутствие color-only состояния;
- сохранение aria-атрибутов существующего modal;
- отсутствие отключения outline без замены;
- поддержку `prefers-reduced-motion` для hover/modal анимаций;
- минимальную высоту интерактивных controls около `44px`.

Контраст проверяется визуально и, при возможности, автоматическим инструментом браузера.

## 13. Безопасность

- имя темы не принимается из HTTP-запроса;
- реестр — статический allow-list;
- `theme_asset()` запрещает traversal;
- assets не содержат PHP;
- внешний CDN не используется;
- внешние шрифты не используются;
- inline script из исходного HTML не переносится;
- текущие CSRF/RBAC/session guards не меняются;
- error text продолжает проходить через серверный белый список и `textContent` modal-компонента.

## 14. Изменяемые категории файлов

### Конфигурация/bootstrap

```text
config/themes.php
app/bootstrap.php
```

### Общий modal behavior

```text
public/assets/js/operation-result-modal.js
```

### Новая тема

```text
themes/asu-light-blue/assets/css/theme.css
themes/asu-light-blue/assets/css/auth.css
themes/asu-light-blue/assets/css/users.css
themes/asu-light-blue/assets/css/operation-result-modal.css
```

### Существующие HTML-emitter'ы

Все PHP-файлы, которые сейчас содержат `/themes/asu-blue/`, включая как минимум:

```text
public/index.php
public/account/change-password.php
public/admin/index.php
public/admin/content.php
public/admin/settings.php
public/admin/users.php
public/admin/users/create.php
public/admin/users/view.php
app/bootstrap.php (themed 403 и operation modal assets)
```

Точный список перед реализацией фиксируется grep-проверкой.

### Документация/checker

```text
docs/design/THEME-ASU-LIGHT-BLUE-V1-DESIGN.md
docs/design/THEME-ASU-LIGHT-BLUE-V1-REVIEW.md
docs/decisions/THEME-ASU-LIGHT-BLUE-V1-APPROVAL.md
tools/check-theme-assets.php
```

## 15. Checker

Добавляется CLI-checker без БД:

```text
php tools/check-theme-assets.php
```

Он проверяет:

1. оба slug присутствуют в registry;
2. default/fallback `asu-blue` существует;
3. каталоги тем существуют;
4. обязательные CSS-файлы существуют;
5. shared modal JS существует;
6. `theme_asset()` возвращает ожидаемый URL для разрешенных путей;
7. traversal `../` отклоняется;
8. неизвестная тема приводит к fallback;
9. в исполняемых PHP-файлах не остается жестких `/themes/asu-blue/`;
10. старый theme-specific modal JS не используется.

Checker не изменяет файлы и БД.

## 16. Автоматическое тестирование

Обязательные проверки:

```text
PHP syntax
Theme assets checker
Deploy
Local smoke с asu-blue
Local smoke с asu-light-blue
Archive/restore regression checker
```

### 16.1 Две темы

Для локального теста `config/local.php` временно переключается:

```php
'theme' => 'asu-blue'
```

затем:

```php
'theme' => 'asu-light-blue'
```

После тестирования локальная конфигурация остается на значении, выбранном заказчиком. Файл не коммитится.

## 17. Ручная desktop-приемка

Мобильная версия не входит в обязательный scope по ранее принятому решению заказчика.

Проверяются обе темы, с основным вниманием к новой:

1. страница входа;
2. первичная настройка логически не ломается;
3. панель управления;
4. настройки;
5. список пользователей;
6. карточка активного пользователя;
7. карточка архивированного пользователя;
8. формы create/update/roles/status/archive/restore;
9. таблица и фильтры;
10. status badges;
11. red error modal;
12. green success modal;
13. themed 403;
14. CSRF 419 сохраняет корректное поведение;
15. отсутствуют темные остатки `asu-blue`, делающие текст нечитаемым;
16. отсутствует горизонтальный overflow на desktop target;
17. после возврата на `asu-blue` текущий интерфейс не имеет регрессий.

## 18. Acceptance criteria

```text
Theme registry works: PASS
Invalid theme falls back safely: PASS
No hardcoded asu-blue asset links in executable PHP: PASS
ASU Blue regression: PASS
ASU Light Blue login: PASS
ASU Light Blue admin dashboard: PASS
ASU Light Blue users list: PASS
ASU Light Blue user detail: PASS
ASU Light Blue forms/tables/statuses: PASS
ASU Light Blue error modal: PASS
ASU Light Blue success modal: PASS
Themed 403: PASS
CSRF/RBAC behavior unchanged: PASS
PHP syntax: PASS
Theme checker: PASS
Smoke both themes: PASS
Desktop UI review: PASS
Mobile acceptance: OUT OF SCOPE
```

## 19. Вне объема v1

- выбор темы через административный UI;
- хранение темы в БД;
- тема на пользователя;
- автоматическое следование системному light/dark mode;
- theme preview через query string;
- загрузка пользовательских CSS;
- конструктор цветов;
- импорт произвольного HTML в runtime;
- отдельная мобильная приемка;
- изменение прикладной бизнес-логики;
- изменение RBAC;
- migration БД.

## 20. Последовательность работ

```text
Architecture
→ Specification
→ Review
→ Approval
→ Implementation
→ Automated testing
→ Desktop acceptance
→ Commit
→ Push
→ PR
→ Final review
→ отдельное разрешение Merge
```

До явного утверждения Architecture/Specification/Review реализация не начинается.
