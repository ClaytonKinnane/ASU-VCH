# Текущее состояние проекта АСУ-ВЧ

Дата актуализации: `2026-07-30`.

## Репозиторий и контрольные точки

Актуальный HEAD стабильной ветки определяется через `origin/main`:

```powershell
git fetch --prune origin
git rev-parse origin/main
git rev-list --left-right --count main...origin/main
```

Устойчивые исторические anchors:

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
last completed documentation PR before reconciliation: #16
last completed documentation merge before reconciliation: 72630757c1a72a6bd971cf819cff9bdd36c148bf
last functional PR: #15
last functional merge commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
final feature documentation HEAD: dd2586dab7a3b3d8b3683d60e2c7eedce002eb54
migrations: 001–009
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
```

PR #16 был documentation-only: изменены только `README.md` и `docs/**`; runtime, deploy и БД не изменялись. Поэтому последний функциональный tested baseline остаётся привязан к PR #15 и `tested runtime HEAD`.

## Последний функциональный инкремент

```text
increment: Organizational Structure v1
feature branch: feature/organizational-structure-v1
pull request: #15 MERGED
merge commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
final feature documentation HEAD: dd2586dab7a3b3d8b3683d60e2c7eedce002eb54
automated testing: PASS
manual desktop acceptance: PASS
final review: PASS
blocking findings: 0
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```

## Завершённые Pull Request

Функциональные PR:

| PR | Инкремент | Статус |
|---:|---|---|
| #1 | Базовый сайт, RBAC и управление пользователями v1 | MERGED |
| #2 | Обязательная смена временного пароля v1 | MERGED |
| #3 | Аудит отклонения пользователей v1 | MERGED |
| #4 | Архивирование и восстановление пользователей v1 | MERGED |
| #5 | Управление темами и АСУ Светлая синяя v1 | MERGED |
| #6 | Выравнивание радиусов и свечения плиток | MERGED |
| #7 | Стартовая страница справочников v1 | MERGED |
| #8 | Справочник составов и воинских званий v1 | MERGED |
| #9 | Справочник типов организационных элементов v1 | MERGED |
| #12 | Evgeniya Rostova Theme v1 | MERGED |
| #15 | Organizational Structure v1 | MERGED |

Documentation-only PR #10, #11, #13, #14 и #16 объединены и не изменяли runtime.

## Реализованные возможности

### Security и пользователи

- bootstrap первого владельца и отключение публичной регистрации после его создания;
- аутентификация, защищённые сессии, logout и CSRF;
- четыре системные роли и 25 системных permissions;
- создание, approval, activation, редактирование, роли, block/unblock;
- обязательная смена временного пароля;
- rejection и archive/restore с обязательным основанием и аудитом;
- защита последнего активного владельца и privacy-ограничения.

### Themes

- доверенный статический реестр и глобальная активная тема в БД;
- default/fallback `asu-blue`;
- три встроенные темы: `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova`;
- восемь обязательных CSS-assets каждой темы, включая `organization.css`;
- четыре локальных SVG-assets темы `Евгения Ростова`;
- desktop-приёмка Organizational Structure во всех трёх темах.

### Directories

- owner-only landing page;
- справочник воинских званий: 2 источника, 6 составов, 20 уровней;
- справочник типов организационных элементов: 4 источника, 6 классов, 28 типов, 32 связи;
- read-only маршруты, поиск, фильтры и профильные checker'ы.

### Organizational Structure v1

- создание, изменение, архивирование и восстановление структур;
- версии со статусами `draft`, `approved`, `active`, `cancelled`;
- создание новой версии на основе действующей либо последней отменённой;
- редактируемое дерево draft-версии;
- стабильные organizational elements между версиями;
- привязка типов к версии справочника;
- metadata документов и связи документов с версиями;
- immutable change events, история и сравнение версий;
- optimistic revision checks, транзакции, CSRF и RBAC;
- 7 таблиц, 16 DB triggers и 6 permissions `organization.structures.*`.

## Проверенный functional baseline

```text
source/deploy UI contract checks: 64 PASS / 0 FAIL
organization integration checks: 58 PASS / 0 FAIL
PHP lint in tested deploy: 104 files / 0 errors
applied migrations: 9
new migrations after repeat installer: none
system roles: 4
system permissions: 25
HTTP smoke: / 200, /health.php 200, /admin/ 302
automated testing: PASS
manual desktop acceptance: PASS
```

## Repository audit

Актуальный post-PR16 snapshot зафиксирован в [REPOSITORY-AUDIT-2026-07-30.md](REPOSITORY-AUDIT-2026-07-30.md). Исторический pre-refresh snapshot сохранён в [REPOSITORY-AUDIT-2026-07-29.md](REPOSITORY-AUDIT-2026-07-29.md).

```text
pre-reconciliation snapshot: 18 branches / 17 non-main
pre-reconciliation non-main assessed: 17
technically safe to delete after separate approval: 17
active reconciliation branch: KEEP UNTIL OWN PR/MERGE AND POST-MERGE CLEANUP APPROVAL
actual branch deletion: NOT PERFORMED
```

После merge reconciliation-инкремента требуется fresh read-only inventory всех существующих non-main веток. Только он может служить основанием для отдельного решения об удалении.

## Не реализовано

- карточки военнослужащих и кадровые назначения;
- должности, штатные расписания и staffing runtime;
- общий Documents domain, файлы, приказы и универсальный document workflow;
- медицинский учёт, имущество, транспорт и обучение;
- общий доменный audit log всех областей;
- production deployment и GitHub CI;
- произвольная загрузка, удаление или browser-редактирование тем.

Metadata документов внутри Organization не означает реализацию общего Documents domain. В проект не включаются закрытые или фактические сведения без отдельного утверждения scope и модели защиты.

## Cleanup gate

Branch cleanup не входит в документационную реализацию и не разрешён текущими process-artifacts. После merge reconciliation-инкремента обязательны fresh fetch/prune, полный read-only inventory, повторная оценка active reconciliation branch и отдельное явное разрешение владельца проекта на точный список удаляемых refs.
