$(document).ready(function () {
    let page = 1;
    let lastPage = 1;
    let selectedBagId = null;
    const escapeHtml = (value) =>
        $("<div>")
            .text(value || "")
            .html();
    const formatDate = (value) =>
        value ? new Date(value).toLocaleString() : "—";

    const loadBags = () =>
        axios
            .get(
                `/seller/bags/data?page=${page}&status=${encodeURIComponent($("#bag-status-filter").val())}`,
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
                                      `<tr data-id="${bag.id}"><td class="font-monospace">${escapeHtml(bag.barcode)}</td><td><span class="badge ${bag.status === "assigned" ? "bg-blue-lt" : "bg-green-lt"}">${escapeHtml(bag.status)}</span></td><td>${escapeHtml(bag.order_number || (bag.seller_order_id ? `Order #${bag.seller_order_id}` : "—"))}</td><td>${escapeHtml(formatDate(bag.created_at))}</td><td>${bag.status === "available" ? '<div class="btn-list flex-nowrap"><button class="btn btn-outline-primary btn-sm bag-edit" type="button">Edit</button><button class="btn btn-outline-danger btn-sm bag-delete" type="button">Delete</button></div>' : "—"}</td></tr>`,
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
            .post("/seller/bags/bulk", { barcodes })
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

    $("#bag-list").on("click", ".bag-edit", function () {
        const row = $(this).closest("tr");
        selectedBagId = row.data("id");
        $("#edit-bag-barcode").val(row.find("td:first").text().trim());
        $("#edit-bag-error").empty();
        $("#edit-bag-modal").modal("show");
    });

    $("#bag-list").on("click", ".bag-delete", function () {
        const row = $(this).closest("tr");
        selectedBagId = row.data("id");
        $("#delete-bag-barcode").text(row.find("td:first").text().trim());
        $("#delete-bag-error").empty();
        $("#delete-bag-modal").modal("show");
    });

    $("#edit-bag-form").on("submit", function (event) {
        event.preventDefault();
        if (!selectedBagId) return;
        const button = $("#edit-bag-submit")
            .prop("disabled", true)
            .text("Saving…");
        axios
            .put(`/seller/bags/${selectedBagId}`, {
                barcode: $("#edit-bag-barcode").val().trim(),
            })
            .then(() => {
                $("#edit-bag-modal").modal("hide");
                loadBags();
            })
            .catch((error) => {
                $("#edit-bag-error").text(
                    error.response?.data?.message ||
                        "Could not update bag barcode.",
                );
            })
            .finally(() => button.prop("disabled", false).text("Save barcode"));
    });

    $("#delete-bag-submit").on("click", function () {
        if (!selectedBagId) return;
        const button = $(this).prop("disabled", true).text("Deleting…");
        axios
            .delete(`/seller/bags/${selectedBagId}`)
            .then(() => {
                $("#delete-bag-modal").modal("hide");
                loadBags();
            })
            .catch((error) => {
                $("#delete-bag-error").text(
                    error.response?.data?.message || "Could not delete bag.",
                );
            })
            .finally(() => button.prop("disabled", false).text("Delete bag"));
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
