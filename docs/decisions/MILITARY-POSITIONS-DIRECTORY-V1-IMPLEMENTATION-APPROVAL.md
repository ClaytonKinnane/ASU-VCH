# Decision: Implementation и публикация feature-ветки

## Статус

```text
Architecture: APPROVED
Specification v0.4: APPROVED
Formal Review v0.4: PASS
Implementation: APPROVED
Feature branch: feature/military-positions-directory
```

Владелец явно разрешил создать ветку `feature/military-positions-directory` и начать Implementation по утверждённым Architecture, Specification v0.4 и Formal Review v0.4.

1 августа 2026 года владелец дополнительно определил порядок локального Testing: все implementation-файлы сначала фиксируются в удалённой feature-ветке GitHub; локальный оператор не применяет patch и не создаёт исходники вручную, а только синхронизирует репозиторий и запускает утверждённый PowerShell 5.1 runner.

Это разрешение охватывает commit и push implementation-кандидата в feature-ветку для локального Testing. Оно **не** разрешает:

- создание PR без отдельного решения после Testing;
- merge;
- удаление ветки;
- заявление automated или manual PASS до фактического выполнения проверок.
