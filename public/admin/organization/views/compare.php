    <?php if ($diff !== []): ?>
    <section id="compare" class="organization-panel glass-tile"><div class="organization-section-heading"><div><h2>Сравнение с базовой версией</h2><p>Сопоставление выполняется по стабильному идентификатору организационного элемента.</p></div></div><div class="organization-diff-list">
        <?php foreach ($diff as $change): $labels = []; if ($change['base_node_id'] === null) $labels[] = 'Добавлен'; elseif ($change['target_node_id'] === null) $labels[] = 'Исключён'; else { if ($change['base_name'] !== $change['target_name']) $labels[] = 'Наименование'; if ($change['base_short_name'] !== $change['target_short_name']) $labels[] = 'Краткое имя'; if ($change['base_internal_code'] !== $change['target_internal_code']) $labels[] = 'Код'; if ((int) $change['base_type_id'] !== (int) $change['target_type_id']) $labels[] = 'Тип'; if ((string) $change['base_parent_element_id'] !== (string) $change['target_parent_element_id']) $labels[] = 'Перемещение'; if ((int) $change['base_sort_order'] !== (int) $change['target_sort_order']) $labels[] = 'Порядок'; if ($change['base_note'] !== $change['target_note']) $labels[] = 'Примечание'; } if ($labels === []) continue; ?>
        <article><strong><?= e((string) ($change['target_name'] ?? $change['base_name'] ?? ('Элемент ' . $change['element_id']))) ?></strong><span><?= e(implode(' · ', $labels)) ?></span></article>
        <?php endforeach; ?>
    </div></section>
    <?php endif; ?>

