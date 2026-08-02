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

Every material change follows:

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
→ Final PR Review
→ separate merge approval
→ Merge
→ post-merge verification
→ separate branch deletion approval
```

No material database, runtime, documentation-baseline or repository-cleanup implementation begins before the relevant scope is reviewed and approved.

## Current state versus historical evidence

Documentation belongs to the following semantic classes:

1. **Living documentation** — describes the current merged baseline and must be refreshed after material merges.
2. **Living indexes** — current inventories embedded in catalogs that may also contain target documents, such as `docs/domains/README.md` and `docs/migrations/README.md`.
3. **Target architecture** — approved or researched future model that may be wider than implemented runtime.
4. **Historical implemented specifications** — original requirements of completed increments, preserved with explicit temporal framing.
5. **Historical process artifacts** — Architecture, Specification, Review, Approval and dated Test Evidence preserving the state of their gate.
6. **Operational increment records** — may contain both attempt history and a current-status section; post-merge closure updates current framing without deleting history.
7. **Immutable audit/cleanup records** — dated snapshots that do not impose a permanent future repository state.

### Semantic classification overrides directory classification

A document or section is treated as living/current-state whenever it asserts any of the following, regardless of its folder:

- current functional or documentation baseline;
- current migration numbering;
- current map of implemented domains or catalogs;
- current roles, permissions, themes, routes or runtime capabilities;
- current repository, PR, Issue or branch state.

A mixed document must label its current-state, target and historical sections explicitly. A file is not exempt from baseline refresh merely because most neighboring files are target specifications or historical evidence.

Baseline refresh scope must therefore include every semantically living document and living index affected by the merged change. The refresh audit records the evaluated current-state document set and explains any exclusion.

Rules:

- current repository HEAD is resolved dynamically through `origin/main`;
- exact SHA values are stored as historical merge/test/refresh anchors;
- documentation-only commits are never presented as runtime-tested heads;
- stale current-state fields must not remain in living documentation or living indexes;
- target architecture must point to current-state sources rather than imply complete implementation;
- historical specifications receive status banners or closure addenda without rewriting original requirements;
- historical `NOT AUTHORIZED`, `NOT CREATED` or `RECHECK REQUIRED` markers are preserved when they accurately describe the recorded moment;
- links in target/historical documents must still resolve unless explicitly marked as obsolete evidence;
- current PR/Issue/branch state is not persisted as a permanent living field.

## Domain ownership

Each business concept has one owning domain. The owner defines invariants, owns writes and lifecycle rules, publishes events and exposes explicit contracts. Other domains do not duplicate or mutate its model.

## Aggregate pattern

An aggregate has one root. External commands target the root; child entities are not modified independently; cross-child invariants are enforced in the aggregate transaction; direct table access must not bypass aggregate rules.

## Immutable historical entities

Historical facts should be append-only when correction must preserve original state. Typical examples include audit records, published catalog versions, source snapshots, document versions and assignment history.

Immutable tables normally omit `updated_at` and `deleted_at`. UPDATE/DELETE should be rejected by triggers or permissions where practical. Corrections create a new record or explicit compensating event.

## Versioned public catalog

Public normative/reference catalogs use version roots and immutable published children.

Approved patterns:

### Whole-catalog versioning

Used when one coherent normative set is published as a unit.

- one current published version;
- child rows belong to exactly one version;
- publication freezes all child rows;
- lifecycle transitions are explicit and forward-only;
- evidence rows cannot cross versions.

Example: military position types catalog.

### Source-centric catalog

Used when public disclosures from heterogeneous official sources must remain distinguishable.

- catalog version defines the public release boundary;
- legal sources and official snapshots are first-class records;
- each disclosed record retains source/evidence context;
- semantic normalization does not exceed what the source supports;
- incomplete public coverage is stated explicitly;
- published records are immutable.

Example: public military occupational specialties catalog.

For both patterns:

- runtime scraping/import is not implied;
- no completeness claim without evidence;
- no automatic cross-domain relation merely because identifiers look similar;
- source URLs are validated and output escaped;
- owner-only read-only UI may reuse `system.*.*` without adding permissions.

## Evidence-bounded identifiers

An identifier is decomposed only when an authoritative source supports the decomposition.

Rules:

- raw identifiers are preserved;
- parsed components may be nullable;
- identifiers from different source regimes are not forced into one semantic format;
- unknown structure is represented explicitly rather than guessed;
- UI labels distinguish normative examples, complete codes and official program identifiers.

## Filter composition policy

When a filter applies only to one record subtype, result composition must be explicit and testable.

Example pattern:

- organization applies to training programs;
- selecting organization excludes unrelated direct-disclosure records;
- selecting an incompatible record type and organization yields a valid empty state;
- policy is implemented in a testable repository/application function and covered by integration plus manual checks.

## Soft deletion

Soft deletion is used only for mutable aggregate roots where removal from active use is required without destroying history.

```text
deleted_at DATETIME(6) NULL
```

Historical child entities are not independently soft-deleted. Registered or legally significant records use lifecycle statuses. FK behavior defaults to RESTRICT. Restoration is explicit.

## Reference data

Reusable classifications belong to the Reference domain unless they are true internal constants.

- no MySQL ENUM;
- lowercase immutable codes;
- group membership validated by domain logic and critical DB guards;
- system values cannot have machine codes changed or be deleted.

## Identity pattern

Internal relational identity:

```text
id BIGINT UNSIGNED AUTO_INCREMENT
```

Externally exposed aggregates may additionally use immutable UUIDs. Business identifiers remain separate from technical identifiers and are not reused without explicit specification.

## Foreign keys

Default policy:

```text
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

