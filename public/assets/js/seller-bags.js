$(document).ready(function () {
    let page = 1;
    let lastPage = 1;
    const escapeHtml = (value) =>
        $("<div>")
            .text(value || "")
            .html();
    const formatDate = (value) =>
        value ? new Date(value).toLocaleString() : "—";

    const loadBags = () =>
        axios
            .get(
                `/api/seller/bags?page=${page}&status=${encodeURIComponent($("#bag-status-filter").val())}`,
            )
            .then(({ data }) => {
                const payload = data.data;
                lastPage = payload.last_page;
                $("#bag-total").text(`${payload.total} bags`);
                $("#bag-list").html(
                    payload.items.length
                        ? payload.items
                              .map(
                                  (bag) =>
                                      `<tr data-id="${bag.id}"><td><input class="form-control form-control-sm bag-barcode" value="${escapeHtml(bag.barcode)}" ${bag.status === "assigned" ? "disabled" : ""}></td><td><span class="badge ${bag.status === "assigned" ? "bg-blue-lt" : "bg-green-lt"}">${escapeHtml(bag.status)}</span></td><td>${escapeHtml(bag.order_number || (bag.seller_order_id ? `Order #${bag.seller_order_id}` : "—"))}</td><td>${escapeHtml(formatDate(bag.created_at))}</td><td>${bag.status === "available" ? '<div class="btn-list flex-nowrap"><button class="btn btn-outline-primary btn-sm bag-save" type="button">Save</button><button class="btn btn-outline-danger btn-sm bag-delete" type="button">Delete</button></div>' : "—"}</td></tr>`,
                              )
                              .join("")
                        : '<tr><td colspan="5" class="text-secondary">No bags found.</td></tr>',
                );
                $("#bag-prev").prop("disabled", page <= 1);
                $("#bag-next").prop("disabled", page >= lastPage);
            })
            .catch((error) =>
                $("#bag-list").html(
                    `<tr><td colspan="5" class="text-danger">${escapeHtml(error.response?.data?.message || "Could not load bags.")}</td></tr>`,
                ),
            );

    $("#bag-bulk-submit").on("click", function () {
        const barcodes = $("#bag-bulk-input").val();
        if (!barcodes.trim()) return;
        const button = $(this).prop("disabled", true).text("Adding…");
        axios
            .post("/api/seller/bags/bulk", { barcodes })
            .then(({ data }) => {
                const result = data.data;
                $("#bag-import-result").html(
                    `<div class="alert alert-success">Added ${result.created_count} bag${result.created_count === 1 ? "" : "s"}; ${result.duplicate_count} duplicate barcode${result.duplicate_count === 1 ? "" : "s"} skipped.${result.duplicate_barcodes.length ? `<div class="mt-1 small">Already in inventory: ${escapeHtml(result.duplicate_barcodes.join(", "))}</div>` : ""}</div>`,
                );
                $("#bag-bulk-input").val("");
                page = 1;
                loadBags();
            })
            .catch((error) =>
                $("#bag-import-result").html(
                    `<div class="alert alert-danger">${escapeHtml(error.response?.data?.message || "Could not add barcodes.")}</div>`,
                ),
            )
            .finally(() => button.prop("disabled", false).text("Add barcodes"));
    });

    $("#bag-list").on("click", ".bag-save", function () {
        const row = $(this).closest("tr");
        const id = row.data("id");
        const barcode = row.find(".bag-barcode").val();
        axios
            .put(`/api/seller/bags/${id}`, { barcode })
            .then(() => loadBags())
            .catch((error) =>
                window.alert(
                    error.response?.data?.message ||
                        "Could not update bag barcode.",
                ),
            );
    });

    $("#bag-list").on("click", ".bag-delete", function () {
        const row = $(this).closest("tr");
        if (!window.confirm(`Delete bag ${row.find(".bag-barcode").val()}?`))
            return;
        axios
            .delete(`/api/seller/bags/${row.data("id")}`)
            .then(() => loadBags())
            .catch((error) =>
                window.alert(
                    error.response?.data?.message || "Could not delete bag.",
                ),
            );
    });

    $("#bag-status-filter").on("change", () => {
        page = 1;
        loadBags();
    });
    $("#bag-refresh").on("click", loadBags);
    $("#bag-prev").on("click", () => {
        page = Math.max(1, page - 1);
        loadBags();
    });
    $("#bag-next").on("click", () => {
        page = Math.min(lastPage, page + 1);
        loadBags();
    });
    loadBags();
});
