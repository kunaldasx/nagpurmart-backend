document.addEventListener("DOMContentLoaded", function () {
    function showScopeCategoryField() {
        const type = document.getElementById("scopeType")?.value;
        const field = document.getElementById("scopeCategoryField");
        if (!field) return;
        field.style.display = type === "category" ? "" : "none";
    }

    function addOfferItem(existing = null) {
        const container = document.getElementById("offerItemsContainer");
        if (!container) return;

        const index = container.querySelectorAll(".offer-item-row").length;
        const row = document.createElement("div");
        row.className = "offer-item-row row g-2 mb-3";
        row.dataset.index = index;
        row.innerHTML = `
            <div class="col-md-2">
                <input type="text" class="form-control" name="metadata[images][${index}][title]" placeholder="${window.i18n?.offer_title || "Offer Title"}" value="${existing?.title || ""}" required>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="metadata[images][${index}][type]" data-offer-type>
                    <option value="product" ${existing?.type === "product" ? "selected" : ""}>${window.i18n?.product || "Product"}</option>
                    <option value="category" ${existing?.type === "category" ? "selected" : ""}>${window.i18n?.category || "Category"}</option>
                </select>
            </div>
            <div class="col-md-3" data-product-field style="display: ${existing?.type === "product" ? "block" : "none"};">
                <select class="form-select" name="metadata[images][${index}][product_id]" data-select-product>
                    <option value="">${window.i18n?.select_product || "Select Product"}</option>
                </select>
            </div>
            <div class="col-md-3" data-category-field style="display: ${existing?.type === "category" ? "block" : "none"};">
                <select class="form-select" name="metadata[images][${index}][category_id]" data-select-category>
                    <option value="">${window.i18n?.select_category || "Select Category"}</option>
                </select>
            </div>
            <div class="col-md-3" data-custom-url-field style="display: none;">
                <input type="text" class="form-control" name="metadata[images][${index}][custom_url]" placeholder="${window.i18n?.custom_url || "Custom URL"}" value="${existing?.custom_url || ""}">
            </div>
            <div class="col-md-1 d-flex align-items-center">
                <button type="button" class="btn btn-danger remove-offer-item">&times;</button>
            </div>
        `;
        container.appendChild(row);
        attachOfferItemEvents(row);
    }

    function attachOfferItemEvents(element) {
        const selectType = element.querySelector("[data-offer-type]");
        const productField = element.querySelector("[data-product-field]");
        const categoryField = element.querySelector("[data-category-field]");
        const customUrlField = element.querySelector("[data-custom-url-field]");
        const removeButton = element.querySelector(".remove-offer-item");

        if (selectType) {
            selectType.addEventListener("change", function () {
                const type = this.value;
                productField.style.display = type === "product" ? "" : "none";
                categoryField.style.display = type === "category" ? "" : "none";
                customUrlField.style.display = type === "custom" ? "" : "none";
            });
        }

        if (removeButton) {
            removeButton.addEventListener("click", function () {
                element.remove();
            });
        }

        const selectProduct = element.querySelector("[data-select-product]");
        if (selectProduct) {
            initializeTomSelect(
                selectProduct,
                "products",
                window.i18n?.search_product || "Search Product",
            );
        }

        const selectCategory = element.querySelector("[data-select-category]");
        if (selectCategory) {
            initializeTomSelect(
                selectCategory,
                "categories",
                window.i18n?.search_category || "Search Category",
            );
        }
    }

    function initializeTomSelect(element, type, placeholder) {
        if (!element || typeof TomSelect === "undefined") return;
        new TomSelect(element, {
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: placeholder,
            render: {
                item: function (data, escape) {
                    return "<div>" + escape(data.text) + "</div>";
                },
                option: function (data, escape) {
                    return "<div>" + escape(data.text) + "</div>";
                },
            },
            load: function (query, callback) {
                if (!query.length) return callback();
                const url =
                    `${window.base_url}/${window.panel}/` +
                    type +
                    "/search?search=" +
                    encodeURIComponent(query);
                fetch(url)
                    .then((res) => res.json())
                    .then((json) => callback(json))
                    .catch(() => callback());
            },
        });
    }

    function updateDeletedMediaList(mediaId) {
        const input = document.getElementById("deletedBannerImages");
        if (!input) return;
        const current = JSON.parse(input.value || "[]");
        current.push(mediaId);
        input.value = JSON.stringify(current);
    }

    function initializeExistingFilePond() {
        const input = document.querySelector('[name="banner_images[]"]');
        if (!input || typeof FilePond === "undefined") return;
        const imagesJson = input.getAttribute("data-images");
        const existing = imagesJson ? JSON.parse(imagesJson) : [];
        FilePond.create(input, {
            allowImagePreview: true,
            credits: false,
            storeAsFile: true,
            maxFileSize: "2MB",
            acceptedFileTypes: ["image/*"],
            files: existing.map((item) => ({
                source: item.url,
                options: { type: "remote" },
            })),
        });
    }

    document
        .getElementById("scopeType")
        ?.addEventListener("change", showScopeCategoryField);
    showScopeCategoryField();

    document
        .getElementById("addOfferItem")
        ?.addEventListener("click", function () {
            addOfferItem();
        });

    document.querySelectorAll(".offer-item-row").forEach((row) => {
        attachOfferItemEvents(row);
    });

    initializeExistingFilePond();

    const table = $("#offer-banners-table").DataTable();
    $("#positionFilter, #statusFilter, #scopeTypeFilter").on(
        "change",
        function () {
            table.ajax.reload(null, false);
        },
    );
    $("#offer-banners-table").on("preXhr.dt", function (e, settings, data) {
        data.position = $("#positionFilter").val();
        data.visibility_status = $("#statusFilter").val();
        data.scope_type = $("#scopeTypeFilter").val();
    });

    document.addEventListener("click", (e) => {
        if (e.target.closest(".delete-offer-banner")) {
            handleDelete(
                e,
                `/${window.panel}/offer-banners/`,
                "You are about to delete this Offer Banner.",
            );
        }
    });
});
