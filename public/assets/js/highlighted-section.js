document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("highlighted-section-modal");
    if (!modal) return;
    document.addEventListener("click", (event) => {
        if (event.target.closest(".delete-highlighted-section"))
            handleDelete(
                event,
                ".delete-highlighted-section",
                `/${panel}/highlighted-sections/`,
                "You are about to delete this highlighted section.",
            );
    });
    const items = document.getElementById("highlighted-items");
    const scopeType = document.getElementById("highlighted-scope-type");
    let index = 0;

    const endpoint = (type) => {
        if (type === "product") return `${base_url}/${panel}/products/search`;
        if (type === "category")
            return `${base_url}/${panel}/highlighted-sections/categories/search`;
        return `${base_url}/${panel}/${type}s/search`;
    };
    const setupSelect = (select, type, selected) => {
        const ts = new TomSelect(select, {
            valueField: "value",
            labelField: "text",
            searchField: "text",
            dropdownParent: "body",
            load(query, callback) {
                if (!query) return callback();
                fetch(
                    `${endpoint(type)}?${type === "brand" ? "q" : "search"}=${encodeURIComponent(query)}`,
                )
                    .then((response) => response.json())
                    .then(callback)
                    .catch(() => callback());
            },
        });
        if (selected) {
            ts.addOption({ value: selected.id, text: selected.title });
            ts.setValue(selected.id);
        }
    };

    const addItem = (data) => {
        const itemIndex = index++;
        const row = document.createElement("div");
        row.className = "row align-items-end mb-3 highlighted-item-row";
        row.innerHTML = `<input type="hidden" name="items[${itemIndex}][id]" value="${data?.id || ""}"><div class="col-md-2"><label class="form-label">Type</label><select class="form-select item-type" name="items[${itemIndex}][item_type]"><option value="product">Product</option><option value="category">Category</option><option value="brand">Brand</option></select></div><div class="col-md-3"><label class="form-label">Record</label><select class="form-select item-record" name="items[${itemIndex}][item_id]"><option value="">Search record</option></select></div><div class="col-md-2"><label class="form-label">Title</label><input class="form-control" name="items[${itemIndex}][title]" required></div><div class="col-md-2"><label class="form-label">Subtitle</label><input class="form-control" name="items[${itemIndex}][subtitle]"></div><div class="col-md-2"><label class="form-label">Image</label><input type="file" accept="image/*" class="form-control" name="items[${itemIndex}][image]">${data?.image ? `<img src="${data.image}" alt="" class="mt-1" style="max-width:50px;max-height:40px">` : ""}</div><div class="col-md-1 px-1"><button type="button" class="btn btn-outline-danger remove-item w-100 px-1" title="Remove item" aria-label="Remove item"><span aria-hidden="true">&times;</span></button></div>`;
        items.appendChild(row);
        const type = data?.item_type || "product";
        row.querySelector(".item-type").value = type;
        setupSelect(row.querySelector(".item-record"), type, data?.item);
        row.querySelector(".item-type").addEventListener("change", (event) => {
            const record = row.querySelector(".item-record");
            record.tomselect?.destroy();
            record.innerHTML = '<option value="">Search record</option>';
            setupSelect(record, event.target.value);
        });
        row.querySelector('[name$="[title]"]').value = data?.title || "";
        row.querySelector('[name$="[subtitle]"]').value = data?.subtitle || "";
        row.querySelector(".remove-item").addEventListener("click", () =>
            row.remove(),
        );
    };
    const scopeCategory = document.getElementById("highlighted-scope-category");
    const reset = () => {
        items.innerHTML = "";
        index = 0;
        addItem();
    };
    setupSelect(scopeCategory, "category");
    scopeType.addEventListener(
        "change",
        () =>
            (document.getElementById(
                "highlighted-scope-category-field",
            ).style.display =
                scopeType.value === "category" ? "block" : "none"),
    );
    document
        .getElementById("add-highlighted-item")
        .addEventListener("click", () => addItem());
    modal.addEventListener("show.bs.modal", (event) => {
        const id = event.relatedTarget?.dataset.id;
        if (!id) {
            modal.querySelector("form").reset();
            modal.querySelector("form").action =
                `${base_url}/${panel}/highlighted-sections`;
            scopeCategory.tomselect?.clear();
            scopeCategory.tomselect?.clearOptions();
            scopeType.dispatchEvent(new Event("change"));
            reset();
            return;
        }
        fetch(`${base_url}/${panel}/highlighted-sections/${id}`)
            .then((response) => response.json())
            .then(({ data }) => {
                const form = modal.querySelector("form");
                form.action = `${base_url}/${panel}/highlighted-sections/${id}`;
                [
                    "title",
                    "subtitle",
                    "template",
                    "scope_type",
                    "background_color",
                    "font_color",
                    "sort_order",
                ].forEach((name) => {
                    const field = form.elements[name];
                    if (field) field.value = data[name] ?? "";
                });
                form.elements.status.checked = data.status === "active";
                const scopeSelect = document.getElementById(
                    "highlighted-scope-category",
                );
                scopeSelect.tomselect?.destroy();
                scopeSelect.innerHTML =
                    '<option value="">Search category</option>';
                setupSelect(scopeSelect, "category", data.scope_category);
                scopeType.dispatchEvent(new Event("change"));
                reset();
                items.innerHTML = "";
                index = 0;
                (data.items || []).forEach(addItem);
            });
    });
    reset();
});