Cascade deletion requires a dedicated approved decision. Nullable FK requires documented absence semantics.

## Domain events and Audit

Domains publish past-tense facts after successful commit. Event payloads contain stable identifiers and sufficient historical context but no secrets or unnecessary sensitive content. Audit consumes events through infrastructure contracts.

## Actor references

Domains may store technical actor references without acquiring business ownership of Security. Nullable actors are allowed only when documented. Audit retains independent actor snapshots where required.

## Transactions and concurrency

Commands modifying an aggregate run in a database transaction. Use `SELECT ... FOR UPDATE` for sequential or exclusive state. Database constraints remain final race protection.

## External resource coordination

Database transactions cannot atomically include filesystem/network operations. Approved sequence: stage, validate, begin transaction, persist metadata, finalize external operation, commit, compensate on failure, publish events after commit. Reconciliation is required for failed compensation.

## Generated columns and uniqueness

Generated nullable columns may enforce conditional uniqueness when the expression depends only on the same row. Cross-table semantics must be materialized, trigger-protected or redesigned.

## Database triggers

Triggers are appropriate for invariants that must hold regardless of application entry point:

- reference-group membership;
- immutable published data;
- lifecycle restrictions;
- cross-version relation guards;
- conditional uniqueness support.

Trigger errors must be deterministic and tested. Application code should also validate for clear user feedback.

## Status lifecycles

Each domain documents initial status, allowed transitions, prohibited reverse transitions, required data, terminal states and restoration/cancellation semantics.

## Security boundaries

Permissions never bypass validation, CSRF, domain invariants, audit, transactions or secret handling. Infrastructure resources are not exposed directly when application authorization is required.

Publicly documented local-only development fixtures are not production secrets, but must be explicitly scoped to local environments, require replacement on first use and never be reused as instance-specific credentials. Real temporary passwords, production credentials, session data, private keys, tokens and local configuration remain secret.

## Migration packaging and compatibility

When transport/parser constraints prevent storing one executable canonical SQL file directly:

- keep a small marker migration;
- package canonical SQL deterministically;
- split only at transport layer, not semantic SQL boundaries;
- verify archive hash, canonical SQL hash, part order and byte count before execution;
- fail closed on mismatch;
- test repeat installer and source/deploy parity.

Packaging is a compatibility mechanism, not a second schema source of truth.

## Migration specifications

Every domain schema requires an approved migration specification covering order, tables, columns, keys, FK actions, triggers, seed, packaging, rollback policy and verification tests. Executable migrations must not introduce undocumented decisions.

## Testing obligations

Critical invariants require integration tests against MySQL. Tests should cover FK actions, unique constraints, trigger rejection, lifecycle, cross-version guards, transaction rollback, immutable records, source/deploy parity and security regressions.

Manual acceptance is required for user-visible behavior defined by Specification. Mobile PASS is not claimed without actual mobile acceptance.

## Repository cleanup pattern

Branch cleanup is a separate administrative operation after merge.

Required sequence:

1. post-merge verification;
2. documentation baseline refresh when current docs are stale;
3. fresh remote and local inventory;
4. reachability/unique-commit checks against current `origin/main`;
5. exact cleanup batch;
6. separate owner approval;
7. remote deletion first when possible;
8. local deletion only within approved scope;
9. final inventory and `main` integrity verification.

`SAFE TO DELETE` is a technical classification, never an authorization.

## Change control

A recurring pattern changes only when the limitation, affected domains, migration/compatibility effects and required ADR updates are documented and approved.

## Status

```text
ARCHITECTURAL PATTERNS: APPROVED
APPLICABILITY: PROJECT-WIDE
CURRENT BASELINE COVERAGE: THROUGH PR #20 / MIGRATION 011
```
