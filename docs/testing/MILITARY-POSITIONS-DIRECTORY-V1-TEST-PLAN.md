# Test Plan: Справочник типов воинских должностей ВС РФ v1

## Автоматизированный runner

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
    -File '.\tools\Test-MilitaryPositionsDirectory.ps1' `
    -AllowInvalidCertificate
```

## Обязательные проверки

1. Чистая синхронизация `feature/military-positions-directory` с `origin`.
2. HEAD локальной ветки равен remote feature HEAD.
3. Merge-base соответствует утверждённому baseline `8cc604eec7e973c2917ea0b1f9b08b976b673f41`.
4. Точный committed scope из 21 изменённого пути.
5. `git diff --check` без ошибок.
6. SHA-256 deploy-only `config/local.php` до и после Testing не изменяется.
7. SQL backup выполняется до deploy и migration.
8. Deploy выполняется только в `C:\OSPanel\home\asu-vch.local`.
9. PHP lint deploy-копии проходит.
10. Compatibility layer объединяет пять base64-частей, подтверждает SHA-256 gzip-архива, распаковывает canonical SQL migration 010 и подтверждает его SHA-256.
11. Первый installer применяет migration 010.
12. Повторный installer не создаёт новых изменений.
13. Integration checker подтверждает 14 таблиц и 41 trigger.
14. Проверяются 24 source entries, 34 types, 35 variants и все evidence counts.
15. Проверяются version-aware FK и отсутствие rank relation tables.
16. Проверяются rejection paths: late INSERT, reverse lifecycle, invalid tariff grade, cross-version evidence.
17. Permissions остаются равны 25.
18. Проходят regressions справочников воинских званий и организационных элементов.
19. Проходят security, theme и Organization regressions.
20. Source/deploy parity проходит по runtime-файлам, marker migration и пяти частям архива.
21. HTTP smoke проходит для `/`, `/health.php` и `/admin/`.
22. После автоматизированного Testing Git HEAD и working tree остаются неизменными и чистыми.
23. Owner route возвращает HTTP 200; ordinary role — HTTP 403.
24. Поиск и фильтры проверяются: family, composition scope, tariff grade, organizational element.
25. Manual desktop acceptance выполняется в трёх встроенных темах.
26. Browser console errors и asset 404 отсутствуют.

## Controlled recovery

Controlled recovery из частичного `building`-состояния выполняется только на отдельной тестовой БД либо на восстановимой копии после зафиксированного backup. Нельзя повреждать рабочую локальную БД для имитации recovery без отдельного безопасного сценария.

## Границы

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
PR/merge: NOT PART OF TEST RUNNER
```
