$(document).ready(function () {
    const table = $("#orders-table").DataTable();
    let currentOrderId = null;
    let orderMode = "regular";

    const updateOrderCount = () => {
        if (table.page.info() !== undefined) {
            const totalRecords = table.page.info().recordsTotal;
            $(".order-count").html(
                "(" +
                    totalRecords +
                    (totalRecords > 1 ? " Order Items" : " Order Item") +
                    ")",
            );
        }
    };

    // Reload table when filters change
    $(".order-mode-tab").on("click", function () {
        orderMode = $(this).data("order-mode");
        $(".order-mode-tab")
            .removeClass("btn-primary")
            .addClass("btn-outline-primary");
        $(this).removeClass("btn-outline-primary").addClass("btn-primary");
        table.ajax.reload(updateOrderCount, false);
    });

    $("#rangeFilter, #statusFilter, #paymentFilter").on("change", function () {
        table.ajax.reload(updateOrderCount, false);
    });
    $("#refresh").on("click", function () {
        table.ajax.reload(updateOrderCount, false);
    });

    setTimeout(function () {
        // Initial order count
        updateOrderCount();
    }, 1000);

    // Add filter params to AJAX request
    $("#orders-table").on("preXhr.dt", function (e, settings, data) {
        data.range = $("#rangeFilter").val();
        data.status = $("#statusFilter").val();
        data.payment_type = $("#paymentFilter").val();
        data.order_mode = orderMode;
    });

    if ($("#newRegularOrderModal").length) {
        const modal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById("newRegularOrderModal"),
        );
        let pendingOrders = [];
        let activeOrder = null;
        let timerHandle = null;
        let startedAt = 0;

        const escapeHtml = (value) =>
            $("<div>")
                .text(value || "")
                .html();
        const playAlert = () => {
            try {
                const context = new (
                    window.AudioContext || window.webkitAudioContext
                )();
                const oscillator = context.createOscillator();
                const gain = context.createGain();
                oscillator.frequency.value = 880;
                gain.gain.value = 0.08;
                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start();
                oscillator.stop(context.currentTime + 0.25);
            } catch (error) {
                // Browser audio may require a prior user gesture.
            }
        };
        const showNextOrder = () => {
            if (activeOrder || pendingOrders.length === 0) return;
            activeOrder = pendingOrders.shift();
            startedAt = Date.now();
            $("#new-order-error").addClass("d-none").text("");
            $("#new-order-summary").html(
                `<p class="mb-1 fw-bold">Order #${escapeHtml(activeOrder.order_number || activeOrder.order_id)}</p>` +
                    `<p class="mb-1">${escapeHtml(activeOrder.customer.name)} · <a href="tel:${escapeHtml(activeOrder.customer.phone)}">${escapeHtml(activeOrder.customer.phone)}</a></p>` +
                    `<p class="mb-1">${escapeHtml(activeOrder.customer.address)}</p>` +
                    `<p class="mb-0">${escapeHtml(activeOrder.payment_method)} · Total: ${escapeHtml(activeOrder.total)}</p>`,
            );
            $("#new-order-items").html(
                activeOrder.items
                    .map(
                        (item) =>
                            `<div class="d-flex justify-content-between border-top py-2"><span>${escapeHtml(item.product)}${item.variant ? ` (${escapeHtml(item.variant)})` : ""} × ${item.quantity}</span><span>${escapeHtml(item.subtotal)}</span></div>`,
                    )
                    .join(""),
            );
            timerHandle = window.setInterval(() => {
                const seconds = Math.floor((Date.now() - startedAt) / 1000);
                $("#new-order-timer").text(
                    `Waiting ${String(Math.floor(seconds / 60)).padStart(2, "0")}:${String(seconds % 60).padStart(2, "0")}`,
                );
            }, 1000);
            modal.show();
            playAlert();
        };
        const finishOrder = () => {
            window.clearInterval(timerHandle);
            activeOrder = null;
            modal.hide();
            table.ajax.reload(updateOrderCount, false);
            showNextOrder();
        };
        const decideOrder = (status) => {
            if (!activeOrder) return;
            $("#new-order-accept, #new-order-reject").prop("disabled", true);
            const items = [...activeOrder.items];
            const processNext = () => {
                if (items.length === 0) {
                    finishOrder();
                    return;
                }
                const item = items.shift();
                axios
                    .post(`/seller/orders/${item.order_item_id}/${status}`)
                    .then((response) => {
                        if (response.data && response.data.success === false) {
                            throw new Error(
                                response.data.message ||
                                    "Order decision failed.",
                            );
                        }
                        activeOrder.items = activeOrder.items.filter(
                            (activeItem) =>
                                activeItem.order_item_id !== item.order_item_id,
                        );
                        processNext();
                    })
                    .catch((error) => {
                        $("#new-order-error")
                            .removeClass("d-none")
                            .text(
                                error.message ||
                                    "Order decision failed. Please try again.",
                            );
                        $("#new-order-accept, #new-order-reject").prop(
                            "disabled",
                            false,
                        );
                    });
            };
            processNext();
        };
        const pollOrders = () =>
            axios.get("/seller/orders/pending-regular").then((response) => {
                const orders = response.data.data || [];
                const known = new Set([
                    ...(activeOrder ? [activeOrder.seller_order_id] : []),
                    ...pendingOrders.map((order) => order.seller_order_id),
                ]);
                orders.forEach((order) => {
                    if (!known.has(order.seller_order_id))
                        pendingOrders.push(order);
                });
                showNextOrder();
            });

        $("#new-order-accept").on("click", () => decideOrder("accept"));
        $("#new-order-reject").on("click", () => decideOrder("reject"));
        pollOrders();
        window.setInterval(pollOrders, 5000);
    }

    // Capture order ID when accept/reject/preparing buttons are clicked
    $("#acceptModel, #rejectModel, #preparingModel").on(
        "show.bs.modal",
        function (event) {
            const button = $(event.relatedTarget);
            currentOrderId = button.data("id");
        },
    );

    // Handle accept order action
    $("#confirmAccept").on("click", function () {
        if (currentOrderId) {
            axios
                .post("/seller/orders/" + currentOrderId + "/accept")
                .then(function (response) {
                    // Handle success
                    table.ajax.reload(updateOrderCount, false);
                    let data = response.data;
                    if (data.success === false) {
                        return Toast.fire({
                            icon: "error",
                            title: data.message,
                        });
                    }
                    return Toast.fire({
                        icon: "success",
                        title: data.message,
                    });
                })
                .catch(function (error) {
                    // Handle error
                    console.error("Error accepting order:", error);
                });
        }
    });

    // Handle reject order action
    $("#confirmReject").on("click", function () {
        if (currentOrderId) {
            axios
                .post("/seller/orders/" + currentOrderId + "/reject")
                .then(function (response) {
                    // Handle success
                    table.ajax.reload(updateOrderCount, false);
                    let data = response.data;
                    if (data.success === false) {
                        return Toast.fire({
                            icon: "error",
                            title: data.message,
                        });
                    }
                    return Toast.fire({
                        icon: "success",
                        title: data.message,
                    });
                })
                .catch(function (error) {
                    // Handle error
                    console.error("Error rejecting order:", error);
                });
        }
    });

    // Handle preparing order action
    $("#confirmPreparing").on("click", function () {
        if (currentOrderId) {
            axios
                .post("/seller/orders/" + currentOrderId + "/preparing")
                .then(function (response) {
                    // Handle success
                    table.ajax.reload(updateOrderCount, false);
                    let data = response.data;
                    if (data.success === false) {
                        return Toast.fire({
                            icon: "error",
                            title: data.message,
                        });
                    }
                    return Toast.fire({
                        icon: "success",
                        title: data.message,
                    });
                })
                .catch(function (error) {
                    // Handle error
                    console.error("Error marking order as preparing:", error);
                });
        }
    });

    // Capture order ID when cancel button is clicked
    $("#cancelOrderModal").on("show.bs.modal", function (event) {
        const button = $(event.relatedTarget);
        currentOrderId = button.data("order-id");
        $("#cancellationNote").val(""); // Clear the textarea
    });

    // Handle cancel order action
    $("#confirmCancelOrder").on("click", function () {
        if (currentOrderId) {
            const cancellationNote = $("#cancellationNote").val();
            const csrfToken = $('meta[name="csrf-token"]').attr("content");

            axios
                .post(
                    "/admin/orders/cancel/" + currentOrderId,
                    {
                        cancellation_note: cancellationNote,
                    },
                    {
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            "Content-Type": "application/json",
                        },
                    },
                )
                .then(function (response) {
                    // Handle success
                    table.ajax.reload(updateOrderCount, false);
                    let data = response.data;
                    if (data.success === false) {
                        return Toast.fire({
                            icon: "error",
                            title: data.message,
                        });
                    }
                    return Toast.fire({
                        icon: "success",
                        title: data.message,
                    });
                })
                .catch(function (error) {
                    // Handle error
                    console.error("Error cancelling order:", error);
                    Toast.fire({
                        icon: "error",
                        title: "Error cancelling order. Please try again.",
                    });
                });
        }
    });
});

