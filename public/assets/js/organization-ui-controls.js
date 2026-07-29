(() => {
    'use strict';

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;

        const trigger = event.target.closest('[data-date-picker-target]');
        if (!(trigger instanceof HTMLButtonElement)) return;

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
    });
})();
