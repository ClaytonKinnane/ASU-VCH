# О проекте

## Наименование

**Полное:** Автоматизированная система учёта военнослужащих «Войсковая часть».

**Краткое:** АСУ-ВЧ.

## Назначение

АСУ-ВЧ предназначена для автоматизации управления доступом, нормативных справочников, организационных структур и связанных процессов войсковой части.

Проект развивается инкрементально. Каждый новый доменный или технический scope проходит отдельный documentation-first цикл. В открытый репозиторий не включаются закрытые, ограниченные или фактические сведения без отдельного утверждения модели данных и защиты.

## Основные требования

- Репозиторий `ClaytonKinnane/ASU-VCH` является единственным источником истины.
- Актуальный HEAD определяется через `origin/main`, а не хранится в living docs как самореферентный SHA.
- Код и документация изменяются в отдельных ветках GitHub.
- Локальный клон используется только для синхронизации, deploy и тестирования.
- Рабочие данные приложения хранятся в MySQL; секреты и локальные параметры в Git не помещаются.
- Материальные изменения проходят Architecture → Specification → Review → Approval до реализации.
- Merge выполняется только после тестирования, review и отдельного явного разрешения владельца проекта.
- Ветки не удаляются без отдельного явного разрешения.

## Реализованное состояние

### Платформа и безопасность

- установка приложения и последовательные migrations 001–009;
- первичное создание единственного владельца системы;
- отключение bootstrap-регистрации после создания владельца;
- вход, выход, защищённые сессии и CSRF;
- RBAC с четырьмя системными ролями и 25 системными permissions;
- полный пользовательский lifecycle: создание, approval, activation, редактирование, роли, block/unblock, rejection, archive/restore;
- обязательная смена временного пароля;
- аудит критических пользовательских операций;
- тематические страницы HTTP 403 и operation-result modal.

### Темы

- доверенный статический реестр тем;
- глобальное хранение активной темы в БД;
- безопасный default/fallback `asu-blue`;
- три встроенные темы: `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova`;
- восемь CSS-assets общего контракта, включая `organization.css`;
- локальные SVG-assets темы `Евгения Ростова`;
- desktop-приёмка всех трёх тем в реализованных разделах.

### Справочники

- стартовая страница справочников;
- read-only нормативный справочник составов военнослужащих и воинских званий;
- read-only нормативно-методический справочник типов организационных элементов;
- поиск, фильтры и профильные integration checker'ы.

### Organization

Organizational Structure v1 реализует:

- создание и lifecycle организационных структур;
- версии `draft`, `approved`, `active`, `cancelled`;
- редактируемое дерево draft-версии;
- стабильные элементы между версиями;
- привязку типов к версии справочника;
- metadata документов и связи с версиями;
- историю изменений и сравнение версий;
- транзакционные операции, revision checks, CSRF и RBAC;
- 7 таблиц, 16 DB triggers и 6 permissions `organization.structures.*`.

Реализованная модель предназначена для контролируемого ведения организационной структуры, но не разрешает размещение закрытых или фактических данных без отдельного утверждения.

## Контрольные точки

Актуальный repository HEAD:

```powershell
git fetch --prune origin
git rev-parse origin/main
```

Исторические anchors:

```text
last completed documentation PR before cleanup closure: #17
last completed documentation merge before cleanup closure: c67632674dce216bb23338de898bf0733a8e42c0
last functional PR: #15
last functional merge commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
migrations: 9
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
```

PR #16 и PR #17 изменили только документацию и не создали нового functional/runtime baseline.

## Repository governance

Завершённая административная операция зафиксирована в [Repository Cleanup Closure 2026-07-31](REPOSITORY-CLEANUP-2026-07-31.md).

```text
PR #17 merge: PASS
post-merge synchronization: PASS
corrected inventory before cleanup: 19 branches / 18 non-main
authorized cleanup batch: 18 remote non-main branches
cleanup result: 18 / 18 deleted
terminal cleanup verification snapshot: main only
local branch set: 12 / 12 unchanged
REMOTE_BRANCH_CLEANUP_STATUS=PASS
```

`main only` относится к terminal snapshot 2026-07-31. Позднее созданная `docs/post-pr17-branch-cleanup-closure` и любые будущие branches не входили в исторический batch и управляются отдельно.

Historical evidence не переписывается:

- [Repository audit 2026-07-30](REPOSITORY-AUDIT-2026-07-30.md) — post-PR16 pre-reconciliation snapshot;
- [Repository audit 2026-07-29](REPOSITORY-AUDIT-2026-07-29.md) — pre-refresh snapshot.

Текущее количество branches определяется динамически:

```powershell
git ls-remote --heads origin
```

Любой future remote cleanup требует fresh inventory и отдельного owner approval. Local branch deletion требует отдельного scope и approval.

## Не реализовано

- карточки военнослужащих;
- должности, штатные расписания и кадровые назначения;
- общий Documents domain, файлы, приказы и универсальный document workflow;
- медицинский учёт, имущество, транспорт и обучение;
- общий неизменяемый audit log всех доменов;
- production deployment и GitHub CI;
- произвольная установка тем и browser-редактор CSS/JS.

Metadata документов внутри Organization не считается реализацией общего Documents domain.

## Границы тестирования

Organizational Structure v1 прошёл автоматическое тестирование и ручную desktop-приёмку.

Post-PR17 Branch Cleanup Closure является documentation-only и не изменяет runtime, deploy или database.

```text
runtime/database retest: NOT RUN / NOT REQUIRED
HTTP/application browser testing: NOT RUN / NOT REQUIRED
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```
