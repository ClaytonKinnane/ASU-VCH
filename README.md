# АСУ-ВЧ

Автоматизированная система учёта военнослужащих «Войсковая часть».

## Текущий merged baseline

Стабильное состояние находится в `main`. Актуальный HEAD определяется через `origin/main`:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

Исторические anchors последнего функционального baseline:

```text
latest functional PR: #20
PR #19 merge commit: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #20 merge commit / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
```

Точный SHA текущего `main` не хранится как самореферентное постоянно актуальное поле. Указанные SHA являются историческими merge/test anchors.

## Реализовано

- установка, аутентификация, защищённые сессии и CSRF;
- RBAC и полный пользовательский lifecycle;
- обязательная смена временного пароля;
- три встроенные доверенные темы;
- owner-only read-only справочники:
  - составы военнослужащих и воинские звания;
  - типы организационных элементов;
  - типовые воинские должности;
  - публичные сведения о военно-учётных специальностях;
- Organizational Structure v1: структуры, версии, draft-дерево, документы-основания, история и сравнение версий.

Справочники воинских должностей и ВУС основаны только на утверждённом публичном scope. Они не создают кадровых назначений, не связываются автоматически с персональными данными и не заявляются как полный воинский учёт.

## Локальная среда

```text
Windows 10/11
Open Server Panel 6.5.1
Apache
PHP 8.5.4
MySQL 8.4.x
Windows PowerShell 5.1

repository: C:\Project\ASU-VCH
deploy root: C:\OSPanel\home\asu-vch.local
web root: C:\OSPanel\home\asu-vch.local\public
URL: https://asu-vch.local
```

GitHub является единственным источником истины. Локальный клон используется для синхронизации, deploy и тестирования. Секреты и содержимое `config/local.php` в Git не помещаются.

## Процесс изменений

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing → Commit → Push → Pull Request
→ Final PR Review → separate merge approval → Merge
→ post-merge verification → separate branch deletion approval
```

Merge и удаление веток требуют отдельных явных разрешений владельца проекта.

## Документация

- [Индекс документации](docs/README.md)
- [Текущее состояние проекта](docs/PROJECT-STATUS.md)
- [О проекте](docs/PROJECT.md)
- [Локальный runbook](docs/LOCAL-RUNBOOK.md)
- [Текущее состояние базы данных](docs/DATABASE-CURRENT.md)
- [План разработки](docs/ROADMAP.md)
- [История изменений](docs/CHANGELOG.md)

## Границы тестирования

PR #19 и PR #20 прошли Automated Testing и Manual Desktop Acceptance. Для PR #20 также завершены targeted manual recheck, Final PR Review и post-merge Git verification.

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```
