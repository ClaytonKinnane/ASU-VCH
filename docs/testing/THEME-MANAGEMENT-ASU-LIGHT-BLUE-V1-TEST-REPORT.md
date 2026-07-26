# Theme Management System & ASU Light Blue Theme v1 — отчет о тестировании

## 1. Общие сведения

- Инкремент: `Theme Management System & ASU Light Blue Theme v1`
- Ветка: `feature/theme-asu-light-blue`
- Базовая ветка: `main`
- Базовый commit: `4e1d692807fbac83d86ec1be431df4563bcfacd5`
- Проверяемый commit до оформления отчета: `e0e25e091468a70b5cce28b349861f81b6a77435`
- Среда: Windows, Open Server Panel 6.5.1, Apache, PHP 8.5.4, MySQL 8.4.8
- Домен: `https://asu-vch.local`
- Дата проверок: `2026-07-26`

## 2. Область проверки

Проверялись:

- доверенный реестр тем;
- полный asset contract двух тем;
- безопасное разрешение runtime active theme;
- хранение `ui.active_theme` в `system_settings`;
- аудит последнего изменения через `updated_by` и `updated_at`;
- migration 006 и повторный запуск установщика;
- RBAC через существующие `system.settings.view` и `system.settings.update`;
- GET-страница списка тем и POST-маршрут активации;
- CSRF и отсутствие mutation через GET;
- fallback на `asu-blue`;
- запрет произвольных slug, URL и path traversal;
- отсутствие hardcoded `/themes/asu-blue/assets/` в исполняемом PHP;
- общий JavaScript operation-result modal;
- новая тема `asu-light-blue`;
- persistence после refresh и logout/login;
- регрессия security-модулей;
- desktop-интерфейс обеих тем.

## 3. Область приемки

Мобильная версия исключена заказчиком из обязательной области приемки. Responsive-код не удалялся, но мобильная работа не заявляется как проверенная.

## 4. Автоматические проверки

### 4.1 PHP syntax

Команда:

```powershell
PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\Test-PhpSyntax.ps1
```

Результат:

```text
Проверено PHP-файлов: 48. Ошибок нет.
```

Статус: **PASS**.

### 4.2 Deploy

Команда:

```powershell
PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File .\deploy\Deploy-Local.ps1
```

Подтверждено:

```text
Open Server Panel: 6.5.1.0
Модули: Apache, PHP-8.5, MySQL-8.4
Существующий config/local.php временно сохранен и восстановлен
Скопировано файлов: 70
Домен: https://asu-vch.local
```

Отдельно подтверждено наличие обязательных серверных файлов, маршрутов и CSS-assets обеих тем. Старый `themes/asu-blue/assets/js/operation-result-modal.js` отсутствует; общий файл расположен в `public/assets/js/operation-result-modal.js`.

Статус: **PASS**.

### 4.3 Migration 006 — первый запуск

Результат:

```text
База данных: asu_vch
Применено миграций: 6
Применена миграция: 006_theme_management.sql
Пользователей: 9
Первичная регистрация отключена.
```

Статус: **PASS**.

### 4.4 Migration 006 — повторный запуск

Результат:

```text
База данных: asu_vch
Применено миграций: 6
Новых миграций нет.
Пользователей: 9
Первичная регистрация отключена.
```

Статус: **PASS**. Идемпотентность установщика подтверждена.

### 4.5 Theme management integration checker

Команда:

```powershell
php C:\OSPanel\home\asu-vch.local\database\check-theme-management.php
```

Результат:

```text
OK default theme is asu-blue
OK registered themes: 2
OK asu-blue assets complete
OK asu-light-blue assets complete
OK light theme asset URL
OK invalid asset paths rejected
OK no hardcoded asu-blue asset URLs in executable PHP
OK shared operation modal JavaScript exists
OK theme-specific operation modal JavaScript removed
OK local config exists
OK migration 006 applied
OK system_settings.updated_by exists
OK theme setting actor foreign key
OK stored active theme is registered
OK active system owner available for repository transaction test
OK theme setting repository write/read
OK invalid theme activation rejected
OK theme management integration check completed
```

Статус: **PASS**.

### 4.6 RBAC regression

```text
OK migration 002
OK system roles: 4
OK system permissions: 19
OK active owners: 1
```

Статус: **PASS**.

### 4.7 Approval regression

```text
OK migration 003
OK approval columns: 5
OK approved users: 7
OK active approved owners: 1
```

Статус: **PASS**.

### 4.8 Required password change regression

```text
OK wrong current password rejected
OK weak password rejected
OK confirmation mismatch rejected
OK current password reuse rejected
OK password hash changed
OK temporary flags cleared
```

Статус: **PASS**.

### 4.9 Rejection regression

```text
OK migration 004
OK rejection columns: 3
OK rejection foreign key
OK system permissions: 19
OK administrator rejection permission
OK short rejection reason rejected
OK pending user rejected
OK rejection audit recorded
OK repeated rejection rejected
OK approval after rejection rejected
OK activation after rejection rejected
```

Статус: **PASS**.

### 4.10 Archive/restore regression

