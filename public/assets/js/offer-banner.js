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
                <input type="text" class="form-control ajax-search-input" placeholder="Search..." value="${selectedItemTitle}">
                <input type="hidden" name="offer_items[][item_id]" value="${id}">
            </div>
            <div class="col-md-1"><button type="button" class="btn btn-danger remove-offer-item">x</button></div>
        `;
        return row;
    }

    // Attach events to a row (search input, type select)
    function attachRowEvents(row) {
        const input = row.querySelector(".ajax-search-input");
        const hidden = row.querySelector(
            'input[type="hidden"][name$="[item_id]"]',
        );
        const typeSelect = row.querySelector(".item-type-select");

        let suggestionBox = null;
        const debounce = (fn, delay) => {
            let t;
            return function () {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, arguments), delay);
            };
        };

        function closeSuggestions() {
            if (suggestionBox && suggestionBox.parentElement)
                suggestionBox.remove();
            suggestionBox = null;
        }

        function showSuggestions(items) {
            closeSuggestions();
            suggestionBox = document.createElement("div");
            suggestionBox.className = "list-group position-relative";
            suggestionBox.style.zIndex = 9999;
            items.forEach((it) => {
                const el = document.createElement("button");
                el.type = "button";
                el.className = "list-group-item list-group-item-action";
                el.textContent = it.text || it.title || it.value;
                el.dataset.id = it.id || it.value || it.id;
                el.addEventListener("click", function () {
                    hidden.value = this.dataset.id;
                    display.value = this.textContent;
                    input.value = this.textContent;
                    closeSuggestions();
                });
                suggestionBox.appendChild(el);
            });
            input.parentNode.appendChild(suggestionBox);
        }

        const doSearch = debounce(function () {
            const q = input.value.trim();
            hidden.value = "";
            display.value = "";
            if (!q) {
                closeSuggestions();
                return;
            }
            const type = typeSelect.value || "product";
            const url =
                type === "product"
                    ? "/admin/products/search"
                    : "/admin/categories/search";
            fetch(url + "?search=" + encodeURIComponent(q))
                .then((r) => r.json())
                .then((data) => {
                    if (Array.isArray(data) && data.length)
                        showSuggestions(data);
                    else closeSuggestions();
                })
                .catch(() => closeSuggestions());
        }, 300);

        input.addEventListener("input", doSearch);
        input.addEventListener("blur", function () {
            setTimeout(closeSuggestions, 200);
        });

        typeSelect.addEventListener("change", function () {
            // clear selection when type changes
            hidden.value = "";
            input.value = "";
        });
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
