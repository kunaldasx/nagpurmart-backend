$(document).ready(function () {
    // Show/hide fields based on banner type
    function toggleFields() {
        const bannerTypeEl = document.getElementById("bannerType");
        if (!bannerTypeEl) return;
        const type = bannerTypeEl.value;

        // Hide all fields
        document.getElementById("productField").style.display = "none";
        document.getElementById("categoryField").style.display = "none";
        document.getElementById("brandField").style.display = "none";
        document.getElementById("customField").style.display = "none";

        // Show the selected field
        if (type === "product") {
            document.getElementById("productField").style.display = "";
        } else if (type === "category") {
            document.getElementById("categoryField").style.display = "";
        } else if (type === "brand") {
            document.getElementById("brandField").style.display = "";
        } else if (type === "custom") {
            document.getElementById("customField").style.display = "";
        }
    }

    function buildOfferItemRow(index, item = {}) {
        const title = item.title || "";
        const type = item.type || "";
        const entityId = item.entity_id || "";
        const entityText = item.entity_text || "";

        return `
            <div class="card mb-3 offer-item" data-index="${index}">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label required">${window.i18n_offer_item_title || "Title"}</label>
                            <input type="text" name="offer_items[${index}][title]" class="form-control"
                                   value="${title}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">${window.i18n_select_offer_item_type || "Select offer item type"}</label>
                            <select class="form-select offer-item-type" name="offer_items[${index}][type]">
                                <option value="">${window.i18n_select_offer_item_type || "Select offer item type"}</option>
                                <option value="product" ${type === "product" ? "selected" : ""}>${window.i18n_product || "Product"}</option>
                                <option value="category" ${type === "category" ? "selected" : ""}>${window.i18n_category || "Category"}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">${window.i18n_select_item || "Select Item"}</label>
                            <select class="form-select offer-item-entity" name="offer_items[${index}][entity_id]" data-selected="${entityId}" data-selected-text="${entityText}">
                                ${entityId && entityText ? `<option value="${entityId}" selected>${entityText}</option>` : ""}
                            </select>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-offer-item">${window.i18n_remove || "Remove"}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function updateOfferItemIndexes() {
        const rows = document.querySelectorAll(".offer-item");
        rows.forEach((row, index) => {
            row.dataset.index = index;
            row.querySelectorAll("input, select").forEach((input) => {
                const name = input.getAttribute("name");
                if (!name) return;
                const newName = name.replace(
                    /offer_items\[\d+\]/,
                    `offer_items[${index}]`,
                );
                input.setAttribute("name", newName);
            });
        });
    }

    function initOfferItemEntity(selectEl) {
        if (!selectEl || !window.TomSelect) return;

        const typeSelect = selectEl
            .closest(".offer-item")
            ?.querySelector(".offer-item-type");
        const type = typeSelect?.value;
        const selectedValue = selectEl.dataset.selected || "";
        const selectedText = selectEl.dataset.selectedText || "";

        if (selectEl.tomselect) {
            selectEl.tomselect.destroy();
        }

        window.TomSelect &&
            new TomSelect(selectEl, {
                copyClassesToDropdown: false,
                dropdownParent: "body",
                controlInput: "<input>",
                valueField: "value",
                labelField: "text",
                searchField: "text",
                placeholder:
                    type === "category"
                        ? window.i18n_search_category || "Search Category"
                        : window.i18n_search_product || "Search Product",
                load: function (query, callback) {
                    if (!query.length || !type) return callback();
                    const url = `${base_url}/${panel}/${type === "category" ? "categories" : "products"}/search?search=${encodeURIComponent(query)}`;
                    fetch(url)
                        .then((response) => response.json())
                        .then((json) => callback(json))
                        .catch(() => callback());
                },
                render: {
                    item: function (data, escape) {
                        return `<div>${escape(data.text)}</div>`;
                    },
                    option: function (data, escape) {
                        return `<div>${escape(data.text)}</div>`;
                    },
                },
            });

        if (selectedValue && selectedText) {
            selectEl.tomselect.addOption({
                value: selectedValue,
                text: selectedText,
            });
            selectEl.tomselect.setValue(selectedValue);
        }
    }

    function initOfferItemRow(row) {
        const typeSelect = row.querySelector(".offer-item-type");
        const entitySelect = row.querySelector(".offer-item-entity");
        if (!typeSelect || !entitySelect) return;

        typeSelect.addEventListener("change", function () {
            entitySelect.dataset.selected = "";
            entitySelect.dataset.selectedText = "";
            entitySelect.innerHTML = '<option value=""></option>';
            initOfferItemEntity(entitySelect);
        });

        row.querySelector(".remove-offer-item")?.addEventListener(
            "click",
            function () {
                row.remove();
                updateOfferItemIndexes();
            },
        );

        initOfferItemEntity(entitySelect);
    }

    function initializeOfferItems() {
        document.querySelectorAll(".offer-item").forEach(initOfferItemRow);

        document
            .getElementById("add-offer-item")
            ?.addEventListener("click", function () {
                const container = document.getElementById("offer-items-list");
                if (!container) return;
                const nextIndex =
                    container.querySelectorAll(".offer-item").length;
                container.insertAdjacentHTML(
                    "beforeend",
                    buildOfferItemRow(nextIndex),
                );
                const newRow = container.querySelector(
                    `.offer-item[data-index="${nextIndex}"]`,
                );
                if (newRow) {
                    initOfferItemRow(newRow);
                }
            });
    }

    // Initial toggle
    toggleFields();
    document
        .getElementById("bannerType")
        ?.addEventListener("change", toggleFields);
    initializeOfferItems();

    const table = $("#banners-table").DataTable();

    // Reload table when filters change
    $("#typeFilter, #positionFilter, #statusFilter,#scopeTypeFilter").on(
        "change",
        function () {
            table.ajax.reload(null, false);
        },
    );

    // Add filter params to AJAX request
    $("#banners-table").on("preXhr.dt", function (e, settings, data) {
        data.type = $("#typeFilter").val();
        data.position = $("#positionFilter").val();
        data.visibility_status = $("#statusFilter").val();
        data.scope_type = $("#scopeTypeFilter").val();
    });

    document.addEventListener("click", (e) => {
        handleDelete(
            e,
            ".delete-banner",
            `/${panel}/banners/`,
            "You are about to delete this Banner.",
        );
    });
});
