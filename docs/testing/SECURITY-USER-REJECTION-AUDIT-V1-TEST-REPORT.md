# Security User Rejection Audit v1 — отчет о тестировании

## 1. Общие сведения

- Инкремент: `Security User Rejection Audit v1`
- Ветка: `feature/user-rejection-audit`
- Базовая ветка: `main`
- Базовый commit: `8661d34602e07f67b4c0425f62b020f5ec439932`
- Проверяемый commit до оформления отчета: `d52799131dbbae5b8c772d16e9fbbe42aa2cb7af`
- Среда: Windows, Open Server Panel 6.5.1, Apache, PHP 8.5.4, MySQL 8.4
- Домен: `https://asu-vch.local`
- Дата исполняемых проверок: 2026-07-25
- Дата оформления отчета: 2026-07-26

## 2. Область проверки

Проверялись:

- migration 004 и ее повторный запуск;
- системное разрешение `security.users.reject`;
- назначение разрешения роли `administrator`;
- отклонение только pending-учетной записи;
- обязательное основание отклонения;
- фиксация `rejected_by`, `rejected_at`, `rejection_reason`;
- принудительное `is_active = 0`;
- очистка `approved_by` и `approved_at`;
- терминальность статуса `rejected`;
- конфликтующие approve/reject и повторные reject-запросы;
- запрет прямой активации rejected-пользователя;
- CSRF-защита;
- серверная проверка permission;
- фильтр и счетчик отклоненных пользователей;
- privacy-аудит для роли viewer;
- экранирование пользовательского текста;
- регрессия RBAC, approval и обязательной смены пароля;
- desktop-интерфейс.

## 3. Изменение области приемки

Заказчик явно исключил мобильную версию из обязательной области проекта и приемочного тестирования:

> «Давай больше не будем проверять версию для мобильных. Она не нужна.»

Поэтому проверки 390 px, 360 px, мобильного overflow и мобильной прокрутки статистических плиток не выполнялись и не являются блокирующими критериями приемки. Существующие responsive-стили не удалялись, но их работа в рамках этого инкремента не заявляется как проверенная.

## 4. Автоматические проверки

### 4.1 PHP syntax

Команда:

```powershell
PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\Test-PhpSyntax.ps1
```

Результат:

```text
Проверено PHP-файлов: 37. Ошибок нет.
```

Статус: **PASS**.

### 4.2 Deploy

Команда:

```powershell
PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File .\deploy\Deploy-Local.ps1
```

Результат:

```text
Существующий config/local.php временно сохранен.
Существующий config/local.php восстановлен.
Скопировано файлов: 48
Домен: https://asu-vch.local
```

Статус: **PASS**.

### 4.3 Migration 004 — первый запуск

Команда:

```powershell
php C:\OSPanel\home\asu-vch.local\database\install.php
```

Результат:

```text
База данных: asu_vch
Применено миграций: 4
Применена миграция: 004_security_user_rejection_audit.sql
Пользователей: 3
Первичная регистрация отключена.
```

Статус: **PASS**.

### 4.4 Migration 004 — повторный запуск

Результат:

```text
База данных: asu_vch
Применено миграций: 4
Новых миграций нет.
Пользователей: 3
Первичная регистрация отключена.
```

Статус: **PASS**. Идемпотентность установщика подтверждена.

### 4.5 RBAC regression

Команда:

```powershell
php .\database\check-security-rbac.php
```

Результат:

```text
OK migration 002
OK system roles: 4
OK system permissions: 19
OK active owners: 1
```

Статус: **PASS**.

### 4.6 Approval regression

Команда:

```powershell
php .\database\check-security-user-approval.php
```

Результат:

```text
OK migration 003
OK approval columns: 5
OK approved users: 3
OK active approved owners: 1
```

Статус: **PASS**.

### 4.7 Required password change regression

Команда:

```powershell
php .\database\check-security-required-password-change.php
```

Результат:

```text
OK wrong current password rejected
OK weak password rejected
OK confirmation mismatch rejected
OK current password reuse rejected
OK password hash changed
OK temporary flags cleared
```

Статус: **PASS**.

### 4.8 User rejection integration check

Команда:

```powershell
php .\database\check-security-user-rejection.php
```

Результат:

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

### 4.9 Local smoke

Команда:

