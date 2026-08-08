# Architectural Patterns

## Purpose and priority

Этот документ определяет recurring patterns АСУ-ВЧ. Priority при конфликте:

1. accepted ADR;
2. approved domain architecture;
3. approved specification/ERD;
4. this document;
5. implementation notes.

## Documentation First

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing → Commit → Push → Pull Request
→ Final PR Review → separate merge approval → Merge
→ post-merge verification → separate branch deletion approval
```

Material implementation не начинается до relevant Approval. Pull Request, merge и branch deletion имеют отдельные owner gates.

## Documentation semantic classes

1. Living documentation.
2. Living indexes.
3. Target architecture.
4. Historical implemented specifications.
5. Historical process/test artifacts.
6. Operational increment records with additive closure.
7. Immutable audit/cleanup snapshots.

### Semantic classification overrides directory classification

Section является living/current-state, если сообщает current functional/technical baseline, migrations, domains, routes, roles, permissions, themes/assets, CI capability или durable tooling capability.

Rules:

- current HEAD определяется dynamically through `origin/main`;
- exact SHA stored only as historical anchors;
- documentation-only head не объявляется runtime-tested;
- target architecture не представляется как current schema;
- historical gate markers сохраняются;
- current PR/branch inventory не сохраняется как permanent living field;
- links должны resolve либо быть явно historical/obsolete evidence.

Canonical rule:

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

### Terminal documentation invariant

Lifecycle новейшего documentation Pull Request не копируется обратно в living Markdown solely to record its own review, merge, Actions run or branch cleanup.

Architecture, Specification, Formal Review, Approval, Implementation и Validation остаются historical gate records. Final PR Review может храниться только в GitHub, если repository-файл создал бы self-modifying review cycle.

A missing Markdown copy of the newest documentation PR lifecycle is not a documentation defect when canonical evidence exists in GitHub PR timeline, reviews, Actions and branch inventory.

Recursive post-merge documentation closure is prohibited when the only missing information is lifecycle copy.

## Domain ownership

Каждая business concept имеет один owning domain. Owner определяет invariants, writes, lifecycle и contracts. Другие domains не мутируют его model напрямую.

## Aggregate and immutable history

Aggregate commands адресуются root; child invariants enforced transactionally. Published catalogs, audit facts, source snapshots и historical versions immutable; corrections создают new version/event, а не overwrite history.

## Versioned public catalogs

### Whole-catalog versioning

- one current published version;
- children belong to one version;
- publication freezes child data;
- lifecycle explicit and forward-only;
- evidence cannot cross versions.

### Source-centric catalog

- version defines release boundary;
- sources/snapshots are first-class;
- every record keeps evidence context;
- normalization does not exceed source;
- incomplete public coverage stated explicitly.

### Catalog evolution with superseded history and application semantics

PR #24 adds a reusable evolution pattern:

- existing published version may become `superseded` with explicit validity end;
- one new version becomes `published/current` atomically;
- historical version remains visible/read-only;
- application-specific categories are stored as version-scoped semantics rather than rewriting normative history;
- derived categories must be explicitly marked and sourced;
- compatibility checks use same-version ancestry;
- published/superseded child data immutable;
- recovery accepts only exact known building state and fails closed on contradiction.

### Managed canonical catalog evolution

PR #36 adds a complementary pattern for evolving an existing classifier without destructive replacement:

- create the next canonical version as `draft`; do not auto-publish it;
- preserve stable identity across copied canonical versions;
- allow content changes only in draft;
- publish atomically and supersede the prior current version;
- use logical archive for entries and append-only readable history;
- keep terminal published/superseded/cancelled content immutable;
- preserve downstream historical version pins instead of remapping them;
- filtering an archived entry from new selectors must not break old historical reads.

## Evidence-bounded identifiers and relations

Identifiers are decomposed only when authoritative evidence supports it. Raw values preserved; unknown semantics stay explicit. Similar identifiers do not imply cross-domain relation. Military-position names do not imply VUS/rank/unit/person/equipment/occupancy relations.

## Reference data

Reusable classifications belong to Reference domain. Use stable lowercase codes, no MySQL ENUM, version-aware relations and critical DB guards. Owner-only read-only catalogs may reuse `system.*.*` without adding permissions. A separately approved managed catalog may introduce explicit module permissions, as Military Positions Directory v1 does.

## Foreign keys and lifecycle

Default:

```text
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

Lifecycle documents initial state, allowed transitions, prohibited reverse transitions, required data, terminal states and restoration/cancellation semantics.

## Transactions and database guards

Material commands run in transactions. Use `SELECT ... FOR UPDATE` where required. Constraints/triggers provide final race and invariant protection. Trigger errors deterministic and tested.

## Migration packaging and compatibility

