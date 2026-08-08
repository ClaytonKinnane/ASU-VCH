# Personnel Core Card v1 — Formal Review

## 1. Review record

```text
REVIEW_STATUS=PASS
REVIEWED_DOCUMENT_HEAD=272eb66184b45e380e92654be90fb8fccd1959a1
BASE_SHA=dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8
DESIGN_BRANCH=design/personnel-core-card-v1
MERGE_BASE=dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8
AHEAD=3
BEHIND=0
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS=0
IMPLEMENTATION=NOT STARTED
```

Reviewed:

```text
docs/domains/PERSONNEL.md
docs/design/PERSONNEL-ACCESS-FUTURE-DESIGN-NOTES.md
docs/design/PERSONNEL-CORE-CARD-V1-ARCHITECTURE.md version 0.2
docs/design/PERSONNEL-CORE-CARD-V1-SPECIFICATION.md version 0.2
docs/domains/README.md
docs/CHAT-HANDOFF.md
```

Exact diff against `main` at reviewed head contains only these six Markdown paths.

## 2. Review scope

- alignment with owner decision for a complete target Personnel dossier;
- reconciliation of the four owner-provided document templates into one canonical model;
- compatibility with current Organization/Staffing/Reference/Security baseline;
- first-increment boundary;
- database model;
- identifier lifecycle and uniqueness;
- optimistic concurrency and history;
- prototype access boundary;
- HTTP/UI model;
- implementation path allowlist;
- test plan;
- deferred future data/security scope.

## 3. Source/model alignment — PASS

The target Personnel domain does not discard categories present in the provided templates merely because they are sensitive. Medical, physical-identification, legal, financial, digital and special-case data remain explicit future parts of the same Personnel target architecture.

The design correctly distinguishes:

```text
canonical/temporal person facts
vs
situational SpecialCase snapshots
```

This prevents event facts such as clothing, weapons, witnesses or movement hypotheses from becoming timeless properties of a person.

Forms such as «Анкета», «Объективка» and «Контрольно-розыскная карта» are correctly modeled as future projections/snapshots over canonical data instead of duplicate person databases.

## 4. Repository compatibility — PASS

### 4.1. Organization / Staffing

Personnel Core v1 does not create another organization hierarchy and does not mutate Staffing.

The target relation remains:

```text
PersonnelRecord
→ Assignment (future)
→ StaffingSlot
→ OrganizationalElement / MilitaryPosition
```

No `position_id`, `department_id`, vacancy or occupancy truth is added to PersonnelRecord before Assignments exists.

### 4.2. Reference

V1 does not mutate Military Ranks, Military Positions or VUS catalogs and does not infer rank/VUS from a person's name or identifier.

### 4.3. Security

The owner explicitly deferred the fine-grained Personnel access model. The v1 temporary boundary is therefore intentionally:

```text
system_owner only
new Personnel permissions = 0
new non-owner grants = 0
```

The final access architecture is preserved in a dedicated deferred document instead of being lost.

### 4.4. Legacy target `DATABASE.md`

Existing target documentation still contains historical `soldiers.position_id/department_id/rank_id` concepts that conflict with the now-implemented Staffing foundation and the new Assignment-derived target.

This is not a blocker because `docs/DATABASE.md` is explicitly included in the implementation allowlist for reconciliation before the increment is closed. Runtime schema 001–014 currently has no Personnel table, so there is no physical migration conflict.

## 5. Resolved review findings

### F-P01 — Identifier uniqueness across history

```text
INITIAL_SEVERITY=MAJOR
STATUS=RESOLVED
```

Architecture v0.1 described personal-number/dog-tag uniqueness in a way that could be interpreted as active-only, allowing a historical identifier value to be reissued later.

Resolution in Architecture/Specification v0.2:

- `personal_number` and `service_dog_tag` are globally unique across retained history;
- values are never reused after ending;
- table number and call sign may be reused according to type policy;
- DB/service tests explicitly cover never-reuse behavior.

### F-P02 — Ambiguous identifier replacement/end date

```text
INITIAL_SEVERITY=MAJOR
STATUS=RESOLVED
```

Initial text allowed replace/end while leaving the ending date effectively optional, which could leave a supposedly ended row active.

Resolution in v0.2:

- temporal semantics are `[valid_from, valid_to)`;
- replace requires explicit `effective_date`;
- old row gets `valid_to=effective_date`;
- new row gets `valid_from=effective_date`;
- end without replacement also requires explicit `effective_date`;
- service/DB tests cover interval validation.

No open finding remains after these corrections.

## 6. Architecture review — PASS

### 6.1. Aggregate boundary

`PersonnelRecord` is an appropriate aggregate root for v1. Identifier mutations lock the root and use a single revision token, preventing partial/stale card changes.

### 6.2. Four-table v1 model

The proposed tables are sufficient and intentionally limited:

