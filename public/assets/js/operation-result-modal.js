(function () {
    'use strict';

    function createElement(tagName, className, text) {
        var element = document.createElement(tagName);
        if (className) {
            element.className = className;
        }
        if (typeof text === 'string') {
            element.textContent = text;
        }
        return element;
    }

    function show(type, message) {
        if ((type !== 'success' && type !== 'error') || typeof message !== 'string' || message === '') {
            return;
        }

        var existing = document.getElementById('operation-result-modal');
        if (existing) {
            existing.remove();
        }

        var dialog = createElement('dialog', 'operation-result-modal operation-result-modal--' + type);
        dialog.id = 'operation-result-modal';
        dialog.setAttribute('aria-labelledby', 'operation-result-modal-title');
        dialog.setAttribute('aria-describedby', 'operation-result-modal-message');

        var content = createElement('div', 'operation-result-modal__content');
        var icon = createElement('div', 'operation-result-modal__icon', type === 'error' ? '!' : '✓');
        icon.setAttribute('aria-hidden', 'true');

        var body = createElement('div', 'operation-result-modal__body');
        var eyebrow = createElement('div', 'operation-result-modal__eyebrow', type === 'error' ? 'Ошибка операции' : 'Результат операции');
        var title = createElement('h2', 'operation-result-modal__title', type === 'error' ? 'Операция не выполнена' : 'Операция выполнена');
        title.id = 'operation-result-modal-title';

        var messageElement = createElement('p', 'operation-result-modal__message', message);
        messageElement.id = 'operation-result-modal-message';

        var actions = createElement('div', 'operation-result-modal__actions');
        var closeButton = createElement(
            'button',
            type === 'error'
                ? 'operation-result-modal__button operation-result-modal__button--error'
                : 'operation-result-modal__button operation-result-modal__button--success',
            type === 'error' ? 'Понятно' : 'Закрыть'
        );
        closeButton.type = 'button';

        actions.appendChild(closeButton);
        body.appendChild(eyebrow);
        body.appendChild(title);
        body.appendChild(messageElement);
        body.appendChild(actions);
        content.appendChild(icon);
        content.appendChild(body);
        dialog.appendChild(content);
        document.body.appendChild(dialog);

        function closeDialog() {
            if (dialog.open && typeof dialog.close === 'function') {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
                dialog.remove();
            }
        }

        closeButton.addEventListener('click', closeDialog);
        dialog.addEventListener('close', function () {
            dialog.remove();
        });
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                closeDialog();
            }
        });

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }

        closeButton.focus();
    }

    window.AsuOperationResultModal = Object.freeze({ show: show });
}());
