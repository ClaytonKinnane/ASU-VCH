# Управление доступом

## Current baseline

```text
system roles: 4
main permissions after migration 013: 31
feature target after migration 014: 35
owner wildcard: system.*.*
```

System roles: `system_owner`, `administrator`, `operator`, `viewer`.

## First owner

First user of an empty installation is created through bootstrap registration and transactionally receives `system_owner`. Public registration closes after successful owner creation.

Invariants:

- one active owner;
- last active owner cannot be blocked, archived or deprived of critical access;
- ordinary role management cannot assign `system_owner`;
- later users do not receive owner automatically.

## Authorization

Permission never bypasses authentication/status, required password change, CSRF, validation, transactions, DB invariants, revisions, audit, privacy or last-owner protection.

Unauthorized authenticated access returns themed HTTP 403. Anonymous admin access redirects to login.

## User lifecycle

Implemented: pending creation with reason, approval/activation, rejection audit, block/unblock, archive/restore, required temporary-password change and status-based login prohibition. Restore does not activate automatically.

## Organizational Structure permissions

Migration 009 added six `organization.structures.*` permissions. They are not auto-assigned to ordinary roles; owner access is via wildcard.

## Reference directories

```text
/admin/directories/military-ranks.php
/admin/directories/organizational-elements.php
/admin/directories/military-occupational-specialties.php
```

Current behavior:

- owner-only through `system.*.*`;
- GET-only/read-only user routes;
- prepared statements and escaped output;
- safe official external links;
- no mutation controls/endpoints;
- ordinary roles without wildcard receive HTTP 403.

### Military Ranks v2

Migration 012 adds no permissions. Route supports current v2, historical/superseded v1, version switch, search/filtering and source evidence.

Reference-owned compatibility service is read-only and evaluates same-version composition/rank compatibility. It does not grant Staffing or Organization write access.

Migrations 010, 011 and 012 add no permissions. Migration 013 adds six `staffing.registers.*` permissions, producing the current `main` baseline of 31.

### Managed Military Positions Directory v1

Migration 014 adds:

```text
directories.military_positions.view
directories.military_positions.manage
directories.military_positions.publish
directories.military_positions.history
```

No permission is automatically assigned to a non-owner role. Owner continues to use `system.*.*`. `/admin/content.php` and `/admin/directories.php` expose the module only to owner or a holder of the view permission. Read pages require view/history as applicable; every mutation is POST-only, permission-first, CSRF-protected, revision-guarded, transactional and PRG-redirected.

## Scope boundaries

- Military Positions is not a staffing schedule and creates no assignments.
- Public VUS is not personal military accounting and is not automatically linked to positions/ranks/equipment/personnel.
- Military Ranks v2 derived semantics do not create Staffing tables, slots or Organization bindings.

## Mutating operation security

Other domain mutations are POST-only, permission + CSRF protected, validate ownership/lifecycle/revision, run in transactions, use prepared statements and record audit/events as specified.

## Latest verification

```text
system roles: 4
main system permissions after migration 013: 31
feature target after migration 014: 35
organization permissions: 6
staffing permissions: 6
military-position directory permissions: 4
ordinary automatic organization assignments: 0
owner access to current directories: PASS
ordinary-role HTTP 403: PASS
Military Ranks current/historical read-only boundary: PASS
automatic non-owner grants from migration 014: 0
Military Positions runtime/DB/HTTP verification: NOT RUN on this implementation worktree
security regressions: PASS
mobile: OUT OF SCOPE / NOT RUN
```

## Secret terminology

Production/instance credentials, session data, tokens, private keys, `config/local.php` and real temporary user passwords are prohibited in documentation/logs.

The approved public `Admin / 12315` local-only fixture is not a production/instance secret, is restricted to local bootstrap, requires first-login replacement and must not be reused for other accounts/environments.
