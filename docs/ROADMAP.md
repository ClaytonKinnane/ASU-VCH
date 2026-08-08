# План разработки

## Stable baseline

```text
latest merged functional increment: PR #36 / migration 014
migrations: 001–014
roles: 4
permissions: 35
themes: 3
active functional implementation: none
active material technical implementation: none
required status check: not enabled
mobile: NOT RUN / OUT OF SCOPE
```

## Completed functional stages

- [x] initial site, authentication, sessions, CSRF;
- [x] RBAC and user lifecycle;
- [x] theme management / 3 themes;
- [x] Military Ranks v1 and v2;
- [x] organizational element types;
- [x] Organizational Structure v1;
- [x] public/legacy Military Positions classifier — migration 010;
- [x] public VUS — migration 011;
- [x] Lowest Unit Staffing Structure v1 — PR #35 / migration 013;
- [x] Managed Military Positions Directory v1 — PR #36 / migration 014;
- [x] PR #36 automatic DB/runtime/HTTP validation;
- [x] PR #36 three-theme desktop acceptance;
- [x] PR #36 Final PR Review, merge and post-merge Actions;
- [x] separately approved cleanup of obsolete design/feature/handoff branches.

## Completed technical/governance stages

- [x] Static CI Stage A — PR #25;
- [x] terminal documentation governance — PR #28;
- [x] local Git/GitHub/Codex automation — PR #29;
- [x] PowerShell 5.1 hardening — PR #30.

Required status check/branch protection Stage B is not implemented.

## Current planning state

```text
ACTIVE_FUNCTIONAL_INCREMENT=NONE
ACTIVE_MATERIAL_TECHNICAL_INCREMENT=NONE
NEXT_PRODUCT_INCREMENT=NOT_SELECTED
NEXT_PRODUCT_APPROVAL=NONE
```

Separate branch `research/military-accounting-order-700` retains unique research. It is not implementation and is not safe cleanup until intentionally reconciled.

## Possible future directions

Each needs a new Research → Analysis → Architecture → Specification → Review → Approval cycle:

- Personnel Core / servicemember card;
- person→Staffing assignments and derived vacancy/occupancy;
- catalog migration/remapping for existing Staffing versions if ever required;
- common Documents/Orders domain;
- common immutable Audit domain;
- import/export and reporting;
- higher-echelon Staffing aggregation;
- production deployment infrastructure;
- branch protection Stage B / required status check;
- separate mobile verification increment.

These are options, not approvals.

## Permanent constraints

- `PersonnelServiceAccounting` is the target contour; `CitizenMilitaryAccounting` remains excluded unless separately reconsidered.
- no real staffing/person/unit data in repository fixtures without approved security/data model;
- public/reference catalogs do not imply person assignments;
- static CI does not replace DB/deploy/browser/manual testing;
- mobile PASS requires actual testing;
- branch deletion always has a separate explicit owner gate;
- documentation lifecycle alone does not justify recursive self-update PR.
