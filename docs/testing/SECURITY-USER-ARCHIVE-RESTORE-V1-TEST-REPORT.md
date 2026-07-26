# Security User Archive & Restore v1 — отчет о тестировании

## 1. Общие сведения

- Инкремент: `Security User Archive & Restore v1`
- UX-дополнение: `Themed Operation Result Modal v1`
- Ветка: `feature/user-archive-restore`
- Базовая ветка: `main`
- Базовый commit: `859a2dc7462a41a4e630b637485bf346437ccdd0`
- Проверяемый commit до оформления отчета: `eaf623c623425c9c4ad8fedff8cb91aabdaa5f10`
- Среда: Windows, Open Server Panel 6.5.1, Apache, PHP 8.5.4, MySQL 8.4
- Домен: `https://asu-vch.local`
- Дата проверок: 2026-07-26

## 2. Область проверки

Проверялись:

- migration 005 и повторный запуск установщика;
- поля, внешние ключи и индексы аудита archive/restore;
- существующие разрешения `security.users.archive` и `security.users.restore`;
- назначение разрешений роли `administrator`;
- архивирование active, blocked, pending и rejected записей;
- обязательные основания архивирования и восстановления;
- запрет самоархивирования;
- защита последнего active + approved + nonarchived владельца;
- немедленный запрет входа и прекращение существующей сессии после архивирования;
- read-only режим архивированной карточки;
- восстановление без автоматической активации;
- сохранение ролей, approval/rejection workflow и учетных данных;
- исключение архивированных записей из обычного списка;
- отдельный фильтр и счетчик архива;
- privacy для viewer;
- серверные `403`, `419` и POST-only/GET safety;
- HTML escaping оснований;
- тематические modal-сообщения результата операции;
- регрессия RBAC, approval, required password change и rejection;
- desktop-интерфейс.

## 3. Область приемки

Мобильная версия ранее явно исключена заказчиком из обязательной области проекта и приемочного тестирования. Поэтому мобильные viewport, overflow и touch-поведение не являются блокирующими критериями этого инкремента. Существующие responsive-стили не удалялись, но мобильная работа не заявляется как проверенная.

## 4. Автоматические проверки

### 4.1 PHP syntax

Команда:

```powershell
PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\Test-PhpSyntax.ps1
```

Результат:

```text
Проверено PHP-файлов: 41. Ошибок нет.
```

Статус: **PASS**.

### 4.2 Deploy

Команда:

```powershell
PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File .\deploy\Deploy-Local.ps1
```

Подтверждено:

```text
Open Server Panel: 6.5.1
Модули: Apache, PHP-8.5, MySQL-8.4
Существующий config/local.php временно сохранен и восстановлен
Домен: https://asu-vch.local
```

На финальном UX-deploy скопированы application-файлы и новые theme-assets modal.

Статус: **PASS**.

### 4.3 Migration 005 — первый запуск

Команда:

```powershell
php C:\OSPanel\home\asu-vch.local\database\install.php
```

Результат:

```text
База данных: asu_vch
Применено миграций: 5
Применена миграция: 005_security_user_archive_restore.sql
Пользователей: 6
Первичная регистрация отключена.
```

Статус: **PASS**.

### 4.4 Migration 005 — повторный запуск

Результат:

```text
База данных: asu_vch
Применено миграций: 5
Новых миграций нет.
Пользователей: 6
Первичная регистрация отключена.
```

Статус: **PASS**. Идемпотентность установщика подтверждена.

### 4.5 RBAC regression

Результат:

```text
OK migration 002
OK system roles: 4
OK system permissions: 19
OK active owners: 1
```

Статус: **PASS**.

### 4.6 Approval regression

Результат:

```text
OK migration 003
OK approval columns: 5
OK approved users: 4
OK active approved owners: 1
```

Статус: **PASS**.

### 4.7 Required password change regression

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

### 4.8 Rejection regression

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

### 4.9 Archive/restore integration check

Команда:

```powershell
php C:\OSPanel\home\asu-vch.local\database\check-security-user-archive-restore.php
```

Результат:

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

### 4.10 Local smoke

Результат:

```text
OK 200 https://asu-vch.local/
OK 200 https://asu-vch.local/health.php
OK 302 https://asu-vch.local/admin/

Smoke test completed successfully.
```

Статус: **PASS**.

## 5. Ручная desktop-приемка — основной жизненный цикл

### 5.1 Создание и подготовка записи

Создан тестовый пользователь `Archive_Test_01`, назначена роль viewer, запись подтверждена и активирована.

Статус: **PASS**.

### 5.2 Валидация основания архивирования

Основание `Коротко` отклонено сообщением о длине 10–500 символов. Введенный текст сохранен, состояние пользователя не изменилось.

Статус: **PASS**.

### 5.3 Успешное архивирование

Подтверждено:

- статус `Архивирован`;
- actor/date/reason в аудите;
- HTML-подобный текст `<b>HTML не исполнять</b>` показан как обычный текст;
- карточка стала read-only;
- осталась только форма восстановления;
- уже открытая сессия пользователя потеряла доступ;
- новый вход архивированного пользователя запрещен общей ошибкой аутентификации.

Статус: **PASS**.

### 5.4 Список и фильтр архива

Подтверждено:

