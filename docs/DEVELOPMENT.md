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

## GitHub Actions Static Verification

Stage A is implemented through `.github/workflows/static-verification.yml`.

When applicable, it provides an additional signal for:

- Pull Requests to `main`;
- pushes to `main`;
- manual `workflow_dispatch` diagnostics;
- `git diff --check`;
- tracked PHP lint;
- 9 explicit CI-safe checkers;
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