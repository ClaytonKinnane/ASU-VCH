# Test Plan — Public Military Occupational Specialties v1

## Implementation scope

Exact changed-path count before testing: **18**.

Migration packaging входит в runtime parity:

- marker migration;
- compatibility loader;
- two gzip/base64 parts;
- archive SHA-256 `1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39`;
- canonical SQL SHA-256 `26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9`.

## Automated

1. Repository/branch/baseline/exact-scope preflight.
2. `git diff --check`.
3. MySQL backup и сохранность `config/local.php`.
4. Deploy и PHP lint.
5. Installer дважды.
6. Integration checker: loader hashes, 9 tables, 26 triggers, exact seed, fingerprints, identifier distributions, lifecycle и rejection tests.
7. Regressions: ranks, organizational elements, military positions, RBAC, users, themes, Organization.
8. Source/deploy parity для 9 runtime/package paths и HTTP smoke.
9. Итоговая чистота и совпадение с origin feature branch.

## Manual desktop

Owner 200, ordinary role 403, warning, 5 legal sources, 4 snapshots, 3 segments, 6 domains, 2 examples, 4 organizations, 15 programs, search и filters, external links, no position matching, no personal data, no completeness claim, три темы, 1920×1080 и 1366×768, console/404.

Mobile: OUT OF SCOPE / NOT RUN.
