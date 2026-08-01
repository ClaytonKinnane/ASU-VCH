# Implementation — Public Military Occupational Specialties v1

## Статус

```text
PHASE: IMPLEMENTATION COMPLETE / LOCAL TESTING REQUIRED
BASELINE: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
BRANCH: feature/public-military-occupational-specialties-directory
MIGRATION: 011_public_military_occupational_specialties_directory.sql
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

## Следующий gate

Локальная синхронизация, automated testing и manual desktop acceptance. Mobile testing остаётся OUT OF SCOPE / NOT RUN.
