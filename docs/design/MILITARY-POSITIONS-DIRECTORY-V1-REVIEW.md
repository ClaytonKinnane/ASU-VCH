# Military Positions Directory v1 — Formal Review

## 1. Статус

```text
DOCUMENT=Formal Review
VERSION=0.6
INCREMENT=Military Positions Directory v1
ARCHITECTURE_VERSION=0.6
SPECIFICATION_VERSION=0.6
IMPLEMENTATION_BASE=main@9ae05b9928903cc483ce415d7378b546e419264c
IMPLEMENTATION_BRANCH=feature/military-positions-directory-v1
MIGRATION=014_military_positions_directory_v1.sql
POST_STAFFING_RECONCILIATION=PASS
ORIGINAL_FORMAL_REVIEW=PASS
ORIGINAL_IMPLEMENTATION=AUTHORIZED
CORRECTIVE_DESIGN_REVIEW=PASS
FIRST_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED
SECOND_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
THIRD_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
FOURTH_CORRECTIVE_UI_IMPLEMENTATION=AUTHORIZED_AND_IMPLEMENTED_PENDING_VALIDATION
```

## 2. Review scope

Проверены:

- owner-approved conceptual model and the exact 24-entry synthetic dataset;
- design source `bad4057251f9ebf996d83b3e246df24127a5d5cc`;
- live post-Staffing `main@9ae05b9928903cc483ce415d7378b546e419264c`;
- PR #35 merge and successful post-merge Actions;
- migrations 001–013 and absence of migration 014;
- migration-010 marker, loader, payload packaging, schema and trigger dependencies;
- Staffing v1 pinning and position selector behavior;
- runtime navigation/RBAC, theme and living-document dependencies;
- exact 38-path implementation allowlist and prohibited changes.

## 3. Anchor review

```text
MAIN_HEAD=9ae05b9928903cc483ce415d7378b546e419264c
IMPLEMENTATION_BRANCH_CREATED_FROM_EXACT_MAIN=PASS
DESIGN_SOURCE_HEAD=bad4057251f9ebf996d83b3e246df24127a5d5cc
DESIGN_SOURCE_MERGE_BASE=3d8a491ff2433994e8580152f190b298c765c66e
DESIGN_BRANCH_IS_NOT_IMPLEMENTATION_BASE=PASS
MIGRATION_013_PRESENT=PASS
MIGRATION_014_ABSENT_BEFORE_IMPLEMENTATION=PASS
UNEXPECTED_MATERIAL_INCREMENT=NONE
OPEN_PULL_REQUESTS=0
OPEN_ISSUES=0
```

## 4. Review questions

### RQ-01. Is a second catalog needed?

Result: NO. Existing `military_position_catalog_versions` and `military_position_types` evolve in place.

### RQ-02. Can migration 010 or legacy classifier schema be replaced?

Result: NO. Migration 014 is standalone SQL. Marker, compatibility loader and five payload parts stay byte-for-byte untouched. Legacy tables/metadata/history remain.

### RQ-03. Is the initial rollout safe?

Result: YES. Legacy version stays published; migration creates one canonical draft with exactly 24 approved entries. Publication is an explicit later UI action that atomically supersedes legacy without deletion.

### RQ-04. Are Staffing histories remapped?

Result: NO. Existing versions retain exact pinned catalog/type/variant references. Draft-from-active retains its catalog version.

### RQ-05. Are archived canonical entries safe?

Result: YES. They remain readable for historical references and are excluded only from selection for new Staffing slots.

### RQ-06. Are catalog-level rank/org catalog links position properties?

Result: NO. Links remain solely for migration-010 and Staffing compatibility. Canonical entry endpoints/model do not accept VUS, rank, unit, person, equipment or occupancy fields.

### RQ-07. Is RBAC complete?

Result: YES in the approved design. Four permissions are created without non-owner grants; module/content navigation and directory tiles are permission-aware; routes/actions enforce the exact required permission.

### RQ-08. Is the UI scope testable?

Result: YES. Managed version/entry/history/form layouts use theme variables across all three desktop themes. Mobile remains outside scope.

## 5. Architecture/Specification consistency

