# История изменений

## 2026-07-31

### Post-PR17 Branch Cleanup Closure

- PR #17 `docs: reconcile repository state after PR #16` объединён в `main` методом merge;
- merge commit PR #17: `c67632674dce216bb23338de898bf0733a8e42c0`;
- локальная post-merge синхронизация `main` завершена маркером `LOCAL_MAIN_POST_PR17_SYNCHRONIZATION_STATUS=PASS`;
- corrected fresh inventory подтвердил `19` GitHub branches: `main` и `18` remote non-main branches;
- локальная строка `origin` классифицирована как symbolic remote HEAD, а не отдельная GitHub branch;
- 17 ordinary branches имели `ahead=0` и были полностью достижимы из `main`;
- для `docs/evgeniya-rostova-theme-v1-design` повторно подтверждён точный blob/size proof двух файлов, `BYTEWISE_DOCUMENT_COMPARISON_STATUS=PASS`;
- владелец проекта отдельно разрешил удалить точный cleanup batch из 18 remote non-main branches;
- первая попытка завершилась HTTP `403` на первой branch до любого успешного удаления, deletion count остался `0`;
- GitHub write authentication восстановлена через Git Credential Manager и GitHub device flow в Microsoft Edge InPrivate;
- dry-run write probe прошёл, реальный probe ref не создан, `GITHUB_WRITE_AUTHENTICATION_STATUS=PASS`;
- успешно удалены `18 / 18` утверждённых remote branches;
- terminal read-only verification snapshot подтвердил одну оставшуюся branch — `main`;
- локальный branch set сохранён: `12` до / `12` после / unchanged;
- `main` остался на `c67632674dce216bb23338de898bf0733a8e42c0`, divergence — `0 0`, working tree — clean;
- итоговый маркер: `REMOTE_BRANCH_CLEANUP_STATUS=PASS`;
- ветка `docs/post-pr17-branch-cleanup-closure` создана после terminal snapshot и не входила в удалённый batch;
- создан immutable evidence record `docs/REPOSITORY-CLEANUP-2026-07-31.md` и обновлены шесть living documents;
- runtime, deploy, database, migrations и checker source не изменялись;
- runtime/database retesting и application browser testing не выполнялись и не требовались;
- mobile testing: `OUT OF SCOPE / NOT RUN`; Mobile PASS не заявляется.

## 2026-07-30

### Post-Organizational-Structure v1 Baseline Refresh

- PR #16 `docs: refresh post-organization project baseline` объединён в `main` методом merge;
- merge commit: `72630757c1a72a6bd971cf819cff9bdd36c148bf`;
- scope PR #16 ограничен `README.md` и `docs/**`;
- runtime, deploy, database, migrations и checker source не изменялись;
- обновлены 13 living documents и добавлен repository audit 2026-07-29;
- локальный `main` синхронизирован fast-forward с `origin/main`;
- локальная проверка завершена маркером `LOCAL_MAIN_SYNCHRONIZATION_STATUS=PASS`.

### Post-PR16 Repository Reconciliation

- выполнен новый read-only аудит всех веток после merge PR #16;
- до создания reconciliation-ветки подтверждены 18 веток: `main` и 17 non-main;
- 16 non-main веток имеют `ahead_by = 0` относительно audit `main`;
- `docs/evgeniya-rostova-theme-v1-design` имеет два уникальных commit, но оба затронутых файла повторно подтверждены как Git-blob-identical с `main`;
- все 17 pre-reconciliation non-main веток технически безопасны для удаления после отдельного явного разрешения;
- создана активная ветка `docs/post-pr16-repository-reconciliation`, которая не входит в прежний cleanup-set и должна оцениваться повторно после собственного merge;
- living documentation переведена на устойчивую модель: текущий HEAD определяется через `origin/main`, а точные SHA используются только как исторические anchors и audit snapshots;
- создан `docs/REPOSITORY-AUDIT-2026-07-30.md`;
- фактическое удаление веток не выполнялось и не разрешено;
- runtime/deploy/database retesting и mobile testing не выполнялись, поскольку инкремент documentation-only.

