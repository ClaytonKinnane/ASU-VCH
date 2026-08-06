# Домен Staffing

## 1. Назначение

`Staffing` — домен нормативной штатной структуры внутри единственного контура `PersonnelServiceAccounting`.

```text
INCREMENT=Lowest Unit Staffing Structure v1
VERSION=0.2
BASE_SHA=d60db94e405979c8f29bdc3dcaae7950362fb13a
FEATURE_BRANCH=feature/lowest-unit-staffing-v1
IMPLEMENTATION=NOT STARTED
```

Он описывает штатные документы и индивидуальные позиции, но не хранит военнослужащих и не утверждает фактическую занятость.

## 2. Ответственность

- register штатной структуры;
- versioned snapshots;
- документы-основания без файлов;
- stable slot identities;
- individual slot snapshots;
- Organization binding;
- position/rank/VUS requirements;
- lifecycle;
- change history;
- read models по organizational elements.

## 3. Вне домена

- собственное организационное дерево;
- редактирование справочников;
- военнослужащие и назначения;
- occupancy/vacancy facts;
- кадровые приказы по лицам;
- files/photos;
- leave/medical;
- `CitizenMilitaryAccounting`;
- общий Security Audit;
- external integration.

## 4. Dependencies

```text
Staffing → Organization
Staffing → Directory/Reference catalogs
Staffing → existing Security authorization
Staffing → DB/HTTP infrastructure
```

Forbidden in v1:

```text
Staffing → Personnel
Staffing → Assignments
Staffing → Orders
Staffing → DocumentsVault
Staffing → CitizenMilitaryAccounting
```

## 5. Entities

### StaffingRegister

- immutable unique code;
- name/note;
- one organizational structure;
- `active/archived` status;
- archive only without pending version.

### StaffingVersion

- one register;
- optional based-on version;
- pinned Organization version;
- pinned position/rank/VUS versions;
- unique version number;
- status and effective interval;
- reason/revision/lifecycle metadata.

Only one pending and one active version per register.

### StaffingSlotIdentity

Stable immutable identity across versions. It is never reused for another normative position.

### StaffingSlot

Version snapshot containing:

- slot identity;
- stable organizational element;
- position type and optional variant;
- code/name/sort order;
- min/max/preferred rank;
- normative state `active/suspended/closed`;
- VUS requirements;
- note.

One row equals one slot. `quantity`, person and assignment fields are forbidden.

### StaffingDocument

Metadata only. Published metadata immutable; changed draft uses copy-on-write.

### StaffingVersionDocument

Roles:

```text
primary_basis|additional_basis|amendment
```

Approved/active version has exactly one primary basis.

### StaffingSlotVusRequirement

```text
required|allowed|preferred
```

No duplicate VUS per slot; all values belong to pinned version.

### StaffingChangeEvent

Append-only successful business event with actor, target, before/after summary, reason and timestamp.

## 6. Aggregates

### StaffingRegister aggregate

Commands:

- create/update;
- archive/restore;
- create initial draft;
- create draft from active.

### StaffingVersion aggregate

Commands:

- manage draft documents/slots/VUS;
- approve/cancel/activate;
- compare/history.

All mutations are transactional.

## 7. Lifecycle

```text
draft → approved → active → superseded
draft → cancelled
approved → cancelled
```

Rules:

- content mutable only in draft;
- initial draft pins current compatible catalogs;
- draft from active copies **the same pinned catalogs**;
- catalog upgrade of an active register is outside v1;
- activation atomically supersedes previous active;
- no published-state rollback to draft;
- periods are `[effective_from, effective_to)`.

## 8. Organization invariants

1. Register references an existing OrganizationalStructure.
2. Version references a version of the same structure.
3. Pinned Organization version is active or superseded.
4. Slot references stable `organizational_structure_elements.id`.
5. Element is present in pinned snapshot.
6. Root and non-root elements are both allowed.
7. Position context compatibility is checked when catalog relation exists.
8. Staffing never changes Organization rows.

The product begins with lower units, but this is a rollout rule, not a DB prohibition on root-level positions.

## 9. Catalog invariants

1. Pinned versions are published.
2. Rank version equals the rank version pinned by position catalog.
3. Position type belongs to position version.
4. Variant belongs to selected type/version.
5. Rank IDs belong to rank version; min ≤ max; preferred lies within range.
6. VUS IDs belong to pinned VUS version.
7. Free-form official codes are prohibited.

## 10. Assignment-state semantics

Normative state:

```text
active|suspended|closed
```

Assignment state:

```text
not-managed-in-v1
```

The UI must not call a slot occupied or vacant until Assignments domain exists.

## 11. Persistence

```text
staffing_registers
staffing_slot_identities
staffing_versions
staffing_documents
staffing_version_documents
staffing_slots
staffing_slot_vus_requirements
staffing_change_events
```

Required:

- composite keys/FKs where possible;
- triggers for cross-table invariants and immutability;
- pending/active guards;
- append-only events;
- no physical deletion of published history.

## 12. Concurrency

Lock order:

```text
register → version → children
```

Draft command requires `expected_revision`; success increments it. Stale command rolls back entirely.

## 13. Permissions

```text
staffing.registers.view
staffing.registers.create
staffing.registers.update
staffing.registers.publish
staffing.registers.archive
staffing.registers.history
```

Owner uses `system.*.*`. No automatic grant to other roles. V1 is module-scoped; subtree ACL is deferred.

## 14. Domain events

```text
StaffingRegisterCreated
StaffingRegisterUpdated
StaffingRegisterArchived
StaffingRegisterRestored
StaffingDraftCreated
StaffingSlotAdded
StaffingSlotUpdated
StaffingSlotRemovedFromDraft
StaffingDocumentLinked
StaffingDocumentUnlinked
StaffingVersionApproved
StaffingVersionCancelled
StaffingVersionActivated
StaffingVersionSuperseded
```

## 15. Test-data rule

Only synthetic non-sensitive codes, units, documents and slots enter migrations, tools or documentation. No real штат, unit name, location or person is committed.

## 16. Future increments

- catalog migration/remapping;
- delegated subtree scope;
- Personnel Core;
- Assignments and computed vacancy;
- Orders/Documents integration;
- import/export and reports;
- higher-echelon aggregation.

## 17. Explicit exclusion

No fields or processes for conscripts, reserve, booking, summons, citizen registration or state military-accounting registries may enter this domain.