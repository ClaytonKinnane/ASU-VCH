# Предметные области АСУ-ВЧ

## Current phase

```text
Project architecture: APPROVED / evolves per increment
Functional runtime: through PR #36 / migration 014
Static CI: PR #25
Documentation governance: PR #28 + permanent rules/handoff
Local automation: PR #29/#30
Active product implementation: NONE
```

Implementation evidence is verified through `../PROJECT-STATUS.md`, `../DATABASE-CURRENT.md`, executable migrations and exact test records. Target domain files may be broader than runtime.

## Current domain map

| Domain | Current implemented state |
|---|---|
| Security | authentication, sessions/CSRF, RBAC, user lifecycle, audit safeguards |
| Reference / Directories | ranks v2+historical v1, element types, public VUS, legacy position classifier, managed Military Positions Directory v1 |
| Organization | Organizational Structure v1 |
| Staffing | Lowest Unit Staffing Structure v1 registers/versions/slots/doc metadata/history; no persons/assignments |
| Audit | domain-specific audit/events; common cross-domain Audit runtime not implemented |
| Documents | common Documents runtime not implemented; Organization/Staffing own scoped metadata |
| Infrastructure | installer/migrations/deploy/themes/checkers/static CI/local automation |

## Specialized Reference catalogs

| Functional PR | Catalog | Migrations |
|---:|---|---:|
| #8 / #24 | Military personnel compositions/ranks v1→v2 | 007 + 012 |
| #9 | Organizational element types | 008 |
| #19 / #36 | Military Positions legacy classifier → managed canonical directory | 010 + 014 |
| #20 | public military occupational specialty information | 011 |

Military Positions Directory v1 is not owner-only read-only: it has explicit view/manage/publish/history permissions plus owner wildcard and controlled draft mutations. Other public/reference routes retain their defined read-only boundary.

## Staffing domain

PR #35 / migration 013 introduced normative versioned Staffing. One slot is one stable individual normative position. Staffing may pin Organization/position/rank/VUS catalog versions but does not own those catalogs.

Not implemented:

- Personnel Core;
- person→slot assignments;
- occupied/vacant facts;
- real unit/personnel data;
- common Documents/Orders/Audit runtime.

## Domain ownership/dependencies

Reference does not depend on Organization/Staffing. Organization and Staffing consume Reference contracts. Staffing consumes Organization stable elements/version snapshots. Personnel/Assignments must be separate approved scopes when introduced.

`CitizenMilitaryAccounting` remains excluded from current target contour; active research is focused on `PersonnelServiceAccounting`.

## Research branch

`research/military-accounting-order-700` contains unique unmerged research and is not part of current `main` runtime. It is neither an active implementation nor cleanup residue.

## New domain increment workflow

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing/Validation → Commit → Push → PR
→ exact-head Actions → Final PR Review → Merge → post-merge verification
```

Branch deletion is separately authorized. DB increments require approved physical/migration design before implementation.
