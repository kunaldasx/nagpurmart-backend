document.addEventListener("DOMContentLoaded", function () {
    const select = document.getElementById("popular-search-category");
    if (select && window.TomSelect) {
        const categorySelect = new TomSelect(select, {
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: select.options[0].text,
            load: function (query, callback) {
                if (!query.length) return callback();
                fetch(
                    `${base_url}/admin/categories/search?q=${encodeURIComponent(query)}`,
                )
                    .then((response) => response.json())
                    .then(callback)
                    .catch(() => callback());
            },
            render: {
                option: function (data, escape) {
                    return `<div>${escape(data.text)}</div>`;
                },
                item: function (data, escape) {
                    return `<div>${escape(data.text)}</div>`;
                },
            },
        });

        const modal = document.getElementById("popular-search-modal");
        modal?.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            const id = button?.dataset.id;
            document.getElementById("popular-search-form").reset();
            categorySelect.clear();
            document.getElementById("popular-search-form").action = id
                ? `${base_url}/admin/popular-searches/${id}`
                : `${base_url}/admin/popular-searches`;
            if (id)
                fetch(`${base_url}/admin/popular-searches/${id}`)
                    .then((response) => response.json())
                    .then((json) => {
                        categorySelect.addOption({
                            value: json.data.category.id,
                            text: json.data.category.title,
                        });
                        categorySelect.setValue(json.data.category.id);
                        document.getElementById(
                            "popular-search-sort-order",
                        ).value = json.data.sort_order;
                        document.querySelector(
                            '#popular-search-form input[name="status"]',
                        ).checked = json.data.status === "active";
                    });
        });
    }

    const sortable = document.getElementById("popular-search-sortable");
    if (sortable && window.Sortable) new Sortable(sortable, { animation: 150 });

    document
        .getElementById("popular-search-form")
        ?.addEventListener("submit", function (event) {
            event.preventDefault();

            const form = event.currentTarget;
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            fetch(form.action, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                },
                body: new FormData(form),
            })
                .then(async (response) => {
                    const data = await response.json();
                    if (!response.ok) throw { response: { data } };
                    window.location.href = `${base_url}/admin/popular-searches`;
                })
                .catch((error) => {
                    const message =
                        error.response?.data?.message ||
                        "An error occurred while saving the popular search.";
                    Toast.fire({ icon: "error", title: message });
                    submitButton.disabled = false;
                });
        });

    document
        .getElementById("save-popular-search-order")
        ?.addEventListener("click", function () {
            const popularSearches = [
                ...document.querySelectorAll(
                    "#popular-search-sortable [data-id]",
                ),
            ].map((item) => item.dataset.id);
            fetch(`${base_url}/admin/popular-searches/sort`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                    Accept: "application/json",
                },
                body: JSON.stringify({ popular_searches: popularSearches }),
            })
                .then((response) => response.json())
                .then((data) =>
                    Toast.fire({
                        icon: data.success === false ? "error" : "success",
                        title: data.message,
                    }),
                );
        });
});
