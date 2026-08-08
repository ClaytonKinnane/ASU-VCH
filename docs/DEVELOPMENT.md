# Правила разработки

Canonical permanent governance: [PROJECT-WORKING-RULES.md](PROJECT-WORKING-RULES.md). Этот файл описывает development conventions и не отменяет более строгие gates постоянного регламента.

## Source of truth

Repository `ClaytonKinnane/ASU-VCH` on GitHub is source of truth. Current SHA/branches/PR/Actions are checked live. Local clone is for synchronization, deploy and testing within approved scope.

## Mandatory material process

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing/Validation → Commit → Push → Pull Request
→ exact-head Actions → Final PR Review → Merge approval → Merge
→ post-merge verification → Branch deletion approval → Branch deletion
```

Implementation before Approval is prohibited. Ordinary PR, merge and deletion are separate gates unless the owner has already issued a precise task-level authorization. Merge never implicitly authorizes deletion.

## Documentation-only work

Documentation reconciliation requires:

- exact base and branch;
- Markdown-only allowlist;
- current-state/historical semantic classification;
- relative-link/stale-claim/secret/mobile review;
- no runtime/config/DB/migration/workflow/theme/tool diff;
- exact-head PR verification and Final PR Review before merge.

Documentation-only commit is not runtime-tested.

### Standing operational-doc exception

Routine maintenance limited to:

```text
docs/PROJECT-WORKING-RULES.md
docs/CHAT-HANDOFF.md
```

may proceed without a repeated permission prompt according to `PROJECT-WORKING-RULES.md`, including documentation PR and merge after PASS. **Branch deletion is excluded and always separately authorized.** Any third path needs task-level authorization.

## Documentation semantic model

- living docs = durable current merged state;
- target docs may be broader than runtime;
- historical Architecture/Specification/Review/Approval/Implementation/Testing = gate snapshots;
- GitHub/Git = mutable lifecycle authority.

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

Do not create recursive documentation PRs solely to copy the previous documentation PR's own merge/run/cleanup lifecycle.

## Runtime validation expectations

Applicable checks include backup before schema/data changes, deploy preserving local config, PHP lint, installer/repeat installer, DB integration/regression, source/deploy parity, HTTP/browser and specification-defined visual/manual acceptance.

Static CI is supplementary and does not replace local runtime evidence. Mobile PASS requires actual mobile testing.

## Branch conventions

- `main` — stable merged state;
- `feature/...` — functional work;
- `bugfix/...` — fixes;
- `docs/...` — documentation;
- no permanent feature branch.

Completed branches are retained until separately approved deletion. Before deletion verify exact tips, reachability/unique commits, PR/post-merge state and exact approved batch. `SAFE TO DELETE` is not permission.

## Local environment

```text
repo: C:\Project\ASU-VCH
deploy: C:\OSPanel\home\asu-vch.local
Apache root: C:\OSPanel\home\asu-vch.local\public
PowerShell: 5.1
PHP: 8.5.4
MySQL: 8.4.x
```

Deploy preserves `config/local.php`. Secrets never enter Git/logs.

## Technology/process constraints

- no MySQL schema change without migration and approved design;
- no force-push/rebase remediation unless explicitly authorized;
- third-party dependency requires justification and Approval;
- native process exit code is authoritative in PowerShell automation;
- mobile is `NOT RUN / OUT OF SCOPE` unless specifically tested.
