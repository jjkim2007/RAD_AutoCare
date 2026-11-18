document.addEventListener("DOMContentLoaded", () => {
    // 1. Intercept delete actions
    const confirmButtons = document.querySelectorAll(".js-confirm-action");
    confirmButtons.forEach((btn) => {
        btn.addEventListener("click", (e) => {
            const message = btn.getAttribute("data-confirm") || "Are you sure you want to proceed?";
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // 2. Auto-submit dropdowns on selection change
    const autoSubmitSelects = document.querySelectorAll(".js-auto-submit");
    autoSubmitSelects.forEach((selectElem) => {
        selectElem.addEventListener("change", function () {
            this.form.submit();
        });
    });

    // 3. Print / Save PDF Handler
    const printButtons = document.querySelectorAll(".js-print-btn");
    printButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            window.print();
        });
    });
});