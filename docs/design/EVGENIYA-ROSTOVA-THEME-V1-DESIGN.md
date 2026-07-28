# Evgeniya Rostova Theme v1 — Architecture / Specification

## 1. Статус документа

- Проект: `АСУ-ВЧ`
- Инкремент: `Evgeniya Rostova Theme v1`
- Документационная ветка: `docs/evgeniya-rostova-theme-v1-design`
- Базовая ветка: `main`
- Базовый commit: `3a93ddf35c872d6710951c71a0044f81dbcacfd6`
- Стадия: Architecture / Specification
- Реализация: не начата
- Runtime-файлы: не создавались
- Миграции: не создавались
- Статус: готово к Formal Review

Документ подготовлен после явного утверждения заказчиком границ инкремента и архитектурного варианта C: отдельная встроенная тема с локальными SVG-assets.

Реализация запрещена до завершения Formal Review и отдельного явного Approval заказчика.

## 2. Исходная задача

Добавить третью встроенную тему оформления, визуально основанную на теме `АСУ Светлая синяя`, но выполненную в розово-лиловой гамме и содержащую декоративные элементы:

- сердечки;
- воздушные шарики;
- мягкие игрушки.

Целевая визуальная аудитория темы — девочка 7–12 лет.

Обязательное отображаемое название темы:

```text
Евгения Ростова
```

## 3. Цель инкремента

Реализовать третью полноценную встроенную тему, которая:

1. использует существующий безопасный механизм управления темами;
2. сохраняет единый HTML/class contract приложения;
3. не меняет бизнес-логику, маршруты, RBAC или данные;
4. оформляет весь текущий интерфейс АСУ-ВЧ, а не только страницу входа;
5. использует локальные доверенные SVG-иллюстрации без внешних ресурсов;
6. сохраняет читаемость, доступность и функциональную однозначность административного интерфейса;
7. проходит автоматические проверки и desktop/browser-приёмку;
8. не заявляет Mobile PASS без отдельного фактического тестирования.

## 4. Проверенный текущий baseline

В `main` уже реализованы:

- статический доверенный реестр `config/themes.php`;
- глобальная активная тема в `system_settings.ui.active_theme`;
- безопасный fallback `asu-blue`;
- административная страница управления темами;
- POST-only активация с permission, CSRF и PRG;
- `ThemeRegistry`, `ThemeSettingsRepository`, `ThemeActivationService`;
- общая JavaScript-логика operation-result modal;
- публикация каталога `themes` в `public/themes` при deploy;
- две встроенные темы: `asu-blue` и `asu-light-blue`;
- обязательный class contract из семи CSS-файлов;
- desktop-приёмка обеих существующих тем.

Текущий механизм уже поддерживает добавление разработчиком статических CSS, изображений и иконок в доверенную тему. Серверная архитектура управления темами не требует переработки.

## 5. Наименование и идентификаторы

### 5.1 Отображаемое название

```text
Евгения Ростова
```

Название должно отображаться в карточке темы и в сведениях об активной теме без добавления префикса `АСУ`.

### 5.2 Технический slug

```text
asu-evgeniya-rostova
```

Slug:

- соответствует текущему формату `\A[a-z0-9][a-z0-9-]{1,63}\z`;
- не содержит кириллицу, пробелы и специальные символы;
- является стабильным идентификатором каталога и значения настройки;
- не зависит от отображаемого имени.

### 5.3 Тип оформления

```text
appearance = light
```

### 5.4 Каталог

```text
themes/asu-evgeniya-rostova
```

### 5.5 Предлагаемая будущая ветка реализации

```text
feature/theme-evgeniya-rostova
```

Создание этой ветки разрешается только после отдельного Approval реализации.

## 6. Архитектурное решение

### 6.1 Выбранный вариант

Используется утверждённый вариант C:

- отдельный каталог встроенной темы;
- семь самостоятельных CSS-файлов;
- четыре локальных SVG-файла;
- регистрация полного состава assets в доверенном allow-list;
- отсутствие внешних ресурсов и пользовательского исполняемого кода.

### 6.2 Тема остаётся слоем представления