- обычный список и состояние `Не в архиве` исключают архивированную запись;
- счетчик `Архивированные` увеличивается;
- фильтр `archived` возвращает тестовую запись;
- архивный статус отображается отдельно.

Статус: **PASS**.

### 5.5 Валидация и успешное восстановление

Короткое основание восстановления отклонено с сохранением текста. Валидное восстановление выполнено успешно.

Подтверждено:

```text
deleted_at = NULL
is_active = 0
```

Статус после восстановления: `Заблокирован`, не `Активен`. Аудит архивирования сохранен, аудит восстановления добавлен.

Статус: **PASS**.

### 5.6 Явная разблокировка

До разблокировки вход восстановленного пользователя запрещен. После отдельного действия `Разблокировать` вход успешен.

Статус: **PASS**.

### 5.7 Финальное состояние тестовой записи

`Archive_Test_01` повторно архивирован после завершения проверки.

Статус: **PASS**.

## 6. Ручная security-приемка

### 6.1 Viewer privacy

Viewer видит общий статус, имя, логин и роли архивированной записи.

Viewer не видит:

- email и последний вход;
- actor/date/reason archive/restore;
- формы archive/restore;
- редактирование, роли, блокировку и разблокировку.

Статус: **PASS**.

### 6.2 Прямые маршруты

Под viewer:

```text
/admin/users/archive.php → HTTP 403
/admin/users/restore.php → HTTP 403
```

Обе страницы оформлены темой АСУ-ВЧ.

Статус: **PASS**.

### 6.3 CSRF

В archive-форму передан `csrf_token=invalid`.

Результат:

```text
Недействительный CSRF-токен.
HTTP 419
```

Пользователь остался активным и неархивированным, audit не записан.

Статус: **PASS**.

### 6.4 GET safety

Авторизованные GET-запросы к archive/restore маршрутам перенаправляют к списку и не изменяют данные.

Статус: **PASS**.

### 6.5 Последний активный владелец

Администратор попытался архивировать единственного active + approved + nonarchived владельца.

Результат:

```text
Нельзя архивировать последнего активного владельца системы.
```

Владелец сохранил active/nonarchived состояние и рабочую сессию.

Статус: **PASS**.

### 6.6 Тестовые security-записи

Viewer- и administrator-записи после завершения проверок архивированы.

Статус: **PASS**.

## 7. Themed Operation Result Modal v1

### 7.1 Красный modal ошибки

Подтверждено:

- красный glass/modal в стиле темы;
- затемненный и размытый backdrop;
- заголовки `Ошибка операции` и `Операция не выполнена`;
- точный текст защиты последнего владельца;
- кнопка `Понятно`;
- закрытие кнопкой;
- закрытие клавишей `Escape`;
- удаление параметра `result` из URL;
- отсутствие повторного modal после refresh;
- inline-message остается видимым fallback;
- нативный `window.alert()` отсутствует.

Статус: **PASS**.

### 7.2 Зеленый modal успешного архивирования

Подтверждено:

```text
Операция выполнена
Учетная запись архивирована.
```

После закрытия карточка имеет статус `Архивирован`, audit заполнен.

Статус: **PASS**.

### 7.3 Зеленый modal успешного восстановления

Подтверждено:

```text
Операция выполнена
Учетная запись восстановлена и оставлена заблокированной.
```

После закрытия карточка имеет статус `Заблокирован`.

Статус: **PASS**.

### 7.4 Безопасность modal

Подтверждено статическим review:

- сообщения восстанавливаются только по детерминированному token из серверного белого списка;
- произвольный GET-текст не выводится;
- DOM строится через `createElement` и `textContent`;
- `innerHTML` и `eval` не используются;
- неизвестный token не создает modal;
- присутствуют `aria-labelledby` и `aria-describedby`;
- фокус устанавливается на кнопку закрытия.

Статус: **PASS**.

## 8. Дефекты, найденные и устраненные при приемке

В процессе ручной проверки выявлено, что доменная ошибка защиты последнего владельца корректно блокировала изменение данных, но результат не был заметен пользователю из-за сочетания PRG, восстановленной прокрутки браузера и скрытого `.form-message`.

Были проверены промежуточные варианты session/cookie result-state. Финальное решение:

- stateless token из жесткого серверного белого списка;
- тематический modal-компонент;
- видимый inline fallback;
- автоматическое удаление `result` из URL;
- отсутствие межзапросной зависимости от session/cookie для текста сообщения.

После финального исправления весь целевой сценарий повторно прошел.

Открытых дефектов: **0**.

## 9. Финальный результат

```text
PHP syntax: PASS
Deploy: PASS
Migration 005: PASS
Migration idempotency: PASS
RBAC regression: PASS
Approval regression: PASS
Required password change regression: PASS
Rejection regression: PASS
Archive/restore integration: PASS
Smoke: PASS
Desktop lifecycle acceptance: PASS
Privacy and authorization: PASS
CSRF and GET safety: PASS
Last-owner protection: PASS
Themed error modal: PASS
Themed archive success modal: PASS
Themed restore success modal: PASS
Mobile acceptance: OUT OF SCOPE BY CUSTOMER DECISION
Blocking defects: 0
```

Инкремент допускается к final review и созданию Pull Request после фиксации итоговой документации.