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

Section является living/current-state, если сообщает current functional/technical baseline, migrations, domains, routes, roles, permissions, themes/assets, CI capability или repository state.

Rules:

- current HEAD определяется dynamically through `origin/main`;
- exact SHA stored only as historical anchors;
- documentation-only head не объявляется runtime-tested;
- target architecture не представляется как current schema;
- historical gate markers сохраняются;
- completed outcome добавляется отдельным closure;
- current PR/branch inventory не сохраняется как permanent living field;
- links должны resolve либо быть явно historical/obsolete evidence.

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

Military Ranks v2 applies this pattern while preserving 20 rank codes/names/order and separating derived staffing scopes from normative compositions.

## Evidence-bounded identifiers and relations

Identifiers are decomposed only when authoritative evidence supports it. Raw values preserved; unknown semantics stay explicit. Similar identifiers do not imply cross-domain relation.

## Reference data

Reusable classifications belong to Reference domain. Use stable lowercase codes, no MySQL ENUM, version-aware relations and critical DB guards. Owner-only read-only catalogs may reuse `system.*.*` without adding permissions.

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

Migrations 010–011 use gzip/base64 packaging. Migration 012 uses a separate compatibility loader with a fail-closed marker and versioned DDL/publication/recovery modules; it must not be falsely described as gzip/base64 packaging.

## Security boundaries

Permissions never bypass validation, CSRF, invariants, audit, transactions or secret handling.

Public local-only development fixtures are not production secrets only when explicitly scoped, replaced on first use and never reused as instance credentials. Production/instance credentials, real temporary user passwords, sessions, tokens, private keys and local config remain secret.

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

Static CI does **not** replace MySQL, migration, deploy, HTTP/browser, source/deploy parity or manual visual acceptance.

Stage B is separate:

```text
required status check: not enabled
branch protection mutation: not performed
conversation-resolution rule: separately gated
```

Changing stable job/check identity after Stage B would be operationally breaking and requires review.

## Repository cleanup pattern

Required sequence:

1. post-merge verification;
2. documentation refresh when needed;
3. fresh remote/local inventory;
4. reachability/unique-commit check;
5. exact deletion batch;
6. separate owner approval;
7. remote deletion first;
8. approved local deletion;
9. final main/inventory verification.

`SAFE TO DELETE` is classification, not authorization.

## Status

```text
ARCHITECTURAL PATTERNS: APPROVED
APPLICABILITY: PROJECT-WIDE
FUNCTIONAL BASELINE COVERAGE: THROUGH PR #24 / MIGRATION 012
TECHNICAL BASELINE COVERAGE: THROUGH PR #25
SYSTEM ROLES: 4
SYSTEM PERMISSIONS: 25
BUILT-IN THEMES: 3
REQUIRED CSS ASSETS PER THEME: 10
REQUIRED STATUS CHECK: NOT ENABLED
```