```text
ONE_CANONICAL_CATALOG=PASS
NO_PARALLEL_POSITION_ENTITY=PASS
POSITION_HAS_NO_VUS=PASS
POSITION_HAS_NO_RANK_REQUIREMENT=PASS
POSITION_HAS_NO_ORG_BINDING=PASS
POSITION_HAS_NO_PERSON=PASS
VERSION_PINNING=PASS
NO_HIDDEN_STAFFING_REMAP=PASS
PUBLISHED_IMMUTABILITY=PASS
STABLE_IDENTITY=PASS
OPTIMISTIC_REVISIONS=PASS
DRAFT_CONCURRENCY=PASS
APPEND_ONLY_HISTORY=PASS
LEGACY_HISTORY_PRESERVED=PASS
NO_DESTRUCTIVE_DROP_IN_V1=PASS
INITIAL_CANONICAL_DRAFT=PASS
EXACT_24_NAME_INITIAL_SET=PASS
EXACT_9_COMBINED_FLAGS=PASS
ARCHIVED_ENTRY_SELECTION_RULE=PASS
PERMISSION_AWARE_NAVIGATION=PASS
THREE_THEME_DESKTOP_REQUIREMENT=PASS
MOBILE_NOT_CLAIMED=PASS
```

## 6. Security/privacy review

```text
PERSONAL_DATA=NONE
REAL_PERSONNEL=NONE
REAL_STAFFING_DATA=NONE
ASSIGNMENTS=NONE
OCCUPIED_VACANT=NONE
CITIZEN_MILITARY_ACCOUNTING=EXCLUDED
EXCEL_RUNTIME_IMPORT=NONE
```

## 7. Data integrity review

Design 0.2 preserves:

- all legacy catalog rows/metadata/FKs and source provenance;
- existing Staffing foreign-key readability and version pinning;
- stable identity across copied canonical versions;
- normalized per-version name uniqueness;
- no physical deletion;
- draft-only mutations with expected revisions;
- atomic publish/cancel transitions;
- terminal-version immutability;
- append-only catalog history.

## 8. Migration review

### M-01 — numbering

Resolved: migration 013 is current; migration 014 is the exact approved number.

### M-02 — mechanism

Resolved: one standalone SQL file. No change to migration 010 marker/loader/payload mechanism.

### M-03 — lifecycle vocabulary

Resolved: migration adapts legacy `building/published/superseded` to `draft/published/superseded/cancelled` without in-place rewrite of published content. Contradictory/unsupported state fails closed.

### M-04 — rollout

Resolved: legacy stays current published; canonical data is seeded as draft only; publication is explicit and atomic.

### M-05 — repeat/existing DB

Required: clean and existing DB with pre-migration backup, repeat installer, legacy/Staffing preservation checks and lifecycle/revision tests.

## 9. UI and authorization review

Required:

- Russian managed UI and safe errors;
- permission-first object access;
- POST + CSRF + PRG for mutations;
- no raw JSON history;
- no internal IDs as business codes;
- content navigation and directory tile visibility by permission;
- identical layout rules across the three theme CSS files, expressed through theme variables.

## 10. Exact implementation boundary review

```text
ALLOWLIST_PATHS=38
MAX_CHANGED_PATHS=38
MIGRATION_010_FILES_ALLOWED=0
WORKFLOW_OR_SETTINGS_CHANGES_ALLOWED=0
PRODUCTION_DEPLOYMENT_ALLOWED=0
PULL_REQUEST_ALLOWED=0
MERGE_ALLOWED=0
```

Any extra path, different migration mechanism, scope expansion or changed anchor requires a new owner approval.

## 11. Testing review

Critical gates:

- exact branch/base/allowlist and PHP lint/static checker;
- clean DB migrations 001–014 and repeat;
- existing DB backup/migration/repeat with legacy and Staffing preservation;
- exactly 24 names and exact combined flags;
- four permissions without non-owner grants;
- duplicate/stale revision rollback;
- draft create/update/archive/restore;
- publish/cancel and terminal immutability;
- stable identity and append-only readable history;
- legacy read-only and existing Staffing pinned;
- archived entry excluded for new Staffing slot;
- HTTP smoke and three-theme desktop acceptance;
- `MOBILE_ACCEPTANCE=NOT_RUN`.

## 12. Findings summary

```text
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=0
MINOR_FINDINGS=0
OPEN_FINDINGS=0
FORMAL_REVIEW=PASS
```

## 13. Approval boundary

Owner Implementation Approval grants implementation, validation, commits and push only to `feature/military-positions-directory-v1`. It does not grant PR, merge, branch deletion, workflow/settings/configuration changes or production deployment.

## 14. Desktop acceptance findings and corrective design review (2026-08-07)

Evidence: six owner-provided desktop screenshots from `asu-blue` on runtime head `7751430288d2b0669dee4fe14101f809f5828db5`.

### UI-F01 — wrong action in opened version card