Тема может содержать:

- CSS;
- repository-authored SVG;
- metadata в `config/themes.php`.

Тема не содержит:

- PHP;
- SQL;
- собственный JavaScript;
- маршруты;
- контроллеры;
- копии бизнес-логики;
- данные пользователей;
- внешние URL;
- CDN;
- remote fonts;
- пользовательские загрузки.

### 6.3 Единый HTML/class contract

Новая тема использует существующую PHP-разметку и существующие class names.

Запрещается:

- копировать PHP-страницы в каталог темы;
- добавлять условную бизнес-логику для `asu-evgeniya-rostova`;
- менять DOM только ради декоративного размещения элементов;
- добавлять theme-specific HTTP routes.

Допустимы CSS pseudo-elements и background images, если они не изменяют семантику и не перекрывают controls.

### 6.4 Независимость CSS

Новая тема не должна использовать `@import` из `asu-light-blue`.

Причины:

- тема должна оставаться полноценной и автономной;
- изменение существующей темы не должно неявно менять новую;
- checker должен подтверждать полный комплект новой темы;
- deploy и fallback не должны зависеть от перекрёстных каталогов.

Визуальная основа `asu-light-blue` переносится как согласованный design language, но значения tokens и декоративные правила задаются самостоятельно.

## 7. Структура файлов темы

```text
themes/asu-evgeniya-rostova/
└── assets/
    ├── css/
    │   ├── theme.css
    │   ├── auth.css
    │   ├── account.css
    │   ├── users.css
    │   ├── theme-management.css
    │   ├── directories.css
    │   └── operation-result-modal.css
    └── img/
        ├── hearts-pattern.svg
        ├── balloons.svg
        ├── teddy-bear.svg
        └── plush-bunny.svg
```

### 7.1 Назначение SVG

- `hearts-pattern.svg` — лёгкий повторяемый фон;
- `balloons.svg` — декоративная композиция воздушных шариков;
- `teddy-bear.svg` — мягкая игрушка-медвежонок;
- `plush-bunny.svg` — мягкая игрушка-зайчик.

### 7.2 Требования к SVG

Каждый SVG должен:

- быть создан специально для проекта;
- храниться локально в репозитории;
- содержать только статическую векторную графику;
- не содержать `<script>`;
- не содержать `foreignObject`;
- не содержать embedded HTML;
- не содержать внешние ссылки или внешние изображения;
- не содержать `javascript:`;
- не содержать event-handler attributes;
- не содержать персональные или скрытые metadata;
- не содержать текста, влияющего на смысл интерфейса;
- быть декоративным и не заменять подписи или статусы.

## 8. Изменение доверенного реестра

В `config/themes.php` добавляется запись:

```php
'asu-evgeniya-rostova' => [
    'name' => 'Евгения Ростова',
    'description' => 'Светлая розово-лиловая тема с сердечками, воздушными шариками и мягкими игрушками.',
    'appearance' => 'light',
    'preview_colors' => ['#fff7fb', '#c12a70', '#9a6bc4'],
    'required_assets' => [
        'css/theme.css',
        'css/auth.css',
        'css/account.css',
        'css/users.css',
        'css/theme-management.css',
        'css/directories.css',
        'css/operation-result-modal.css',
        'img/hearts-pattern.svg',
        'img/balloons.svg',
        'img/teddy-bear.svg',
        'img/plush-bunny.svg',
    ],
],
```

### 8.1 Почему SVG включаются в `required_assets`

Декоративные изображения являются частью заявленного дизайна. Тема не должна считаться полностью доступной, если отсутствует хотя бы один из них.

Это обеспечивает:

- блокировку активации неполной темы;
- безопасный fallback;
- обнаружение ошибок deploy;
- воспроизводимую проверку полного визуального комплекта.

### 8.2 Default theme

Default остаётся без изменений:

```text
asu-blue
```

Новая тема не становится fallback автоматически.

## 9. Подключение изображений

CSS-файлы находятся в `assets/css`, изображения — в `assets/img`.

Допустимый относительный URL:

```css
background-image: url("../img/hearts-pattern.svg");
```