```text
personnel_records
personnel_identifier_types
personnel_identifiers
personnel_change_events
```

They provide extensibility without pretending to implement the full dossier in one migration.

### 6.3. Card lifecycle

`active ↔ archived` is clearly defined as record lifecycle, not military service status. This avoids false claims about dismissal, exclusion from lists or service status before Service Record exists.

### 6.4. History

Identifier replacement is non-destructive and change events are append-only. No physical-delete UI/service operation is specified.

### 6.5. Future extensibility

The design leaves separate ownership for Assignments, Documents/Media, Medical/Physical Identification, Legal/Financial data, SpecialCases and generated forms. No future category is accidentally prohibited by v1.

## 7. Specification review — PASS

### 7.1. Functional completeness

V1 contains enough behavior to be a working prototype rather than a schema-only increment:

- navigation;
- list/search;
- create/update card;
- identifier add/replace/end;
- readable identifier history;
- archive/restore;
- overall change history.

### 7.2. Validation and concurrency

Input lengths, date validation, CSRF, aggregate revision and lock order are explicit. Stale-write behavior is fail-closed.

### 7.3. HTTP boundary

All routes are owner-only, no-store/private, POST-only for mutations, CSRF protected and PRG based. No direct controller DB mutation is permitted.

### 7.4. UI

The card clearly distinguishes implemented v1 sections from future placeholders using `Не реализовано в v1`, avoiding false missing-data assertions.

No new theme asset is planned. If existing components are insufficient, implementation must stop for scope expansion rather than silently editing theme assets.

## 8. Scope/path review — PASS

Specification enumerates exactly:

```text
MAX_EXPECTED_CHANGED_PATHS=40
```

The allowlist is limited to:

- one new migration 015;
- new `app/Personnel` domain;
- new protected Personnel admin UI;
- content landing integration;
- two validation tools;
- required living DB/access/status/roadmap/traceability/handoff documentation;
- Personnel target/design/review/approval records.

Explicitly not allowlisted:

- theme asset files;
- workflows;
- repository settings;
- deployment config;
- migrations 001–014;
- Organization runtime;
- Staffing runtime;
- Reference runtime;
- fine-grained Security runtime.

Any need for such a path requires fail-closed re-approval.

## 9. Testing review — PASS

The test plan covers:

- clean and current MySQL 8.4 paths;
- repeat initialization;
- exactly four new tables;
- exactly four identifier-type seeds;
- zero seeded persons;
- permissions total remaining 35;
- never-reuse personal number/dog tag;
- temporal identifier intervals;
- stale revision rollback;
- owner/non-owner/anonymous HTTP behavior;
- three-theme desktop browser acceptance;
- regressions for Security, Organization, Staffing and directories;
- explicit `MOBILE=NOT_RUN_OUT_OF_SCOPE`;
- no production deployment claim.

Synthetic-only repository/test data remains mandatory.

## 10. Risk assessment

| Risk | Control | Residual |
|---|---|---|
| duplicate canonical persons | no unsafe FIO unique key; identifier uniqueness and future data-quality process | medium |
| stale concurrent update | root revision + `FOR UPDATE` | low |
| identifier reassignment ambiguity | never-reuse personal number/dog tag; explicit effective dates | low |
| duplicate position/unit truth | Assignments deferred; no position/unit fields in Personnel Core | low |
| accidental broad Personnel access | owner-only v1 | low for prototype |
| final access model forgotten | dedicated deferred access design note | low |
| full dossier scope explosion | staged domain plan; exact v1 exclusions | low |
| theme scope expansion | reuse existing assets or fail closed | low |
| real personnel data in repository | synthetic-only fixtures/docs/tests | low |
| production use mistaken for prototype | production deployment explicitly excluded | low |

## 11. Review conclusion

```text
TARGET_PERSONNEL_DOMAIN=PASS
ARCHITECTURE=PASS
SPECIFICATION=PASS
REPOSITORY_COMPATIBILITY=PASS
IDENTIFIER_MODEL=PASS
PROTOTYPE_ACCESS_BOUNDARY=PASS
PATH_ALLOWLIST=PASS
TEST_PLAN=PASS
OPEN_FINDINGS=0
READY_FOR_OWNER_APPROVAL=YES
READY_FOR_RUNTIME_IMPLEMENTATION=NO_UNTIL_OWNER_APPROVAL
```

The next gate is explicit owner Approval of:

```text
repository = ClaytonKinnane/ASU-VCH
base = main@dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8
reviewed design head = 272eb66184b45e380e92654be90fb8fccd1959a1
increment = Personnel Core Card v1
implementation allowlist maximum = 40 paths
migration = 015_personnel_core_card_v1.sql
access boundary = system_owner only for v1
```

This Review does not authorize runtime implementation, Pull Request, merge or branch deletion.