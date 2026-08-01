# Публичные сведения о ВУС — Specification v0.2

## Статус

OWNER-APPROVED после Formal Review. Specification v0.1 superseded.

## Контрольные показатели

- tables: 9;
- triggers: 26;
- catalog versions: 1;
- legal sources: 5;
- source snapshots: 4;
- code segments: 3;
- public context domains: 6;
- personnel scopes: 3;
- direct disclosures: 2;
- training organizations: 4;
- atomic training programs: 15;
- searchable records: 17;
- records with identifiers: 14;
- records with qualifications: 9;
- position/rank/equipment/person relations: 0;
- permissions after migration: 25.

Identifier kinds: `none`, `base-specialty-number`, `full-code-complete`, `official-program-identifier`. Шестизначные значения ВУЦ не разбираются на смысловые сегменты.

Migration: `011_public_military_occupational_specialties_directory.sql`.
Route: `/admin/directories/military-occupational-specialties.php`.
Permission: `system.*.*`.

Фильтр организации относится только к официальным программам подготовки. При выбранной организации нормативные direct-disclosure записи не включаются в результаты; сочетание `record_type=direct-disclosure` и организации возвращает пустой результат.

## Compatibility packaging

В соответствии с Implementation Approval canonical migration хранится в двух gzip/base64 частях и загружается через `database/MilitaryOccupationalSpecialtyMigrationCompatibility.php`.

```text
canonical SQL SHA-256: 26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9
archive SHA-256:       1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39
parts:                  2
implementation paths:   25
```

Marker migration не содержит предметного SQL. Installer получает только canonical SQL после успешной проверки обоих hashes.
