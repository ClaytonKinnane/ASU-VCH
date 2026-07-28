# Текущее состояние проекта АСУ-ВЧ

Дата актуализации feature-ветки: `2026-07-28`.

## Стабильный функциональный baseline

```text
repository: ClaytonKinnane/ASU-VCH
branch: main
main commit: 3a93ddf35c872d6710951c71a0044f81dbcacfd6
functional baseline: 17169e268e024ab50464ba13f7d0bf0f3d01a87e
last functional PR: #9
last documentation PR: #11
migrations: 001–008
system roles: 4
system permissions: 19
built-in themes in main: 2
```

PR #10 и #11 изменяли только Markdown-документацию и не меняли runtime baseline.

## Активный функциональный инкремент

```text
increment: Evgeniya Rostova Theme v1
branch: feature/theme-evgeniya-rostova
base: main @ 3a93ddf35c872d6710951c71a0044f81dbcacfd6
runtime implementation checkpoint: 8b9342ad19e000b12a2389f94bc522d7e59d2b4d
stage: Implementation completed; local Testing pending
registered themes in feature branch: 3
migrations: unchanged, 001–008
system permissions: unchanged, 19
pull request: not opened
merge: prohibited until separate approval
```

В feature-ветке реализована третья встроенная тема `asu-evgeniya-rostova` с отображаемым названием `Евгения Ростова`, семью CSS-файлами и четырьмя локальными SVG. Выполнены статические lint, XML/CSS safety и contrast sanity checks. Open Server, MySQL, HTTP и browser-приёмка ещё не выполнены и не заявляются как PASS.

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

Feature- и docs-ветки сохраняются. Их наличие само по себе не означает, что инкремент объединён в `main`.

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
- безопасный fallback;
- `asu-blue` и `asu-light-blue`;
- общие assets и operation-result modal;
- desktop-приёмка обеих стабильных тем.

### Directories

- owner-only landing page;
- справочник воинских званий: 2 источника, 6 составов, 20 уровней;
- справочник типов организационных элементов: 4 источника, 6 классов, 28 типов, 32 связи;
- read-only routes, поиск и фильтры;
- профильные integration checker'ы.

## Последняя post-merge проверка

Для PR #9 подтверждено:

```text
Local main = GitHub main = 17169e268e024ab50464ba13f7d0bf0f3d01a87e
local/remote divergence = 0/0
working tree = clean
applied migrations = 8
new migrations = none
organizational directory checker = PASS
military ranks checker = PASS
asu-blue directories.css = HTTP 200
asu-light-blue directories.css = HTTP 200
config/local.php = preserved
```

После документационных PR #10 и #11 `main` синхронизирован до `3a93ddf35c872d6710951c71a0044f81dbcacfd6`; runtime-проверки повторно не запускались, поскольку runtime не менялся.

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

## Следующий gate активного инкремента

Необходимо синхронизировать точный feature HEAD в локальную среду, выполнить PHP lint, controlled deploy, два запуска installer, профильные и security checker'ы, HTTP asset acceptance и desktop/browser-приёмку. Только после Test Report и Final Review разрешается открыть Pull Request. Merge требует отдельного точного разрешения.
