# Security User Archive & Restore v1 — PR final review

## 1. Статус

- Инкремент: `Security User Archive & Restore v1`
- UX-дополнение: `Themed Operation Result Modal v1`
- Ветка: `feature/user-archive-restore`
- База: `main`
- Базовый commit: `859a2dc7462a41a4e630b637485bf346437ccdd0`
- Реализация и тестирование завершены
- Блокирующих дефектов не обнаружено
- Мобильная версия исключена заказчиком из критериев приемки

## 2. Проверенные артефакты

### Документация

- `docs/design/SECURITY-USER-ARCHIVE-RESTORE-V1-DESIGN.md`
- `docs/design/SECURITY-USER-ARCHIVE-RESTORE-V1-REVIEW.md`
- `docs/decisions/SECURITY-USER-ARCHIVE-RESTORE-V1-APPROVAL.md`
- `docs/testing/SECURITY-USER-ARCHIVE-RESTORE-V1-TEST-REPORT.md`

### Схема и проверки

- `database/migrations/005_security_user_archive_restore.sql`
- `database/check-security-rbac.php`
- `database/check-security-user-archive-restore.php`

### Серверная логика

- `app/Security/UserArchiveRestoreService.php`
- `app/Security/UserDetailRepository.php`
- `app/Security/UserListRepository.php`
- `app/bootstrap.php`

### HTTP/UI

- `public/admin/users/archive.php`
- `public/admin/users/restore.php`
- `public/admin/users/view.php`
- `public/admin/users.php`
- `themes/asu-blue/assets/css/users.css`
- `themes/asu-blue/assets/css/operation-result-modal.css`
- `themes/asu-blue/assets/js/operation-result-modal.js`

## 3. Соответствие утвержденной архитектуре

### 3.1 Модель данных

Migration 005 добавляет:

```text
archived_by
last_archived_at
archive_reason
restored_by
restored_at
restore_reason
```

Подтверждено:

- `deleted_at` остается каноническим признаком текущего архива;
- `last_archived_at` сохраняет время последнего архивирования после restore;
- внешние ключи actor-полей используют `ON DELETE SET NULL`;
- добавлены необходимые индексы;
- существующие migration не переписаны;
- повторный запуск установщика идемпотентен.

Результат: **PASS**.

### 3.2 RBAC

Переиспользуются существующие разрешения:

```text
security.users.archive
security.users.restore
```

Подтверждено:

- разрешения назначены роли `administrator`;
- `system_owner` имеет доступ через существующую политику;
- viewer получает HTTP 403 на прямых archive/restore маршрутах;
- каталог сохраняет 19 системных разрешений.

Результат: **PASS**.

### 3.3 Архивирование

`UserArchiveRestoreService::archive()`:

- валидирует UTF-8, длину 10–500 и управляющие символы;
- использует транзакцию;
- блокирует target row через `SELECT ... FOR UPDATE`;
- запрещает самоархивирование;
- запрещает повторное архивирование;
- защищает последнего active + approved + nonarchived владельца;
- устанавливает `deleted_at` и `last_archived_at`;
- устанавливает `is_active = 0`;
- записывает actor и reason;
- сохраняет роли, пароль, login/email и approval/rejection audit;
- откатывает транзакцию при исключении.

Результат: **PASS**.

### 3.4 Восстановление

`UserArchiveRestoreService::restore()`:

- принимает только архивированную запись;
- валидирует основание;
- использует транзакцию и row lock;
- очищает `deleted_at`;
- всегда оставляет `is_active = 0`;
- записывает actor/date/reason восстановления;
- сохраняет archive audit и workflow;
- не возвращает доступ автоматически.

Матрица подтверждена:

```text
approved → blocked
pending → pending/inactive
rejected → rejected/inactive
```

Результат: **PASS**.

### 3.5 Защита сессии и login

Текущая проверка пользователя включает:

```text
is_active = 1
approval_status = approved
deleted_at IS NULL
```

Подтверждено:

- уже открытая сессия теряет доступ после archive;
- новый login архивированной записи запрещен;
- restored approved-пользователь не может войти до отдельной разблокировки;
- явная разблокировка возвращает доступ.

Результат: **PASS**.

### 3.6 HTTP-защита

Маршруты archive/restore:

- требуют соответствующее permission;
- изменяют данные только через POST;
- GET не выполняет mutation;
- проверяют CSRF до доменной операции;
- возвращают HTTP 419 для недействительного token;
- используют PRG;
- не записывают reason/PII в server log;
- показывают нейтральное сообщение при server exception.

Результат: **PASS**.

### 3.7 Read-only и privacy

Подтверждено:

- архивированная карточка read-only;
- update, roles, status, approve/reject и повторный archive недоступны;
- authorized actor видит archive/restore audit;
- viewer видит общий статус и роли, но не sensitive audit, email, последний вход и формы mutation.

Результат: **PASS**.

### 3.8 Список пользователей

Подтверждено:

- обычный список показывает только `deleted_at IS NULL`;
- состояние по умолчанию называется `Не в архиве`;
- отдельные счетчик и фильтр `Архивированные`;
- архивная запись имеет отдельный visual status;
- login/email архивированной записи не освобождаются.

Результат: **PASS**.

## 4. Themed Operation Result Modal v1

### 4.1 Источник результата

Результат archive/restore преобразуется в детерминированный 32-символьный token по серверному белому списку.

Подтверждено:

- произвольное сообщение из URL не принимается;
- неизвестный token не создает modal;
- точные сообщения восстанавливаются только из catalog;
- parameter `result` удаляется через `history.replaceState`;
- после refresh modal не повторяется.

Результат: **PASS**.

### 4.2 DOM и XSS

Modal-компонент:

- строит DOM через `document.createElement`;
- устанавливает текст через `textContent`;
- не использует `innerHTML` и `eval`;
- получает только `success|error` и фиксированный text;
- нативный `window.alert()` удален.

Результат: **PASS**.

### 4.3 Доступность и управление

Подтверждено:

- используется нативный `<dialog>`;
- присутствуют `aria-labelledby` и `aria-describedby`;
- focus устанавливается на кнопку;
- кнопка закрывает modal;
- `Escape` закрывает modal;
- backdrop блокирует и визуально отделяет страницу;
- inline-message остается видимым fallback.

Результат: **PASS**.

### 4.4 Визуальный review

Desktop modal соответствует теме АСУ-ВЧ:

- error: dark red glass background, red border/glow, icon `!`, button `Понятно`;
- success: teal/green glass background, green border/glow, check icon, button `Закрыть`;
- затемненный и размытый backdrop;
- читаемый заголовок и message;
- modal центрирован и не конфликтует с карточкой.

Результат: **PASS**.

## 5. Регрессия

Выполнены и прошли:

```text
PHP syntax — 41 файл
Migration installer — 5 миграций
Migration 005 idempotency
RBAC check — 19 разрешений
User approval check
Required password change check
User rejection check
User archive/restore check
Local smoke
```

Результат: **PASS**.

## 6. Замечания final review

В ходе реализации и приемки устранены:

1. невидимое доменное сообщение при блокировке archive последнего владельца;
2. восстановление прежней позиции прокрутки браузером после PRG;
3. зависимость результата операции от ненадежного session/cookie межзапросного state;
4. скрытый inline fallback из-за `.form-message { display: none; }`;
5. нативный браузерный `alert()` заменен на themed modal;
6. добавлена безопасная stateless token-map с жестким белым списком;
7. добавлены keyboard/accessibility сценарии modal.

После финального исправления повторная desktop-приемка прошла полностью.

Открытых блокирующих findings: **0**.

## 7. Вне объема

Не реализованы и не должны интерпретироваться как часть PR:

- физическое удаление пользователей;
- освобождение login/email архивированных записей;
- автоматическая активация после restore;
- массовые archive/restore операции;
- уведомления по email;
- полный журнал всех исторических циклов;
- отдельная сущность event log;
- мобильная приемка;
- управление сессиями как отдельный модуль.

Полный журнал изменений пользователя остается следующим самостоятельным инкрементом.

## 8. Риски

### Низкий риск: хранится последний цикл

Migration 005 хранит текущие archive/restore audit-поля в `users`. Для полноценной истории нескольких циклов требуется отдельная таблица событий в будущем инкременте.

### Низкий риск: детерминированные result tokens

Tokens не являются секретами и используются только как безопасные идентификаторы фиксированных UI-сообщений. Они не дают доступ к операции и не заменяют CSRF/RBAC.

### Низкий риск: JavaScript отключен

При отключенном JavaScript modal не откроется, но inline-message остается видимым fallback. Состояние данных и серверная защита не зависят от JavaScript.

### Низкий риск: мобильная область

Responsive-код существует, но мобильная приемка исключена заказчиком и не заявляется как проверенная.

## 9. Итоговое решение

```text
Architecture compliance: PASS
Security review: PASS
Database review: PASS
RBAC review: PASS
Privacy review: PASS
Session/login review: PASS
Archive/restore workflow: PASS
Themed modal security: PASS
Themed modal accessibility: PASS
Regression: PASS
Desktop UI review: PASS
Mobile acceptance: OUT OF SCOPE BY CUSTOMER DECISION
Blocking findings: 0
```

Инкремент готов к созданию Pull Request в `main`.

Merge не должен выполняться без отдельного явного разрешения заказчика после проверки PR.