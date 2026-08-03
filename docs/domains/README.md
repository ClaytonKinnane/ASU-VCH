# Предметные области АСУ-ВЧ

## Назначение и классификация

Каталог содержит target domain specifications. Этот `README.md` — living domain index. Факт implementation подтверждается `../PROJECT-STATUS.md`, `../DATABASE-CURRENT.md`, executable migrations и Test Evidence.

## Текущая фаза

```text
Project architecture        — APPROVED
Domain modeling             — CONTINUES PER INCREMENT
Implementation              — STARTED
Functional increments       — PR #1–#9, #12, #15, #19, #20, #24 MERGED
Latest functional PR        — #24
Latest technical PR         — #25
Active functional increment — NONE
Active technical increment  — NONE
```

## Current domain map

| Domain | Current state |
|---|---|
| Security | users, authentication, RBAC, approval, password change, rejection, archive/restore |
| Reference | four owner-only read-only routes; Military Ranks has current v2/historical v1 and compatibility service |
| Organization | Organizational Structure v1: structures, versions, draft tree, document metadata, history, compare |
| Audit | critical operation audit inside Security/Organization; common domain log not implemented |
| Infrastructure | installer, migrations, deploy, theme registry, health, CLI checkers and static CI Stage A |
| Documents | common Documents runtime not implemented; Organization owns only its document metadata |

## Specialized Reference catalogs

| Functional PR | Catalog | Migrations |
|---:|---|---:|
| #8 / #24 | Military personnel compositions and ranks: v1 → current v2 + historical v1 | 007 + 012 |
| #9 | Organizational element types | 008 |
| #19 | Public military position types | 010 |
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
- no Organization dependency, Staffing schema or personnel assignments.

The four user-facing directories remain owner-only/read-only. Universal editable Reference runtime is not claimed.

## Future directions

```text
Personnel
Staff positions and personnel assignments
Orders
Medical
Equipment
Transport
Training
Archive domain
Notifications
```

Future Staffing model does not duplicate public military position types or Military Ranks application semantics. It requires a separate approved increment.

## Domain ownership

Each business concept has one owning domain. The owner defines invariants, write operations, lifecycle and contracts. Reading another domain's data does not grant write ownership.

## Organization boundary

Organizational Structure v1 implements structure/version lifecycle, stable elements, version-scoped tree, draft mutation, catalog-version bindings, document metadata, immutable change events, history and compare.

Not implemented:

- personnel cards;
- organization-specific staffing slots;
- personnel assignments;
- actual strength, equipment or restricted operational data;
- common Documents and Audit domains.

Public catalogs and Military Ranks v2 semantics do not remove these boundaries.

## Allowed dependencies

```text
Security       → Audit
Security       → Reference
Organization   → Reference
Organization   → Audit
Documents      → Security / Reference / Organization / Audit
Infrastructure → external technical systems
```

Reference does not depend on Organization. Static CI belongs to Infrastructure and does not change domain ownership.

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