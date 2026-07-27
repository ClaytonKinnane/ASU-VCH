# Аудит документации АСУ-ВЧ — 2026-07-27

## 1. Основание

Проверен GitHub-репозиторий:

```text
ClaytonKinnane/ASU-VCH
main @ 17169e268e024ab50464ba13f7d0bf0f3d01a87e
```

Аудит выполнен по документационным файлам, присутствующим в изменениях merged PR #1–#9, корневому `README.md`, текущим migrations, deployment scripts, theme registry и последним Test Report.

## 2. Методика

Документы разделены на три класса.

### Актуальные документы

Описывают текущее состояние проекта и должны изменяться вместе с baseline:

```text
README.md
docs/README.md
docs/PROJECT.md
docs/PROJECT-STATUS.md
docs/DEVELOPMENT.md
docs/ENVIRONMENT.md
docs/LOCAL-RUNBOOK.md
docs/DATABASE-CURRENT.md
docs/THEMES.md
docs/ACCESS.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/domains/README.md
docs/migrations/README.md
```

### Целевая архитектура

Описывает утверждённое или исследуемое будущее состояние и может быть шире runtime:

```text
docs/DATABASE.md
docs/STARTER-ADMIN-SPEC.md
docs/ARCHITECTURAL-PATTERNS.md
docs/domains/*.md
docs/erd/**
docs/migrations/*-MIGRATIONS.md
docs/specifications/**
```

Такие документы не считаются описанием уже существующей физической схемы, если это прямо не подтверждено migrations и Test Report.

### Исторические артефакты процесса

```text
docs/design/**
docs/decisions/**
docs/testing/**
```

Они фиксируют Architecture, Review, Approval и Testing на конкретном этапе. Предыдущие формулировки `PENDING`, `Ready for review` или `Merge prohibited` сохраняются как исторические gates. Текущий lifecycle вынесен в `docs/PROJECT-STATUS.md`.

## 3. Найденные несоответствия

До исправления обнаружено:

1. корневой `README.md` сообщал только об инициализации репозитория;
2. `docs/PROJECT.md` утверждал, что БД, регистрация и авторизация не реализованы;
3. `docs/ACCESS.md` утверждал, что серверная регистрация и RBAC не реализованы;
4. `docs/ROADMAP.md` содержал незавершённые задачи первого локального deploy и merge initial-site;
5. `docs/CHANGELOG.md` называл приложение интерфейсным прототипом без БД и авторизации;
6. `docs/DEVELOPMENT.md`, `docs/ENVIRONMENT.md` и `docs/LOCAL-RUNBOOK.md` использовали завершённую ветку `feature/initial-site` как текущую;
7. `docs/ENVIRONMENT.md` описывал устаревший состав deploy и сообщал, что подключение MySQL ещё не реализовано;
8. `docs/THEMES.md` не включал обязательный `css/directories.css`;
9. `docs/domains/README.md` утверждал, что implementation не начата;
10. `docs/migrations/README.md` не отражал фактическую numeric naming convention и migrations 001–008;
11. отсутствовали единые документы текущего project status и фактического database baseline;
12. historical Test Report последних инкрементов содержали корректные для времени review merge-gates, но отсутствовало общее правило их интерпретации после merge.

## 4. Выполненные обновления

Обновлены:

```text
README.md
docs/README.md
docs/PROJECT.md
docs/DEVELOPMENT.md
docs/ENVIRONMENT.md
docs/LOCAL-RUNBOOK.md
docs/THEMES.md
docs/ACCESS.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/domains/README.md
docs/migrations/README.md
```

Добавлены:

```text
docs/PROJECT-STATUS.md
docs/DATABASE-CURRENT.md
docs/DOCUMENTATION-AUDIT-2026-07-27.md
```

## 5. Согласованное текущее состояние

Документация теперь фиксирует:

```text
completed functional PRs: #1–#9
functional baseline: 17169e268e024ab50464ba13f7d0bf0f3d01a87e
migrations: 001–008
system roles: 4
system permissions: 19
built-in themes: 2
active functional increment: none
```

Также зафиксированы:

- GitHub-first изменения кода и документации;
- local-only sync/deploy/testing;
- запрет локальных project commit/push;
- сохранение `config/local.php`;
- обязательные backups перед migrations;
- отдельное merge approval;
- запрет удаления feature-веток без разрешения;
- различие текущей реализации, target architecture и historical process evidence;
- отсутствие утверждения о мобильном PASS для справочных инкрементов.

## 6. Что не изменялось

Не изменялись:

- PHP-код;
- SQL migrations;
- seed data;
- RBAC;
- routes;
- themes и assets;
- deployment scripts;
- исторические Architecture/Review/Approval/Test Report.

Исторические документы сохранены без переписывания фактов и решений задним числом. Их текущий lifecycle однозначно определяется `PROJECT-STATUS.md`.

## 7. Ограничения аудита

- Архитектурные документы могут описывать future scope и не являются доказательством runtime-реализации.
- Состояние локальной БД, число пользователей и локальные credentials не являются repository constants.
- GitHub CI отсутствует; проверка документации выполнялась по repository content и merged history.
- Мобильное тестирование последних справочных инкрементов не выполнялось.

## 8. Результат

```text
Repository documentation inventory: PASS
Living documentation consistency: PASS AFTER CORRECTIONS
Target architecture classification: PASS
Historical artifact preservation: PASS
Runtime changes: NONE
Database changes: NONE
Documentation branch: docs/project-documentation-audit-2026-07-27
Merge: REQUIRES SEPARATE EXPLICIT APPROVAL
```
