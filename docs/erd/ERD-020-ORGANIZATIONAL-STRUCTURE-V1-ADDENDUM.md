# ERD-020 addendum: versioned organizational structure v1

```text
organizational_structures 1 ── * organizational_structure_versions
organizational_structures 1 ── * organizational_structure_elements
organizational_structures 1 ── * organizational_structure_documents
organizational_structure_versions 1 ── * organizational_structure_nodes
organizational_structure_versions 1 ── * organizational_structure_version_documents
organizational_structure_elements 1 ── * organizational_structure_nodes
organizational_structure_nodes 0..1 ── * organizational_structure_nodes (parent)
organizational_structure_documents 1 ── * organizational_structure_version_documents
organizational_structures 1 ── * organizational_structure_change_events
```

`organizational_structure_nodes` фиксирует состояние стабильного элемента в конкретной версии. Родитель принадлежит той же структуре и версии. Тип принадлежит закреплённой версии каталога типов.

Generated nullable columns обеспечивают условную уникальность одной незавершённой версии, одной активной версии, одного корня и одного основного документа.


## Ссылочная целостность

Композитные candidate keys и FK гарантируют общую принадлежность узлов, родителей, документов и событий одной структуре. `organizational_structure_change_events` ссылается на версию и стабильный элемент совместно с `organizational_structure_id`, поэтому история не может содержать межструктурные несогласованные ссылки.

DB-triggers защищают неизменяемый код и archive/restore lifecycle контейнера, допустимые переходы версии, дочерние данные опубликованных версий и append-only историю.
