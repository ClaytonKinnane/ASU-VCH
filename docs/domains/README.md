# Предметные области АСУ-ВЧ

## Назначение и классификация

Каталог содержит target domain specifications. Этот `README.md` — living domain index. Факт implementation подтверждается `../PROJECT-STATUS.md`, `../DATABASE-CURRENT.md`, executable migrations и Test Evidence.

## Текущая фаза

```text
Project architecture        — APPROVED
Domain modeling             — CONTINUES PER INCREMENT
Implementation              — STARTED
Functional runtime          — THROUGH PR #36 / MIGRATION 014
Latest functional validation — PR #36 / exact runtime head c647a933011873048866c75978d3f506634011fd
Static CI baseline          — PR #25
Documentation governance    — PR #28 + permanent rules/handoff
Local automation foundation — PR #29
Local automation corrected  — PR #30
Active functional implementation — NONE
Active material technical implementation — NONE
```

## Current domain map

| Domain | Current state |
|---|---|
| Security | users, authentication, RBAC, approval, password change, rejection, archive/restore |
| Reference | Military Ranks v2/historical v1, organizational element types, public VUS, legacy position classifier and Managed Military Positions Directory v1 |
| Organization | Organizational Structure v1: structures, versions, draft tree, document metadata, history, compare |
| Staffing | Lowest Unit Staffing Structure v1: registers, versions, stable individual slots, document metadata, catalog/Organization pins, history/compare; no persons/assignments |
| Audit | critical operation audit/events inside implemented domains; common domain log not implemented |
| Infrastructure | installer, migrations, deploy, theme registry, health, CLI checkers, static CI Stage A and local Git/GitHub/Codex automation through PR #30 |
| Documents | common Documents runtime not implemented; Organization/Staffing own only their scoped metadata |

## Infrastructure tooling capability

Current repository tooling includes:

- local Git/GitHub/Codex bootstrap for Windows PowerShell 5.1;
- approved WinGet/npm installation flows;
- Codex authentication-mode separation;
- integrity manifest;
- atomic helper installation and rollback;
- native PowerShell 5.1 regression harness;
- fail-closed remote branch cleanup helper.

Infrastructure tooling:

- is not a business domain;
- is not application runtime;
- is not deployed to the web root;
- does not change Security, Reference, Organization or Staffing ownership;
- does not imply real authentication or paid API request acceptance without separate evidence.

## Specialized Reference catalogs

| Functional PR | Catalog | Migrations |
|---:|---|---:|
| #8 / #24 | Military personnel compositions and ranks: v1 → current v2 + historical v1 | 007 + 012 |
| #9 | Organizational element types | 008 |
| #19 / #36 | Military Positions legacy classifier → managed canonical directory | 010 + 014 |
| #20 | Public military occupational specialty information | 011 |

### Military Ranks v2 Reference contract

- v1 remains visible as superseded historical version;
- v2 is the single current published version;
- 20 rank codes/names/order preserved;
- 8 version-scoped compositions/categories;
- 8 semantic records;
- 2 version sources and 8 composition sources;
- derived categories explicitly distinguished from normative compositions;
- Reference-owned read-only compatibility service uses same-version ancestry;
- no Organization or Staffing write ownership and no personnel assignments.

### Managed Military Positions Directory v1 Reference contract

- the migration-010 catalog is evolved in place; no parallel position entity;
- new canonical version begins as draft and is not auto-published;
- stable identity, draft mutations, logical archive/restore and append-only history;
- explicit view/manage/publish/history permissions, with owner wildcard and no automatic non-owner grants;
- existing Staffing pins/history are preserved;
- no VUS/rank/unit/person/equipment/occupancy properties are inferred from a position name.

The Military Ranks, organizational-elements and public VUS user-facing routes retain their owner-only/read-only boundaries. Managed Military Positions is separately permission-aware and mutable only under its approved draft lifecycle. Universal editable Reference runtime is not claimed.

## Future directions

```text
Personnel Core and person cards
Staffing personnel assignments / derived vacancy-occupancy
Orders
Medical
Equipment
Transport
Training
Archive domain
Notifications
```

Staffing v1 already exists; Personnel/Assignments and any catalog remapping are separate future approved increments.

## Domain ownership

Each business concept has one owning domain. The owner defines invariants, write operations, lifecycle and contracts. Reading another domain's data does not grant write ownership.

## Organization and Staffing boundary

Organizational Structure v1 implements structure/version lifecycle, stable elements, version-scoped tree, draft mutation, catalog-version bindings, document metadata, immutable change events, history and compare.

Lowest Unit Staffing Structure v1 implements normative registers/versions/stable individual slots, rank/VUS requirements, document metadata, catalog/Organization pins, history and compare.

Not implemented:

- personnel cards;
- person→slot assignments;
- occupied/vacant factual state;
- real personnel/unit operational data;
- common Documents and Audit domains.

Public catalogs and Military Ranks/Military Positions semantics do not remove these boundaries.

## Allowed dependencies

```text
Security       → Audit
Security       → Reference
Organization   → Reference
Organization   → Audit
Staffing       → Organization / Reference / Audit
Documents      → Security / Reference / Organization / Audit
Infrastructure → external technical systems
```

Reference does not depend on Organization or Staffing. Static CI and local automation belong to Infrastructure and do not change business-domain ownership.

## Current research boundary

`research/military-accounting-order-700` contains unique unmerged research for `PersonnelServiceAccounting`. It is not current runtime and is not automatically an implementation approval. `CitizenMilitaryAccounting` remains excluded from the current target contour unless separately reconsidered.

## New domain increment workflow

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing → Commit → Push → Pull Request
→ Final PR Review → separate merge approval → Merge
→ post-merge verification → separate branch deletion approval
```

For DB increments, ERD/Migration Specification are included before Implementation. Runtime and migrations are prohibited before approval.

## Target documents

Security, Reference, Organization and Documents architecture files may describe a broader target. They are not proof that a runtime capability exists.
