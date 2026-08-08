# Personnel Core Card v1 — Specification

## 1. Статус

```text
DOCUMENT=Specification
VERSION=0.2
INCREMENT=Personnel Core Card v1
BASE_SHA=dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8
DESIGN_BRANCH=design/personnel-core-card-v1
ARCHITECTURE=docs/design/PERSONNEL-CORE-CARD-V1-ARCHITECTURE.md
DOMAIN=docs/domains/PERSONNEL.md
IMPLEMENTATION=NOT STARTED
```

## 2. Цель v1

System owner получает работающий prototype раздела «Военнослужащие» для создания, поиска, просмотра, редактирования и архивирования канонических карточек, а также ведения типизированных идентификаторов и истории изменений.

V1 является основой полного электронного досье, но не пытается реализовать все будущие data domains одновременно.

## 3. Actor и временная access model

Единственный actor v1:

```text
system_owner
```

Все Personnel routes используют `require_system_owner()`.

Migration 015:

```text
new permissions = 0
new role grants = 0
```

Это временная prototype boundary. Будущая модель доступа зафиксирована в `PERSONNEL-ACCESS-FUTURE-DESIGN-NOTES.md` и не реализуется скрыто в этом increment.

## 4. Functional requirements

### FR-01. Navigation

Для `system_owner` плитка «Военнослужащие» в `public/admin/content.php` становится активной и ведет на:

```text
/admin/personnel/persons.php
```

Для non-owner плитка Personnel не отображается и прямые Personnel routes не раскрывают данные.

### FR-02. Personnel list

Страница показывает:

```text
ФИО
Дата рождения
Личный номер
Жетон
Табельный номер
Позывной
Статус карточки
Дата изменения
```

Поддерживаются:

- `q` — bounded search до 150 символов по ФИО и identifier values;
- `status=active|archived|all`;
- optional birth date exact filter;
- стабильная сортировка: active first, затем ФИО, затем id;
- bounded pagination, default 50, maximum 100.

Никакого export в v1.

### FR-03. Empty state

При отсутствии записей показывается понятное empty state и действие «Создать карточку».

Migration/tests не создают synthetic persons автоматически.

### FR-04. Create PersonnelRecord

POST input:

```text
last_name
first_name
middle_name nullable
birth_date
birth_place nullable
citizenship nullable
nationality nullable
religion nullable
csrf_token
```

Validation:

- `last_name`: trim, 1–100;
- `first_name`: trim, 1–100;
- `middle_name`: trim, 0–100;
- `birth_date`: valid DATE, not future;
- `birth_place`: 0–255;
- `citizenship`: 0–100;
- `nationality`: 0–100;
- `religion`: 0–150;
- control characters rejected;
- no duplicate-person heuristic blocks creation: совпадение ФИО/даты рождения не является unique key.

Effect in one transaction:

- create `personnel_records` with `record_status=active`, `revision=1`;
- append `personnel.created` event;
- redirect to card.

### FR-05. Personnel card

URL:

```text
/admin/personnel/person.php?id={positive-int}
```

Header shows:

- neutral photo placeholder;
- ФИО;
- active personal number/dog tag/call sign where present;
- `Активна` or `Архив`;
- updated timestamp.

Implemented sections:

1. Обзор;
2. Персональные данные;
3. Идентификаторы;
4. История изменений.

Roadmap placeholders are allowed only with visible label `Не реализовано в v1`:

- Служба и назначения;
- Контакты и семья;
- Документы и фото;
- Медицинские сведения;
- Опознавательные сведения;
- Особые случаи;
- Формы и отчеты.

### FR-06. Update core data

POST input contains all FR-04 core fields plus:

```text
expected_revision
reason nullable
```

Rules:

- active record only;
- owner only;
- CSRF;
- expected revision exact;
- no-op update rejected or returned as safe no-change result;
- changed values saved atomically;
- root revision increments once;
- `personnel.core_updated` event stores safe before/after core values;
- PRG redirect.

