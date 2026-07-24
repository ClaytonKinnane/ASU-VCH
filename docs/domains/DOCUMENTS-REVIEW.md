# Documents Domain Architecture Review

## Status

**Review result:** APPROVED  
**Architecture status:** READY FOR ERD

## 1. Review Scope

This review validates `docs/domains/DOCUMENTS.md` against the project architecture, ADRs, naming rules, database conventions, domain boundaries, integrity requirements, and version 1 scope.

## 2. Architecture Compliance

| Requirement | Result |
|---|---|
| Documentation First | PASS |
| Domain-Oriented Architecture | PASS |
| Architecture as Source of Truth | PASS |
| Reference-based classifiers | PASS |
| No MySQL ENUM | PASS |
| No cyclic domain dependencies | PASS |
| Soft-delete policy | PASS |
| RESTRICT relationship policy | PASS |

## 3. Domain Boundary

The domain has one clear responsibility: document registration, lifecycle, versioning, file representation, storage abstraction, and document-to-document relations.

Documents does not own:

- order workflows;
- personnel decisions;
- medical conclusions;
- equipment operations;
- authentication or authorization;
- audit persistence.

Other business domains own their references to Documents. Documents does not contain polymorphic `owner_type` / `owner_id` fields.

**Result:** PASS

## 4. Aggregate Review

`Document` is the aggregate root.

`DocumentVersion`, `DocumentFile`, and `DocumentRelation` are subordinate entities whose mutations are coordinated by the aggregate or its application services.

Direct independent mutation of child entities is prohibited by architecture.

**Result:** PASS

## 5. Document and File Separation

The model correctly distinguishes:

```text
Document != File
```

A document may exist as a draft without a file, may have multiple immutable versions, and each version may have one primary representation plus attachments.

**Result:** PASS

## 6. Public Identifier

A stable non-sequential `public_id` is approved for external integrations and public-facing references.

Rules:

- internal joins continue to use `id BIGINT UNSIGNED`;
- `public_id` is unique and immutable;
- external integrations should not depend on sequential primary keys;
- the concrete storage type is finalized in the ERD.

**Result:** APPROVED

## 7. Lifecycle Review

Approved initial statuses:

- `draft`;
- `registered`;
- `approved`;
- `cancelled`;
- `archived`.

Approved normal path:

```text
draft -> registered -> approved -> archived
```

Approved cancellation paths:

```text
draft -> cancelled
registered -> cancelled
approved -> cancelled
```

Reverse transitions are prohibited unless a future architecture revision explicitly introduces them.

A registered, approved, cancelled, or archived document cannot be deleted. A draft may be soft-deleted only when no protected relationship or business reference prevents it.

Because Documents cannot discover arbitrary references owned by future domains, deletion eligibility must be enforced by application use cases and physical foreign keys in referencing domains. No cascade delete is permitted.

**Result:** APPROVED

## 8. Registration Number

Approved rules:

- optional only for draft documents;
- mandatory on registration;
- normalized by trimming;
- immutable after registration;
- never reused;
- uniqueness applies historically, not only to active rows;
- uniqueness scope is `(document_type_id, registration_number)`.

No soft-delete-aware generated column is used for registration uniqueness because historical reuse is forbidden.

**Result:** APPROVED

## 9. Versioning

Approved rules:

- version numbering begins at `1`;
- `(document_id, version_number)` is unique;
- versions are immutable;
- persisted versions are not soft-deleted;
- concurrent version creation locks the parent document row;
- `current_version_id` must point to a version of the same document.

The cross-table current-version invariant cannot be expressed by a normal foreign key alone and therefore requires a mandatory database trigger in addition to domain validation and integration tests.

**Result:** APPROVED WITH MANDATORY TRIGGER

## 10. Primary File Invariant

Exactly one primary file is allowed per completed document version.

Approved implementation direction:

- `file_role` is stored as a constrained machine code in version 1;
- allowed values are `primary` and `attachment`;
- a generated column exposes `document_version_id` only for primary files;
- a unique index on that generated column prevents more than one primary file per version;
- version completion and selection as current require one primary file;
- attachments may be multiple.

A normal unique index on `(document_version_id, file_role)` is rejected because it would allow only one attachment.

**Result:** APPROVED

## 11. File Immutability and Integrity

SHA-256 is mandatory, server-calculated, and immutable.

MIME type is server-detected. Client extension and `Content-Type` are not trusted.

`storage_key` is system-generated and globally unique. Original filenames are display metadata only.

Identical hashes do not cause automatic deduplication.

**Result:** PASS

## 12. Storage Boundary

Documents works only with `StorageProvider` and `storage_key`.

It does not know absolute paths, web-root layout, S3 concepts, NAS paths, or operating-system-specific locations.

Version 1 uses a local filesystem provider outside the Apache public directory.

**Result:** PASS

## 13. Database and Filesystem Consistency

The approved workflow is:

1. upload to temporary storage;
2. validate size and MIME type;
3. calculate SHA-256;
4. begin DB transaction;
5. create metadata;
6. move file to permanent storage;
7. commit DB transaction;
8. publish events after commit.

Compensation is mandatory when commit fails after the permanent move.

Orphan cleanup is an infrastructure maintenance concern. It must not silently change document history.

**Result:** APPROVED

## 14. Document Relations

Relations are directed.

Approved invariants:

- no self-relation;
- no duplicate `(source_document_id, target_document_id, relation_type_id)`;
- no automatic reverse relation;
- no cascade delete;
- relation types use Reference.

Self-relation prevention requires a database `CHECK` constraint and domain validation.

**Result:** PASS

## 15. Reference Groups

Approved groups:

```text
document_status
document_type
document_relation_type
```

Mandatory validation must ensure that each foreign key points to a value from the correct Reference group. Because `reference_values(id)` alone does not encode the group, database triggers are required in addition to application validation.

**Result:** APPROVED WITH MANDATORY TRIGGERS

## 16. User References

`created_by_user_id` may physically reference `users(id)` with `ON DELETE RESTRICT` and `ON UPDATE RESTRICT`.

This is an integration-level database relationship and does not authorize Documents to call Security services or own Security rules.

**Result:** PASS

## 17. Event Publication

Domain events are published only after a successful commit.

Documents must not call Audit directly. Audit consumes events through the shared infrastructure mechanism.

**Result:** PASS

## 18. Security Review

Mandatory controls are defined:

- files outside web root;
- authorization before file delivery;
- generated storage keys;
- path traversal prevention;
- server-side MIME detection;
- configuration-driven limits;
- executable-file prohibition;
- temporary upload staging;
- compensation on cross-resource failure.

**Result:** PASS

## 19. Required ERD Decisions

The ERD must finalize:

- concrete type and format of `public_id`;
- exact MySQL column types and lengths;
- nullable fields;
- all foreign-key names;
- generated column for one primary file;
- trigger definitions for current-version ownership;
- trigger definitions for Reference group membership;
- status-dependent registration requirements;
- indexing for common search paths;
- whether `document_versions` and `document_files` omit `updated_at` and `deleted_at` to reflect immutability.

## 20. Review Checklist

- [x] Aggregate root is defined.
- [x] Document is separate from File.
- [x] Versions are immutable.
- [x] Exactly one primary file is allowed per completed version.
- [x] StorageProvider isolates infrastructure.
- [x] SHA-256 is mandatory.
- [x] MIME type is detected by the server.
- [x] Registration number is immutable and never reused.
- [x] Reference is used for classifiers.
- [x] Relations are directed.
- [x] No cyclic dependency is introduced.
- [x] Events are published after commit.
- [x] Compensation behavior is defined.
- [x] Database triggers required by cross-table invariants are identified.
- [x] Version 1 boundaries are explicit.

## 21. Final Result

```text
REVIEW RESULT: APPROVED
ARCHITECTURE STATUS: READY FOR ERD
```