$(document).ready(function () {
    // Handle select all checkbox
    $("#select-all-items").on("change", function () {
        $(".item-checkbox").prop("checked", $(this).prop("checked"));
    });

    // Update select all checkbox when individual checkboxes change
    $(".item-checkbox").on("change", function () {
        if ($(".item-checkbox:checked").length === $(".item-checkbox").length) {
            $("#select-all-items").prop("checked", true);
        } else {
            $("#select-all-items").prop("checked", false);
        }
    });
});
$(document).ready(function () {
    $("#update-status-form").on("submit", function (e) {
        e.preventDefault();

        const selectedItems = $(".item-checkbox:checked");
        if (selectedItems.length === 0) {
            $("#status-update-results").html(
                '<div class="alert alert-danger">' +
                    '<h4 class="alert-heading">Error</h4>' +
                    "<p>Please select at least one item to update.</p>" +
                    "</div>",
            );
            return;
        }

        // Clear previous results
        $("#status-update-results").empty();

        const status = $("#item-status").val();
        let successCount = 0;
        let errorCount = 0;
        let totalRequests = selectedItems.length;
        let completedRequests = 0;

        // Disable the submit button during processing
        $("#update-items-status")
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...',
            );

        // Create a progress alert
        $("#status-update-results").append(
            '<div class="alert alert-info" id="progress-alert">' +
                '<h4 class="alert-heading">Processing...</h4>' +
                "<p>Updating status for selected items. Please wait.</p>" +
                '<div class="progress mt-2">' +
                '<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>' +
                "</div>" +
                "</div>",
        );

        selectedItems.each(function () {
            const itemId = $(this).val();
            const itemRow = $(this).closest("tr");
            const productName = itemRow.find("td:eq(1)").text(); // Product name is in the second column
            const variantName = itemRow.find("td:eq(2)").text(); // Variant name is in the third column

            // Process each selected item
            axios
                .post("/seller/orders/" + itemId + "/" + status)
                .then(function (response) {
                    // Handle success
                    let data = response.data;
                    completedRequests++;

                    // Update progress bar
                    const progressPercentage =
                        (completedRequests / totalRequests) * 100;
                    $("#progress-alert .progress-bar")
                        .css("width", progressPercentage + "%")
                        .attr("aria-valuenow", progressPercentage);

                    // Add item-specific result
                    const alertClass = data.success
                        ? "alert-success"
                        : "alert-danger";
                    const alertIcon = data.success
                        ? "check-circle"
                        : "x-circle";
                    const alertTitle = data.success ? "Success" : "Error";

                    $("#status-update-results").append(
                        '<div class="alert ' +
                            alertClass +
                            ' mt-2">' +
                            '<div class="d-flex">' +
                            "<div>" +
                            '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-' +
                            alertIcon +
                            ' me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' +
                            '<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>' +
                            (data.success
                                ? '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M9 12l2 2l4 -4"></path>'
                                : '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M10 10l4 4m0 -4l-4 4"></path>') +
                            "</svg>" +
                            "</div>" +
                            "<div>" +
                            '<h4 class="alert-title">' +
                            alertTitle +
                            ": " +
                            productName +
                            " - " +
                            variantName +
                            "</h4>" +
                            "<p>" +
                            data.message +
                            "</p>" +
                            "</div>" +
                            "</div>" +
                            "</div>",
                    );

                    data.success ? successCount++ : errorCount++;

                    // Check if all requests are completed
                    if (completedRequests === totalRequests) {
                        finishProcessing(
                            successCount,
                            errorCount,
                            totalRequests,
                        );
                    }
                })
                .catch(function (error) {
                    // Handle error
                    completedRequests++;
                    errorCount++;

                    // Update progress bar
                    const progressPercentage =
                        (completedRequests / totalRequests) * 100;
                    $("#progress-alert .progress-bar")
                        .css("width", progressPercentage + "%")
                        .attr("aria-valuenow", progressPercentage);
                    // Add error message
                    const errorMessage =
                        error.response &&
                        error.response.data &&
                        error.response.data.message
                            ? error.response.data.message
                            : "An error occurred while updating the item status.";

                    $("#status-update-results").append(
                        '<div class="alert alert-danger mt-2">' +
                            '<div class="d-flex">' +
                            "<div>" +
                            '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x-circle me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' +
                            '<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>' +
                            '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path>' +
                            '<path d="M10 10l4 4m0 -4l-4 4"></path>' +
                            "</svg>" +
                            "</div>" +
                            "<div>" +
                            '<h4 class="alert-title">Error</h4>' +
                            "<p>" +
                            errorMessage +
                            "</p>" +
                            "</div>" +
                            "</div>" +
                            "</div>",
                    );

                    // Check if all requests are completed
                    if (completedRequests === totalRequests) {
                        finishProcessing(
                            successCount,
                            errorCount,
                            totalRequests,
                        );
                    }
                });
        });
    });
});

