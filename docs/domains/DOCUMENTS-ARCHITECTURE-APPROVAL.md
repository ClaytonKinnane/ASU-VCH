# Documents Architecture Approval

## Decision

The Documents domain architecture is approved and frozen for implementation.

## Approved documentation

- `docs/domains/DOCUMENTS.md`
- `docs/domains/DOCUMENTS-REVIEW.md`
- `docs/erd/ERD-050-documents.md`
- `docs/erd/ERD-050-documents-review.md`
- `docs/migrations/DOCUMENTS-MIGRATIONS.md`

## Approved scope

The domain owns:

- document registration and lifecycle metadata;
- immutable document versions;
- immutable file metadata;
- directed document relations;
- storage abstraction through `StorageProvider`;
- file integrity metadata;
- domain events emitted after commit.

The domain does not own:

- order approval workflows;
- medical conclusions;
- personnel decisions;
- electronic signatures;
- OCR or full-text content extraction;
- physical storage implementation;
- audit persistence.

## Frozen architectural decisions

1. `Document` is the aggregate root.
2. A document is distinct from its files.
3. Content changes create immutable versions.
4. Files are immutable representations of versions.
5. `documents.public_id` uses immutable UUID data stored as `BINARY(16)`.
6. Registration numbers are immutable and never reused.
7. Registered versions require exactly one primary file.
8. Types, statuses, roles, and relation types use Reference.
9. `StorageProvider` isolates the domain from physical storage.
10. Files remain outside the web root and are served only through the application.
11. SHA-256, server-detected MIME type, size, and storage key are mandatory.
12. Document relations are directed and immutable.
13. Business domains own their references to Documents.
14. Documents does not directly depend on Audit.
15. Domain events are published only after successful commit.
16. All foreign keys use RESTRICT.
17. Child historical tables do not support soft deletion.
18. Database triggers protect Reference membership, lifecycle rules, ownership, and immutability.
19. MySQL and filesystem consistency uses temporary storage and compensating actions.
20. No MySQL ENUM is permitted.

## Implementation obligations

Implementation must include:

- migrations matching the approved specification;
- idempotent Reference seeders;
- database triggers defined by the ERD review;
- local filesystem implementation of `StorageProvider`;
- transactional version creation with row locking;
- compensation for failed database/filesystem coordination;
- authorization-controlled download endpoints;
- unit and integration tests for all documented invariants;
- domain events compatible with the Audit consumer contract.

## Change control

Changes to frozen decisions require:

1. documented problem statement;
2. impact analysis across Documents and dependent domains;
3. architecture review;
4. ADR when the change affects a cross-domain or project-wide rule;
5. updated ERD and migration specification before implementation.

Implementation details that do not alter approved invariants may be refined without reopening the domain architecture.

## Approval matrix

| Stage | Result |
|---|---|
| Domain architecture | APPROVED |
| Domain review | PASSED |
| ERD | APPROVED |
| ERD review | PASSED |
| Migration specification | APPROVED |
| Implementation readiness | YES |
| Architecture frozen | YES |

## Final status

```text
DOCUMENTS DOMAIN: COMPLETE
ARCHITECTURE: FROZEN
IMPLEMENTATION: AUTHORIZED
```