### FR-07. Identifier type catalog

Migration seeds exactly these system type codes:

```text
personal_number
service_dog_tag
table_number
call_sign
```

Policy:

```text
personal_number → global historical uniqueness / never reused
service_dog_tag → global historical uniqueness / never reused
table_number    → reuse allowed by type policy
call_sign       → reuse allowed by type policy
```

UI displays Russian names and description where applicable.

No generic identifier-type editor in v1.

### FR-08. Add identifier

POST input:

```text
personnel_id
identifier_type_id
value
valid_from nullable
note nullable
expected_revision
csrf_token
```

Rules:

- PersonnelRecord active;
- system type exists;
- value trim 1–255;
- one active value per person/type;
- for type with `enforce_global_unique=1`, the value must not exist in any current or historical row of that type;
- `valid_from` valid DATE when supplied;
- transaction lock root → relevant identifier candidates;
- create row with `valid_to=NULL`;
- root revision +1;
- append `identifier.added`.

### FR-09. Replace identifier

Used when identifier value changes while history must be preserved.

POST:

```text
personnel_id
identifier_type_id
new_value
effective_date
reason nullable
expected_revision
csrf_token
```

Temporal rule:

```text
old interval ends at effective_date
new interval begins at effective_date
interval semantics = [valid_from, valid_to)
```

Effect in one transaction:

1. lock root;
2. validate expected revision;
3. find exactly one active identifier of type;
4. validate `effective_date` as DATE and `effective_date >= old.valid_from` when old start is known;
5. end old row with `valid_to=effective_date`, `ended_at=now`, `ended_by=actor`;
6. validate new value;
7. if type has `enforce_global_unique=1`, reject any new value already present in current or historical rows of that type, including a value previously ended on another/personnel record;
8. create new active row with `valid_from=effective_date`, `valid_to=NULL`;
9. increment root revision once;
10. append `identifier.replaced` referencing old/new ids.

Old row is never overwritten with new value.

### FR-10. End identifier without replacement

POST:

```text
personnel_id
identifier_type_id
effective_date
reason nullable
expected_revision
csrf_token
```

Rules:

- exactly one active identifier of type;
- `effective_date` valid DATE;
- if active row has `valid_from`, require `effective_date >= valid_from`;
- set `valid_to=effective_date`, `ended_at=now`, `ended_by=actor`;
- root revision +1;
- append `identifier.ended`;
- historical identifier cannot be ended twice.

### FR-11. Identifier history

Card shows current identifiers first and readable history per type:

```text
value
valid_from
valid_to
created_at
ended_at
```

Interval semantics are `[valid_from, valid_to)`.

No physical delete control.

### FR-12. Archive card

POST:

```text
id
expected_revision
reason required 1–500
csrf_token
```

Preconditions:

- active record;
- revision match.

Effect:

- `record_status=archived`;
- archived metadata;
- revision +1;
- `personnel.archived` event;
- card remains readable.

Archive does not claim «уволен», «исключен из списков» or any service event.

### FR-13. Archived behavior

Archived card:

- remains searchable with archived/all filter;
- is read-only except restore;
- identifiers cannot be added/replaced/ended;
- history remains visible.

### FR-14. Restore card

POST with expected revision and CSRF:

- archived only;
- status → active;
- archived metadata cleared where appropriate while event history preserves fact of prior archive;
- revision +1;
- append `personnel.restored`.

### FR-15. Change history

History screen/card shows append-only events ordered newest first:

```text
occurred_at
actor display name
event type
safe summary
revision context where available
```

Raw SQL errors and secrets never shown.

### FR-16. No physical delete

V1 exposes no HTTP/service operation that physically deletes:

```text
personnel_records
personnel_identifiers
personnel_change_events
```

Identifier type deletion is absent.

### FR-17. No Assignment truth

