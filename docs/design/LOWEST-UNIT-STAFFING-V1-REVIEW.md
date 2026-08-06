# Lowest Unit Staffing Structure v1 — Formal Review

## 1. Review record

```text
REVIEW_STATUS=PASS
REVIEWED_DOCUMENT_HEAD=9bc736f1c195f938e2b8203ee15c171afabbd108
BASE_SHA=d60db94e405979c8f29bdc3dcaae7950362fb13a
FEATURE_BRANCH=feature/lowest-unit-staffing-v1
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS=0
IMPLEMENTATION=NOT STARTED
```

Reviewed:

```text
docs/domains/STAFFING.md version 0.2
docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md version 0.2
docs/design/LOWEST-UNIT-STAFFING-V1-SPECIFICATION.md version 0.2
```

## 2. Review scope

- compatibility with existing Organization Structure v1;
- database/domain boundaries;
- version lifecycle;
- stable identities;
- catalog pinning;
- permission model;
- concurrency;
- HTTP/UI boundaries;
- testing and exact path scope;
- exclusion of Personnel and CitizenMilitaryAccounting;
- source-derived requirements from Order No. 700 relevant to staff positions and document evidence.

## 3. Repository compatibility

### 3.1. Organization — PASS

The design reuses stable `organizational_structure_elements.id` and a pinned OrganizationalStructureVersion. It does not recreate `military_units`, `departments` or another hierarchy.

The model complies with the existing Organization addendum requiring future positions to use stable elements through a separately approved temporal/versioned model.

### 3.2. Directories — PASS

The design uses existing published position, rank and public VUS catalogs and preserves their version boundaries.

Position catalog’s pinned rank catalog is explicitly checked. Free-form official codes are prohibited.

### 3.3. Security — PASS WITH OPERATIONAL CONDITION

The design uses existing module-level RBAC, CSRF and owner wildcard. It does not claim fine-grained subtree authorization.

No automatic grants to non-owner roles are allowed.

## 4. Resolved review findings

### F-001 — Root organizational element prohibition

```text
INITIAL_SEVERITY=MAJOR
STATUS=RESOLVED
```

Initial drafts prohibited slots on the root element. The prohibition had no source or domain basis and would block legitimate headquarters/unit-level positions.

Resolution in version 0.2:

- root and non-root elements are allowed;
- rollout starts from lower units as a product sequence, not a database constraint;
- catalog compatibility and presence in pinned Organization version remain mandatory.

### F-002 — Catalog change while copying active version

```text
INITIAL_SEVERITY=MAJOR
STATUS=RESOLVED
```

Initial Specification allowed copying an active version while selecting newer catalogs without defining deterministic ID mapping.

Resolution in version 0.2:

- draft from active keeps the same Organization/position/rank/VUS versions;
- catalog upgrade/remapping is explicitly outside v1;
- future catalog migration requires a separate increment.

### F-003 — Incorrect maximum path count

```text
INITIAL_SEVERITY=MINOR
STATUS=RESOLVED
```

Initial Specification declared 42 paths while the enumerated allowlist contained 44.

Resolution:

```text
MAX_EXPECTED_CHANGED_PATHS=44
```

The path list and stated maximum now match.

## 5. Architecture review

### 5.1. Aggregate boundaries — PASS

Register owns administrative lifecycle; Version owns slots/documents/lifecycle. No generic mutation API or cross-domain writes are required.

### 5.2. Stable identity — PASS

`StaffingSlotIdentity` separates identity from snapshots and supports compare/history without destructive updates.

### 5.3. One slot per row — PASS

The absence of `quantity` avoids ambiguous assignment semantics. Future Personnel assignments can reference one stable position.

### 5.4. Lifecycle — PASS

One pending/one active guards, immutable published states, effective intervals and atomic supersession are consistent with established Organization patterns.

### 5.5. Vacancy semantics — PASS

The design correctly refuses to claim actual vacancy/occupancy before Assignments domain. This avoids false personnel statements.

