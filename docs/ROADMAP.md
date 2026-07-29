# План разработки

## Стабильная контрольная точка

Функциональные PR #1–#9, #12 и #15 завершены и объединены в `main`. Documentation-only PR #10, #11, #13 и #14 также объединены и не изменяли runtime.

```text
merged main commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
completed functional pull requests: #1–#9, #12, #15
last completed documentation pull request before baseline refresh: #14
applied migrations: 001–009
stable built-in themes: 3
system roles: 4
system permissions: 25
```

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
- [x] документационный аудит baseline 2026-07-27;
- [x] третья встроенная тема `Евгения Ростова`;
- [x] Organizational Structure v1;
- [x] UI Polish 1: управление состояниями дерева и controls;
- [x] UI Polish 2: уточнение геометрии и доступности;
- [x] UI Polish 3: дополнительная визуальная доработка;
- [x] UI Polish 4: выравнивание поиска, единая иконка изменения и заметный level toggle;
- [x] автоматическое тестирование Organizational Structure v1;
- [x] ручная desktop-приёмка Organizational Structure v1;
- [x] PR #15, Final Review, отдельный merge approval и merge;
- [x] repository/branch audit после PR #15.

## Завершённый инкремент: Organizational Structure v1

```text
feature branch: feature/organizational-structure-v1
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
final feature documentation HEAD: dd2586dab7a3b3d8b3683d60e2c7eedce002eb54
pull request: #15 MERGED
merge commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
final review: PASS
blocking findings: 0
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```

Реализованы structures, version lifecycle, редактируемое дерево draft-версии, metadata документов, история и сравнение версий. Migration 009 добавила 7 таблиц, 16 triggers и 6 permissions; общее количество системных permissions стало 25.

## Документационный инкремент baseline refresh

`Post-Organizational-Structure v1 Baseline Refresh` предназначен для актуализации living documentation и фиксации repository audit после PR #15. Он не меняет runtime, БД, checker source, deploy или Git refs.

## Следующий функциональный инкремент

Не выбран и не утверждён. Возможные направления не являются задачами до отдельного Research / Analysis / Architecture / Approval:

- карточка военнослужащего;
- должности, штатные структуры и кадровые назначения;
- развитие нормативных справочников;
- общий Documents domain, документы и приказы;
- общий audit domain;
- production/CI-инфраструктура;
- отдельный инкремент мобильной проверки и доработки.

Отдельным техническим инкрементом должен быть устранён exact-count debt legacy checker-файлов для текущего baseline 25 permissions.

## Постоянные ограничения

- Нельзя включать закрытые, ограниченные или фактические сведения без отдельного утверждения scope, модели данных и защиты.
- Нельзя считать общий Documents domain реализованным только из-за metadata документов внутри Organization.
- Нельзя считать мобильную версию проверенной без отдельной фактической приёмки.
- Нельзя выполнять merge или удалять feature/docs-ветку без отдельного явного разрешения владельца проекта.

## Обязательный workflow

```text
Research
→ Analysis
→ Architecture
→ Specification
→ Review
→ Approval
→ Implementation
→ Testing
→ Commit
→ Push
→ Pull Request
→ Merge
```
