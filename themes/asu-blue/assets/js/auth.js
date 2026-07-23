"use strict";

const tabButtons = Array.from(document.querySelectorAll("[data-tab-target]"));
const authPanels = Array.from(document.querySelectorAll(".auth-panel"));
const passwordButtons = document.querySelectorAll("[data-password-target]");

function activateTab(selectedButton) {
    const targetPanelId = selectedButton.getAttribute("data-tab-target");

    tabButtons.forEach((button) => {
        const isSelected = button === selectedButton;
        button.classList.toggle("is-active", isSelected);
        button.setAttribute("aria-selected", String(isSelected));
        button.tabIndex = isSelected ? 0 : -1;
    });

    authPanels.forEach((panel) => {
        const isSelected = panel.id === targetPanelId;
        panel.classList.toggle("is-active", isSelected);
        panel.hidden = !isSelected;
    });
}

tabButtons.forEach((button, index) => {
    button.addEventListener("click", () => activateTab(button));
    button.addEventListener("keydown", (event) => {
        if (event.key !== "ArrowLeft" && event.key !== "ArrowRight") return;
        event.preventDefault();
        const direction = event.key === "ArrowRight" ? 1 : -1;
        const nextButton = tabButtons[(index + direction + tabButtons.length) % tabButtons.length];
        activateTab(nextButton);
        nextButton.focus();
    });
});

passwordButtons.forEach((button) => {
    button.addEventListener("click", () => {
        const input = document.getElementById(button.getAttribute("data-password-target"));
        if (!input) return;
        const isVisible = input.type === "text";
        input.type = isVisible ? "password" : "text";
        button.textContent = isVisible ? "Показать" : "Скрыть";
        button.setAttribute("aria-label", isVisible ? "Показать пароль" : "Скрыть пароль");
    });
});

document.getElementById("login-form")?.addEventListener("submit", (event) => {
    event.preventDefault();
    const message = document.getElementById("login-message");
    message.textContent = "Обработчик входа будет подключен на этапе разработки авторизации.";
    message.classList.add("is-visible");
});

document.getElementById("register-form")?.addEventListener("submit", (event) => {
    event.preventDefault();
    const password = document.getElementById("register-password");
    const confirmation = document.getElementById("register-password-confirmation");
    const message = document.getElementById("register-message");

    if (password.value !== confirmation.value) {
        message.textContent = "Введенные пароли не совпадают.";
        message.classList.add("is-visible");
        confirmation.focus();
        return;
    }

    message.textContent = "Обработчик регистрации будет подключен на этапе разработки авторизации.";
    message.classList.add("is-visible");
});
