// Offer Banner admin JS: FilePond init and AJAX product/category search for offer items
document.addEventListener("DOMContentLoaded", function () {
    // FilePond init for inputs with class .filepond
    try {
        if (window.FilePond) {
            FilePond.registerPlugin(FilePondPluginImagePreview);

            document
                .querySelectorAll("input.filepond")
                .forEach(function (input) {
                    try {
                        const imagesData = input.getAttribute("data-images");
                        let files = [];
                        if (imagesData) {
                            try {
                                const arr = JSON.parse(imagesData);
                                if (Array.isArray(arr)) {
                                    files = arr.map(function (url) {
                                        return {
                                            source: url,
                                            options: { type: "remote" },
                                        };
                                    });
                                }
                            } catch (e) {}
                        }

                        FilePond.create(input, {
                            allowImagePreview: true,
                            credits: false,
                            storeAsFile: true,
                            allowMultiple: true,
                            allowReorder: true,
                            acceptedFileTypes: ["image/*"],
                            maxFiles:
                                parseInt(
                                    input.getAttribute("data-max-files"),
                                ) || 5,
                            instantUpload: false,
                            files: files,
                        });
                    } catch (e) {}
                });
        }
    } catch (e) {}

    // Scope type toggle
    const scopeType = document.getElementById("scopeType");
    const scopeField = document.getElementById("scopeCategoryField");
    scopeType?.addEventListener("change", function () {
        if (this.value === "category") scopeField.style.display = "block";
        else scopeField.style.display = "none";
    });

    // Offer items: add/remove and AJAX search
    const offerItemsContainer = document.getElementById("offer-items");
    const addOfferItemBtn = document.getElementById("add-offer-item");

    function createOfferItemRow(
        selectedType = "product",
        offerTitle = "",
        selectedItemTitle = "",
        id = "",
    ) {
        const row = document.createElement("div");
        row.className = "row offer-item-row mb-2";
        row.innerHTML = `
            <div class="col-md-4">
                <input type="text" name="offer_items[][title]" class="form-control" placeholder="Offer Title" value="${offerTitle}">
            </div>
            <div class="col-md-3">
                <select name="offer_items[][type]" class="form-select item-type-select">
                    <option value="product" ${selectedType === "product" ? "selected" : ""}>Product</option>
                    <option value="category" ${selectedType === "category" ? "selected" : ""}>Category</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="offer_items[][item_id]" class="form-select tom-select-ajax" data-type="${selectedType}">
                    ${id ? `<option value="${id}" selected>${selectedItemTitle}</option>` : ""}
                </select>
            </div>
            <div class="col-md-1"><button type="button" class="btn btn-danger remove-offer-item">x</button></div>
        `;
        return row;
    }

    // Attach events to a row: initialize TomSelect and wire type change
    function attachRowEvents(row) {
        const typeSelect = row.querySelector(".item-type-select");
        const selectEl = row.querySelector(".tom-select-ajax");

        if (!selectEl) return;

        if (window.TomSelect && !selectEl.tomselect) {
            new TomSelect(selectEl, {
                copyClassesToDropdown: false,
                dropdownParent: "body",
                controlInput: "<input>",
                valueField: "value",
                labelField: "text",
                searchField: "text",
                render: {
                    option: function (data, escape) {
                        return "<div>" + escape(data.text) + "</div>";
                    },
                    item: function (data, escape) {
                        return "<div>" + escape(data.text) + "</div>";
                    },
                },
                load: function (query, callback) {
                    if (!query.length) return callback();
                    const type =
                        selectEl.getAttribute("data-type") ||
                        (typeSelect ? typeSelect.value : "product");
                    const url =
                        type === "product"
                            ? "/admin/products/search"
                            : "/admin/categories/search";
                    fetch(url + "?search=" + encodeURIComponent(query))
                        .then((r) => r.json())
                        .then((json) => callback(json))
                        .catch(() => callback());
                },
            });

            typeSelect?.addEventListener("change", function () {
                selectEl.setAttribute("data-type", this.value);
                try {
                    selectEl.tomselect.clearOptions();
                    selectEl.tomselect.clear();
                } catch (e) {}
            });
        }
    }

    // initialize existing rows
    offerItemsContainer
        ?.querySelectorAll(".offer-item-row")
        .forEach(function (row) {
            attachRowEvents(row);
        });

    addOfferItemBtn?.addEventListener("click", function () {
        const row = createOfferItemRow();
        offerItemsContainer.appendChild(row);
        attachRowEvents(row);
    });

    // delegate remove
    document.addEventListener("click", function (e) {
        if (e.target && e.target.classList.contains("remove-offer-item")) {
            e.target.closest(".offer-item-row")?.remove();
        }
    });
});