## 2026-07-29

### Organizational Structure v1

- PR #15 `feat(organization): add organizational structure v1` объединён в `main` методом merge commit;
- merged main commit: `5aaf0a7aca51cae575b3765309b2bf3ad7d76d28`;
- протестированный runtime HEAD: `238868950c5f7417ea3d1c283610f2d282d4395a`;
- добавлена migration `009_organizational_structure_v1.sql`;
- созданы 7 таблиц организационных структур и 16 DB triggers;
- добавлены 6 permissions `organization.structures.*`, итоговое количество системных permissions — `25`;
- реализованы создание, изменение, архивирование и восстановление структур;
- реализован lifecycle версий `draft → approved → active`, отмена approved-версии и создание нового draft;
- реализовано редактируемое дерево draft-версии: добавление, изменение, перемещение, упорядочивание и удаление узлов;
- реализованы стабильные organizational elements и привязка к версии каталога типов;
- добавлены metadata документов, связи документов с версиями, история изменений и сравнение версий;
- добавлены транзакционные операции, revision checks, RBAC и CSRF-защита.

### UI Polish 1–4

- доработаны controls дерева, состояния раскрытия и поиск;
- выровнена геометрия интерфейса во всех трёх темах;
- унифицирована иконка изменения;
- улучшена видимость level toggle;
- сохранены keyboard navigation и focus-visible;
- UI contract checker расширен до `64 PASS / 0 FAIL`.

### Проверка

- organization integration checker: `58 PASS / 0 FAIL`;
- PHP lint проверенного deploy: `104` файла, `0` ошибок;
- installer подтвердил 9 migrations и повторный запуск без новых migrations;
- security, theme и directory regressions: PASS;
- HTTP smoke: `/` 200, `/health.php` 200, `/admin/` 302;
- автоматическое тестирование: PASS;
- ручная desktop-приёмка: PASS;
- Final Review: PASS, blocking findings: `0`;
- mobile testing: `OUT OF SCOPE / NOT RUN`;
- Mobile PASS не заявляется.

### Repository audit и документация

- проверены содержимое репозитория, living documentation и все 17 веток;
- 16 non-main веток оценены как технически безопасные для удаления после отдельного явного разрешения;
- для `docs/evgeniya-rostova-theme-v1-design` подтверждена побайтовая идентичность двух документов с `main`;
- фактическое удаление веток не выполнялось;
- начат documentation-only инкремент Post-Organizational-Structure v1 Baseline Refresh;
- изменение legacy checker source вынесено в отдельный будущий технический инкремент.

## 2026-07-28

### Темы

- PR #12 `feat(theme): add Evgeniya Rostova theme v1` объединён в `main` методом merge commit;
- новый стабильный `main` commit: `967546087868f0d7eb347b186f7798015d268811`;
- зарегистрирована третья встроенная светлая тема `asu-evgeniya-rostova` с отображаемым названием `Евгения Ростова`;
- добавлена розово-лиловая палитра и оформление для auth, account, users, theme management, directories и operation-result modal;
- добавлены четыре локальных SVG: сердечки, воздушные шарики, медвежонок и зайчик;
- все семь CSS и четыре SVG включены в `required_assets` и блокируют активацию темы при неполной поставке;
- на dashboard добавлены кластеры сердечек на плитках;
- success operation-result modal приведён к розово-лиловому стилю темы;
- default и fallback остаются `asu-blue`;
- стабильных встроенных тем в `main` теперь `3`.

### Проверка