V1 must not store or display current position/unit/rank/VUS as Personnel core fields.

Placeholders say `Не реализовано в v1`; they do not show `Вакантно`, `Не назначен` or other factual statement derived from missing Assignment data.

### FR-18. No document-specific duplicate stores

No tables or models named by output forms (`questionnaire`, `objective_card`, `search_card`) are created.

Future forms read canonical Personnel data.

### FR-19. User account remains separate

V1 does not add `personnel_id`/`soldier_id` to `users` and does not create automatic account links.

This avoids Security schema expansion before a separately approved design.

### FR-20. No file/media upload

Profile uses placeholder only. File input, direct file storage and BLOB fields are prohibited in v1.

## 5. Database specification

Migration:

```text
database/migrations/015_personnel_core_card_v1.sql
```

Tables:

```text
personnel_records
personnel_identifier_types
personnel_identifiers
personnel_change_events
```

### Required indexes

At minimum:

```text
personnel_records(record_status, last_name, first_name, middle_name, id)
personnel_records(birth_date)
personnel_identifiers(personnel_id, identifier_type_id)
personnel_identifiers(identifier_type_id, value)
personnel_change_events(personnel_id, occurred_at, id)
```

Exact composite/generated indexes may differ if needed to enforce invariants under MySQL 8.4.

### Required DB guards

- no invalid record status;
- revision >= 1;
- no future `birth_date` enforced in service;
- identifier interval validity;
- at most one active identifier per person/type;
- never-reuse global uniqueness for configured types across current + historical rows;
- FK protection;
- append-only change events;
- no automatic person seeds;
- no permissions/grants added.

## 6. Service/concurrency specification

Aggregate command pattern:

```text
BEGIN
SELECT personnel_record ... FOR UPDATE
validate expected_revision
validate command
mutate root/child
UPDATE personnel_records SET revision=revision+1 ...
INSERT personnel_change_events ...
COMMIT
```

Rollback on any error.

Canonical lock order:

```text
personnel_records
→ personnel_identifiers
→ personnel_change_events insert
```

No route mutates DB directly outside PersonnelService/domain functions.

## 7. HTTP/security specification

Every Personnel route:

- loads `app/bootstrap.php`;
- loads `app/Personnel/functions.php`;
- sets `Cache-Control: no-store, private`;
- sets `Pragma: no-cache`;
- sets `Referrer-Policy: same-origin`;
- sets `X-Content-Type-Options: nosniff`;
- calls `require_system_owner()` before target-specific reads;
- uses prepared statements;
- escapes output;
- no state mutation via GET;
- mutations require CSRF;
- PRG after success;
- safe not-found/error behavior.

Non-owner direct request must not disclose Personnel values.

## 8. UI specification

Navigation:

```text
/admin/content.php
→ Военнослужащие
→ /admin/personnel/persons.php
```

Screens:

- list/search;
- create form;
- card;
- update form;
- identifier form/history;
- overall history.

Existing theme components are reused. No hardcoded theme colors and no new theme asset path is planned.

### Date-input convention — project UI rule

Для каждого пользовательского поля АСУ-ВЧ, предназначенного для ввода календарной даты, обязателен единый функциональный date-picker control:

- семантическое поле остаётся `input[type="date"]`;
- рядом с полем отображается отдельная theme-compatible кнопка с понятным значком календаря;
- штатный browser calendar indicator в интерфейсах, где применяется этот control, скрывается и не должен оставаться отдельным чёрным/неуправляемым значком;
- кнопка имеет `type="button"`, доступные `title`/`aria-label` и явную привязку к целевому date input;
- по нажатию вызывается native picker через `showPicker()` когда он доступен, с безопасным fallback `focus()` + `click()`;
- это правило применяется ко всем новым и изменяемым экранам АСУ-ВЧ, где требуется ввод даты; обнаруженное существующее исключение считается UI finding и подлежит исправлению в соответствующем утверждённом scope;
- эталон поведения: date controls в Staffing register version form и Personnel list/create/update/identifier forms.