function finishProcessing(successCount, errorCount, totalRequests) {
    // Re-enable the submit button
    $("#update-items-status").prop("disabled", false).html("Update Status");

    // Remove the progress alert
    $("#progress-alert").remove();

    // Add a summary alert at the top of the results
    let summaryAlertClass, summaryAlertIcon, summaryAlertTitle, summaryMessage;

    if (successCount === totalRequests) {
        summaryAlertClass = "alert-success";
        summaryAlertIcon = "check-circle";
        summaryAlertTitle = "Success";
        summaryMessage = "All selected items have been updated successfully.";

        // Reload the page after a delay
        setTimeout(function () {
            window.location.reload();
        }, 3000);
    } else if (successCount > 0) {
        summaryAlertClass = "alert-warning";
        summaryAlertIcon = "alert-triangle";
        summaryAlertTitle = "Partial Success";
        summaryMessage =
            successCount +
            " out of " +
            totalRequests +
            " items updated successfully.";

        // Reload the page after a delay
        setTimeout(function () {
            window.location.reload();
        }, 3000);
    } else {
        summaryAlertClass = "alert-danger";
        summaryAlertIcon = "x-circle";
        summaryAlertTitle = "Error";
        summaryMessage = "Failed to update any items. Please try again.";
    }

    // Prepend the summary alert to the results container
    $("#status-update-results").prepend(
        '<div class="alert ' +
            summaryAlertClass +
            '">' +
            '<div class="d-flex">' +
            "<div>" +
            '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-' +
            summaryAlertIcon +
            ' me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' +
            '<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>' +
            (summaryAlertIcon === "check-circle"
                ? '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M9 12l2 2l4 -4"></path>'
                : summaryAlertIcon === "alert-triangle"
                  ? '<path d="M12 9v2m0 4v.01"></path><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"></path>'
                  : '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M10 10l4 4m0 -4l-4 4"></path>') +
            "</svg>" +
            "</div>" +
            "<div>" +
            '<h4 class="alert-title">' +
            summaryAlertTitle +
            "</h4>" +
            "<p>" +
            summaryMessage +
            "</p>" +
            (successCount > 0
                ? '<p class="mb-0"><small>Page will reload in 3 seconds...</small></p>'
                : "") +
            "</div>" +
            "</div>" +
            "</div>",
    );

    // Scroll to the top of the results
    $("html, body").animate(
        {
            scrollTop: $("#status-update-results").offset().top - 100,
        },
        500,
    );
}
