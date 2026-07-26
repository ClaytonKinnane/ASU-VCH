# Theme Management System & ASU Light Blue Theme v1 — Approval

## Решение

Заказчик утвердил расширенный инкремент:

```text
Theme Management System & ASU Light Blue Theme v1
```

и разрешил реализацию после Architecture / Specification / Formal Review.

## Хронология

1. Заказчик предоставил `index0004.html` как визуальный референс светлой темы.
2. Первоначально была спроектирована файловая тема с конфигурационным переключением.
3. Заказчик предложил сразу добавить систему управления темами.
4. Заказчик разрешил переработать Architecture / Specification / Review под расширенный scope.
5. После переработки заказчик отправил точное разрешение:

> «Утверждаю Architecture/Specification/Review Theme Management System & ASU Light Blue Theme v1 и разрешаю реализацию.»

## Утверждённый scope

- доверенный реестр тем;
- глобальная активная тема;
- хранение в `system_settings`;
- migration 006 и аудит последнего изменения;
- RBAC через существующие `system.settings.view` и `system.settings.update`;
- интерфейс просмотра и активации тем;
- безопасный runtime asset resolver;
- fallback `asu-blue`;
- общая JavaScript-реализация operation modal;
- новая тема `asu-light-blue`;
- автоматические проверки;
- desktop-приёмка обеих тем.

## Ограничения

В v1 не входят:

- установка ZIP или произвольной темы через браузер;
- редактор CSS/JavaScript;
- внешние URL ресурсов;
- удаление тем;
- per-user theme;
- query/cookie preview;
- автоматическое следование настройкам ОС;
- мобильная приёмка.

## База реализации

```text
main @ 4e1d692807fbac83d86ec1be431df4563bcfacd5
feature/theme-asu-light-blue
```

Дата фиксации решения: `2026-07-26`.