### User-facing language convention — project UI rule

Все пользовательские названия, подписи, действия, подсказки, empty-state тексты и пояснения АСУ-ВЧ должны отображаться на русском языке.

- английские domain/code names допустимы в исходном коде, БД, event codes и технической документации, когда они являются техническими идентификаторами;
- внутренние имена `Personnel`, `Assignment`, `event_type`, `target_type` и аналогичные значения не должны без необходимости выводиться пользователю как UI-подписи;
- если техническое значение необходимо представить пользователю, UI обязан отобразить русское человекочитаемое наименование или безопасное русское обобщение;
- raw event/target codes на экране истории запрещены; отображаются русские labels и summaries;
- обнаруженная необоснованная англоязычная пользовательская подпись считается UI finding.

### Personnel card desktop layout — corrective acceptance

Для основной карточки военнослужащего:

- status, ФИО и краткие сведения образуют левую смысловую часть summary-плитки;
- действия `Изменить` и `История` образуют компактную группу справа в верхней части той же плитки, одинаковой высоты и с шириной по содержимому;
- для архивной карточки `Изменить` отсутствует, `История` остаётся;
- рабочие секции `Персональные данные`, `Идентификаторы`, `История изменений`, `Состояние карточки` используют компактный последовательный vertical rhythm без избыточного пространства над заголовком;
- ориентир для section padding: 16–20 px; heading top margin = 0; heading-to-content gap ≈ 12–16 px;
- `Добавить идентификатор` находится в heading-row блока идентификаторов, имеет обычную action-width и не растягивается на всю плитку;
- пользовательское пояснение идентификаторов: `Удаление идентификаторов недоступно. Все изменения сохраняются в истории.`;
- `Вся история` находится в heading-row блока истории изменений;
- lifecycle-действия карточки `Архивировать карточку` и `Восстановить карточку` размещаются в отдельной выровненной влево action-row и имеют ширину по содержимому; full-width lifecycle-кнопка запрещена, если отдельное утверждённое UX-требование явно не требует обратного;
- corrective layout не меняет состав данных, lifecycle, revision, history или identifier semantics.

### Identifier action/card convention — corrective acceptance

Для форм и вложенных плиток идентификаторов:

- submit-действия форм добавления, замены и прекращения действия идентификатора размещаются в отдельной action-row, выравниваются по левому краю и имеют ширину по содержимому, а не на всю форму;
- каждая запись идентификатора в карточке военнослужащего отображается как самостоятельная нейтральная вложенная карточка с theme-aware рамкой, скруглением и достаточным вертикальным интервалом до соседней записи;
- состояние записи передаётся через status badge `Действует` или `История`, а не через специальный акцентный фон всей вложенной карточки;
- название, значение, период и примечание образуют единый информационный блок записи; lifecycle actions располагаются ниже этого блока в отдельной нижней action-row, а не справа от названия;
- нижняя action-row визуально отделяется theme-aware верхней границей и сохраняет компактный одинаковый gap между кнопками;
- для действующего идентификатора действия называются `Заменить значение` и `Прекратить действие идентификатора`, имеют одинаковую высоту и ширину по содержимому;
- форма прекращения действия явно сообщает: `Идентификатор не удаляется. После указанной даты он будет сохранён в истории как недействующий.`;
- для исторического идентификатора lifecycle actions не отображаются;
- корректировка не добавляет hardcoded theme colors и не меняет identifier semantics, историю значений, правила never-reuse или запрет физического удаления.

Desktop acceptance: all three managed themes.

Responsive markup required; mobile actual test remains `NOT RUN / OUT OF SCOPE`.

## 9. Exact proposed implementation path allowlist

Any implementation change outside this list requires fail-closed re-approval.

### Existing integration/living docs

