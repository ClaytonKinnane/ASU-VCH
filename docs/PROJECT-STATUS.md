# Текущее состояние проекта АСУ-ВЧ

Дата актуализации: `2026-07-29`.

## Стабильный merged baseline

```text
repository: ClaytonKinnane/ASU-VCH
branch: main
merged main commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
final feature documentation HEAD: dd2586dab7a3b3d8b3683d60e2c7eedce002eb54
last functional PR: #15
last completed documentation PR before this refresh: #14
migrations: 001–009
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
```

`merged main commit` — результат merge PR #15. `tested runtime HEAD` — точный application commit, на котором завершены автоматические проверки и desktop-приёмка. Merge commit отдельно не заявляется повторно runtime-протестированным.

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

## Завершённые функциональные Pull Request

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

Documentation-only PR #10, #11, #13 и #14 объединены и не изменяли runtime.

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
- редактируемое дерево draft-версии: добавление, изменение, перемещение, упорядочивание и удаление узлов;
- стабильные organizational elements между версиями;
- привязка типов к версии справочника;
- metadata документов и связи документов с версиями;
- immutable change events, история и сравнение версий;
- optimistic revision checks, транзакции, CSRF и RBAC;
- 7 таблиц, 16 DB triggers и 6 permissions `organization.structures.*`.

## Проверенный baseline

Для Organizational Structure v1 подтверждены:

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

Полная проверка содержимого и веток зафиксирована в [REPOSITORY-AUDIT-2026-07-29.md](REPOSITORY-AUDIT-2026-07-29.md).

```text
branches assessed: 17
main: KEEP
non-main branches assessed: 16
cleanup assessment: SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL
actual branch deletion: NOT PERFORMED
```

## Не реализовано

- карточки военнослужащих и кадровые назначения;
- должности, штатные расписания и staffing runtime;
- общий Documents domain, файлы, приказы и универсальный document workflow;
- медицинский учёт, имущество, транспорт и обучение;
- общий доменный audit log всех областей;
- production deployment и GitHub CI;
- произвольная загрузка, удаление или browser-редактирование тем.

Metadata документов внутри Organization не означает реализацию общего Documents domain. В проект не включаются закрытые или фактические сведения без отдельного утверждения scope и модели защиты.

## Следующий gate

Следующий функциональный инкремент не выбран. Новая задача начинается с Research → Analysis → Architecture → Specification → Formal Review → Approval и отдельной ветки от актуального `main`.
