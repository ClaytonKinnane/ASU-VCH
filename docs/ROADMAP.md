# План разработки

## Стабильная контрольная точка

Функциональные PR #1–#9 и #12 завершены и объединены в `main`. Документационные PR #10, #11 и #13 также объединены и не изменили runtime.

```text
runtime baseline commit: 967546087868f0d7eb347b186f7798015d268811
completed functional pull requests: #1–#9, #12
last documentation pull request: #13
applied migrations: 001–008
stable built-in themes: 3
system roles: 4
system permissions: 19
```

Поле `runtime baseline commit` фиксирует последний commit, изменивший runtime. Оно не является попыткой хранить в Markdown постоянно актуальный HEAD ветки `main`.

## Завершённые этапы

- [x] базовый сайт, установка, авторизация, сессии и CSRF;
- [x] RBAC и управление пользователями;
- [x] обязательная смена временного пароля;
- [x] отклонение пользователя с аудитом;
- [x] архивирование и восстановление пользователя;
- [x] управление темами и светлая синяя тема;
- [x] унификация геометрии и hover-эффектов тем;
- [x] стартовая страница справочников;
- [x] справочник составов военнослужащих и воинских званий;
- [x] справочник типов организационных элементов;
- [x] документационный аудит текущего baseline;
- [x] третья встроенная тема `Евгения Ростова`.

## Завершённый инкремент: Evgeniya Rostova Theme v1

```text
feature branch: feature/theme-evgeniya-rostova
final feature HEAD: c524480f47082b0f827bf16460617b24449d7780
tested runtime HEAD: 8dabdda09f9f29b1bf84ea7eea1127971d4d8f45
pull request: #12 MERGED
merge commit / runtime baseline: 967546087868f0d7eb347b186f7798015d268811
final review: PASS
blocking findings: 0
mobile acceptance: OUT OF SCOPE
```

Этапы:

- [x] Research;
- [x] Analysis;
- [x] Architecture options и recommendation;
- [x] Architecture / Specification;
- [x] Formal Review;
- [x] Approval;
- [x] Implementation;
- [x] локальный PHP/Open Server/MySQL Testing;
- [x] desktop/browser-приёмка;
- [x] Test Report;
- [x] Pull Request;
- [x] Final Review;
- [x] отдельное merge approval;
- [x] Merge;
- [x] GitHub post-merge verification.

Новая тема не добавила migration или permission. Mobile acceptance была исключена из scope и Mobile PASS не заявляется. Документационный PR #13 актуализировал living-документацию и не изменил runtime baseline. Локальная синхронизация checkout с актуальным `main` и повторный post-merge smoke фиксируются только после фактического вывода локальной среды.

## Следующий функциональный инкремент

Не выбран и не утверждён. Возможные направления не являются задачами до отдельного Research/Analysis/Approval:

- конкретные организационные структуры и отношения подчинённости;
- карточка военнослужащего;
- должности и кадровые назначения;
- развитие нормативных справочников;
- аудит событий приложения;
- документы и приказы;
- production/CI-инфраструктура;
- отдельный инкремент мобильной проверки и доработки.

## Постоянные ограничения

- Нельзя реализовывать фактическую организационную структуру без отдельной модели данных и Approval.
- Нельзя включать закрытые, ограниченные или фактические сведения в открытые справочники.
- Нельзя считать мобильную версию проверенной без отдельной приёмки.
- Нельзя выполнять merge или удалять feature/docs-ветку без отдельного явного разрешения владельца проекта.
