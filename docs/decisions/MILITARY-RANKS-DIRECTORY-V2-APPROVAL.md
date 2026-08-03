# Approval — Составы военнослужащих и воинские звания v2

Дата: **2026-08-03**

Статус: **APPROVED FOR IMPLEMENTATION AND PR**

## Утверждённые решения

Владелец утвердил:

- version code `rf-military-ranks-staffing-scopes-v2`;
- source verification date `2026-08-02`;
- business effective date `2026-08-03`;
- завершение действия v1 датой `2026-08-02`;
- lifecycle `building → published → superseded`;
- version-scoped composition semantics;
- отсутствие Staffing eligibility у v1;
- derived categories `soldiers-and-sailors` и `sergeants-and-starshinas`;
- неизменные 20 rank codes/names/order;
- Reference-owned read-only compatibility service;
- phased migration preflight, idempotent DDL, transactional DML и verification;
- recovery contract;
- отсутствие permissions и mutation UI;
- отсутствие реальных unit/personnel/Excel данных;
- заморозку инкремента B;
- отсутствие Staffing tables и Organization bindings;
- mobile `OUT OF SCOPE / NOT RUN`.

## Process approval

Разрешена реализация в отдельной ветке:

`feature/military-ranks-directory-v2`

Обязательный процесс сохранён:

`Architecture → Specification → Review → Approval → Implementation → Testing → Commit → Push → PR → Final PR Review → отдельное Merge approval → Merge`.

## Manual acceptance approval

На runtime/documentation baseline `b44aed14ee1a54be213cbc939322ba21b02e7a58` владелец подтвердил:

- все три desktop-темы;
- разрешения 1920×1080 и 1366×768;
- current v2;
- historical v1;
- переключение версий;
- v2/v1 filter counts;
- search и empty state;
- read-only UI;
- official source links;
- non-owner HTTP 403;
- console errors 0;
- HTTP/asset 404 = 0;
- defects = NONE;
- final result = PASS.

## Ограничение решения

Это Approval разрешает фиксацию evidence и создание Pull Request.

Оно **не разрешает merge**. Merge выполняется только после Final PR Review и нового отдельного явного разрешения владельца.

Удаление feature-ветки также требует отдельного разрешения после post-merge verification.
