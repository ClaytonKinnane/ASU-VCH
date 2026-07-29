(() => {
    'use strict';

    const closeNodeActionPanels = (container) => {
        container.querySelectorAll('[data-node-action-target]').forEach((trigger) => {
            trigger.setAttribute('aria-expanded', 'false');
        });
        container.querySelectorAll('[data-node-action-panel]').forEach((panel) => {
            panel.hidden = true;
        });
    };

    const openDatePicker = (trigger) => {
        const targetId = trigger.dataset.datePickerTarget || '';
        const input = targetId !== '' ? document.getElementById(targetId) : null;
        if (!(input instanceof HTMLInputElement) || input.type !== 'date') return;
        if (input.disabled || input.readOnly) return;

        input.focus({ preventScroll: true });

        if (typeof input.showPicker === 'function') {
            try {
                input.showPicker();
                return;
            } catch {
                // Browser-specific picker restrictions fall back to the native click path.
            }
        }

        input.click();
    };

    const toggleNodeActionPanel = (trigger) => {
        const container = trigger.closest('[data-node-actions]');
        if (!(container instanceof HTMLElement)) return;

        const targetId = trigger.dataset.nodeActionTarget || '';
        const panel = targetId !== '' ? document.getElementById(targetId) : null;
        if (!(panel instanceof HTMLElement) || !container.contains(panel)) return;

        const shouldOpen = panel.hidden;
        closeNodeActionPanels(container);

        if (shouldOpen) {
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
        }
    };

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;

        const dateTrigger = event.target.closest('[data-date-picker-target]');
        if (dateTrigger instanceof HTMLButtonElement) {
            openDatePicker(dateTrigger);
            return;
        }

        const nodeActionTrigger = event.target.closest('[data-node-action-target]');
        if (nodeActionTrigger instanceof HTMLButtonElement) {
            toggleNodeActionPanel(nodeActionTrigger);
        }
    });
})();