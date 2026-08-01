# Test Plan — Public Military Occupational Specialties v1

## Implementation scope

Exact changed-path count before re-testing: **23**.

Migration packaging входит в runtime parity:

- marker migration;
- compatibility loader;
- two gzip/base64 parts;
- archive SHA-256 `1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39`;
- canonical SQL SHA-256 `26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9`.

UI remediation добавляет:

- `config/themes.php`;
- три theme-specific VUS stylesheet;
- отдельный статический UI checker;
- русификацию видимых labels;
- linked/static card behavior;
- VUS-specific table proportions;
- compact bottom boundary note.

## Automated

1. Repository/branch/baseline/exact-scope preflight.
2. `git diff --check`.
3. MySQL backup и сохранность `config/local.php`.
4. Deploy и PHP lint.
5. Installer дважды.
6. Integration checker: loader hashes, 9 tables, 26 triggers, exact seed, fingerprints, identifier distributions, lifecycle и rejection tests.
7. UI checker: русификация, отсутствие fingerprints в пользовательском UI, стили трёх тем, интерактивные и статичные карточки, пропорции таблицы, compact bottom note.
8. Regressions: ranks, organizational elements, military positions, RBAC, users, themes, Organization.
9. Source/deploy parity для 13 runtime/package paths и HTTP smoke.
10. Итоговая чистота и совпадение с origin feature branch.

## Manual desktop re-acceptance

Обязательно повторно проверить:

- все видимые подписи на русском языке;
- отсутствие SHA-256 и технического evidence-bundle пояснения;
- интервалы между секциями;
- подъём только карточек с внешними ссылками;
- отсутствие подъёма у статичных карточек структуры кода и публичных областей;
- равномерные ширины таблицы и нормальный перенос названий источников;
- отсутствие лишнего пустого пространства внизу блока записей;
- owner 200 и ordinary role 403;
- warning, 5 legal sources, 4 snapshots, 3 segments, 6 domains, 2 examples, 4 organizations, 15 programs;
- search, filters, empty state, external links;
- no position matching, no personal data, no completeness claim;
- три темы при 1920×1080 и 1366×768;
- console errors и HTTP/asset 404 отсутствуют.

Mobile: OUT OF SCOPE / NOT RUN.
