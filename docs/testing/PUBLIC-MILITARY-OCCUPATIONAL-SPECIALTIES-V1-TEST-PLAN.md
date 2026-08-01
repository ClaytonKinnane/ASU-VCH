# Test Plan — Public Military Occupational Specialties v1

## Automated

1. Repository/branch/baseline/scope preflight.
2. `git diff --check`.
3. MySQL backup и сохранность `config/local.php`.
4. Deploy и PHP lint.
5. Installer дважды.
6. Integration checker: 9 tables, 26 triggers, exact seed, fingerprints, identifier distributions, lifecycle и rejection tests.
7. Regressions: ranks, organizational elements, military positions, RBAC, users, themes, Organization.
8. Source/deploy parity и HTTP smoke.
9. Итоговая чистота и совпадение с origin feature branch.

## Manual desktop

Owner 200, ordinary role 403, warning, 5 legal sources, 4 snapshots, 3 segments, 6 domains, 2 examples, 4 organizations, 15 programs, search и filters, external links, no position matching, no personal data, no completeness claim, три темы, 1920×1080 и 1366×768, console/404.

Mobile: OUT OF SCOPE / NOT RUN.
