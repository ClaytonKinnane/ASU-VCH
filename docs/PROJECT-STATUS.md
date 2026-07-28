# Текущее состояние проекта АСУ-ВЧ

Дата актуализации: `2026-07-28`.

## Стабильный функциональный baseline

```text
repository: ClaytonKinnane/ASU-VCH
branch: main
runtime baseline commit: 967546087868f0d7eb347b186f7798015d268811
last functional PR: #12
last documentation PR: #13
migrations: 001–008
system roles: 4
system permissions: 19
built-in themes in main: 3
```

Поле `runtime baseline commit` фиксирует последний commit, изменивший runtime. Оно не является попыткой хранить в Markdown постоянно актуальный HEAD ветки `main`.

PR #12 `feat(theme): add Evgeniya Rostova theme v1` объединён в `main` методом merge commit. Новых migrations, permissions, RBAC-правил, маршрутов и бизнес-логики инкремент не добавил. Документационный PR #13 актуализировал living-документацию после merge и не изменил runtime baseline.

## Состояние последнего функционального инкремента

```text
increment: Evgeniya Rostova Theme v1
feature branch: feature/theme-evgeniya-rostova
final feature HEAD: c524480f47082b0f827bf16460617b24449d7780
tested runtime HEAD: 8dabdda09f9f29b1bf84ea7eea1127971d4d8f45
pull request: #12 MERGED
merge commit / runtime baseline: 967546087868f0d7eb347b186f7798015d268811
final review: PASS
blocking findings: 0
registered themes in main: 3
migrations: unchanged, 001–008
system permissions: unchanged, 19
mobile acceptance: OUT OF SCOPE
```

В `main` доступна третья встроенная тема `asu-evgeniya-rostova` с отображаемым названием `Евгения Ростова`, семью CSS-файлами и четырьмя локальными SVG. Локальное Testing, desktop/browser-приёмка, regression двух прежних тем, тематическая HTTP 403, success/error modal и HTTP-доступность всех assets завершены со статусом PASS. Mobile PASS не заявляется.

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
| #12 | Евгения Ростова Theme v1 | MERGED |

Feature- и docs-ветки сохраняются по прямому указанию владельца проекта. Их наличие после merge не означает наличие незавершённого runtime-инкремента.

## Реализованные возможности стабильного main

### Security и пользователи

- bootstrap первого владельца;
- отключение публичной регистрации после создания владельца;
- аутентификация, сессии, CSRF и logout;
- четыре системные роли и 19 permissions;
- список, поиск, фильтры и карточка пользователя;
- создание pending-пользователя;
- approval и activation;
- редактирование пользователя и ролей;
- block/unblock;
- обязательная смена временного пароля;
- rejection с обязательным основанием и аудитом;
- archive/restore с обязательным основанием и аудитом;
- защита последнего активного владельца;
- privacy-ограничения для чувствительного аудита.

### Themes

- доверенный реестр `config/themes.php`;
- глобальная активная тема в БД;
- безопасный fallback на `asu-blue`;
- три встроенные темы: `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova`;
- семь обязательных CSS-контрактов каждой темы;
- локальные SVG-декорации темы `Евгения Ростова`;
- тематические operation-result modal и HTTP 403;
- desktop-приёмка всех трёх стабильных тем.

### Directories

- owner-only landing page;
- справочник воинских званий: 2 источника, 6 составов, 20 уровней;
- справочник типов организационных элементов: 4 источника, 6 классов, 28 типов, 32 связи;
- read-only routes, поиск и фильтры;
- registry-driven проверка `directories.css` всех зарегистрированных тем;
- профильные integration checker'ы.

## Проверка последнего инкремента

Для `Evgeniya Rostova Theme v1` подтверждено:

```text
feature local/remote divergence = 0/0
feature working tree = clean
PHP lint = 59 files, PASS
deploy = PASS
config/local.php SHA-256 = preserved
applied migrations = 8
new migrations = none
theme management checker = PASS
missing-asset checker = PASS
both directory checkers = PASS
security regression checkers = PASS
system roles = 4
system permissions = 19
local smoke = PASS with -AllowInvalidCertificate
7 CSS + 4 SVG = HTTP 200
desktop/browser acceptance = PASS
asu-blue regression = PASS
asu-light-blue regression = PASS
PR final review = PASS
blocking findings = 0
```

После merge GitHub подтвердил PR #12 как `MERGED`, runtime baseline commit `967546087868f0d7eb347b186f7798015d268811`, наличие темы и process-документов в `main`, а также сохранность feature- и documentation-веток. Документационный PR #13 не изменял runtime. Локальная синхронизация checkout с актуальным `main` и повторный post-merge smoke не заявляются как выполненные до получения отдельного фактического вывода локальной среды.

## Ограничения текущего стабильного baseline

Не реализованы:

- фактическая организационная структура конкретной части;
- отношения подчинённости и дерево подразделений;
- карточки военнослужащих;
- должности и кадровые назначения;
- общий runtime домена Documents и приказы;
- медицинский учёт, имущество, транспорт и обучение;
- общий неизменяемый журнал аудита для всех доменов;
- GitHub CI и production deployment;
- произвольная установка и редактирование тем.

Справочники не содержат реальные номера частей, дислокацию, численность, вооружение, штат или закрытые сведения.

## Мобильное тестирование

Мобильная приёмка выполнялась в ранних security-инкрементах там, где была включена в scope. Для последних справочных инкрементов и `Evgeniya Rostova Theme v1` мобильное тестирование исключено и не заявляется как выполненное.

## Следующий gate

Активный функциональный инкремент не выбран. Новая задача должна начинаться с Research → Analysis → Architecture → Specification → Formal Review → Approval и отдельной feature-ветки от актуального `main`.