```text
OK migration 005
OK archive restore columns: 6
OK archive restore foreign keys
OK archive restore indexes: 5
OK system permissions: 19
OK administrator archive restore permissions
OK self archive rejected
OK short archive reason rejected
OK approved user archived with audit
OK repeated archive rejected
OK archived login rejected
OK archived mutations rejected
OK archive list isolation
OK short restore reason rejected
OK approved user restored blocked with audit
OK repeated restore rejected
OK second archive cycle recorded
OK pending and rejected restore matrix
OK archive restore integration check completed
```

Статус: **PASS**.

### 4.11 Local smoke

```text
OK 200 https://asu-vch.local/
OK 200 https://asu-vch.local/health.php
OK 302 https://asu-vch.local/admin/
Smoke test completed successfully.
```

Статус: **PASS**.

## 5. Ручная desktop-приемка

### 5.1 Страница настроек и список тем

Подтверждено:

- карточка «Темы оформления» активна и ведет на `/admin/settings/themes.php`;
- техническая информация показывает runtime active theme;
- отображаются две карточки: `АСУ Синяя` и `АСУ Светлая синяя`;
- каждая карточка содержит название, описание, тип оформления и три palette swatch;
- у текущей темы отображается `Активна` и `Используется сейчас`;
- у доступной неактивной темы отображается кнопка `Активировать`.

Статус: **PASS**.

### 5.2 Активация `asu-light-blue`

Подтверждено:

- POST-активация применяет тему сразу после redirect;
- появляется зеленый themed modal;
- заголовок: `Операция выполнена`;
- сообщение: `Тема оформления активирована.`;
- кнопка: `Закрыть`;
- native browser alert отсутствует;
- `result` удаляется из URL;
- после refresh modal не повторяется.

Статус: **PASS**.

### 5.3 Визуальная приемка светлой темы

Проверены:

- панель управления;
- настройки системы;
- страница управления темами;
- список пользователей;
- карточка пользователя;
- форма создания пользователя;
- обязательная смена пароля.

Подтверждено:

- белый фон;
- основной синий визуальный акцент;
- тонкие синие контуры;
- читаемый темный текст;
- светлые поля ввода;
- различимые кнопки, ссылки и статусы;
- корректные таблицы и формы;
- сохранение semantic warning/success/error/archive состояний.

Статус: **PASS**.

### 5.4 Persistence

После активации светлой темы выполнены refresh, logout и повторный login.

Подтверждено:

- страница входа остается светлой;
- административная панель остается светлой;
- `АСУ Светлая синяя` остается активной;
- техническая информация показывает `АСУ Светлая синяя (asu-light-blue)`.

Статус: **PASS**.

### 5.5 Last-owner error modal в светлой теме

Выполнена попытка архивировать единственного active + approved + nonarchived владельца.

Подтверждено:

```text
Операция не выполнена
Нельзя архивировать последнего активного владельца системы.
```

Дополнительно подтверждено:

- красное themed-оформление;
- кнопка `Понятно`;
- закрытие modal;
- inline fallback остается видимым;
- владелец остается активным и неархивированным.

Статус: **PASS**.

### 5.6 RBAC

`administrator`:

- открывает страницу настроек;
- открывает список тем;
- видит кнопку активации;
- успешно переключает тему.

`operator` / `viewer`:

- получает HTTP 403 при прямом открытии `/admin/settings/themes.php`;
- видит оформленную страницу `Доступ запрещен`;
- 403 использует текущую активную тему;
- список тем и mutation недоступны.

Статус: **PASS**.

### 5.7 Возврат к `asu-blue`

Подтверждено:

- `АСУ Синяя` активируется через UI;
- redirect уже использует темную тему;
- появляется зеленый modal с точным success-текстом;
- после закрытия и refresh modal не повторяется;
- техническая информация снова показывает `АСУ Синяя (asu-blue)`.

Статус: **PASS**.

## 6. Визуальные свидетельства

Заказчик предоставил desktop-скриншоты, подтверждающие:

- исходную темную страницу настроек;
- две карточки тем;
- success modal при переходе на светлую тему;
- светлую страницу управления темами;
- светлую панель управления;
- светлую страницу настроек;
- светлый список пользователей;
- светлую карточку пользователя;
- светлую форму создания пользователя;
- светлую страницу обязательной смены пароля;
- last-owner error modal;
- сохранение владельца активным;
- themed 403;
- success modal при возврате к темной теме.

Скриншоты используются как приемочное свидетельство текущего локального стенда и не добавляются в репозиторий.

## 7. Дефекты

Открытых дефектов по утвержденному desktop scope: **0**.

## 8. Финальный результат

```text
PHP syntax: PASS — 48 файлов
Deploy: PASS — 70 файлов
Migration 006: PASS
Migration idempotency: PASS
Theme registry/assets: PASS
Asset path security: PASS
Database persistence/audit: PASS
RBAC regression: PASS — 19 permissions
Approval regression: PASS
Required password change regression: PASS
Rejection regression: PASS
Archive/restore regression: PASS
Smoke: PASS
Desktop theme management acceptance: PASS
Light theme visual acceptance: PASS
Theme persistence: PASS
Themed success modal: PASS
Themed error modal: PASS
Administrator activation: PASS
Operator/viewer 403: PASS
Return to asu-blue: PASS
Mobile acceptance: OUT OF SCOPE BY CUSTOMER DECISION
Blocking defects: 0
```

Инкремент допускается к PR final review и созданию Pull Request.
