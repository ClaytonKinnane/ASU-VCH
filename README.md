# АСУ-ВЧ

Автоматизированная система учёта военнослужащих «Войсковая часть».

## Текущий baseline

Стабильное объединённое состояние находится в ветке `main`.

```text
repository: ClaytonKinnane/ASU-VCH
branch: main
merged main commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
last functional PR: #15
migrations: 001–009
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
```

`merged main commit` фиксирует merge PR #15. `tested runtime HEAD` — точный application commit, на котором выполнялись автоматические проверки и desktop-приёмка.

## Реализовано

- установка, аутентификация, защищённые сессии и CSRF;
- RBAC и полный пользовательский lifecycle;
- три встроенные доверенные темы;
- справочники воинских званий и типов организационных элементов;
- Organizational Structure v1: структуры, версии, дерево черновика, связи с документами, история и сравнение версий.

## Локальная среда

```text
Windows 10/11
Open Server Panel 6.5.1
Apache
PHP 8.5.4
MySQL 8.4.x
Windows PowerShell 5.1
```

```text
репозиторий: C:\Project\ASU-VCH
развёртывание: C:\OSPanel\home\asu-vch.local
web root: C:\OSPanel\home\asu-vch.local\public
URL: https://asu-vch.local
```

GitHub является единственным источником истины. Локальный клон используется для синхронизации, deploy и тестирования. Секреты и содержимое `config/local.php` в Git не помещаются.

## Процесс изменений

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing → Commit → Push → Pull Request → Merge
```

Merge и удаление веток требуют отдельных явных разрешений владельца проекта.

## Документация

- [Индекс документации](docs/README.md)
- [Текущее состояние проекта](docs/PROJECT-STATUS.md)
- [Локальный runbook](docs/LOCAL-RUNBOOK.md)
- [Текущее состояние базы данных](docs/DATABASE-CURRENT.md)
- [Repository audit 2026-07-29](docs/REPOSITORY-AUDIT-2026-07-29.md)

## Границы тестирования

Organizational Structure v1 прошёл автоматические проверки и ручную desktop-приёмку во всех трёх темах.

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```
