# Architectural Patterns

## Purpose

This document defines recurring architectural patterns used across ASU-VCH. It complements domain documents and ADRs but does not replace them.

Priority when documents conflict:

1. accepted ADR;
2. domain architecture approval;
3. domain specification and ERD;
4. this document;
5. implementation notes.

## Documentation First

Every material feature follows:

```text
Architecture
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

No database or business implementation begins before the relevant architecture and migration specification are approved.

## Domain ownership

Each business concept has exactly one owning domain.

The owning domain:

- defines invariants;
- owns writes;
- owns lifecycle rules;
- publishes domain events;
- exposes explicit application contracts.

Other domains reference the owner but do not duplicate or mutate its data model.

## Aggregate pattern

An aggregate has one aggregate root.

Rules:

- external commands target the root;
- child entities cannot be modified independently;
- invariants spanning child entities are enforced inside the aggregate transaction;
- repository contracts are expressed primarily around the aggregate root;
- direct table access must not bypass aggregate rules.

## Immutable historical entities

Historical facts should be append-only when correction must preserve the original state.

Typical examples:

- audit records;
- document versions;
- document files;
- assignment and revocation history.

Immutable tables normally omit:

- `updated_at`;
- `deleted_at`.

Where database-level protection is practical, UPDATE and DELETE are rejected by triggers or permissions.

Corrections create new records or explicit compensating events.

## Soft deletion

Soft deletion is used only for mutable aggregate roots where removal from active use is required without destroying history.

Standard column:

```text
deleted_at DATETIME(6) NULL
```

Rules:

- soft deletion does not imply identifier reuse;
- historical child entities are not independently soft-deleted;
- registered or legally significant records should use lifecycle statuses instead of deletion;
- FK behavior remains RESTRICT;
- restoration is an explicit domain operation and must preserve invariants.

## Reference data

Reusable classifications belong to the Reference domain unless they are true internal constants with no administrative or reporting value.

Reference rules:

- no MySQL ENUM;
- lowercase immutable codes;
- group membership validated by domain logic and, for critical constraints, database triggers;
- foreign keys point to `reference_values(id)`;
- a reference value from the wrong group is invalid even if the FK exists;
- system values cannot have machine codes changed or be deleted.

## Identity pattern

Internal relational identity uses:

```text
id BIGINT UNSIGNED AUTO_INCREMENT
```

Externally exposed aggregates may additionally use an immutable public identifier, normally UUID stored as `BINARY(16)`.

Rules:

- internal IDs are not stable integration contracts;
- public IDs never change;
- business identifiers remain separate from technical identifiers;
- historical business identifiers are not reused unless a domain specification explicitly allows it.

## Foreign keys

Default FK policy:

```text
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

Cascade deletion is prohibited unless approved by a dedicated architectural decision.

A nullable FK is permitted only when the domain meaning of absence is explicitly documented.

## Domain events and Audit

Domains publish past-tense events describing completed facts.

Convention:

```text
<Aggregate><PastTenseVerb>
```

Examples:

- `DocumentRegistered`
- `UserBlocked`
- `RoleAssigned`

Rules:

- events are emitted only after successful commit;
- domains do not call Audit persistence directly;
- Audit consumes events through infrastructure contracts;
- event payloads contain stable identifiers and sufficient historical context;
- secrets and sensitive raw content are excluded.

## Actor references

A domain may store `created_by_user_id`, `assigned_by_user_id`, or equivalent technical actor references without acquiring a business dependency on Security.

Rules:

- Security services are not called from domain entities;
- FK usage is allowed where approved;
- system or installation events may use nullable actors only when documented;
- Audit retains actor snapshots independently.

## Transactions and concurrency

Commands that modify an aggregate execute in a database transaction.

Use row locking when deriving sequential or exclusive state:

```sql
SELECT ... FOR UPDATE
```

Examples:

- document version numbering;
- active assignment replacement;
- ownership transfer.

Database constraints remain the final protection against races; application checks alone are insufficient for critical uniqueness rules.

## External resource coordination

A database transaction cannot atomically include filesystem, network, or external-service changes.

Approved pattern:

1. prepare or stage external data;
2. validate it;
3. begin the database transaction;
4. persist metadata;
5. finalize the external operation;
6. commit;
7. execute compensation if either side fails;
8. publish events after commit.

Systems using this pattern must include reconciliation for failed compensation and orphaned resources.

## Generated columns and uniqueness

Generated nullable columns may enforce conditional uniqueness in MySQL.

Approved use cases include:

- one active default per Reference group;
- one active role assignment;
- one primary file per document version.

The expression must depend only on data available in the same row. Semantics requiring another table must be materialized and protected by triggers or redesigned.

## Database triggers

Triggers are appropriate for invariants that must hold regardless of application entry point, including:

- Reference-group membership;
- immutable table protection;
- cross-row ownership checks;
- lifecycle restrictions;
- conditional uniqueness support.

Trigger rules:

- logic must be documented in migration specifications;
- errors must be deterministic and testable;
- triggers must not hide core business workflows from the application layer;
- the same invariant should be validated in domain code for clear user feedback.

## Status lifecycles

Status transitions are explicit domain rules, not arbitrary updates.

Each domain must document:

- initial status;
- allowed forward transitions;
- prohibited reverse transitions;
- required data for each transition;
- terminal states;
- restoration or cancellation semantics.

Reference stores the status values; the owning domain defines transition logic.

## Security boundaries

Permissions authorize actions but never bypass:

- domain invariants;
- validation;
- CSRF protection;
- audit requirements;
- transactional consistency;
- secret-handling rules.

Infrastructure resources such as files must not be exposed directly when application authorization is required.

## Migration specifications

Every domain schema requires a migration specification before executable migrations.

The specification defines:

- migration order;
- tables and columns;
- data types;
- keys and indexes;
- FK actions;
- generated columns;
- triggers;
- seed data;
- rollback policy;
- verification tests;
- implementation gate.

Executable migrations must not introduce undocumented schema decisions.

## Testing obligations

Critical invariants require integration tests against MySQL, not only unit tests.

Tests should cover:

- FK actions;
- unique constraints;
- trigger rejection paths;
- soft-delete behavior;
- concurrent writes;
- transaction rollback;
- Reference-group validation;
- immutable-record protection;
- compensation behavior for external resources.

## Change control

A recurring pattern may be changed only when:

- the limitation is documented;
- affected domains are identified;
- migration and compatibility effects are evaluated;
- an ADR is created for project-wide changes;
- frozen domain approvals are updated where necessary.

## Status

```text
ARCHITECTURAL PATTERNS: APPROVED
APPLICABILITY: PROJECT-WIDE
```
