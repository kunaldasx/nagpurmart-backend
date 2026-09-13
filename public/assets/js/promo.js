function getDateTimeLocalValue(value) {
    if (!value) return "";

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        const text = String(value).replace(" ", "T");
        return /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(text)
            ? text.slice(0, 16)
            : "";
    }

    const adjusted = new Date(
        date.getTime() - date.getTimezoneOffset() * 60000,
    );
    return adjusted.toISOString().slice(0, 16);
}

function setFieldValue(selector, value, fallback = "") {
    const field = document.querySelector(selector);
    if (!field) return;

    if (field.type === "checkbox" || field.type === "radio") {
        field.checked = Boolean(value);
        return;
    }

    field.value = value ?? fallback;
}

function viewPromo(id) {
    axios
        .get(`/admin/promos/${id}`)
        .then((response) => {
            if (response.data.success) {
                const promo = response.data.data;
                let start_date = new Date(promo.start_date);
                let end_date = new Date(promo.end_date);
                let discount_type = promo.discount_type.replace("_", " ");
                document.getElementById("promo-code").textContent = promo.code;
                document.getElementById("promo-heading").textContent =
                    promo.heading || "N/A";
                document.getElementById("promo-sub-heading").textContent =
                    promo.sub_heading || "N/A";
                const bannerImage =
                    document.getElementById("promo-banner-image");
                const bannerShell =
                    document.getElementById("promo-banner-shell");
                const bgColor = promo.bg_color || "#F5E6C8";
                const fontColor = promo.font_color || "#111827";

                bannerShell.style.backgroundColor = bgColor;
                bannerShell.style.color = fontColor;

                if (promo.banner_image || promo.image) {
                    bannerImage.src = promo.banner_image || promo.image;
                    bannerShell.style.display = "block";
                } else {
                    bannerImage.removeAttribute("src");
                    bannerShell.style.display = "none";
                }
                document.getElementById("promo-description").textContent =
                    promo.description || "N/A";
                document.getElementById("promo-discount-type").textContent =
                    discount_type;
                document.getElementById("promo-discount-amount").textContent =
                    promo.discount_amount;
                document.getElementById("promo-start-date").textContent =
                    start_date.toISOString().split("T")[0];
                document.getElementById("promo-end-date").textContent = end_date
                    .toISOString()
                    .split("T")[0];
                document.getElementById("promo-usage-count").textContent =
                    promo.usage_count;
                document.getElementById("promo-max-total-usage").textContent =
                    promo.max_total_usage || "Unlimited";
                document.getElementById(
                    "promo-max-usage-per-user",
                ).textContent = promo.max_usage_per_user || "Unlimited";
                document.getElementById("promo-min-order-total").textContent =
                    promo.min_order_total || "No minimum";
                document.getElementById(
                    "promo-max-discount-value",
                ).textContent = promo.max_discount_value || "N/A";
            }
        })
        .catch((error) => console.error("Error:", error));
}

function editPromo(id) {
    axios
        .get(`/admin/promos/${id}`)
        .then((response) => {
            if (response.data.success) {
                const promo = response.data.data;
                const form = document.querySelector("#promo-modal form");

                // Update form action for editing
                form.action = `/admin/promos/${id}`;
                form.insertAdjacentHTML(
                    "afterbegin",
                    '<input type="hidden" name="_method" value="PUT">',
                );

                // Populate form fields
                document.getElementById("promo-id").value = promo.id;
                setFieldValue('input[name="code"]', promo.code);
                setFieldValue('input[name="heading"]', promo.heading);
                setFieldValue('input[name="sub_heading"]', promo.sub_heading);
                setFieldValue(
                    'input[name="bg_color"]',
                    promo.bg_color || "#F5E6C8",
                );
                setFieldValue(
                    'input[name="font_color"]',
                    promo.font_color || "#111827",
                );
                setFieldValue(
                    'textarea[name="description"]',
                    promo.description,
                );

                const discountTypeField = document.querySelector(
                    'select[name="discount_type"]',
                );
                if (discountTypeField && promo.discount_type) {
                    discountTypeField.value = promo.discount_type;
                }

                setFieldValue(
                    'input[name="discount_amount"]',
                    promo.discount_amount,
                );
                setFieldValue(
                    'input[name="max_discount_value"]',
                    promo.max_discount_value,
                );
                setFieldValue(
                    'input[name="start_date"]',
                    getDateTimeLocalValue(promo.start_date),
                );
                setFieldValue(
                    'input[name="end_date"]',
                    getDateTimeLocalValue(promo.end_date),
                );
                setFieldValue(
                    'input[name="min_order_total"]',
                    promo.min_order_total,
                );

                const promoModeField = document.querySelector(
                    'select[name="promo_mode"]',
                );
                if (promoModeField && promo.promo_mode) {
                    promoModeField.value = promo.promo_mode;
                }

                setFieldValue(
                    'input[name="max_total_usage"]',
                    promo.max_total_usage,
                );
                setFieldValue(
                    'input[name="max_usage_per_user"]',
                    promo.max_usage_per_user,
                );
                // document.querySelector('input[name="individual_use"]').checked = promo.individual_use == 1;

                // Update modal title and button text
                document.querySelector(
                    "#promo-modal .modal-title",
                ).textContent = "Edit Promo";
                document.querySelector(
                    '#promo-modal button[type="submit"]',
                ).innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Update Promo
                        `;
            }
        })
        .catch((error) => console.error("Error:", error));
}

document
    .getElementById("promo-modal")
    .addEventListener("hidden.bs.modal", function () {
        const form = this.querySelector("form");
        form.reset();
        form.action = "/admin/promos";
        form.querySelector('input[name="_method"]')?.remove();
        document.getElementById("promo-id").value = "";

        // Reset modal title and button text
        this.querySelector(".modal-title").textContent = "Create Promo";
        this.querySelector('button[type="submit"]').innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2">
                    <path d="M12 5l0 14"/>
                    <path d="M5 12l14 0"/>
                </svg>
                Create New Promo
            `;
    });

document.addEventListener("click", function (event) {
    handleDelete(
        event,
        ".delete-promo-code",
        `/admin/promos/`,
        "You are about to delete this Promo Code.",
    );
});

function deletePromo(id) {
    if (confirm("Are you sure you want to delete this promo?")) {
        fetch(`/admin/promos/${id}`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                "Content-Type": "application/json",
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // Refresh the DataTable
                    $("#promos-table").DataTable().ajax.reload();
                    // Show success message (you can implement a toast notification here)
                    alert("Promo deleted successfully!");
                } else {
                    alert("Error deleting promo: " + data.message);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                alert("Error deleting promo");
            });
    }
}
