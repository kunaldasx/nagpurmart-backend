document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("delivery-slot-modal");
    if (!modal) return;
    const form = modal.querySelector("form");
    const store = document.getElementById("delivery-slot-store");
    const storeSelect = new TomSelect(store, {
        valueField: "value",
        labelField: "text",
        searchField: "text",
        load(query, callback) {
            fetch(
                `${base_url}/${panel}/sellers/store/search?search=${encodeURIComponent(query)}`,
            )
                .then((response) => response.json())
                .then(callback)
                .catch(() => callback());
        },
    });

    modal.addEventListener("show.bs.modal", (event) => {
        const id = event.relatedTarget?.dataset.id;
        form.reset();
        storeSelect.clear();
        storeSelect.clearOptions();
        form.action = `${base_url}/${panel}/delivery-slots`;
        modal.querySelector(".modal-title").textContent = "Add delivery slot";
        if (!id) return;
        fetch(`${base_url}/${panel}/delivery-slots/${id}`)
            .then((response) => response.json())
            .then(({ data }) => {
                form.action = `${base_url}/${panel}/delivery-slots/${id}`;
                modal.querySelector(".modal-title").textContent =
                    "Edit delivery slot";
                ["day_of_week", "start_time", "end_time", "max_orders"].forEach(
                    (name) => {
                        form.elements[name].value = data[name] ?? "";
                    },
                );
                form.elements.is_active.checked = Boolean(data.is_active);
                storeSelect.addOption({
                    value: data.store_id,
                    text: data.store?.name || "Store",
                });
                storeSelect.setValue(data.store_id);
            });
    });
});
