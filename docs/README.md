# Документация АСУ-ВЧ

## Актуальные документы

Эти документы описывают текущий merged baseline и должны обновляться при изменении состояния проекта:

- [Текущее состояние проекта](PROJECT-STATUS.md)
- [О проекте](PROJECT.md)
- [Правила разработки](DEVELOPMENT.md)
- [Среда разработки и запуска](ENVIRONMENT.md)
- [Локальный runbook](LOCAL-RUNBOOK.md)
- [Текущее состояние базы данных](DATABASE-CURRENT.md)
- [Темы оформления](THEMES.md)
- [Управление доступом](ACCESS.md)
- [План разработки](ROADMAP.md)
- [История изменений](CHANGELOG.md)
- [Архитектурные паттерны](ARCHITECTURAL-PATTERNS.md)
- [Repository audit 2026-07-29](REPOSITORY-AUDIT-2026-07-29.md)

Каноническое текущее состояние фиксируют `PROJECT-STATUS.md` и `DATABASE-CURRENT.md`. Фактическую схему определяют `database/migrations/*.sql`, installer и профильные integration checker'ы.

## Целевая архитектура

Документы этой группы описывают утверждённую или исследуемую целевую модель и могут быть шире реализованного runtime:

- [Целевая архитектура базы данных](DATABASE.md)
- [Стартовая административная спецификация](STARTER-ADMIN-SPEC.md)
- [Предметные области](domains/README.md)
- [ERD](erd/)
- [Спецификации миграций](migrations/README.md)

## Документы инкрементов

```text
docs/architecture/      Architecture
docs/specifications/    Specification
docs/reviews/           Formal Review
docs/decisions/         Approval и зафиксированные решения
docs/implementation/    Implementation records
docs/design/            исторические объединённые design/review/addendum документы
docs/testing/           Test Plan, Test Attempts, Test Reports и validation reports
```

Эти файлы являются историческими process-artifacts. Формулировки `PENDING`, `Ready for review`, `Merge prohibited` или прежние baseline-значения внутри закрытого артефакта отражают соответствующий gate и не заменяют текущее состояние проекта.

Исторический аудит до Organizational Structure v1 сохранён в [DOCUMENTATION-AUDIT-2026-07-27.md](DOCUMENTATION-AUDIT-2026-07-27.md). Актуальная проверка содержимого репозитория, living documentation и всех веток зафиксирована в [REPOSITORY-AUDIT-2026-07-29.md](REPOSITORY-AUDIT-2026-07-29.md).

## Правила актуальности

1. GitHub-репозиторий является единственным источником истины.
2. Завершённая feature/docs-ветка может упоминаться только как историческая.
3. Текущие возможности подтверждаются merged-кодом, migrations и результатами тестирования.
4. Исторические спецификации не переписываются задним числом; новое решение оформляется addendum или новым документом.
5. Секреты, реальные пароли и содержимое `config/local.php` не включаются в документацию.
6. Мобильное тестирование не объявляется выполненным, если оно было исключено из scope.