После публикации он разрешается браузером в:

```text
/themes/asu-evgeniya-rostova/assets/img/hearts-pattern.svg
```

Запрещаются:

- абсолютные filesystem paths;
- `http://` и `https://`;
- data URI для утверждённых основных иллюстраций;
- ссылки на assets другой темы;
- inline SVG в PHP-разметке;
- query-параметры выбора темы.

## 10. Визуальная система

### 10.1 Основные tokens

```css
:root {
    --page-background: #fff7fb;
    --tile-background: #ffffff;
    --tile-border: #e8a9c7;
    --tile-border-hover: #c12a70;
    --button-background: #c12a70;
    --button-background-hover: #9d1b58;
    --button-border: #c12a70;
    --text-primary: #492438;
    --text-secondary: #745568;
    --heading-color: #b52265;
    --input-background: #ffffff;
    --input-border: #d99ab9;
    --tab-background: #fff0f6;
    --tab-active-background: #fde7f1;
    --focus-color: #9d1b58;
    --accent-purple: #9a6bc4;
    --accent-yellow: #e9ad45;
    --tile-radius: 12px;
    --control-radius: 9px;
}
```

Tokens могут быть уточнены при реализации только в пределах утверждённого визуального направления и требований контраста. Существенная смена палитры требует Addendum.

### 10.2 Контрастные ограничения

Предварительная проверка:

- `#c12a70` на белом: приблизительно `5.48:1`;
- `#9d1b58` на белом: приблизительно `7.69:1`;
- `#492438` на `#fff7fb`: приблизительно `12.53:1`;
- `#745568` на `#fff7fb`: приблизительно `6.15:1`.

`#9a6bc4` и `#e8a9c7` не используются как единственный цвет мелкого текста на белом фоне. Они предназначены для декоративных акцентов, границ и крупных элементов.

### 10.3 Поверхности

- фон страницы светло-розовый;
- основные карточки белые;
- контуры розовые, небольшой толщины;
- тени мягкие и умеренные;
- формы и таблицы остаются спокойными и читаемыми;
- плотные текстовые области не получают активный паттерн под текстом.

### 10.4 Скругления

- крупные карточки: ориентир `12px`;
- controls: ориентир `9px`;
- badges: pill geometry сохраняется;
- изменение geometry не должно ломать текущую сетку.

## 11. Использование декоративных элементов

### 11.1 Сердечки

Сердечки используются:

- как малоконтрастный background pattern страницы;
- как небольшой акцент header/footer;
- в отдельных пустых декоративных областях карточек.

Ограничения:

- opacity должна сохранять читаемость;
- паттерн не должен создавать визуальный шум под таблицами;
- сердечки не заменяют icons статусов;
- pattern не должен влиять на layout size.

### 11.2 Воздушные шарики

Композиция шариков используется преимущественно:

- на странице входа/первичной установки;
- в свободной части header или крупной intro-панели;
- как декоративный элемент, не как control.

В v1 постоянная анимация шариков не требуется и по умолчанию не применяется.

### 11.3 Мягкие игрушки

Медвежонок и зайчик используются умеренно:

- на auth page;
- на dashboard или landing page в свободной области;
- в качестве decorative background/pseudo-element.

Запрещается:

- помещать игрушку поверх input, button, table row или текста;
- использовать игрушки в каждой строке или каждой карточке;
- делать SVG кликабельным;
- использовать иллюстрацию вместо понятной подписи.

### 11.4 Техническая реализация декора

Декоративные pseudo-elements должны использовать:

```css
pointer-events: none;
user-select: none;
```

Их stacking context обязан оставлять controls выше декоративного слоя.

При узком viewport декоративные элементы должны:

- уменьшаться;
- смещаться за пределы текстовой области;
- либо скрываться через media query.

Это является требованием адаптивности, но не считается Mobile PASS без отдельной приёмки.

## 12. Компонентная спецификация

### 12.1 Header и footer

- сохраняют существующую структуру;
- получают бело-розовую поверхность;
- заголовок использует насыщенный розовый;
- декоративный элемент не перекрывает logout и navigation controls;
- footer остаётся спокойным и не отвлекает от действий.

