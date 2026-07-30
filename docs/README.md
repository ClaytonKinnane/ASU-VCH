# Документация АСУ-ВЧ

## Актуальные документы

Эти документы описывают текущий merged baseline и обновляются при изменении состояния проекта:

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
- [Repository audit 2026-07-30](REPOSITORY-AUDIT-2026-07-30.md)

Каноническое функциональное состояние фиксируют `PROJECT-STATUS.md` и `DATABASE-CURRENT.md`. Фактическую схему определяют `database/migrations/*.sql`, installer и профильные integration checker'ы.

Актуальный repository HEAD определяется через `origin/main`:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

Living documentation не хранит самореферентный SHA как постоянно актуальное значение `current main HEAD`. Точные SHA используются только как исторические merge/test anchors и в датированных audit snapshots.

## Repository audits

- [Repository audit 2026-07-30](REPOSITORY-AUDIT-2026-07-30.md) — post-PR16 snapshot, текущая доказательная база для будущего cleanup gate;
- [Repository audit 2026-07-29](REPOSITORY-AUDIT-2026-07-29.md) — исторический pre-refresh snapshot;
- [Documentation audit 2026-07-27](DOCUMENTATION-AUDIT-2026-07-27.md) — исторический аудит до Organizational Structure v1.

Техническая классификация веток как безопасных для удаления не является разрешением на фактическое удаление. Перед cleanup требуется fresh post-merge inventory и отдельное явное решение владельца проекта.

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

## Правила актуальности

1. GitHub-репозиторий является единственным источником истины.
2. Текущий HEAD определяется через `origin/main`; exact SHA в living docs не используется как самореферентный current-state marker.
3. Завершённая feature/docs-ветка может упоминаться только как историческая либо как объект датированного branch audit.
4. Текущие возможности подтверждаются merged-кодом, migrations и результатами тестирования.
5. Исторические спецификации и audits не переписываются задним числом; новое состояние оформляется addendum или новым документом.
6. Секреты, реальные пароли и содержимое `config/local.php` не включаются в документацию.
7. Мобильное тестирование не объявляется выполненным, если оно было исключено из scope.
8. Merge и branch cleanup требуют отдельных явных разрешений.