- PHP lint: 59 файлов, PASS;
- controlled deploy: PASS;
- SHA-256 `config/local.php` сохранён;
- migrations остались `001–008`, повторный installer сообщил «Новых миграций нет»;
- theme management checker: PASS;
- missing-asset checker: PASS;
- оба directory checker'а: PASS;
- security regression checker'ы: PASS;
- system roles: `4`;
- system permissions: `19`;
- local smoke: PASS с предусмотренным параметром `-AllowInvalidCertificate`;
- все семь CSS и четыре SVG возвращают HTTP 200;
- desktop/browser-приёмка темы `Евгения Ростова`: PASS;
- regression `asu-blue` и `asu-light-blue`: PASS;
- тематические success/error modal и HTTP 403: PASS;
- Final Review: PASS, blocking findings: `0`;
- Mobile PASS не заявляется, мобильное тестирование было исключено из scope.

### Архитектура и документация

- добавлены Architecture / Specification, Formal Review, Approval, Implementation Addendum, Final Test Report и PR Final Review;
- GitHub post-merge verification подтвердила PR #12 как `MERGED`, наличие третьей темы и process-документов в `main`;
- feature- и documentation-ветки сохранены по указанию владельца проекта.

Новых migrations, permissions, RBAC-правил, маршрутов и бизнес-логики инкремент не добавил.

## 2026-07-27

### Справочники

- добавлена owner-only стартовая страница справочников;
- добавлен read-only нормативный справочник составов военнослужащих и воинских званий;
- добавлены migration 007, 2 нормативных источника, 6 составов и 20 уровней званий;
- добавлен read-only нормативно-методический справочник типов организационных элементов;
- добавлены migration 008, 4 официальных источника, 6 организационных классов, 28 типов и 32 связи тип–класс;
- добавлены поиск, фильтры и CLI integration checker'ы;
- исправлена публикация `css/directories.css` и добавлена проверка ресурсов через `ThemeRegistry`;
- repository справочника организационных элементов подключён через bootstrap factory.

### Интерфейс

- активированы плитки справочников;
- обе встроенные темы получили стили справочных страниц;
- выровнены радиусы и периметральное свечение интерактивных плиток.

### Проверка

- завершена desktop-приёмка обеих тем;
- подтверждена тематическая HTTP 403 для пользователей без permission;
- post-merge проверка PR #9 завершена успешно;
- функциональный baseline обновлён до `17169e268e024ab50464ba13f7d0bf0f3d01a87e`.

Мобильное тестирование справочных инкрементов не выполнялось и не заявляется.

## 2026-07-26

### Темы

- добавлен доверенный статический реестр тем;
- добавлена migration 006 и хранение глобальной активной темы;
- добавлена страница управления темами;
- добавлена тема `asu-light-blue`;
- добавлен безопасный fallback `asu-blue`;
- operation-result modal вынесен в общий JavaScript;
- завершена desktop-приёмка обеих тем.

### Пользователи

- добавлена migration 005;
- реализованы архивирование и восстановление пользователя;
- добавлены обязательные основания, аудит, защита последнего владельца и немедленный запрет входа архивированной записи;
- добавлены тематические сообщения результата операций.

## 2026-07-25

### Базовая платформа и безопасность

- добавлены установка приложения и migrations 001–003;
- реализованы первичная регистрация владельца, вход, выход, сессии и CSRF;
- добавлены RBAC, четыре системные роли и управление пользователями;
- добавлена обязательная смена временного пароля;
- добавлена migration 004 и отклонение пользователя с обязательным основанием и аудитом;
- реализованы подтверждение, редактирование, блокировка, роли и privacy-ограничения;
- добавлены CLI checker'ы и локальные smoke-тесты.

## Документация

Архитектура, спецификации, Formal Review, Approval и Test Report каждого инкремента хранятся в `docs/architecture`, `docs/specifications`, `docs/reviews`, `docs/decisions`, `docs/implementation`, `docs/design` и `docs/testing`. Эти документы являются историческими артефактами соответствующих этапов. Текущее состояние проекта фиксируется в `PROJECT-STATUS.md`.
