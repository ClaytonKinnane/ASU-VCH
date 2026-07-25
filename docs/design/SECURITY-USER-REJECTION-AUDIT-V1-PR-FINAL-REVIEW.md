# Security User Rejection Audit v1 — PR final review

## 1. Статус

- Инкремент: `Security User Rejection Audit v1`
- Ветка: `feature/user-rejection-audit`
- База: `main`
- Базовый commit: `8661d34602e07f67b4c0425f62b020f5ec439932`
- Реализация и тестирование завершены
- Блокирующих дефектов не обнаружено
- Мобильная версия исключена заказчиком из критериев приемки

## 2. Проверенные артефакты

### Документация

- `docs/design/SECURITY-USER-REJECTION-AUDIT-V1-DESIGN.md`
- `docs/design/SECURITY-USER-REJECTION-AUDIT-V1-REVIEW.md`
- `docs/decisions/SECURITY-USER-REJECTION-AUDIT-V1-APPROVAL.md`
- `docs/testing/SECURITY-USER-REJECTION-AUDIT-V1-TEST-REPORT.md`

### Схема и проверки

- `database/migrations/004_security_user_rejection_audit.sql`
- `database/check-security-rbac.php`
- `database/check-security-user-rejection.php`

### Серверная логика

- `app/Security/UserRejectionService.php`
- `app/Security/UserDetailRepository.php`
- `app/Security/UserListRepository.php`
- `app/bootstrap.php`

### HTTP/UI

- `public/admin/users/reject.php`
- `public/admin/users/view.php`
- `public/admin/users.php`
- `themes/asu-blue/assets/css/users.css`

## 3. Соответствие утвержденной архитектуре

### 3.1 Модель данных

Migration 004 добавляет:

```text
rejected_by
rejected_at
rejection_reason
```

Подтверждено:

- внешний ключ `rejected_by → users.id`;
- `ON DELETE SET NULL`;
- индексы для субъекта и даты;
- существующие миграции не изменены;
- повторный запуск установщика идемпотентен.

Результат: **PASS**.

### 3.2 RBAC

Добавлено разрешение:

```text
security.users.reject
```

Подтверждено:

- разрешение системное;
- назначено роли `administrator`;
- `system_owner` получает доступ через существующий `system.*.*`;
- viewer получает `403` при прямом обращении к маршруту;
- каталог содержит 19 системных разрешений.

Результат: **PASS**.

### 3.3 Транзакционная операция

`UserRejectionService`:

- валидирует UTF-8 и длину основания до изменения БД;
- запрещает управляющие символы;
- использует транзакцию;
- блокирует строку через `SELECT ... FOR UPDATE`;
- принимает только `approval_status = pending`;
- устанавливает `approval_status = rejected`;
- устанавливает `is_active = 0`;
- записывает субъект, дату и основание;
- защитно очищает `approved_by` и `approved_at`;
- откатывает транзакцию при исключении.

Результат: **PASS**.

### 3.4 Терминальность rejected

Подтверждено:

- повторный reject запрещен;
- approve после reject запрещен;
- set-status/активация после reject запрещена;
- reject после approve запрещен;
- первое успешно принятое решение и аудит сохраняются.

Результат: **PASS**.

### 3.5 HTTP-защита

Маршрут `/admin/users/reject.php`:

- требует `security.users.reject`;
- принимает изменение только через POST;
- GET перенаправляет к списку;
- проверяет CSRF до обработки команды;
- возвращает HTTP 419 для недействительного токена;
- использует PRG;
- не записывает основание в серверный log;
- при исключении показывает нейтральную ошибку.

Результат: **PASS**.

### 3.6 Privacy

`UserDetailRepository` загружает аудит отклонения только при `includeSensitive = true`.

Подтверждено под viewer:

- общий статус доступен;
- субъект, дата и основание скрыты;
- email и основание добавления скрыты;
- формы изменения и обработки отсутствуют.

Результат: **PASS**.

### 3.7 Экранирование

Основание с HTML-подобным текстом выводится через экранирование и не исполняется браузером.

Результат: **PASS**.

### 3.8 Список пользователей

Подтверждено:

- отдельный счетчик `Отклоненные`;
- фильтр `rejected`;
- условие исключает архивированные записи;
- rejected-статус имеет отдельное визуальное состояние.

Результат: **PASS**.

## 4. Регрессия

Выполнены и прошли:

```text
PHP syntax — 37 файлов
Migration installer — 4 миграции
RBAC check — 19 разрешений
User approval check
Required password change check
User rejection check
Local smoke
```

Результат: **PASS**.

## 5. UI review

Desktop-интерфейс соответствует теме АСУ-ВЧ:

- темно-синий фон;
- glass-панели;
- бирюзовые основные действия;
- красная опасная зона отклонения;
- понятные статусы;
- многострочное основание;
- исчезновение операций после терминального решения;
- ссылка на субъект решения.

Мобильная версия не является критерием приемки по явному решению заказчика. Responsive-код не удалялся, но мобильная работа не заявляется как проверенная.

Результат desktop review: **PASS**.

## 6. Замечания final review

В ходе внутреннего review до тестирования были устранены:

1. пустой блок управления доступом для rejected-пользователя;
2. порядок проверки UTF-8 до regex и длины;
3. возврат запрещенных управляющих символов через session-state;
4. недостающая регрессия прямой активации rejected-записи.

После исправлений повторный review блокирующих замечаний не выявил.

## 7. Вне объема

Не реализованы и не должны интерпретироваться как часть PR:

- возврат rejected в pending;
- подтверждение rejected-пользователя;
- удаление или архивирование rejected-записи;
- массовое отклонение;
- email-уведомления;
- история нескольких решений;
- комментарии к решению;
- мобильная приемка;
- сброс пароля;
- добровольная смена пароля.

## 8. Риски

### Низкий риск: исторические rejected-записи

Поля аудита nullable, поэтому запись, созданная напрямую в БД до migration 004, может не иметь субъекта или основания. UI выводит нейтральное состояние и не выдумывает данные.

### Низкий риск: изменение scope мобильной версии

Responsive-стили существуют, но не прошли обязательную приемку. Это явно отражено в test report и не блокирует desktop-релиз.

### Низкий риск: аудит хранится в users

Для v1 хранится одно терминальное решение. Полноценный журнал нескольких решений потребует отдельной сущности и отдельного инкремента.

## 9. Итоговое решение

```text
Architecture compliance: PASS
Security review: PASS
Database review: PASS
RBAC review: PASS
Privacy review: PASS
Regression: PASS
Desktop UI review: PASS
Mobile acceptance: OUT OF SCOPE BY CUSTOMER DECISION
Blocking findings: 0
```

Инкремент готов к созданию Pull Request в `main`.

Merge не должен выполняться без отдельного явного разрешения заказчика после проверки PR.