```text
public/admin/content.php
docs/domains/README.md
docs/DATABASE.md
docs/DATABASE-CURRENT.md
docs/ACCESS.md
docs/PROJECT-STATUS.md
docs/ROADMAP.md
docs/TRACEABILITY.md
docs/CHAT-HANDOFF.md
```

### Database/application

```text
database/migrations/015_personnel_core_card_v1.sql
app/Personnel/PersonnelRepository.php
app/Personnel/PersonnelService.php
app/Personnel/PersonnelSupportTrait.php
app/Personnel/PersonnelCreateUpdateTrait.php
app/Personnel/PersonnelIdentifierTrait.php
app/Personnel/PersonnelLifecycleTrait.php
app/Personnel/functions.php
```

### HTTP/UI

```text
public/admin/personnel/persons.php
public/admin/personnel/person.php
public/admin/personnel/persons/create.php
public/admin/personnel/persons/update.php
public/admin/personnel/persons/archive.php
public/admin/personnel/persons/restore.php
public/admin/personnel/identifiers/create.php
public/admin/personnel/identifiers/replace.php
public/admin/personnel/identifiers/end.php
public/admin/personnel/history.php
public/admin/personnel/views/person-list.php
public/admin/personnel/views/person-card.php
public/admin/personnel/views/person-form.php
public/admin/personnel/views/identifier-form.php
public/admin/personnel/views/history-list.php
```

### Validation

```text
tools/check-personnel-core-card-v1.php
tools/Test-PersonnelCoreCardV1.ps1
```

### Process/domain documents

```text
docs/domains/PERSONNEL.md
docs/design/PERSONNEL-ACCESS-FUTURE-DESIGN-NOTES.md
docs/design/PERSONNEL-CORE-CARD-V1-ARCHITECTURE.md
docs/design/PERSONNEL-CORE-CARD-V1-SPECIFICATION.md
docs/design/PERSONNEL-CORE-CARD-V1-REVIEW.md
docs/design/PERSONNEL-CORE-CARD-V1-APPROVAL.md
```

```text
MAX_EXPECTED_CHANGED_PATHS=40
```

No theme asset, workflow, repository-setting, deployment config, existing migration 001–014, Staffing, Organization or Reference runtime file is in the allowlist.

## 10. Test specification

### Static

- PHP lint for all tracked PHP;
- exact changed-path allowlist;
- migration registry/order check;
- no real personnel values in migrations/tests/docs;
- no file/media handling;
- no new Personnel permission codes;
- no accidental Assignment/position fields in `personnel_records`.

### Clean DB

- migrations 001–015 success;
- exactly four new Personnel tables;
- exactly four identifier type seed rows;
- zero PersonnelRecord seed rows;
- permissions total remains 35;
- rerun initialization no duplicate migration/data.

### Current DB

- pre-migration backup;
- migration 015 after 014;
- prior Organization/Staffing/Reference data unchanged;
- migration recorded once;
- repeat initialization no-op.

### DB invariant tests

Reject:

- invalid status;
- duplicate personal number in any current or historical row of another/same person;
- duplicate dog tag in any current or historical row of another/same person;
- second active identifier same type/person;
- invalid `[valid_from,valid_to)` interval;
- replace/end without valid `effective_date`;
- event update/delete;
- orphan child rows.

Accept:

- same call sign for different persons if allowed by type policy;
- same table number for different persons if allowed by type policy;
- historical call-sign/table-number reuse after prior row is ended.

### Service tests

- create;
- update;
- archive;
- restore;
- add identifier;
- replace identifier using exact effective date;
- end identifier using exact effective date;
- never-reuse enforcement for personal number/dog tag;
- stale revision for every mutation;
- rollback after child validation failure;
- history event exactness.

### HTTP tests

