(() => {
    'use strict';

    const tree = document.querySelector('[data-organization-tree]');
    if (!tree) return;

    const rows = Array.from(tree.querySelectorAll('[data-tree-node]'));
    const byId = new Map(rows.map((row) => [row.dataset.nodeId, row]));
    const childrenByParent = new Map();
    rows.forEach((row) => {
        const parentId = row.dataset.parentId || '0';
        if (!childrenByParent.has(parentId)) childrenByParent.set(parentId, []);
        childrenByParent.get(parentId).push(row.dataset.nodeId);
    });

    const collapsed = new Set();
    let searchValue = '';

    const visibleForSearch = () => {
        if (!searchValue) return new Set(rows);
        const visible = new Set();
        rows.forEach((row) => {
            if ((row.dataset.searchText || '').includes(searchValue)) {
                visible.add(row);
                let parentId = row.dataset.parentId;
                while (parentId && parentId !== '0') {
                    const parent = byId.get(parentId);
                    if (!parent) break;
                    visible.add(parent);
                    parentId = parent.dataset.parentId;
                }
            }
        });
        return visible;
    };

    const render = () => {
        const searchVisible = visibleForSearch();
        rows.forEach((row) => {
            let hiddenByCollapse = false;
            let parentId = row.dataset.parentId;
            while (parentId && parentId !== '0') {
                if (collapsed.has(parentId)) {
                    hiddenByCollapse = true;
                    break;
                }
                const parent = byId.get(parentId);
                parentId = parent ? parent.dataset.parentId : '0';
            }
            const hidden = !searchVisible.has(row) || (searchValue === '' && hiddenByCollapse);
            row.hidden = hidden;
            const toggle = row.querySelector('[data-tree-toggle]');
            if (toggle && !toggle.disabled) {
                const isCollapsed = collapsed.has(row.dataset.nodeId);
                toggle.textContent = isCollapsed ? '▸' : '▾';
                toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
            }
        });
    };

    rows.forEach((row) => {
        const toggle = row.querySelector('[data-tree-toggle]');
        if (!toggle || toggle.disabled) return;
        toggle.addEventListener('click', () => {
            const id = row.dataset.nodeId;
            if (collapsed.has(id)) collapsed.delete(id); else collapsed.add(id);
            render();
        });
    });

    document.querySelector('[data-tree-expand]')?.addEventListener('click', () => {
        collapsed.clear();
        render();
    });
    document.querySelector('[data-tree-collapse]')?.addEventListener('click', () => {
        rows.forEach((row) => {
            if ((childrenByParent.get(row.dataset.nodeId) || []).length > 0) collapsed.add(row.dataset.nodeId);
        });
        render();
    });
    document.querySelector('[data-tree-search]')?.addEventListener('input', (event) => {
        searchValue = String(event.target.value || '').trim().toLocaleLowerCase('ru-RU');
        render();
    });

    render();
})();
