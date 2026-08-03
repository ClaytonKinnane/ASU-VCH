# Formal Review — Составы военнослужащих и воинские звания v2

Статус: **PASS**

Дата: **2026-08-03**

Ветка: `feature/military-ranks-directory-v2`

## 1. Рассмотренные материалы

- Architecture & Specification;
- migration 012 и compatibility loader;
- versioned repository;
- read-only compatibility service;
- owner-only historical UI;
- theme assets и UI-layout checker;
- integration, regression и manual acceptance evidence.

## 2. Архитектурная проверка

Подтверждено:

- справочник остаётся владельцем Reference-данных;
- v2 не создаёт зависимость от Organization;
- Staffing semantics отделены от нормативной классификации;
- semantics и source links version-scoped;
- v1 не получает ретроспективную Staffing eligibility;
- 20 кодов, наименований и порядок званий не меняются;
- current version единственная;
- published/superseded child data неизменяемы;
- recovery fail closed при противоречивом состоянии.

Результат: **PASS**.

## 3. Scope review

Запрещённые элементы не обнаружены:

- Staffing tables;
- `staff_slot`;
- personnel assignments;
- Organization bindings;
- реальные данные подразделений и военнослужащих;
- Excel fixtures;
- mutation routes/forms;
- новые permissions;
- реализация инкремента B.

Результат: **PASS**.

## 4. Security review

Подтверждено:

- owner access через существующий `system.*.*`;
- non-owner получает HTTP 403;
- UI и route read-only;
- поиск/фильтры используют GET;
- repository queries prepared;
- official URLs выводятся с `noopener noreferrer`;
- controlled HTTP 503 при нарушении current/integrity contract.

Результат: **PASS**.

## 5. Migration review

Подтверждено:

- marker 012 требует compatibility loader;
- DDL идемпотентен;
- publication DML транзакционен;
- точные v1/v2 anchors проверяются до recovery/registration;
- поддержаны fresh, DDL-only partial, valid building, contradictory building и published-without-registry состояния;
- повторный installer не создаёт дубликатов.

Во время реализации source review выявил повреждённые tokens `TRIGGGER` в SQL templates. Они были исправлены до запуска migration, а source checker усилен проверкой UTF-8, управляющих байтов, запрещённых tokens и точного количества 18 DROP/CREATE trigger declarations.

Результат после remediation: **PASS**.

## 6. UI review

Первичная ручная проверка выявила blocking visual defect:

- двухколоночный Grid растягивал короткие карточки до высоты соседних;
- parent/child hierarchy выглядела как случайная сетка;
- дочерние карточки повторяли полный путь `Родитель → Ребёнок`.

Remediation:

- одна start-aligned колонка;
- компактные parent cards;
- child indentation и connector;
- короткие child labels;
- одинаковые правила во всех трёх темах;
- dedicated UI-layout checker.

Повторная desktop-приёмка v2 и v1 во всех темах и двух разрешениях: **PASS**.

## 7. Testing review

Подтверждены:

- PHP lint;
- source/loader/service checkers;
- migration 012;
- repeated installer;
- DB integration checker;
- Reference/Security/Theme/Organization regressions;
- Organization 58 PASS / 0 FAIL;
- source/deploy parity 24/24;
- HTTP smoke;
- full manual desktop acceptance;
- console errors 0;
- HTTP/asset 404 = 0;
- defects = NONE.

Process deviation: предмиграционная резервная копия не была создана из-за остановки preflight-блока. После migration создана и проверена post-migration backup. Откат не потребовался. Отклонение явно отражено в Test Report и не скрывается.

## 8. Итог

Blocking findings: **0**

Major findings: **0**

Minor findings: **0**

Formal Review: **PASS**

Инкремент допускается к Pull Request и Final PR Review. Merge не разрешён этим review и требует отдельного явного решения владельца.
