# Architecture: Organizational Structure v1 UI Polish 3

## Статус

```text
PHASE: ARCHITECTURE
STATUS: APPROVED BY USER
DATE: 2026-07-29
IMPLEMENTATION: ALLOWED
```

## Цель

Устранить визуальную нестабильность action controls в строках узлов организационного дерева и сделать порядок, редактирование, добавление, перемещение и удаление однозначными.

## Причина текущего поведения

Текущая разметка помещает каждое раскрываемое действие в отдельный `details` внутри flex-контейнера. Правило `details[open] { flex: 1 1 100%; }` переносит открытый trigger и его форму на отдельную строку. Из-за этого `Переместить`, `Изменить`, `Добавить дочерний` и `Удалить` меняют положение при раскрытии.

## Архитектурное решение

Для каждого узла вводятся два независимых presentation-слоя:

1. `organization-node-action-bar` — стабильная строка action buttons;
2. `organization-node-action-panels` — область форм, отображаемая ниже строки.

Trigger-кнопки остаются в action bar независимо от открытого panel. Панель выбирается минимальным JavaScript с `aria-controls`, `aria-expanded` и атрибутом `hidden`.

## Action bar

Action bar включает:

- `Выше`;
- `Ниже`;
- `Переместить`, если узел не корневой;
- `Изменить`;
- `Добавить дочерний`;
- `Удалить`, если узел не корневой.

Все controls используют единый `--organization-action-height` и одинаковое вертикальное выравнивание.

## Panels

Под action bar размещаются отдельные panels:

- move panel;
- edit panel;
- add-child panel;
- delete-confirmation panel.

В пределах одного узла одновременно открывается не более одной панели. Открытие другой панели закрывает предыдущую. Повторное нажатие на активный trigger закрывает панель.

## Порядок

Кнопки `Выше` и `Ниже` сохраняют текущий POST endpoint и payload. Внутрь добавляются декоративные тематические стрелки вверх/вниз.

## Удаление

`Удалить` в action bar является безопасным trigger подтверждения. Реальное destructive-действие выполняет отдельная кнопка `Подтвердить удаление` внутри delete panel. Существующие обязательные `confirm_subtree` и `reason` сохраняются.

## Доступность

- trigger controls — `button type="button"`;
- связь с panel — `aria-controls`;
- состояние — `aria-expanded`;
- panel скрывается через `hidden`;
- Enter и Space работают нативно для button;
- после закрытия focus остаётся на trigger.

## Неизменяемая область

Не меняются:

- БД и migrations;
- services/repositories;
- RBAC;
- CSRF;
- revision fields;
- POST endpoints и payload names;
- server validation;
- правила перемещения и удаления поддерева;
- tree collapse/search behavior.

## Темы

Один structural `organization.css` применяется идентично к:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

Внешние assets и библиотеки не добавляются.
