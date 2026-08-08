# Personnel Core Card v1 — Owner Approval

## 1. Approval record

```text
APPROVAL_STATUS=APPROVED
APPROVED_AT=2026-08-08
REPOSITORY=ClaytonKinnane/ASU-VCH
BASE_BRANCH=main
BASE_SHA=dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8
DESIGN_BRANCH=design/personnel-core-card-v1
DESIGN_HEAD=854708e8375ce3cb55a610690e546aab1c18a7cf
REVIEWED_ARCHITECTURE_SPECIFICATION_HEAD=272eb66184b45e380e92654be90fb8fccd1959a1
FORMAL_REVIEW=PASS
OPEN_FINDINGS=0
INCREMENT=Personnel Core Card v1
MIGRATION=015_personnel_core_card_v1.sql
MAX_EXPECTED_CHANGED_PATHS=40
IMPLEMENTATION_BRANCH=feature/personnel-core-card-v1
```

## 2. Approved scope

Owner approved implementation and Testing/Validation of Personnel Core Card v1 exactly according to Architecture v0.2 and Specification v0.2.

Approved prototype capabilities:

- canonical PersonnelRecord;
- ФИО;
- date/place of birth;
- citizenship, nationality and religion;
- typed identifiers `personal_number`, `service_dog_tag`, `table_number`, `call_sign`;
- list/search/card;
- create/update;
- archive/restore;
- aggregate revision and stale-write protection;
- non-destructive identifier history with effective dates;
- append-only Personnel change history;
- owner-only prototype access with existing `system_owner`;
- migration 015;
- synthetic-only validation data.

## 3. Explicitly deferred

Not approved in this increment:

- fine-grained Personnel access/security model;
- new Personnel roles/permissions/grants;
- assignment to Staffing slots;
- position/unit/rank/VUS/occupancy truth;
- contacts/family;
- files/photos/documents;
- medical/physical-identification runtime;
- legal/financial/digital runtime;
- SpecialCases;
- generated forms/reports;
- import/export or external integration;
- production deployment;
- mobile acceptance.

Deferred items remain target Personnel requirements and are not rejected functionality.

## 4. Exact path gate

Implementation changes are restricted to the exact 40-path allowlist in `PERSONNEL-CORE-CARD-V1-SPECIFICATION.md`. Any path outside that list requires fail-closed re-approval.

## 5. Lifecycle authorization boundary

Authorized by this gate:

```text
create feature/personnel-core-card-v1
Implementation
Testing/Validation
```

Not authorized by this gate:

```text
scope expansion
Pull Request
merge
branch deletion
force-push/history rewrite
production deployment
```

The owner additionally requested notification before introducing any new reference catalog beyond the four fixed system identifier types approved in Specification v0.2.
