# Текущее состояние проекта АСУ-ВЧ

Дата актуализации: `2026-07-31`.

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
last completed documentation PR before cleanup closure: #17
last completed documentation merge before cleanup closure: c67632674dce216bb23338de898bf0733a8e42c0
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

PR #16 и PR #17 были documentation-only: изменены только `README.md` и `docs/**`; runtime, deploy и БД не изменялись. Поэтому последний функциональный tested baseline остаётся привязан к PR #15 и `tested runtime HEAD`.

## Последний функциональный инкремент

```text
increment: Organizational Structure v1
historical feature branch: feature/organizational-structure-v1
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

Документационные PR #10, #11, #13, #14, #16 и #17 объединены и не изменяли runtime. PR #17 `docs: reconcile repository state after PR #16` объединён методом `merge`, merge commit — `c67632674dce216bb23338de898bf0733a8e42c0`.

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

## Repository cleanup status

Historical evidence:

- [Repository cleanup closure 2026-07-31](REPOSITORY-CLEANUP-2026-07-31.md) — completed administrative outcome;
- [Repository audit 2026-07-30](REPOSITORY-AUDIT-2026-07-30.md) — historical post-PR16 pre-reconciliation snapshot;
- [Repository audit 2026-07-29](REPOSITORY-AUDIT-2026-07-29.md) — historical pre-refresh snapshot.

Завершённая последовательность:

```text
PR #17: MERGED
post-merge local synchronization: PASS
fresh post-merge inventory: PASS
corrected inventory before cleanup: 19 total / 18 non-main
authorized cleanup batch: 18 remote non-main branches
cleanup result: 18 / 18 DELETED
terminal verification snapshot 2026-07-31: main only
local branches: 12 before / 12 after / unchanged
main HEAD during cleanup: unchanged
divergence after cleanup: 0 0
working tree after cleanup: clean
REMOTE_BRANCH_CLEANUP_STATUS=PASS
```

`main only` описывает terminal verification snapshot, а не бессрочное текущее состояние. Позднее созданные branches имеют собственный lifecycle. Текущее количество remote branches определяется read-only командой:

```powershell
git ls-remote --heads origin
```

Любое последующее remote branch deletion требует отдельного явного owner approval. Удаление локальных branches требует отдельного scope и approval.

## Не реализовано

- карточки военнослужащих и кадровые назначения;
- должности, штатные расписания и staffing runtime;
- общий Documents domain, файлы, приказы и универсальный document workflow;
- медицинский учёт, имущество, транспорт и обучение;
- общий доменный audit log всех областей;
- production deployment и GitHub CI;
- произвольная загрузка, удаление или browser-редактирование тем.

Metadata документов внутри Organization не означает реализацию общего Documents domain. В проект не включаются закрытые или фактические сведения без отдельного утверждения scope и модели защиты.

## Границы текущего документационного инкремента

Post-PR17 Branch Cleanup Closure является documentation-only:

```text
runtime/deploy/database changes: none
runtime/database retest: NOT RUN / NOT REQUIRED
HTTP/application browser testing: NOT RUN / NOT REQUIRED
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```

Microsoft Edge применялся только для GitHub authentication recovery перед cleanup, а не для application browser acceptance.
