# Test Plan — Public Military Occupational Specialties v1

## Implementation scope

Exact changed-path count before re-testing: **25**.

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
- compact bottom boundary note;
- обновлённое ожидание `database/check-theme-management.php` для нового обязательного stylesheet.

Final PR Review remediation добавляет:

- тестируемую policy `shouldSearchPublicDisclosures(recordType, organization)`;
- исключение direct-disclosure записей при выбранной организации;
- пустой результат для `record_type=direct-disclosure + organization`;
- integration regression для пяти комбинаций `record_type/organization`;
- DB regression, сопоставляющий repository-фильтр программ с контрольным SQL count;
- manual acceptance evidence в exact path set runner.

## Automated

1. Repository/branch/baseline/exact-scope preflight для 25 путей.
2. `git diff --check`.
3. MySQL backup и сохранность `config/local.php`.
4. Deploy и PHP lint.
5. Installer дважды.
6. Integration checker: loader hashes, 9 tables, 26 triggers, exact seed, fingerprints, identifier distributions, lifecycle, rejection tests и organization-filter policy.
7. UI checker: русификация, отсутствие fingerprints в пользовательском UI, стили трёх тем, интерактивные и статичные карточки, пропорции таблицы, compact bottom note.
8. Regressions: ranks, organizational elements, military positions, RBAC, users, themes, Organization.
9. Source/deploy parity для 14 runtime/package/test paths и HTTP smoke.
10. Итоговая чистота и совпадение с origin feature branch.

## Targeted manual desktop recheck after Final PR Review remediation

После Automated Testing PASS обязательно проверить под владельцем:

1. Без фильтров отображаются 17 записей.
2. `record_type=all` + выбранная организация показывает только программы этой организации и не показывает нормативные примеры.
3. `record_type=direct-disclosure` + выбранная организация показывает корректное пустое состояние.
4. `record_type=training-program` + выбранная организация показывает тот же набор программ организации.
5. Сброс возвращает полный набор из 17 записей.
6. Console errors и HTTP/asset 404 отсутствуют.

Ранее принятая визуальная проверка трёх тем при 1920×1080 и 1366×768 сохраняется: theme assets и CSS в Final PR Review remediation не менялись.

Mobile: OUT OF SCOPE / NOT RUN.
