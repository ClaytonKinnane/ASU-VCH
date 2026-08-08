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
Active design increment     — Personnel Core Card v1 / pre-Approval
```

## Current domain map

| Domain | Current state |
|---|---|
| Security | users, authentication, RBAC, approval, password change, rejection, archive/restore; fine-grained Personnel access deferred |
| Reference | Military Ranks v2/historical v1, organizational element types, public VUS, legacy position classifier and Managed Military Positions Directory v1 |
| Organization | Organizational Structure v1: structures, versions, draft tree, document metadata, history, compare |
| Staffing | Lowest Unit Staffing Structure v1: registers, versions, stable individual slots, document metadata, catalog/Organization pins, history/compare; no persons/assignments |
| Personnel | target domain defined; Personnel Core Card v1 Architecture/Specification in design; runtime not started |
| Audit | critical operation audit/events inside implemented domains; common domain log not implemented |
| Infrastructure | installer, migrations, deploy, theme registry, health, CLI checkers, static CI Stage A and local Git/GitHub/Codex automation through PR #30 |
| Documents | common Documents runtime not implemented; Organization/Staffing own only their scoped metadata |

## Personnel target direction

`PERSONNEL.md` is the current target domain architecture for active servicemember data.

Core principles:

- one servicemember = one canonical PersonnelRecord;
- complete required servicemember data may be represented by separate related subject entities;
- document/report forms do not own duplicate person databases;
- current position/unit/occupancy will be derived through future Assignments + existing Staffing;
- permanent/temporal person facts are separated from event/case snapshots;
- military position is not a Security role;
- fine-grained order-backed Personnel authorization is recorded as deferred design and is not part of Personnel Core Card v1;
- prototype v1 is system-owner only.

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
- does not change Security, Reference, Organization, Staffing or Personnel ownership;
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
Personnel Core Card v1
Personnel assignments / derived vacancy-occupancy
Personnel service record / contracts / ranks / VUS
Personnel contacts and family
Personnel documents and media
Medical and physical identification
Legal / financial / additional Personnel data
Special Cases
Personnel forms and reports
Fine-grained Personnel access model
Orders
Common Audit
Production deployment
```

Staffing v1 already exists; Personnel Core is the next selected design increment. Dependent runtime work does not start before required gates.

## Domain ownership

Each business concept has one owning domain. The owner defines invariants, write operations, lifecycle and contracts. Reading another domain's data does not grant write ownership.

## Organization, Staffing and Personnel boundary

Organizational Structure v1 implements structure/version lifecycle, stable elements, version-scoped tree, draft mutation, catalog-version bindings, document metadata, immutable change events, history and compare.

Lowest Unit Staffing Structure v1 implements normative registers/versions/stable individual slots, rank/VUS requirements, document metadata, catalog/Organization pins, history and compare.

Personnel target architecture owns canonical active-servicemember identity and related person-centric facts. Future Assignments own person→staffing-slot relations.

Until Assignments is implemented:

- Personnel Core does not store current position/unit as duplicate truth;
- Staffing does not claim occupied/vacant factual state;
- no runtime relation between person and staffing slot exists.

## Allowed dependencies

```text
Security       → Audit
Security       → Reference
Organization   → Reference
Organization   → Audit
Staffing       → Organization / Reference / Audit
Personnel      → Security / Audit
Assignments    → Personnel / Staffing / Organization / Audit
Documents      → Security / Reference / Organization / Personnel / Audit
Infrastructure → external technical systems
```

Reference does not depend on Organization, Staffing or Personnel. Personnel Core v1 does not write Organization/Staffing/Reference.

## Current research boundary

`research/military-accounting-order-700` contains unique unmerged research for `PersonnelServiceAccounting`. It remains preserved and is not automatically merged implementation. Relevant Personnel concepts may be reconciled through the active design process without declaring the research branch merged.

`CitizenMilitaryAccounting` remains excluded.

## New domain increment workflow

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing → Commit → Push → Pull Request
→ Final PR Review → separate merge approval → Merge
→ post-merge verification → separate branch deletion approval
```

For DB increments, ERD/Migration Specification are included before Implementation. Runtime and migrations are prohibited before approval.

## Target documents

Security, Reference, Organization, Documents and Personnel architecture files may describe a broader target. They are not proof that a runtime capability exists.