Severity: major. `Открыть` on an already opened draft or historical version is a self-navigation action and does not provide the required return behavior.

Resolution design: detail mode replaces it with `Закрыть`, returning to the anchored card in the version list.

### UI-F02 — action sizing and history placement

Severity: minor. The list/detail action stretches across the card header, while `История этой версии` is placed after the entire entry list.

Resolution design: content-sized header controls; version-specific history moves into the opened card action group beside `Закрыть`.

### UI-F03 — archive/restore reason grouping

Severity: minor. The reason field is always exposed in a side column and becomes visually detached from both the entry data and the selected lifecycle action when the editor is opened.

Resolution design: archive/restore becomes its own disclosure; the reason and confirmation are co-located in a full-width panel and remain hidden until that action is selected.

### Corrective review result

```text
CORRECTIVE_ARCHITECTURE_VERSION=0.3
CORRECTIVE_SPECIFICATION_VERSION=0.3
CORRECTIVE_DESIGN_REVIEW=PASS
BLOCKING_FINDINGS=0
MAJOR_FINDINGS=1
MINOR_FINDINGS=2
OPEN_FINDINGS=3
IMPLEMENTATION_ACCEPTANCE_REVIEW=CHANGES_REQUIRED
FIRST_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```

The 12-path corrective allowlist is a subset of the already approved 38-path increment allowlist. Any database, migration, service, repository, action-route, permission, JavaScript, workflow, configuration or additional path change requires a new review and approval.
## 15. Corrective implementation review

Patch review confirms direct traceability to UI-C01–UI-C05:

- explicit version-card modes remove the detail-page self-link;
- `Закрыть` targets the exact list-card anchor without JavaScript;
- version history is permission-aware and contextual;
- lifecycle reason and confirmation are contained inside a dedicated disclosure;
- all backend POST/CSRF/revision/PRG contracts are preserved;
- managed CSS remains symmetric and variable-only;
- corrective commit inventory is fail-closed at exactly 12 paths.

```text
CORRECTIVE_IMPLEMENTATION_REVIEW=PASS
CORRECTIVE_SCOPE_REVIEW=PASS
CORRECTIVE_ALLOWLIST_PATHS=12
FIRST_CORRECTIVE_RUNTIME_VALIDATION=PASS
FIRST_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
OPEN_UI_FINDINGS=3_PENDING_RETEST
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```

The automatic gate passed on exact head `6b63efd6d3a6e7567cc48106bd8c12bd9371e585`. The original findings remain pending a complete three-theme retest; owner desktop evidence additionally opened UI-F04 below.


## 16. Second corrective desktop design review (2026-08-07)

Evidence: owner-provided desktop screenshot of a canonical draft on exact runtime head `6b63efd6d3a6e7567cc48106bd8c12bd9371e585` after the successful automatic gate.

### UI-F04 — lifecycle action is visually isolated

Severity: minor. The collapsed `Архивировать должность` disclosure has the same internal summary styling as `Изменить`, but its parent receives a full-width border/background/padding shell and occupies a separate row. The visual hierarchy incorrectly suggests a separate card section instead of an action equal to `Изменить`.

Resolution design: place both disclosure controls adjacent in one shared action row with identical presentation and no collapsed lifecycle shell. The opened edit or lifecycle form occupies a full-width row below the controls; the two disclosures are mutually exclusive per entry.

### Review result

```text
SECOND_CORRECTIVE_ARCHITECTURE_VERSION=0.4
SECOND_CORRECTIVE_SPECIFICATION_VERSION=0.4
SECOND_CORRECTIVE_DESIGN_REVIEW=PASS
SECOND_CORRECTIVE_BLOCKING_FINDINGS=0
SECOND_CORRECTIVE_MAJOR_FINDINGS=0
SECOND_CORRECTIVE_MINOR_FINDINGS=1
SECOND_CORRECTIVE_OPEN_FINDINGS=1
SECOND_CORRECTIVE_ALLOWLIST_PATHS=9
SECOND_CORRECTIVE_UI_IMPLEMENTATION=VALIDATED_AUTOMATIC_DESKTOP_FAIL
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```

The nine-path boundary is a strict subset of the approved 38-path increment allowlist. It preserves native disclosure behavior, archive/restore payloads, CSRF/revision/PRG controls, routes and all database/runtime-domain contracts. Any JavaScript or extra path requires a new review.


## 17. Second corrective implementation review

Owner Approval was granted against exact documentation head `294cd91e26513217187cbf07447b2e769aa2ff72`. Patch review confirms direct traceability to UI-C06–UI-C08:

- the two entry actions use the same `summary` component and one shared row;
- an entry-scoped native `details[name]` group provides mutual exclusion without JavaScript;
- both opened forms span the row below the shared controls;
- the lifecycle reason and confirmation remain together and hidden while collapsed;
- the archive/restore payload, CSRF, revisions, PRG and routes are unchanged;
- all three managed CSS blocks remain symmetric and variable-only;
- the current commit is constrained to the exact nine-path second corrective allowlist.

```text
SECOND_CORRECTIVE_IMPLEMENTATION_REVIEW=PASS
SECOND_CORRECTIVE_SCOPE_REVIEW=PASS
SECOND_CORRECTIVE_ALLOWLIST_PATHS=9
SECOND_CORRECTIVE_STATIC_VALIDATION=PASS
SECOND_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=PASS
SECOND_CORRECTIVE_DESKTOP_ACCEPTANCE=FAIL
SECOND_CORRECTIVE_OPEN_FINDINGS=1_RETEST_FAIL
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```


## 18. Third corrective desktop design review (2026-08-07)

Evidence: successful full automatic gate on exact runtime head `297c9e6566c0010556324506bb0c9947b4ed6f43` and subsequent owner-provided desktop screenshot of the canonical draft in theme `asu-blue`.

### UI-F04 retest — adjacent controls requirement not met

Severity: minor, open. Both summaries use the same component, but CSS leaves their grid columns to automatic placement while flattening native `details` through `display: contents`. The observed browser layout assigns excess width before the lifecycle summary, so the controls are not visually adjacent.

Resolution design: preserve the markup and native `details[name]`; assign the edit summary and lifecycle summary explicit first and second grid columns, retaining the existing fixed small gap.

### UI-F05 — lifecycle reason form is vertically fragmented

Severity: minor, open. The lifecycle panel is full-width, but its single-column form places the label/input unit above a separate confirmation row. The relationship between reason and operation is visually unclear.

Resolution design: on desktop, render the label text, input and confirmation button as one horizontal row. The input consumes remaining width; the button remains content-sized. Preserve the existing narrow-screen stacking fallback and all server contracts.

### Review result

```text
THIRD_CORRECTIVE_ARCHITECTURE_VERSION=0.5
THIRD_CORRECTIVE_SPECIFICATION_VERSION=0.5
THIRD_CORRECTIVE_DESIGN_REVIEW=PASS
THIRD_CORRECTIVE_BLOCKING_FINDINGS=0
THIRD_CORRECTIVE_MAJOR_FINDINGS=0
THIRD_CORRECTIVE_MINOR_FINDINGS=2
THIRD_CORRECTIVE_OPEN_FINDINGS=2
THIRD_CORRECTIVE_ALLOWLIST_PATHS=8
THIRD_CORRECTIVE_UI_IMPLEMENTATION=AUTHORIZED_AND_IMPLEMENTED_PENDING_VALIDATION
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```

The eight-path boundary is a strict subset of the approved 38-path increment allowlist and of the previous corrective boundary. It changes CSS presentation, checker assertions and living design/handoff documents only. PHP markup, native disclosure behavior, archive/restore payloads, CSRF/revision/PRG controls, routes and all database/runtime-domain contracts remain unchanged.

## 19. Third corrective implementation review

Owner Approval was granted against exact documentation head `b1768ad5ffce5e1da1057096bcf6e02063cea3a1`. Patch review confirms direct traceability to UI-C09–UI-C11:

- explicit summary columns eliminate browser-dependent action auto-placement;
- the equal controls retain one fixed 10px gap;
- opened edit and lifecycle forms still span the full row below both controls;
- lifecycle label, growing input and content-sized submit render in one desktop row;
- the narrow breakpoint restores vertical stacking;
- entry-card PHP, native `details[name]`, archive/restore payload, CSRF, revisions, PRG and routes are unchanged;
- all three managed CSS blocks remain symmetric and variable-only;
- the current commit is constrained to the exact eight-path third corrective allowlist.

```text
THIRD_CORRECTIVE_IMPLEMENTATION_REVIEW=PASS
THIRD_CORRECTIVE_SCOPE_REVIEW=PASS
THIRD_CORRECTIVE_ALLOWLIST_PATHS=8
THIRD_CORRECTIVE_STATIC_VALIDATION=PENDING_EXACT_COMMIT
THIRD_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=NOT_RUN
THIRD_CORRECTIVE_DESKTOP_ACCEPTANCE=NOT_RUN
THIRD_CORRECTIVE_OPEN_FINDINGS=2_PENDING_RETEST
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```

