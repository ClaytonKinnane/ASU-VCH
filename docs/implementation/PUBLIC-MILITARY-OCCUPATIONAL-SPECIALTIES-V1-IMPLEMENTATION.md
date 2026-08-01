# Implementation — Public Military Occupational Specialties v1

## Статус

```text
PHASE: IMPLEMENTATION COMPLETE / LOCAL TESTING REQUIRED
BASELINE: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
BRANCH: feature/public-military-occupational-specialties-directory
MIGRATION: 011_public_military_occupational_specialties_directory.sql
IMPLEMENTATION_PATHS: 18
PR: NOT CREATED
MERGE: NOT AUTHORIZED
```

## Реализовано

- source-centric схема из 9 таблиц и 26 triggers;
- exact seed: 5 legal sources, 4 snapshots, 3 code segments, 6 context domains, 3 scopes, 2 direct disclosures, 4 organizations, 15 programs;
- read-only repository и bootstrap factory;
- owner-only GET route и плитка справочника;
- статический/интеграционный checker;
- PowerShell 5.1 testing runner;
- отсутствие position/rank/equipment/person relations.

## Migration packaging

Canonical SQL выполняется через compatibility loader из двух упорядоченных gzip/base64 частей.

```text
CANONICAL_SQL_BYTES: 88267
CANONICAL_SQL_SHA256: 26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9
GZIP_ARCHIVE_BYTES: 9472
GZIP_ARCHIVE_SHA256: 1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39
BASE64_PARTS: 2
```

Loader проверяет archive SHA-256, распаковывает canonical SQL и проверяет SQL SHA-256 до передачи installer.

## Проверено до локального Testing

- PHP syntax: PASS для новых и изменённых PHP-файлов;
- migration static structure: 9 tables / 26 triggers;
- `DELIMITER`: absent;
- migration loader reconstruction и оба SHA-256: PASS;
- checker static phase: PASS;
- database/runtime tests: NOT RUN в среде пользователя.

## Local Testing attempt 1

```text
DATE: 2026-08-01
RESULT: PRE-EXECUTION PARSER FAILURE
BACKUP: NOT STARTED
DEPLOY: NOT STARTED
INSTALLER: NOT STARTED
DATABASE CHANGES: NONE
```

Windows PowerShell 5.1 прочитал UTF-8 без BOM в системной ANSI-кодировке. Кириллические строки runner были искажены до разбора и вызвали ParserError. Runtime implementation и migration не выполнялись.

Исправление:

```text
COMMIT: fb28a8d071fb871c0a0f7bc39042bb7331b4771e
RUNNER ENCODING POLICY: ASCII-ONLY
```

Runner не содержит non-ASCII символов и не зависит от BOM или локальной кодовой страницы Windows PowerShell 5.1.

## Следующий gate

Повторная локальная синхронизация, parser preflight, automated testing и manual desktop acceptance. Mobile testing остаётся OUT OF SCOPE / NOT RUN.
