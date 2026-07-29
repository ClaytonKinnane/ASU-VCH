# Review: Organizational Structure v1 UI Polish 3

## Статус

```text
DATE: 2026-07-29
ARCHITECTURE: PASS
SPECIFICATION: PASS
BLOCKING FINDINGS: 0
USER APPROVAL: RECEIVED
IMPLEMENTATION: ALLOWED
```

## Проверенная проблема

Текущий flex-layout переносит открытый `details` на полную ширину. Trigger и форма перемещаются вниз, а соседние действия визуально меняют положение. Высота reorder buttons отличается от disclosure controls.

## Проверенное решение

Предложенная action-bar + panels архитектура разделяет:

- стабильные triggers;
- раскрываемое содержимое форм.

Это устраняет изменение положения кнопок без вмешательства в серверные операции.

## Доступность

Решение использует нативные buttons с `aria-controls`, `aria-expanded` и panels с `hidden`. Enter/Space предоставляются нативным button behavior. Inline handlers не требуются.

## Безопасность destructive action

Прямое удаление по первому нажатию не допускается. `Удалить` открывает confirmation panel, а отдельная кнопка `Подтвердить удаление` отправляет существующую POST-форму. Защита CSRF, expected revision и subtree confirmation сохраняются.

## Совместимость

- PHP/backend contracts не меняются;
- current endpoints сохраняются;
- общий organization UI script уже подключён с `defer` и может быть расширен;
- CSS variables и theme contract уже существуют;
- решение не требует внешних assets.

## Риски и меры

### Несколько одновременно открытых панелей

Снижается закрытием sibling panels внутри одного `data-node-actions` container.

### Потеря клавиатурной доступности

Снижается использованием `button type="button"`, а не clickable div/span.

### Случайная отправка формы

Снижается явным `type="button"` для triggers и automated check отсутствия submit/requestSubmit в toggle path.

### Регрессия POST payload

Снижается сохранением существующих forms и static checker counts для CSRF, revision и endpoints.

### Различие трёх тем

Снижается идентичным structural CSS и checker на blob/content equality.

## Verdict

```text
READY FOR IMPLEMENTATION
RETEST REQUIRED
PR GATE: CLOSED UNTIL AUTOMATED AND MANUAL DESKTOP PASS
```
