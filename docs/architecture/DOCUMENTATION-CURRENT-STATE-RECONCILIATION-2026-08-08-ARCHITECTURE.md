# Documentation Current-State Reconciliation — Architecture — 2026-08-08

## Status

```text
CLASSIFICATION=documentation-only
BASE_BRANCH=main
BASE_SHA=b3dda6cae88072c1e74c25de28f7023a8d73620d
RUNTIME_CHANGE=NO
DB_MIGRATION_CHANGE=NO
WORKFLOW_CHANGE=NO
THEME_ASSET_CHANGE=NO
TOOL_CHANGE=NO
BRANCH_DELETION=FORBIDDEN
```

## Goal

Restore one coherent current-state documentation layer after completed PR #35/#36 and branch cleanup, while preserving historical gate records as immutable evidence.

## Documentation architecture

The project uses four semantic layers:

1. `docs/PROJECT-WORKING-RULES.md` — permanent operational governance.
2. `docs/CHAT-HANDOFF.md` — current operational snapshot for chat continuity.
3. living documentation/indexes — durable merged product/technical state.
4. historical/target records — immutable gate evidence or future architecture.

GitHub/Git remains canonical for mutable lifecycle facts.

## Permanent governance invariant

Routine maintenance of `PROJECT-WORKING-RULES.md` and `CHAT-HANDOFF.md` requires no repeated owner permission prompt. It may include documentation branch creation, commits, push, PR, exact-head verification, Final PR Review, merge and post-merge verification when changes remain documentation-only and limited to those two paths.

Branch deletion is never included in that standing authorization. It requires a separate explicit owner authorization for the exact branch and deletion gate.

## Handoff invariant

After every meaningful project state transition, `CHAT-HANDOFF.md` must be checked and, when needed, updated so a new chat can recover:

- current live snapshot and anchors;
- completed increments and validation;
- active scope/stage;
- open findings;
- existing branches/PRs/issues relevant to continuation;
- current permissions/authorizations and prohibitions;
- next safe action.

The handoff should summarize historical details and link to canonical evidence rather than accumulate every obsolete intermediate gate forever.

## Scope boundary

Reconciliation changes Markdown only. It does not modify executable behavior or assert new runtime testing. Runtime evidence is quoted only from previously completed exact-head validation.