```powershell
PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\Test-LocalSmoke.ps1 -AllowInvalidCertificate
```

Результат:

```text
OK 200 https://asu-vch.local/
OK 200 https://asu-vch.local/health.php
OK 302 https://asu-vch.local/admin/

Smoke test completed successfully.
```

Статус: **PASS**.

## 5. Ручные проверки

### 5.1 Список пользователей

Проверено:

- присутствуют шесть статистических плиток;
- счетчик `Отклоненные` изменяется после отклонения;
- фильтр `status=rejected` возвращает только rejected-записи;
- rejected-пользователь отображается со статусом `Отклонен`.

Статус: **PASS**.

### 5.2 Pending-карточка

Проверено:

- статус `Ожидает подтверждения`;
- кнопка `Подтвердить и активировать`;
- поле обязательного основания;
- danger-кнопка `Отклонить пользователя`;
- pending-пользователь не может войти.

Статус: **PASS**.

### 5.3 Валидация основания

Проверено основание `Коротко`.

Результат:

```text
Основание отклонения должно содержать от 10 до 500 символов.
```

Пользователь остался pending, текст был сохранен в форме.

Статус: **PASS**.

### 5.4 Успешное отклонение

Проверено многострочное основание с текстом:

```text
Заявка не соответствует утвержденному основанию доступа.
Требуется повторное согласование. <b>HTML не исполнять</b>
```

Результат:

```text
Учетная запись «Rejection_Test_01» отклонена.
```

Подтверждено:

- основной статус `Отклонен`;
- результат обработки `Отклонен`;
- отображение субъекта решения;
- корректная ссылка на карточку субъекта;
- заполненные дата и основание;
- HTML показан как обычный текст;
- формы approve/reject и кнопки block/unblock исчезли;
- пустой блок управления доступом отсутствует;
- rejected-пользователь не может войти.

Статус: **PASS**.

### 5.5 CSRF

Токен в форме отклонения был заменен на `invalid`.

Результат:

```text
Недействительный CSRF-токен.
HTTP 419
```

После отказа пользователь остался pending и не получил аудит отклонения.

Статус: **PASS**.

### 5.6 Approve → reject

Карточка pending-пользователя была открыта в двух вкладках. В первой вкладке пользователь подтвержден и активирован. Из второй вкладки отправлена сохраненная reject-форма.

Результат:

```text
Учетная запись уже обработана.
```

Пользователь сохранил:

```text
approval_status = approved
is_active = 1
```

Статус: **PASS**.

### 5.7 Reject → reject

Карточка pending-пользователя была открыта в двух вкладках. Первое отклонение выполнено успешно, после чего из второй вкладки отправлена повторная reject-форма.

Результат:

```text
Учетная запись уже обработана.
```

Первоначальные субъект, дата и основание не изменились.

Статус: **PASS**.

### 5.8 Viewer privacy

Под активным viewer открыта карточка rejected-пользователя.

Viewer видит:

- имя;
- логин;
- общий статус `Отклонен`;
- назначенные роли.

Viewer не видит:

- email;
- субъекта отклонения;
- дату отклонения;
- основание отклонения;
- основание добавления;
- блок аудита;
- формы редактирования;
- формы approve/reject.

Статус: **PASS**.

### 5.9 Серверная авторизация reject-маршрута

Под viewer открыт прямой URL:

```text
/admin/users/reject.php
```

Результат:

```text
Доступ запрещен
HTTP 403
```

Статус: **PASS**.

### 5.10 GET reject-маршрута

Под владельцем выполнен GET-запрос к `/admin/users/reject.php`.

Результат: перенаправление к списку пользователей без изменения данных.

Статус: **PASS**.

## 6. Финальный Git status перед отчетом

```text
On branch feature/user-rejection-audit
Your branch is up to date with 'origin/feature/user-rejection-audit'.

nothing to commit, working tree clean
```

Проверенный HEAD:

```text
d527991
```

## 7. Итог

Все обязательные автоматические и desktop-ручные проверки завершены успешно.

```text
Automated tests: PASS
Manual desktop tests: PASS
Security checks: PASS
Regression checks: PASS
Mobile checks: NOT IN ACCEPTANCE SCOPE
```

Блокирующих дефектов не обнаружено. Инкремент готов к PR final review.
