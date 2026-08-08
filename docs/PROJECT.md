# О проекте

## Назначение

**АСУ-ВЧ** — автоматизированная система учёта военнослужащих «Войсковая часть». Проект развивается инкрементально, documentation-first, с точными GitHub gates и ограничением чувствительных/реальных данных до отдельной security/data-model approval.

## Current functional state

```text
migrations: 001–014
system roles: 4
system permissions: 35
themes: 3
latest functional PR: #36
active product implementation: none
```

### Platform / Security

- bootstrap первого `system_owner`;
- authentication, sessions, CSRF;
- public registration closes after owner creation;
- 4 roles / 35 permissions;
- user approval, rejection, block/unblock, archive/restore;
- required password change and audit safeguards.

### Reference / Directories

- Military Ranks v2: current v2 and historical v1;
- organizational element types;
- public VUS information;
- legacy military-position classifier;
- Managed Military Positions Directory v1.

Managed positions are versioned, permission-aware and editable only in draft state. Migration 014 seeds one 24-entry synthetic canonical draft; explicit publication is required. Stable identity/history and terminal immutability are guarded. No position entity fields for VUS/rank/unit/person/equipment/occupancy are introduced.

### Organization

Organizational Structure v1: structures, versions, stable elements, draft tree, documents metadata, history and compare.

### Staffing

Lowest Unit Staffing Structure v1 / migration 013: registers, versions, stable individual slots, documents metadata, Organization/catalog pins, rank/VUS requirements, lifecycle/history/compare.

Personnel records, person→slot assignments and occupied/vacant facts are not implemented.

## Technical state

- MySQL 8.4 / PHP 8.5.4 local application baseline;
- GitHub Actions `ASU-VCH Static Verification` on PR/push/manual events;
- Windows PowerShell 5.1 local Git/GitHub/Codex automation package through PR #30;
- required status check / branch protection Stage B not enabled;
- production deployment infrastructure not implemented as a completed production capability.

## Validation boundaries

Latest functional validation is Military Positions Directory v1 runtime head `c647a933011873048866c75978d3f506634011fd`: 171-file PHP lint PASS, migrations 001–014, `167 PASS`, HTTP `200/200/302`, all three managed desktop themes PASS, 0 open findings.

```text
mobile: NOT RUN / OUT OF SCOPE
mobile PASS: NOT CLAIMED
production deployment: NOT PERFORMED
```

Documentation-only commits do not create new runtime evidence.

## Current research/planning

No implementation increment is active. `research/military-accounting-order-700` remains a separate unmerged branch with unique personnel-service research. It does not authorize implementation by itself.

Future work requires a new Research → Analysis → Architecture → Specification → Review → Approval cycle.

## Project governance

GitHub is source of truth. Permanent rules: `PROJECT-WORKING-RULES.md`. New-chat continuity: `CHAT-HANDOFF.md`. Branch deletion is always separately authorized.