### 12.2 Логотип

Текстовый логотип `АСУ` сохраняется.

Допускается:

- розовый или розово-лиловый фон;
- мягкое свечение;
- небольшой декоративный heart accent средствами CSS.

Не допускается изменение текста логотипа на имя темы.

### 12.3 Кнопки

Primary:

- насыщенный розовый фон;
- белый текст;
- тёмно-розовый hover;
- заметный focus-visible.

Secondary:

- белый или очень светло-розовый фон;
- розовый контур;
- тёмный текст.

Danger:

- сохраняет отдельную красную семантику;
- не превращается в обычный розовый primary.

Disabled:

- отличается не только цветом;
- сохраняет невозможность interaction.

### 12.4 Формы

- белая поверхность input;
- контрастный label;
- видимый border;
- focus ring отличается от обычного состояния;
- placeholder остаётся читаемым, но слабее значения;
- autofill не нарушает читаемость;
- validation states сохраняют текст и форму, а не только цвет.

### 12.5 Таблицы и фильтры

- table surface белая;
- header row получает очень светлую розовую заливку;
- разделители различимы;
- hover не снижает контраст;
- search/filter controls сохраняют текущую геометрию;
- empty state читаем и не маскируется иллюстрацией.

### 12.6 Dashboard и module tiles

- карточки имеют мягкий розовый контур;
- hover использует умеренный подъём и усиление border/shadow;
- декоративная игрушка может появляться только в свободной зоне;
- status badge и tile action остаются доминирующими функциональными элементами.

### 12.7 Theme management

Карточка `Евгения Ростова` показывает:

- точное имя;
- описание;
- label `Светлая`;
- три preview swatch;
- availability;
- active badge после активации;
- кнопку активации только при наличии update permission.

Тема должна корректно стилизовать собственную карточку и страницу управления темами после переключения.

### 12.8 Directories

Обе текущие справочные страницы и landing page получают полноценный `directories.css`:

- фильтры;
- source cards;
- tables;
- class/type badges;
- empty state;
- pagination/summary, если присутствуют;
- ссылки и focus states.

### 12.9 Operation-result modal

- общий JavaScript не меняется;
- новая тема предоставляет только CSS;
- success сохраняет зелёную семантику;
- error сохраняет красную семантику;
- backdrop не скрывает modal;
- close button и focus-visible различимы.

### 12.10 HTTP 403

Тематическая страница 403 должна:

- использовать новую тему;
- сохранять понятный текст отказа;
- не заменять сообщение декоративной иллюстрацией;
- не раскрывать permission internals.

## 13. Accessibility и usability

Обязательные условия:

1. функциональный смысл не передаётся только цветом;
2. decorative SVG не содержит смыслового текста;
3. focus-visible заметен на всех controls;
4. primary text соответствует достаточному контрасту;
5. semantic success/warning/error/muted/danger сохраняются;
6. декор не перехватывает pointer events;
7. декор не вызывает horizontal scroll;
8. interface остаётся рабочим при отключённых изображениях;
9. постоянная motion-анимация не вводится;
10. существующее `prefers-reduced-motion` правило сохраняется.

## 14. Security

### 14.1 Trusted allow-list

Slug и assets объявляются только в `config/themes.php`.

Никакое значение из GET, POST, cookie или БД не становится filesystem path без текущей registry validation.

### 14.2 External resources

Запрещены:

- CDN;
- внешние fonts;
- remote CSS/JS;
- remote image URLs;
- external SVG references.

### 14.3 SVG safety

Checker или отдельная controlled inspection должны подтверждать отсутствие:

```text
<script
foreignObject
javascript:
onload=
onclick=
href="http
href='http
xlink:href="http
xlink:href='http
```

Проверка должна учитывать регистр там, где это необходимо.

### 14.4 Existing activation security

Не изменяются:

- POST-only mutation;
- `system.settings.update`;
- CSRF;
- PRG;
- transaction;
- safe fallback;
- fixed operation-result catalog.

## 15. Database и migrations

Изменений схемы или данных нет.

Новая migration не создаётся.