## 20. Fourth corrective desktop design review (2026-08-08)

Evidence: successful full automatic gate on exact runtime head `caee9845ef2faf4245397299d232ad820b77040c` and subsequent owner-provided desktop screenshot of the canonical draft in theme `asu-blue`.

### UI-F04 second retest — opened disclosure still separates controls

Severity: minor, open. Explicit grid-column numbers did not eliminate the browser-specific behavior. The screenshot shows adjacent controls while disclosures are closed, but when lifecycle is open the second control moves away from `Изменить`. The common factor is `details { display: contents; }`: opened native disclosure content participates in the parent grid through a flattened special element, so the form still affects track sizing in Edge.

Resolution design: stop flattening `<details>`. Render two summary-only native disclosure switches as ordinary first-row grid items and render their controlled forms as explicit sibling panels in the second full-width row. Preserve entry-scoped `details[name]`, add summary `aria-controls` and unique panel ids, and keep JavaScript absent.

### UI-F05 retest — horizontal label/input relationship rejected

Severity: minor, open. The third corrective made the elements horizontal, but the owner-provided evidence shows that the label is cramped beside a short input and the hierarchy remains unclear.

Resolution design: place the lifecycle label text above its input, give the input a bounded desktop width of approximately `360px`, and place the content-sized confirmation control to the right aligned with the input. Preserve one semantic label/input unit and the existing narrow fallback.

### Fourth corrective review result

```text
FOURTH_CORRECTIVE_ARCHITECTURE_VERSION=0.6
FOURTH_CORRECTIVE_SPECIFICATION_VERSION=0.6
FOURTH_CORRECTIVE_DESIGN_REVIEW=PASS
FOURTH_CORRECTIVE_BLOCKING_FINDINGS=0
FOURTH_CORRECTIVE_MAJOR_FINDINGS=0
FOURTH_CORRECTIVE_MINOR_FINDINGS=2
FOURTH_CORRECTIVE_OPEN_FINDINGS=2
FOURTH_CORRECTIVE_ALLOWLIST_PATHS=9
FOURTH_CORRECTIVE_UI_IMPLEMENTATION=AUTHORIZED_AND_IMPLEMENTED_PENDING_VALIDATION
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```

The nine-path boundary is the minimum reliable correction: one presentation-only PHP template, three managed CSS files, the fail-closed checker and four living design/handoff documents. Database/runtime-domain model, migrations, repositories, services, routes, permissions, CSRF/revision/PRG contracts, workflows and JavaScript remain unchanged.

Architecture 0.6 and Specification 0.6 are internally consistent and directly address the observed evidence. Review passes with no blocking or major findings. Owner Approval was subsequently granted against exact documentation head `bc660b4e211269bb0f63379ad300bb5e6e72d427`.

## 21. Fourth corrective implementation review

Owner Approval was granted against exact documentation head `bc660b4e211269bb0f63379ad300bb5e6e72d427`. Patch review confirms direct traceability to UI-C12–UI-C14:

- native `details[name]` elements now contain summaries only and remain mutually exclusive;
- edit and lifecycle content moved into uniquely identified sibling panels controlled by the matching open disclosure;
- removing `display: contents` prevents opened content from participating in toolbar track sizing;
- the two controls remain in explicit adjacent columns with the sole `10px` gap;
- lifecycle label/input hierarchy is vertical, the input track is approximately `360px`, and confirmation is bottom-aligned on the right;
- the narrow breakpoint remains stacked;
- three managed CSS blocks remain symmetric and variable-only;
- PHP changes are presentation-only and preserve action URLs, names, CSRF, revisions and return path;
- the current commit is constrained to the exact nine-path fourth corrective allowlist.

```text
FOURTH_CORRECTIVE_IMPLEMENTATION_REVIEW=PASS
FOURTH_CORRECTIVE_SCOPE_REVIEW=PASS
FOURTH_CORRECTIVE_ALLOWLIST_PATHS=9
FOURTH_CORRECTIVE_STATIC_VALIDATION=PENDING_EXACT_COMMIT
FOURTH_CORRECTIVE_LOCAL_RUNTIME_VALIDATION=NOT_RUN
FOURTH_CORRECTIVE_DESKTOP_ACCEPTANCE=NOT_RUN
FOURTH_CORRECTIVE_OPEN_FINDINGS=2_PENDING_RETEST
PULL_REQUEST=NOT_AUTHORIZED
MERGE=NOT_AUTHORIZED
```