When transport constraints prevent direct canonical SQL:

- marker migration;
- deterministic package/loader;
- exact part/order/hash/byte checks where packaging is used;
- fail closed on mismatch;
- repeat installer and parity verification.

Migrations 010–011 use gzip/base64 packaging. Migration 012 uses a separate compatibility loader with a fail-closed marker and versioned DDL/publication/recovery modules. Migration 013 and migration 014 are standalone increment migrations; migration 014 deliberately leaves migration-010 packaging untouched.

## Security boundaries

Permissions never bypass validation, CSRF, invariants, audit, transactions or secret handling.

Public local-only development fixtures are not production secrets only when explicitly scoped, replaced on first use and never reused as instance credentials. Production/instance credentials, real temporary user passwords, sessions, tokens, API keys, private keys and local config remain secret.

## Testing obligations

Critical invariants require applicable MySQL integration tests, rejection paths, lifecycle/cross-version guards, repeat installer, parity and security regressions. Manual acceptance required for user-visible Specification behavior. Mobile PASS requires actual mobile acceptance.

## Static CI Stage A pattern

GitHub Actions Static Verification is an additional early/final signal:

- untrusted PR code runs with `contents: read`;
- no secrets, environments or write permissions;
- immutable action SHA;
- exact event-aware diff;
- tracked PHP lint;
- explicit CI-safe checker allowlist;
- final tracked/untracked worktree guard;
- push and workflow_dispatch post-merge diagnostics.

Static CI does not replace MySQL, migration, deploy, HTTP/browser, source/deploy parity or manual visual acceptance.

Stage B is separate:

```text
required status check: not enabled
branch protection mutation: not performed
conversation-resolution rule: separately gated
```

## Windows PowerShell 5.1 native automation pattern

Native process execution follows these invariants:

```text
native exit code is authoritative
stderr alone is not failure
stdout and stderr are captured separately
cmd/bat invocation uses ComSpec
interactive login remains visible
secrets use redirected stdin only
optional output is normalized to collections
valid empty output remains valid
process timeouts are bounded
temporary environment changes are restored
helper installation is atomic
failed installation rolls back
```

PowerShell 5.1 scalar/collection unrolling must be handled explicitly through stable result objects or `@(...)` normalization before `.Count`, indexing or iteration.

Secrets must not be passed through command arguments, environment variables or logs.

## Authentication mode separation pattern

Supported Codex modes:

```text
Auto
ChatGPT
ApiKey
Skip
```

Invariants:

```text
requested ChatGPT mode must not silently accept API-key mode
requested ApiKey mode must not silently accept ChatGPT mode
API-key authentication is not called ChatGPT login
ChatGPT subscription is not API billing
paid remote request readiness is not claimed without an authorized request
```

## Local helper integrity pattern

Repository helpers are installed through:

- source manifest verification;
- staging verification;
- normalized UTF-8/LF hashing where specified;
- Cleanup Doctor before acceptance;
- atomic destination replacement;
- rollback on failure;
- secret-free logs.

Local helpers are repository tooling and are not application deploy artifacts.

## Repository cleanup pattern

Required sequence:

1. post-merge verification;
2. fresh remote/local inventory;
3. exact main, PR head and merge anchors;
4. successful post-merge push run/job/steps;
5. canonical post-merge PASS evidence;
6. reachability/unique-commit check;
7. exact deletion batch;
8. separate owner approval;
9. Cleanup Doctor;
10. remote deletion first;
11. `fetch --prune`;
12. approved local deletion;
13. final main/inventory verification.

Cleanup tool invariants:

```text
Doctor must pass before Verify and Delete
ApprovalToken == BranchName case-sensitive
branch ahead of main = 0
unique unmerged commits = 0
destructive command limited to git push origin --delete approved-branch
tool deletes no local branch
anchor or evidence mismatch fails closed
```

`SAFE TO DELETE` is classification, not authorization.

## Status

```text
ARCHITECTURAL PATTERNS: APPROVED
APPLICABILITY: PROJECT-WIDE
FUNCTIONAL BASELINE COVERAGE: THROUGH PR #36 / MIGRATION 014
STATIC CI COVERAGE: THROUGH PR #25
DOCUMENTATION GOVERNANCE COVERAGE: THROUGH PR #28 + PERMANENT RULES/HANDOFF
LOCAL AUTOMATION COVERAGE: THROUGH PR #30
DURABLE TECHNICAL CAPABILITY COVERAGE: THROUGH PR #36
SYSTEM ROLES: 4
SYSTEM PERMISSIONS: 35
BUILT-IN THEMES: 3
REQUIRED CSS ASSETS PER THEME: 10
REQUIRED STATUS CHECK: NOT ENABLED
MOBILE: NOT RUN / OUT OF SCOPE
```