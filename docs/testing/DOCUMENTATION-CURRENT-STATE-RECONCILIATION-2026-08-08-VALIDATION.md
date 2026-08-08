# Documentation Current-State Reconciliation — Validation — 2026-08-08

## Validation type

Documentation-only semantic/static validation. This is **not** a new application runtime, DB, migration, browser, visual or mobile test.

## Pre-PR criteria

The candidate branch must satisfy live checks immediately before Pull Request:

```text
merge base == audited main base
behind main == 0
all changed paths == Markdown
unexpected runtime/config/DB/migration/workflow/theme-asset/tool paths == 0
current migrations == 001-014
current system permissions == 35
latest functional increment == PR #36
open functional findings == 0
mobile == NOT RUN / OUT OF SCOPE
production deployment == NOT PERFORMED
branch deletion == NOT AUTHORIZED
```

## Semantic checks completed

- living baseline documents were checked against live GitHub state and merged PR #35/#36 evidence;
- permanent rules preserve fail-closed, testing boundaries, terminal documentation model and separate deletion authorization;
- new-chat handoff records current durable state, research branch boundary, standing permissions and safety boundaries;
- stale pre-PR36 current claims for migrations 012/013, 25/31 permissions and pending migration014 were corrected in the identified living/index files;
- target/historical documents were not globally converted into current-state documents;
- `docs/domains/REFERENCE.md` and `docs/domains/STAFFING.md` were reviewed and preserved because their approved current compatibility sections already cover migration014/Staffing interactions;
- `research/military-accounting-order-700` content was not copied, modified, merged or deleted;
- no secret/production credential was introduced;
- no Mobile PASS or production-deployment PASS was introduced.

## Exact-head verification rule

This validation file deliberately avoids embedding its own commit SHA. The exact PR head, compare inventory and GitHub Actions result are canonical in GitHub and must be checked immediately before Final PR Review/merge.

Final PR Review may proceed only if the live diff remains documentation-only, the exact-head workflow succeeds, and no blocking/major/minor findings remain.
