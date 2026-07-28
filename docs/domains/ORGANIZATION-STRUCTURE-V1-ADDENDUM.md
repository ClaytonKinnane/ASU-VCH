# Addendum к домену Organization: versioned structure v1

Этот документ частично замещает раннюю невыполненную модель `military_units / departments` в `docs/domains/ORGANIZATION.md`.

Для организационной структуры утверждена новая модель:

```text
organizational_structures
organizational_structure_elements
organizational_structure_versions
organizational_structure_nodes
```

Таблицы `military_units` и `departments` не создаются параллельно. Будущие должности и военнослужащие должны ссылаться на стабильный `organizational_structure_elements.id` через отдельно утверждённую временную/версионную модель.

Прежние разделы о ranks, soldiers и identifiers остаются проектным материалом будущих инкрементов и не реализуются migration 009.


## Статус замещения

Для всех последующих миграций и runtime-инкрементов разделы `docs/domains/ORGANIZATION.md`, описывающие `military_units`, `departments` и неверсионируемое `departments.parent_id`, считаются замещёнными настоящим addendum и `docs/design/ORGANIZATIONAL-STRUCTURE-V1-DESIGN.md`.

Корнями агрегатов являются `organizational_structures` и `organizational_structure_versions`. Содержимое опубликованных версий неизменяемо; стабильная идентичность элемента — `organizational_structure_elements.id`. Документы, уже использованные опубликованной версией, изменяются только copy-on-write.
