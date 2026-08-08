# Personnel — будущая модель доступа к сведениям

## 1. Статус

```text
DOCUMENT=Deferred Security Design Notes
DOMAIN=Personnel / Security
STATUS=DEFERRED
OWNER_DECISION_DATE=2026-08-08
RUNTIME_IMPLEMENTATION=NOT AUTHORIZED IN PERSONNEL CORE CARD V1
```

Документ сохраняет архитектурные выводы по организации доступа к сведениям военнослужащих, чтобы к ним вернуться после создания работающего prototype Personnel data/accounting.

Это не Specification текущего security increment и не основание для изменения ролей/permissions сейчас.

## 2. Решение владельца

Целевая АСУ-ВЧ должна уметь хранить все необходимые сведения о действующем военнослужащем, включая специальные, медицинские, идентификационные, правовые, финансовые, цифровые и ситуационные данные, если они требуются военным процессам.

Ограничение доступа должно решаться не исключением данных из Personnel model, а системой полномочий пользователей.

Реальные роли и полномочия должны в будущем создаваться/назначаться на основании соответствующих приказов и иных утвержденных документов.

Разработка этой расширенной системы доступа сознательно отложена.

## 3. Базовый security invariant

```text
MILITARY_POSITION != SECURITY_ROLE
```

Воинская должность человека и роль пользователя АСУ-ВЧ — разные сущности.

Например, наличие у военнослужащего конкретной командной должности не должно само по себе автоматически выдавать информационные полномочия. Система должна иметь отдельное проверяемое основание назначения роли.

## 4. Целевая модель решения о доступе

Будущий authorization должен учитывать не только `user → role → permission`, а как минимум:

```text
USER
× ROLE
× DATA_DOMAIN
× OPERATION
× ORGANIZATIONAL_SCOPE
× VALIDITY_PERIOD
× AUTHORITY_DOCUMENT
```

Где:

- `DATA_DOMAIN` — группа сведений: identity, service, family, medical, physical-identification, legal, finance, special-case и т.д.;
- `OPERATION` — конкретное действие;
- `ORGANIZATIONAL_SCOPE` — область военнослужащих/подразделений, к которым применяется полномочие;
- `VALIDITY_PERIOD` — период действия полномочия;
- `AUTHORITY_DOCUMENT` — приказ/иной документ, являющийся основанием назначения.

## 5. Целевые операции

Минимальный future vocabulary:

```text
VIEW
CREATE
UPDATE
ARCHIVE
RESTORE
VIEW_HISTORY
DOWNLOAD
PRINT
EXPORT
GENERATE_REPORT
MANAGE_CASE
VIEW_ACCESS_AUDIT
```

Разные операции над одной категорией данных могут требовать разных permissions.

Например, право просматривать документ не обязано означать право скачать его, распечатать или экспортировать.

## 6. Документальное основание назначения роли

В будущем assignment security role должен иметь доказуемое основание.

Целевая структура:

```text
user_role_assignment
├── user_id
├── role_id
├── organizational_scope
├── valid_from
├── valid_to
├── authority_document_id / reference
├── authority_document_number
├── authority_document_date
├── assigned_by
├── assigned_at
└── revoked_at
```

Точная схема будет определена отдельной Security Architecture и не должна внедряться скрыто в Personnel Core Card v1.

## 7. Organizational scope

Будущая модель должна уметь выражать минимум:

- всю разрешенную установку/контур;
- конкретную организационную структуру;
- конкретный organizational element;
- элемент и разрешенных descendants;
- при необходимости специальную case-based область.

Scope не должен выводиться автоматически только из должности пользователя.

## 8. Разделы карточки и deny-by-default

После реализации fine-grained access вкладка/раздел, на который у пользователя нет права, не должен раскрываться через обычную навигацию.

Authorization должен применяться на сервере к каждому read/write endpoint независимо от визуального скрытия элемента UI.

Отдельно должны контролироваться чувствительные data domains, в том числе:

```text
medical
physical_identification
psychological_behavioral
legal_disciplinary
financial_identifiers
digital_accounts
special_cases
restricted_documents
```

Список не является окончательным.

## 9. Аудит доступа

Для расширенного Personnel security недостаточно аудита только изменения записей.

Будущая модель должна регистрировать как минимум успешные и, где обосновано, отклоненные действия:

```text
VIEW
DOWNLOAD
PRINT
EXPORT
GENERATE_REPORT
UPDATE
ARCHIVE/RESTORE
```

Audit record должен позволять установить:

- actor user;
- personnel record;
- data domain;
- operation;
- organizational/case scope;
- timestamp;
- result;
- использованную security role/authority context, если это требуется моделью.

При этом audit не должен копировать в лог полный объем чувствительного содержимого.

## 10. Связь с приказами и будущим Documents domain

На целевом уровне security assignment должен ссылаться на документ-основание, а не хранить его смысл только свободным текстом.

До появления common Documents runtime допустим переходный metadata reference, но final architecture должна связывать полномочия с каноническим документом/приказом.

## 11. Никаких автоматических grants из Personnel data

Запрещенное target behavior:

```text
person has position X → automatically receives role Y
person has rank X → automatically receives role Y
person belongs to unit X → automatically receives permission Y
```

Такие факты могут использоваться для проверки совместимости или предупреждения, но не заменяют документально оформленное назначение полномочий.

## 12. Временная модель prototype

До отдельного Security increment применяется строго временная граница:

```text
Personnel Core Card v1 access = system_owner only
new Personnel permissions = 0
new non-owner grants = 0
organizational fine-grained scope = NOT IMPLEMENTED
```

Это сознательно упрощенная модель для разработки prototype.

Она не считается окончательным решением Security и не должна быть представлена как модель operational access для будущей эксплуатации.

## 13. Что отложено

Отдельный будущий increment должен определить:

1. role taxonomy;
2. permission taxonomy;
3. data-domain classification;
4. organizational scope expressions;
5. authority-document model;
6. validity/revocation rules;
7. view/download/print/export audit;
8. emergency/break-glass access, если он будет нужен;
9. delegation/substitution rules;
10. access review/re-certification;
11. integration with common Audit/Documents;
12. migrations и переход с owner-only prototype.

## 14. Trigger для возвращения к документу

К этой модели необходимо вернуться до любого из следующих событий:

- выдача Personnel access обычным non-owner roles;
- production deployment Personnel module;
- внешняя интеграция с Personnel data;
- массовый export/print/download;
- отдельный Medical или Special Cases operational rollout;
- утверждение реальных организационных ролей пользователей.

До этого документ остается `DEFERRED` и не блокирует owner-only prototype.