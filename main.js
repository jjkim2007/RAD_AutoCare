document.addEventListener("DOMContentLoaded", () => {
    // 1. Intercept confirmation actions
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

    // 4. Toggle Major Service Mileage Package (10k / 100k)
    const serviceSelects = document.querySelectorAll(".js-service-type");
    serviceSelects.forEach((sel) => {
        const toggleMajorPackage = () => {
            const form = sel.closest("form");
            if (!form) return;
            const pkgGroup = form.querySelector(".js-major-package-group");
            const pkgSelect = form.querySelector(".js-major-package-select");
            if (!pkgGroup || !pkgSelect) return;

            if (sel.value === "Major Service") {
                pkgGroup.style.display = "block";
                pkgSelect.setAttribute("required", "required");
            } else {
                pkgGroup.style.display = "none";
                pkgSelect.removeAttribute("required");
                pkgSelect.value = "";
            }
        };
        sel.addEventListener("change", toggleMajorPackage);
        toggleMajorPackage();
    });
});