- owner GET list/card = 200;
- owner valid POST = 302 PRG;
- anonymous redirect;
- non-owner no Personnel data disclosure;
- CSRF rejection;
- invalid ID and stale revision safe behavior;
- no GET mutation.

### Browser desktop

For each current theme:

- empty list;
- create card using synthetic person;
- populated list;
- card header/sections;
- update;
- add/replace/end identifiers;
- history;
- archive/restore;
- every visible date-input control uses the functional themed calendar button, hides the native browser indicator and opens the picker when activated;
- visible Personnel UI uses Russian labels and does not expose raw internal event/target codes or unnecessary English domain names;
- summary actions are grouped clearly and working card sections have compact consistent top spacing;
- identifier add action has normal action width next to its section heading and the identifier retention explanation is user-facing Russian text;
- identifier form submit controls are compact content-width actions rather than full-width bars;
- multiple identifier entries remain visually separable as independent theme-aware nested cards;
- active identifier entries expose the bottom action-row with explicit actions `Заменить значение` / `Прекратить действие идентификатора`, while historical entries expose no lifecycle actions;
- the identifier deactivation form explicitly explains that the identifier is retained in history rather than deleted;
- archive and restore lifecycle submit controls are compact content-width actions aligned to the left and do not expand to the section width;
- narrow desktop/window responsive behavior as observation only;
- no Mobile PASS claim.

### Regression

- login/logout;
- content landing;
- user management;
- Organization Structure v1;
- Staffing v1;
- Military Ranks;
- Military Positions;
- VUS;
- themes;
- existing static verification/checkers.

## 11. Synthetic test-data policy

Repository/checkers may use only clearly synthetic values, for example fictional names and identifiers that are not copied from real military personnel.

No real:

- ФИО;
- personal numbers;
- dog tags;
- unit assignments;
- medical facts;
- contacts;
- addresses;
- documents;
- photos

may be committed as fixtures/evidence.

Runtime manual testing on the local instance may create synthetic records according to the test plan. Production data acceptance is not part of v1 claims.

## 12. Acceptance criteria

1. exact base/head/path gates pass;
2. Architecture/Specification/Review align;
3. migration clean/current passes;
4. permissions remain exactly 35;
5. four Personnel tables exist with expected invariants;
6. no seeded persons;
7. root revision protects every mutation;
8. identifier history is non-destructive;
9. personal number/dog tag never-reuse invariant passes;
10. identifier replace/end effective-date semantics pass;
11. archive/restore non-destructive;
12. owner-only access enforced;
13. non-owner Personnel disclosure absent;
14. no Assignment/occupancy truth introduced;
15. no file/media behavior introduced;
16. list/card/search/history behavior passes;
17. desktop visual acceptance passes in all three themes;
18. mobile remains honestly untested;
19. regressions pass;
20. docs match runtime exact head;
21. production deployment not claimed;
22. Final PR Review later has no blocking/major findings;
23. visible Personnel UI uses Russian user-facing labels and does not expose raw internal event/target codes;
24. Personnel card corrective layout passes: compact section spacing, clear action grouping, normal-width identifier action and compact content-width archive/restore lifecycle actions;
25. identifier corrective UI passes: compact form submit, visually separated neutral nested entries, status conveyed by badge, lifecycle actions placed below entry details and explicit Russian lifecycle action labels.

## 13. Explicit non-requirements

No fine-grained Personnel security, role creation by orders, organizational ACL, assignment, rank/VUS/service history, contacts/family, documents/photos, medical/identification, legal/financial data, SpecialCases, form generation, import/export, external integration, production deployment, branch protection or mobile verification.

These are deferred increments of the same target Personnel domain, not rejected requirements.

## 14. Gate

Implementation begins only after:

```text
Formal Review = PASS
Owner Approval = explicit
approved base/head/scope = exact
MAX_EXPECTED_CHANGED_PATHS = 40
```

Approval does not automatically authorize Pull Request, merge or branch deletion unless explicitly stated by the owner.