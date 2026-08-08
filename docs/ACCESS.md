# Управление доступом

## Current baseline

```text
system roles: 4
system permissions: 35
owner wildcard: system.*.*
```

Roles: `system_owner`, `administrator`, `operator`, `viewer`.

## Owner / user lifecycle

First user of empty installation becomes `system_owner`; public registration closes after successful owner creation. Last active owner protections remain in force. Ordinary role management cannot assign `system_owner`.

User lifecycle includes pending/approval/rejection, block/unblock, archive/restore and required temporary-password change. Authorization never bypasses authentication/status, CSRF, validation, revisions, transactions or DB invariants.

## Domain permissions

Migration 009 added six `organization.structures.*` permissions.

Migration 013 added six Staffing permissions:

```text
staffing.registers.view
staffing.registers.create
staffing.registers.update
staffing.registers.publish
staffing.registers.archive
staffing.registers.history
```

Migration 014 added four Military Positions Directory permissions:

```text
directories.military_positions.view
directories.military_positions.manage
directories.military_positions.publish
directories.military_positions.history
```

Migration 014 performs no automatic non-owner grants. Owner continues via `system.*.*`.

## Reference routes

Owner-only/read-only catalog routes remain for:

```text
/admin/directories/military-ranks.php
/admin/directories/organizational-elements.php
/admin/directories/military-occupational-specialties.php
```

Managed Military Positions is different:

```text
/admin/directories/military-positions.php
/admin/directories/military-positions/version.php
/admin/directories/military-positions/history.php
```

Its navigation/read access is permission-aware; draft mutations require `manage`, publication requires `publish`, history requires applicable view/history access. Mutations are POST-only, permission-first, CSRF-protected, revision-guarded, transactional and PRG-redirected.

## Scope boundaries

- Military Positions catalog is not Staffing and creates no person assignment.
- Staffing v1 does not claim occupancy/vacancy facts.
- Public VUS is not personal military accounting and is not automatically linked to position/rank/equipment/personnel.
- Military Ranks semantics do not create Staffing assignments.

## Verification

Military Positions runtime/DB/HTTP verification completed on exact runtime head `c647a933011873048866c75978d3f506634011fd` as part of the `167 PASS` gate with HTTP `200/200/302`, four permission/no-grant checks and zero open findings.

```text
system roles: 4
system permissions: 35
organization permissions: 6
staffing permissions: 6
military-position directory permissions: 4
automatic non-owner grants from 014: 0
mobile: NOT RUN / OUT OF SCOPE
```

## Secrets

Production/instance credentials, sessions, tokens, private keys, `config/local.php` and real temporary user passwords are prohibited in docs/logs. Public local-only fixture `Admin / 12315` is restricted to local bootstrap and must be changed; it must not be reused as production/instance credential.