Системное количество:

```text
migrations = 8
system permissions = 19
```

должно остаться без изменений.

SQL backup для этого инкремента не требуется, потому что утверждённый scope не содержит migration или data mutation. Backup изменяемых deploy-файлов и сохранение `config/local.php` остаются обязательными.

## 16. Изменяемые runtime-файлы

Ожидается изменение:

```text
config/themes.php
database/check-theme-management.php
tools/check-military-ranks-directory.php
tools/check-organizational-elements-directory.php
```

Ожидается добавление:

```text
themes/asu-evgeniya-rostova/assets/css/theme.css
themes/asu-evgeniya-rostova/assets/css/auth.css
themes/asu-evgeniya-rostova/assets/css/account.css
themes/asu-evgeniya-rostova/assets/css/users.css
themes/asu-evgeniya-rostova/assets/css/theme-management.css
themes/asu-evgeniya-rostova/assets/css/directories.css
themes/asu-evgeniya-rostova/assets/css/operation-result-modal.css
themes/asu-evgeniya-rostova/assets/img/hearts-pattern.svg
themes/asu-evgeniya-rostova/assets/img/balloons.svg
themes/asu-evgeniya-rostova/assets/img/teddy-bear.svg
themes/asu-evgeniya-rostova/assets/img/plush-bunny.svg
```

Изменение PHP emitter’ов, routes, bootstrap, services и database schema не ожидается. Если реализация покажет необходимость такого изменения, работа останавливается и готовится Addendum до продолжения.

## 17. Обновление checker’ов

### 17.1 Theme management checker

`database/check-theme-management.php` должен:

- ожидать три зарегистрированные темы;
- ожидать порядок `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova`;
- подтверждать availability всех трёх;
- подтверждать точное отображаемое имя новой темы;
- подтверждать `appearance = light`;
- подтверждать три preview colors;
- подтверждать все семь CSS-assets;
- подтверждать все четыре SVG-assets;
- проверять URL `css/theme.css` новой темы;
- проверять URL каждого SVG;
- сохранять negative path tests;
- сохранять fallback/default tests;
- проводить repository write/read в rollback-транзакции;
- не оставлять активную тему изменённой после checker’а.

### 17.2 Military ranks checker

`tools/check-military-ranks-directory.php` сейчас перечисляет две темы явно.

Он должен:

- проверять `css/directories.css` всех трёх зарегистрированных тем;
- ожидать финальный маркер `OK theme assets: 3`;
- сохранять все профильные проверки справочника.

### 17.3 Organizational elements checker

`tools/check-organizational-elements-directory.php` также перечисляет две темы явно.

Он должен:

- проверять `css/directories.css` всех трёх зарегистрированных тем;
- ожидать финальный маркер `OK theme assets: 3`;
- сохранять все профильные проверки справочника.

### 17.4 Предпочтительный способ устранения hardcode

В обоих справочных checker’ах рекомендуется получать список slug из валидированного registry/config, а не вводить третий независимый hardcoded array, при условии сохранения явной проверки ожидаемого количества тем.

Это уменьшает риск повторного пропуска темы в будущих инкрементах.

## 18. Deploy

Существующий deploy уже публикует весь каталог `themes` в:

```text
C:\OSPanel\home\asu-vch.local\public\themes
```

Новый deploy script не требуется.

Перед deploy необходимо:

1. подтвердить точный GitHub SHA утверждённой реализации;
2. подтвердить чистое рабочее дерево;
3. сохранить изменяемые deploy-файлы;
4. сохранить `config/local.php` предусмотренным runbook-механизмом;
5. выполнить контролируемый deploy;
6. подтвердить восстановление `config/local.php` без раскрытия содержимого;
7. сравнить source/deploy assets по SHA-256.

## 19. Автоматические проверки

До browser acceptance обязательны:

1. точный GitHub branch и HEAD;
2. local/remote divergence `0/0`;
3. clean working tree;
4. PHP lint всех PHP-файлов;
5. отсутствие новой migration;
6. installer: `Применено миграций: 8`;
7. повторный installer: `Новых миграций нет`;
8. theme management checker PASS;
9. military ranks checker PASS;
10. organizational elements checker PASS;
11. system permissions `19`;
12. registry themes `3`;
13. default `asu-blue`;
14. все required assets source-side существуют;
15. все required assets deploy-side существуют;
16. source/deploy SHA-256 совпадают;
17. CSS и SVG возвращают HTTP 200;
18. неизвестный slug отклоняется;
19. missing asset делает тему unavailable;
20. invalid asset path отклоняется;
21. stored active theme остаётся зарегистрированной;
22. checker не оставляет mutation после rollback;
23. `config/local.php` сохранён;
24. external resources отсутствуют;
25. SVG safety scan PASS.

## 20. HTTP asset acceptance

Минимально проверяются HTTP 200:

```text
/themes/asu-evgeniya-rostova/assets/css/theme.css
/themes/asu-evgeniya-rostova/assets/css/auth.css
/themes/asu-evgeniya-rostova/assets/css/account.css
/themes/asu-evgeniya-rostova/assets/css/users.css
/themes/asu-evgeniya-rostova/assets/css/theme-management.css
/themes/asu-evgeniya-rostova/assets/css/directories.css
/themes/asu-evgeniya-rostova/assets/css/operation-result-modal.css
/themes/asu-evgeniya-rostova/assets/img/hearts-pattern.svg
/themes/asu-evgeniya-rostova/assets/img/balloons.svg
/themes/asu-evgeniya-rostova/assets/img/teddy-bear.svg
/themes/asu-evgeniya-rostova/assets/img/plush-bunny.svg
```

Content-Type для SVG должен быть безопасно обслужен web server как image/svg+xml либо эквивалентно корректный SVG MIME type текущего Apache.

## 21. Desktop/browser-приёмка

### 21.1 Тема `Евгения Ростова`

Обязательные страницы и состояния:

1. публичная страница входа;
2. первичная установка на изолированном тестовом состоянии, если предусмотрена acceptance-среда;
3. admin dashboard;
4. settings landing;
5. theme management;
6. users list;
7. user create;
8. user view/edit states;
9. account change-password;
10. directories landing;
11. military ranks directory;
12. organizational element types directory;
13. themed HTTP 403;
14. success operation-result modal;
15. error operation-result modal;
16. search results;
17. empty state;
18. filters;
19. status badges;
20. long text and narrow desktop viewport sanity.

На каждой странице проверяются:

- отсутствие перекрытия controls декором;
- отсутствие horizontal scroll на утверждённых desktop viewport;
- читаемость текста;
- focus-visible;
- hover;
- active/disabled/danger states;
- наличие сердечек, шариков и мягких игрушек в предусмотренных зонах;
- отсутствие чрезмерного декора в таблицах и формах.

### 21.2 Переключение

Проверяется последовательность:

1. активировать `Евгения Ростова`;
2. подтвердить themed success modal;
3. обновить страницу;
4. выполнить logout/login;
5. подтвердить persistence;
6. переключиться на `asu-light-blue`;
7. подтвердить отсутствие регрессии;
8. переключиться на `asu-blue`;
9. подтвердить отсутствие регрессии;
10. вернуть согласованную финальную активную тему.

### 21.3 Роли

- `system_owner`: просмотр и активация разрешены;
- `administrator`: просмотр и активация разрешены;
- `operator`: прямой доступ запрещён;
- `viewer`: прямой доступ запрещён.

Permission count не меняется.

### 21.4 Existing themes regression

Для `asu-blue` и `asu-light-blue` выполняется desktop smoke/regression по ключевым страницам:

- login;
- dashboard;
- settings/themes;
- users;
- directories;
- modal;
- 403.

Новая тема не должна менять CSS существующих тем.

## 22. Адаптивность и mobile scope

CSS должен учитывать узкие viewport:

- декор уменьшается или скрывается;
- grid переходит в существующую одноколоночную структуру;
- controls не перекрываются;
- изображения не создают overflow.

Однако мобильная browser-приёмка не входит в текущий scope.

Запрещено заявлять:

```text
Mobile PASS
```

без отдельного фактического тестирования и доказательств.