### 5.6. Documents — PASS

Metadata-only documents are sufficient for v1. File storage remains separately scoped.

## 6. Specification review

### 6.1. Functional completeness — PASS

The Specification covers register CRUD, version creation/copy, documents, slots, rank/VUS requirements, approve/cancel/activate, compare and history.

### 6.2. Validation — PASS

Organization/catalog membership, rank ranges, duplicate prevention, lifecycle and revision checks are explicit.

### 6.3. HTTP security — PASS

Read-only GET, POST mutations, CSRF, permission-before-disclosure, PRG and safe errors are required.

### 6.4. UI — PASS

The feature has a permission-aware content tile, management screens and read-only representations. Three-theme desktop acceptance is required; mobile is not claimed.

### 6.5. Tests — PASS

The test matrix covers clean/current MySQL, constraints, services, authorization, CSRF, browser, themes and regressions.

## 7. Scope review

### Included and accepted

- migration 013;
- eight Staffing tables;
- six permissions;
- new `app/Staffing` domain;
- protected admin UI;
- metadata-only basis documents;
- synthetic validation tools;
- living project documentation updates.

### Excluded and confirmed

- Personnel Core;
- assignments and actual vacancy;
- files/photos;
- leaves and medical data;
- citizen military accounting;
- reserve/conscription/booking/summons;
- state military-accounting registries;
- catalog upgrade/remapping;
- subtree ACL;
- real staffing data in repository;
- production and mobile acceptance.

## 8. Source alignment

The uploaded Order No. 700 supports using documented штатная должность, VUS, order references and verified service records as source-derived design inputs. It does not justify adding the excluded citizen-accounting processes to this increment.

The design therefore uses Order No. 700 as a supporting source for evidence and field relationships, while the functional contour remains `PersonnelServiceAccounting` only.

## 9. Operational condition

```text
CONDITION-01=NO_REAL_STAFFING_DATA_BEFORE_SECURITY_FOUNDATION
```

Implementation and tests may proceed with synthetic data. The increment must not be declared approved for entry of real штатные сведения until a separate Data Classification and Security Foundation increment determines:

- information category;
- deployment boundary;
- need-to-know and delegated scope;
- threat model;
- protection/attestation requirements;
- logging and retention rules.

This condition is not a blocker for development; it is a blocker for real-data operational acceptance.

## 10. Implementation path review

The Specification enumerates 44 maximum changed paths. They are limited to:

- four process documents;
- migration 013;
- `app/Staffing`;
- `public/admin/staffing`;
- bootstrap/content integration;
- two validation tools;
- six living documentation files.

No workflow, repository setting, theme asset, deployment config or unrelated domain path is authorized.

## 11. Risk assessment

| Risk | Control | Residual |
|---|---|---|
| duplicate hierarchy | stable Organization references | low |
| stale concurrent edit | expected revision + locks | low |
| catalog mismatch | pinned versions + DB/service checks | low |
| false vacancy statement | no assignment state in v1 | low |
| published history mutation | triggers + domain commands | low |
| unauthorized access | existing RBAC/CSRF; owner-first rollout | medium until Security foundation |
| sensitive real data in repo | synthetic-only tests/docs | low |
| scope creep into citizens/personnel | explicit forbidden fields/processes | low |

## 12. Review conclusion

```text
ARCHITECTURE=PASS
SPECIFICATION=PASS
DOMAIN_CONTRACT=PASS
SOURCE_ALIGNMENT=PASS
INTERNAL_CONSISTENCY=PASS
PATH_ALLOWLIST=PASS
OPEN_FINDINGS=0
READY_FOR_OWNER_APPROVAL=YES
READY_FOR_RUNTIME_IMPLEMENTATION=NO_UNTIL_OWNER_APPROVAL
```

The next gate is owner Approval of the exact feature head, implementation scope and 44-path changed-file allowlist. No Pull Request, merge or branch deletion is authorized by this Review.