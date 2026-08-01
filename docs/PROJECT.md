# О проекте

## Наименование

**Полное:** Автоматизированная система учёта военнослужащих «Войсковая часть».

**Краткое:** АСУ-ВЧ.

## Назначение

АСУ-ВЧ предназначена для автоматизации управления доступом, нормативных справочников, организационных структур и связанных процессов войсковой части.

Проект развивается инкрементально. Каждый новый доменный или технический scope проходит отдельный documentation-first цикл. В открытый репозиторий не включаются закрытые, ограниченные или фактические сведения без отдельного утверждения модели данных и защиты.

## Основные требования

- GitHub-репозиторий `ClaytonKinnane/ASU-VCH` является единственным источником истины.
- Актуальный HEAD определяется через `origin/main`, а не хранится в living docs как самореферентный SHA.
- Код и документация изменяются только в отдельных ветках.
- Локальный клон используется для синхронизации, deploy и тестирования.
- Рабочие данные приложения хранятся в MySQL; секреты и локальные параметры в Git не помещаются.
- Материальные изменения проходят Research → Analysis → Architecture → Specification → Review → Approval до реализации.
- Merge выполняется после Testing, Final PR Review и отдельного разрешения владельца.
- Удаление веток выполняется только после post-merge verification, fresh inventory и отдельного разрешения.

## Реализованное состояние

### Платформа и безопасность

- последовательные migrations 001–011;
- bootstrap единственного первого владельца;
- отключение публичной регистрации после создания владельца;
- вход, выход, защищённые сессии и CSRF;
- RBAC с четырьмя системными ролями и 25 permissions;
- полный пользовательский lifecycle;
- обязательная смена временного пароля;
- аудит критических пользовательских операций;
- тематические HTTP 403 и operation-result modal.

### Темы

- trusted static registry;
- глобальная active theme в БД;
- безопасный default/fallback `asu-blue`;
- три встроенные темы: `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova`;
- девять обязательных CSS-assets, включая профильный VUS stylesheet и `organization.css`;
- локальные SVG-assets темы `Евгения Ростова`;
- desktop acceptance затронутых интерфейсов во всех трёх темах.

### Справочники

Реализованы owner-only read-only справочники:

- составы военнослужащих и воинские звания;
- типы организационных элементов;
- типовые воинские должности;
- публичные сведения о военно-учётных специальностях.

Общие свойства:

- GET-only пользовательские маршруты;
- prepared statements и escaped output;
- поиск и фильтры;
- официальные источники и evidence metadata;
- отсутствие runtime scraping/import;
- отсутствие mutation UI.

Каталог должностей не создаёт кадровые назначения и не связывает типы автоматически со званиями. Каталог ВУС не связывается с должностями, званиями, ВВСТ или персональными данными и не заявляется как полный воинский учёт.

### Organizational Structure v1

- lifecycle структур и версий;
- редактируемое draft-дерево;
- stable elements между версиями;
- документы-основания;
- история и сравнение;
- транзакции, optimistic revisions, CSRF и RBAC;
- 7 таблиц, 16 triggers и 6 permissions `organization.structures.*`.

## Контрольные точки

```text
latest functional PR: #20
latest completed documentation PR: #21
PR #19 merge: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #20 merge / functional refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime: 9db06c4a26066ca25dc36c627c1236089a3c1238
PR #21 merge: f5b53f2ee4453f293b58cbe486e0943ab602335b
migrations: 11
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
active documentation increment after closure: none
```

Documentation-only commits после tested runtime не объявляются runtime-протестированными.

## Repository governance

PR #21 завершил documentation baseline refresh после PR #19/#20. Repeat Documentation Validation и Final PR Review прошли, merge выполнен отдельным разрешением владельца, post-merge Git verification завершён PASS.

После отдельного cleanup approval удалены три remote branches и 13 merged local feature branches. Terminal verification 2026-08-01 зафиксировала:

```text
remote branches: main only
local branches: main only
local main = origin/main = f5b53f2ee4453f293b58cbe486e0943ab602335b
working tree: clean
force deletion used: no
```

Это immutable датированный snapshot. Позднее созданные утверждённые branches имеют собственный lifecycle и не противоречат этому evidence.

Текущее состояние определяется динамически:

```powershell
git fetch --prune origin
git rev-parse origin/main
git ls-remote --heads origin
git branch -vv
git status --short
```

Open Pull Requests и Issues проверяются в GitHub. Living docs не зависят от transient branch/PR state текущего документационного workflow.

Evidence: [Post-PR21 Merge and Cleanup Closure 2026-08-01](POST-PR21-MERGE-CLEANUP-CLOSURE-2026-08-01.md).

## Не реализовано

- карточки военнослужащих и персональный воинский учёт;
- штатные расписания и кадровые назначения;
- общий Documents domain, файлы и универсальный workflow приказов;
- медицинский учёт, имущество, транспорт и обучение как рабочие домены;
- общий immutable audit log всех доменов;
- production deployment и GitHub CI;
- произвольная установка тем и browser-редактор CSS/JS.

Metadata документов внутри Organization не считается реализацией общего Documents domain.

## Границы тестирования

PR #19 и PR #20 прошли Automated Testing и Manual Desktop Acceptance. Mobile runtime testing было исключено из утверждённого scope.

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```