## 23. Документация реализации

После реализации должны быть актуализированы:

```text
docs/THEMES.md
docs/PROJECT-STATUS.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/LOCAL-RUNBOOK.md
```

Также создаются process artifacts:

```text
docs/decisions/EVGENIYA-ROSTOVA-THEME-V1-APPROVAL.md
docs/design/EVGENIYA-ROSTOVA-THEME-V1-IMPLEMENTATION-ADDENDUM.md
docs/testing/EVGENIYA-ROSTOVA-THEME-V1-TEST-REPORT.md
docs/design/EVGENIYA-ROSTOVA-THEME-V1-PR-FINAL-REVIEW.md
```

Implementation Addendum создаётся после реализации и фиксирует фактические изменения. Он не заменяет настоящую Specification.

## 24. Scope

### 24.1 В scope

- третья встроенная тема;
- slug `asu-evgeniya-rostova`;
- имя `Евгения Ростова`;
- розово-лиловая светлая палитра;
- сердечки;
- воздушные шарики;
- мягкие игрушки;
- четыре локальных SVG;
- семь обязательных CSS;
- полная регистрация assets;
- checker updates;
- desktop/browser acceptance;
- regression двух существующих тем;
- living documentation и process artifacts.

### 24.2 Вне scope

- новая migration;
- изменение database schema/data;
- новый permission;
- изменение RBAC;
- per-user themes;
- browser theme editor;
- ZIP upload;
- arbitrary theme installation;
- theme deletion;
- внешние изображения;
- CDN и remote fonts;
- отдельный JavaScript темы;
- sound effects;
- постоянная сложная animation;
- изменение HTML только ради декора;
- изменение fallback;
- production deployment;
- GitHub CI;
- mobile acceptance.

## 25. Failure modes

### 25.1 Отсутствует SVG

Результат:

- тема unavailable;
- активация отклоняется;
- если значение уже сохранено, runtime использует fallback;
- checker блокирует acceptance.

### 25.2 SVG не опубликован при deploy

Результат:

- source/deploy hash/existence check падает;
- HTTP asset acceptance падает;
- browser acceptance не начинается.

### 25.3 Декор перекрывает controls

Результат:

- blocking UI defect;
- исправление обязательно до Test Report PASS.

### 25.4 Недостаточный контраст

Результат:

- корректируются tokens в пределах утверждённой палитры;
- существенное изменение визуального направления требует Addendum.

### 25.5 Существующая тема регрессировала

Результат:

- blocking regression;
- PR не допускается к merge.

### 25.6 Checker оставляет активную тему изменённой

Результат:

- blocking test isolation defect;
- checker исправляется до acceptance.

### 25.7 Потребовалось изменение PHP/DOM

Результат:

- реализация останавливается;
- создаётся Addendum с причиной и минимальным изменением;
- требуется отдельное утверждение Addendum.

## 26. Acceptance criteria

Инкремент считается реализованным только если одновременно выполнены условия:

1. точное имя темы — `Евгения Ростова`;
2. точный slug — `asu-evgeniya-rostova`;
3. registry содержит три темы;
4. default остаётся `asu-blue`;
5. новая тема имеет семь CSS и четыре SVG;
6. все assets зарегистрированы и доступны;
7. сердечки, шарики и мягкие игрушки визуально присутствуют;
8. decor не мешает функциональности;
9. theme management checker PASS;
10. оба directory checker’а PASS;
11. migrations остаются `8`;
12. permissions остаются `19`;
13. installer idempotency PASS;
14. config/local.php сохранён;
15. desktop acceptance новой темы PASS;
16. regression `asu-blue` PASS;
17. regression `asu-light-blue` PASS;
18. role denial PASS;
19. SVG security PASS;
20. external resources отсутствуют;
21. working tree clean;
22. документация актуализирована;
23. PR прошёл Formal Review;
24. получено отдельное разрешение на merge.

## 27. Implementation gates

До начала реализации требуется точное отдельное разрешение заказчика после Formal Review.

До merge требуется другое отдельное разрешение после Testing, PR и Final Review.

Документационное утверждение не является merge approval.
