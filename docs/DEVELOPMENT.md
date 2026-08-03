# Правила разработки

## Source of truth

Repository `ClaytonKinnane/ASU-VCH` on GitHub is the source of truth. Changes are made in separate branches. Local clone is used for synchronization, deploy and testing, not unapproved source editing.

## Mandatory process

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing → Commit → Push → Pull Request
→ Final PR Review → separate merge approval → Merge
→ post-merge verification → separate branch deletion approval
```

Implementation is prohibited before approved Architecture, Specification, Review and explicit owner Approval.

Pull Request creation, merge and branch deletion are separate gates. Merge approval does not include deletion approval.

## Change classes

### Runtime increment

Applicable evidence includes exact scope preflight, backup for schema/data changes, deploy preserving local config, PHP lint, installer/repeat installer, integration/regression checks, parity, HTTP smoke and Specification-defined manual acceptance.

### Documentation-only increment

Required:

- exact approved path allowlist;
- Markdown-only diff;
- no runtime/config/database/migration/workflow/theme/tool diff;
- current baseline and historical-anchor review;
- relative-link validation;
- stale-current-state scan;
- secret/mobile claim review;
- Final PR Review before merge.

Documentation-only commit is not a runtime-tested head.

## Terminal documentation model

### Documentation classes

**Living documentation** describes durable current merged project state. It must be updated when that durable state changes or when a real living-content defect is found.

**Historical gate records** capture evidence, permissions, decisions and status at a specific stage. They include:

- Architecture;
- Specification;
- Formal Review;
- Approval;
- Implementation;
- Validation/Test Report;
- Final PR Review.

**GitHub lifecycle evidence** is the canonical source for mutable repository state:

- current `main` and PR base/head SHA;
- open, closed and merged PR state;
- review submissions and review threads;
- Actions runs, jobs and logs;
- current branch inventory;
- merge and branch-deletion timeline events.

### Historical interpretation

A gate-scoped statement remains historical evidence after later gates complete. Historical `PENDING`, `NEXT GATE`, `NOT AUTHORIZED`, `NOT PERFORMED` and equivalent markers do not become current tasks merely because they are present in a repository file.

Canonical rule:

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

Before declaring documentation stale or a task open, an audit must determine:

1. the semantic class of the document or section;
2. the date/gate to which the statement applies;
3. the current canonical source of truth;
4. whether the statement is currently actionable;
5. whether a later canonical source supersedes it.

Directory placement alone does not determine semantics.

### Terminal invariant

The lifecycle of the newest documentation Pull Request is not copied back into living Markdown solely to record its own review, merge, Actions run or branch cleanup.

Architecture, Specification, Formal Review, Approval, Implementation, Validation and Final PR Review are not rewritten merely because later gates complete.

A missing Markdown copy of the newest documentation PR lifecycle is not a documentation defect when the authoritative evidence exists in GitHub PR timeline, reviews, Actions and branch inventory.

Recursive post-merge documentation closure is prohibited when the only missing information is that lifecycle copy.

A new documentation increment remains valid for a genuine durable living-state error, broken normative rule, incorrect link, security/claim defect or other substantive content problem.

This terminal model changes documentation interpretation only. It does not weaken or bypass the mandatory owner-gated process, Final PR Review, separate merge approval, post-merge verification or separately approved branch cleanup.

## GitHub Actions Static Verification

Stage A is implemented through `.github/workflows/static-verification.yml`.

When applicable, it provides an additional signal for:

- Pull Requests to `main`;
- pushes to `main`;
- manual `workflow_dispatch` diagnostics;
- `git diff --check`;
- tracked PHP lint;
- 9 explicit CI-safe checker'ов;
- final clean-worktree verification.

Security boundary:

```text
permissions: contents read
secrets/environments: none
write permissions: none
required status check: not enabled
branch protection mutation: not performed
```

Static CI does not replace local MySQL, migrations, installer, deploy, source/deploy parity, HTTP/browser or manual visual acceptance. A successful static run cannot be used to claim unperformed functional tests as PASS.

Stage B — required check, conversation-resolution rule or other branch-protection settings — requires separate Architecture, Specification, Review and Approval.

## Branches

- `main` is stable merged state;
- `feature/...` for functional work;
- `bugfix/...` for fixes;
- `docs/...` for documentation;
- no permanent feature branch;
- completed branch retained until separately approved cleanup.

Before deletion:

1. fresh inventory;
2. reachability/unique-commit proof;
3. PR and post-merge state;
4. exact owner-approved batch;
5. safe deletion without force when applicable;
6. final main/inventory verification.

`SAFE TO DELETE` is not authorization.

## Local test clone

```text
C:\Project\ASU-VCH
```

Allowed: fetch/prune, approved branch switch, SHA/divergence/worktree checks, controlled deploy, installer, lint, checkers, HTTP/browser testing and separately approved cleanup.

Without scope approval prohibited: local source/doc editing, project commit/push, secret disclosure and force branch deletion.

## Repository versus web root

```text
Git clone:   C:\Project\ASU-VCH
Deploy root: C:\OSPanel\home\asu-vch.local
Apache root: C:\OSPanel\home\asu-vch.local\public
```

Deploy preserves `config/local.php`. SQL backup is required before schema/data migration, subject to explicit documented deviations.

## Commit prefixes

`feat:`, `fix:`, `style:`, `docs:`, `refactor:`, `test:`, `chore:`.

## Technology constraints

- Windows PowerShell 5.1;
- local PHP 8.5.4;
- MySQL 8.4.x;
- GitHub static runner uses PHP 8.5.x independently;
- third-party dependencies require justification and Approval;
- secrets/local parameters are not stored in Git;
- architecture decisions are not introduced silently;
- mobile is not declared tested without actual acceptance.