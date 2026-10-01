$(document).ready(function () {
    const modal = $("#newRegularOrderModal");
    const pendingOrdersUrl = modal.data("pending-url");
    if (!modal.length || !pendingOrdersUrl) return;

    const timerRing = $("#new-order-timer-ring");
    const timerText = $("#new-order-timer");
    let pendingOrders = [];
    let activeOrder = null;
    const handledOrderIds = new Set();
    let popupStage = "accept";
    let verificationIndex = 0;
    let verifiedItems = [];
    let currentItemCheckPassed = false;
    let timerHandle = null;
    let startedAt = 0;
    const timerDuration = 60;
    let alertAudioContext = null;
    let alertSoundHandle = null;
    const ringAlert = () => {
        if (!alertAudioContext) return;
        const now = alertAudioContext.currentTime;
        const gain = alertAudioContext.createGain();
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.12, now + 0.025);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.7);
        gain.connect(alertAudioContext.destination);

        [660, 880].forEach((frequency, index) => {
            const oscillator = alertAudioContext.createOscillator();
            oscillator.type = "sine";
            oscillator.frequency.value = frequency;
            oscillator.detune.value = index * 4;
            oscillator.connect(gain);
            oscillator.start(now + index * 0.08);
            oscillator.stop(now + 0.72);
        });
        window.setTimeout(() => gain.disconnect(), 900);
    };
    const startAlertSound = () => {
        try {
            if (!alertAudioContext) {
                alertAudioContext = new (
                    window.AudioContext || window.webkitAudioContext
                )();
            }
            alertAudioContext
                .resume()
                .then(() => {
                    if (!activeOrder || alertSoundHandle) return;
                    ringAlert();
                    alertSoundHandle = window.setInterval(ringAlert, 1800);
                })
                .catch((error) => {
                    console.warn("Order alert sound was blocked:", error);
                });
        } catch (error) {
            console.warn("Order alert sound was blocked:", error);
        }
    };
    const stopAlertSound = () => {
        if (alertSoundHandle) {
            window.clearInterval(alertSoundHandle);
            alertSoundHandle = null;
        }
    };

    const escapeHtml = (value) =>
        $("<div>")
            .text(value || "")
            .html();
    const formatOrderTime = (value) => {
        if (!value) return "Just now";
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return "Just now";
        return date.toLocaleString([], {
            dateStyle: "medium",
            timeStyle: "short",
        });
    };
    const updateTimer = () => {
        const seconds = Math.floor((Date.now() - startedAt) / 1000);
        const minutes = String(Math.floor(seconds / 60)).padStart(2, "0");
        const remaining = String(seconds % 60).padStart(2, "0");
        const progress = Math.min(seconds / timerDuration, 1) * 360;
        timerText.text(`${minutes}:${remaining}`);
        timerRing.css("--timer-progress", `${progress}deg`);
    };
    const showNextOrder = () => {
        if (activeOrder || pendingOrders.length === 0) return;
        activeOrder = pendingOrders.shift();
        startedAt = Date.now();
        verifiedItems = [];
        verificationIndex = 0;
        currentItemCheckPassed = false;
        activeOrder.items.forEach((item) => {
            item.verification_status = "pending";
        });
        activeOrder.checklistMarkup = "";
        popupStage = activeOrder.items.every(
            (item) => item.status === "accepted",
        )
            ? "verify"
            : "accept";
        $("#new-order-accept").prop("disabled", false);
        $("#new-order-accept").text(
            popupStage === "accept" ? "Accept order" : "Verify items",
        );
        $("#new-order-dismiss").addClass("d-none");
        $("#new-order-error").addClass("d-none").text("");
        $("#new-order-verification").addClass("d-none").empty();
        $("#new-order-items").removeClass("d-none");
        $("#new-order-summary").html(
            `<div class="new-order-summary-card">` +
                `<div class="order-number">Order #${escapeHtml(activeOrder.order_number || activeOrder.order_id)}</div>` +
                `<div class="customer-line mt-1">${escapeHtml(activeOrder.customer.name)} · <a href="tel:${escapeHtml(activeOrder.customer.phone)}">${escapeHtml(activeOrder.customer.phone)}</a></div>` +
                `<div class="mt-2">${escapeHtml(activeOrder.customer.address)}</div>` +
                `<div class="new-order-meta">` +
                `<div class="new-order-meta-item"><span class="new-order-meta-label">Ordered</span><span class="new-order-meta-value">${escapeHtml(formatOrderTime(activeOrder.created_at))}</span></div>` +
                `<div class="new-order-meta-item"><span class="new-order-meta-label">Order type</span><span class="new-order-meta-value">${escapeHtml((activeOrder.order_mode || "regular").replace(/^./, (character) => character.toUpperCase()))}</span></div>` +
                `<div class="new-order-meta-item"><span class="new-order-meta-label">Payment</span><span class="new-order-meta-value">${escapeHtml(activeOrder.payment_method || "Not specified")}</span></div>` +
                `<div class="new-order-meta-item"><span class="new-order-meta-label">Total</span><span class="new-order-meta-value">${escapeHtml(activeOrder.total)}</span></div>` +
                `<div class="new-order-meta-item"><span class="new-order-meta-label">Items</span><span class="new-order-meta-value">${activeOrder.items.length} item${activeOrder.items.length === 1 ? "" : "s"}</span></div>` +
                (activeOrder.delivery
                    ? `<div class="new-order-meta-item"><span class="new-order-meta-label">Delivery</span><span class="new-order-meta-value">${escapeHtml(activeOrder.delivery)}</span></div>`
                    : "") +
                `</div>` +
                `</div>`,
        );
        $("#new-order-items").html(
            activeOrder.items
                .map(
                    (item) =>
                        `<div class="d-flex justify-content-between border-top py-2">` +
                        `<img class="new-order-item-image" src="${escapeHtml(item.image)}" alt="${escapeHtml(item.product)}" loading="lazy">` +
                        `<span class="new-order-item-details">${escapeHtml(item.product)}${item.variant ? ` (${escapeHtml(item.variant)})` : ""} × ${item.quantity}</span>` +
                        `<span class="new-order-item-price">${escapeHtml(item.subtotal)}</span>` +
                        `</div>`,
                )
                .join(""),
        );
        if (popupStage === "accept") {
            updateTimer();
            timerHandle = window.setInterval(updateTimer, 1000);
        } else {
            $("#new-order-items").addClass("d-none");
            showVerificationItem();
        }
        modal.modal("show");
        if (popupStage === "accept") startAlertSound();
    };
    const finishOrder = () => {
        window.clearInterval(timerHandle);
        stopAlertSound();
        if (activeOrder) handledOrderIds.add(activeOrder.seller_order_id);
        activeOrder = null;
        modal.one("hidden.bs.modal", showNextOrder);
        modal.modal("hide");
    };
    const showVerificationItem = () => {
        const item = activeOrder.items[verificationIndex];
        const checklist =
            `<div class="new-order-checklist" aria-label="Order item verification status">` +
            activeOrder.items
                .map((orderItem, index) => {
                    const status = orderItem.verification_status || "pending";
                    const icon =
                        status === "valid"
                            ? "✓"
                            : status === "invalid"
                              ? "✕"
                              : "○";
                    return (
                        `<div class="new-order-checklist-item is-${status}${index === verificationIndex ? " is-current" : ""}" aria-current="${index === verificationIndex ? "step" : "false"}">` +
                        `<span class="new-order-checklist-icon" aria-hidden="true">${icon}</span>` +
                        `<span class="new-order-checklist-title">${escapeHtml(orderItem.product)}${orderItem.variant && orderItem.variant !== orderItem.product ? ` · ${escapeHtml(orderItem.variant)}` : ""}</span>` +
                        `<span class="new-order-checklist-quantity">× ${escapeHtml(orderItem.quantity)}</span>` +
                        `</div>`
                    );
                })
                .join("") +
            `</div>`;
        if (!item) {
            activeOrder.checklistMarkup = checklist;
            showBagScan(checklist);
            return;
        }

        currentItemCheckPassed = false;
        $("#new-order-verification")
            .removeClass("d-none")
            .html(
                checklist +
                    `<div class="new-order-verification-card">` +
                    `<div class="d-flex justify-content-between gap-2 mb-3">` +
                    `<strong>Scan item ${verificationIndex + 1} of ${activeOrder.items.length}</strong>` +
                    `<span class="new-order-item-price">${escapeHtml(item.subtotal)}</span>` +
                    `</div>` +
                    `<div class="d-flex align-items-center gap-3 mb-3">` +
                    `<img class="new-order-item-image" src="${escapeHtml(item.image)}" alt="${escapeHtml(item.product)}" loading="lazy">` +
                    `<div class="new-order-item-details"><strong>${escapeHtml(item.product)}</strong>${item.variant && item.variant !== item.product ? `<div>${escapeHtml(item.variant)}</div>` : ""}` +
                    `${item.sku ? `<div class="text-secondary small">SKU: ${escapeHtml(item.sku)}</div>` : ""}` +
                    `${item.variant_weight ? `<div class="text-secondary small">Weight: ${escapeHtml(item.variant_weight)} kg</div>` : ""}` +
                    `${item.variant_dimensions ? `<div class="text-secondary small">Dimensions: ${escapeHtml(item.variant_dimensions)}</div>` : ""}</div>` +
                    `</div>` +
                    `<label class="form-label fw-bold" for="new-order-scanned-barcode">Scan or enter barcode</label>` +
                    `<input id="new-order-scanned-barcode" class="form-control" type="text" autocomplete="off" value="">` +
                    `<div id="new-order-barcode-error" class="new-order-field-error" role="alert"></div>` +
                    `<div class="new-order-quantity-panel mt-3">` +
                    `<div class="d-flex justify-content-between align-items-baseline gap-2 mb-2"><label class="form-label fw-bold mb-0" for="new-order-verified-quantity">Confirm quantity</label><span class="badge bg-blue-lt fs-3">${escapeHtml(item.quantity)} ordered</span></div>` +
                    `<input id="new-order-verified-quantity" class="form-control" type="number" inputmode="none" min="0" max="${escapeHtml(item.quantity)}" step="1" value="0" aria-describedby="new-order-quantity-error">` +
                    `<div class="small text-secondary mt-1">Use the up/down arrow keys to match the ordered quantity.</div>` +
                    `<div id="new-order-quantity-error" class="new-order-field-error" role="alert"></div>` +
                    `</div>` +
                    `</div>`,
            );
        if (!item.barcode) {
            const productEditLink = item.product_id
                ? ` <a href="/seller/products/${encodeURIComponent(item.product_id)}/edit" target="_blank" rel="noopener">Open product to add its barcode</a>.`
                : " Update this product to add its barcode.";
            $("#new-order-scanned-barcode")
                .prop("disabled", true)
                .addClass("is-invalid");
            $("#new-order-barcode-error").html(
                `No barcode is recorded for this item.${productEditLink} Save the product, close this popup, then reload the page to resume.`,
            );
            $("#new-order-dismiss").removeClass("d-none");
        }
        $("#new-order-accept").prop("disabled", false).text("Check item");
        window.setTimeout(
            () => $("#new-order-scanned-barcode").trigger("focus"),
            50,
        );
    };
    const showBagScan = (checklist = "") => {
        checklist = checklist || activeOrder.checklistMarkup || "";
        if (activeOrder.bag) {
            popupStage = "dispatch";
            $("#new-order-verification")
                .removeClass("d-none")
                .html(
                    checklist +
                        `<div class="new-order-verification-card">` +
                        `<div class="new-order-verification-status is-valid">Bag assigned: ${escapeHtml(activeOrder.bag.barcode)}</div>` +
                        `<p class="mb-0">All items and the assigned bag are ready for dispatch.</p>` +
                        `</div>`,
                );
            $("#new-order-accept")
                .prop("disabled", false)
                .text("Dispatch order");
            return;
        }

        popupStage = "bag";
        $("#new-order-verification")
            .removeClass("d-none")
            .html(
                checklist +
                    `<div class="new-order-verification-card">` +
                    `<div class="new-order-verification-status is-valid mb-3">All items verified. Scan a bag to assign it to this order.</div>` +
                    `<label class="form-label fw-bold" for="new-order-bag-barcode">Bag barcode</label>` +
                    `<input id="new-order-bag-barcode" class="form-control" type="text" autocomplete="off">` +
                    `<div id="new-order-bag-error" class="new-order-field-error" role="alert"></div>` +
                    `</div>`,
            );
        $("#new-order-accept").prop("disabled", false).text("Assign bag");
        window.setTimeout(
            () => $("#new-order-bag-barcode").trigger("focus"),
            50,
        );
    };
    const assignScannedBag = () => {
        const barcode = String($("#new-order-bag-barcode").val() || "").trim();
        if (!barcode) {
            $("#new-order-bag-barcode").addClass("is-invalid");
            $("#new-order-bag-error").text("Scan or enter a bag barcode.");
            return;
        }

        $("#new-order-accept").prop("disabled", true);
        axios
            .post(`/seller/orders/${activeOrder.seller_order_id}/assign-bag`, {
                barcode,
            })
            .then((response) => {
                if (response.data?.success === false) {
                    const error = new Error(
                        response.data.message || "Bag not found.",
                    );
                    error.responseData = response.data;
                    throw error;
                }
                activeOrder.bag = response.data.data?.bag || { barcode };
                showBagScan(activeOrder.checklistMarkup);
            })
            .catch((error) => {
                $("#new-order-bag-barcode").addClass("is-invalid");
                $("#new-order-bag-error").text(
                    error.responseData?.message ||
                        error.response?.data?.message ||
                        "Bag not found in your available bag pool.",
                );
                $("#new-order-accept")
                    .prop("disabled", false)
                    .text("Try bag again");
            });
    };
    const acceptOrderItems = () => {
        if (!activeOrder) return;
        $("#new-order-accept").prop("disabled", true);
        window.clearInterval(timerHandle);
        stopAlertSound();
        axios
            .post(`/seller/orders/${activeOrder.seller_order_id}/accept-items`)
            .then((response) => {
                if (response.data?.success === false)
                    throw new Error(
                        response.data.message || "Order acceptance failed.",
                    );
                activeOrder.items.forEach((item) => {
                    item.status = "accepted";
                });
                popupStage = "verify";
                $("#new-order-error").addClass("d-none").text("");
                $("#new-order-items").addClass("d-none");
                showVerificationItem();
            })
            .catch((error) => {
                $("#new-order-error")
                    .removeClass("d-none")
                    .text(
                        error.response?.data?.message ||
                            error.message ||
                            "Order acceptance failed. Please try again.",
                    );
                $("#new-order-accept").prop("disabled", false);
            });
    };
    const checkCurrentItem = () => {
        const item = activeOrder.items[verificationIndex];
        const barcode = $("#new-order-scanned-barcode").val();
        const quantity = Number($("#new-order-verified-quantity").val());
        const barcodeMatches =
            Boolean(item.barcode) && barcode === String(item.barcode);
        const quantityMatches =
            Number.isInteger(quantity) && quantity === Number(item.quantity);
        const barcodeError = !item.barcode
            ? "No barcode is recorded for this item. Add a barcode to its product variant before preparing."
            : !barcodeMatches
              ? "Barcode does not match this item."
              : "";
        const quantityError = !quantityMatches
            ? `Enter the exact ordered quantity: ${item.quantity}.`
            : "";

        $("#new-order-scanned-barcode")
            .toggleClass("is-invalid", Boolean(barcodeError))
            .toggleClass("is-valid", !barcodeError);
        $("#new-order-barcode-error").text(barcodeError);
        $("#new-order-verified-quantity")
            .toggleClass("is-invalid", Boolean(quantityError))
            .toggleClass("is-valid", !quantityError);
        $("#new-order-quantity-error").text(quantityError);

        currentItemCheckPassed = !barcodeError && !quantityError;
        item.verification_status = currentItemCheckPassed ? "valid" : "invalid";
        if (currentItemCheckPassed) {
            verifiedItems.push({
                order_item_id: item.order_item_id,
                barcode: String(barcode),
                quantity,
            });
            verificationIndex += 1;
            showVerificationItem();
            return;
        }

        $("#new-order-checklist .new-order-checklist-item")
            .eq(verificationIndex)
            .removeClass("is-pending is-valid")
            .addClass("is-invalid")
            .find(".new-order-checklist-icon")
            .text("✕");
        $("#new-order-accept").text("Check item again");
    };
    const submitVerifiedOrder = () => {
        $("#new-order-accept").prop("disabled", true);
        axios
            .post(
                `/seller/orders/${activeOrder.seller_order_id}/verify-and-prepare`,
                { items: verifiedItems },
            )
            .then((response) => {
                if (response.data?.success === false)
                    throw new Error(
                        response.data.message || "Server verification failed.",
                    );
                finishOrder();
            })
            .catch((error) => {
                $("#new-order-error")
                    .removeClass("d-none")
                    .text(
                        error.response?.data?.message ||
                            error.message ||
                            "Could not prepare the order. Review the item checks and retry.",
                    );
                $("#new-order-accept").prop("disabled", false);
            });
    };
    const decideOrder = () => {
        if (!activeOrder) return;
        if (popupStage === "accept") {
            acceptOrderItems();
            return;
        }
        if (popupStage === "verify") {
            checkCurrentItem();
            return;
        }
        if (popupStage === "bag") {
            assignScannedBag();
            return;
        }
        submitVerifiedOrder();
    };
    const pollOrders = (orderMode, popupOnly = false) =>
        axios
            .get(
                `${pendingOrdersUrl}?order_mode=${orderMode}${
                    popupOnly ? "&popup=1" : ""
                }`,
            )
            .then((response) => {
                const orders = response.data.data || [];
                if (!popupOnly) {
                    $(`#${orderMode}-order-count`).text(
                        response.data.count ?? orders.length,
                    );
                }
                enqueueOrders(orders);
                showNextOrder();
            });
    const enqueueOrders = (orders) => {
        const known = new Set([
            ...(activeOrder ? [activeOrder.seller_order_id] : []),
            ...pendingOrders.map((order) => order.seller_order_id),
        ]);
        orders.forEach((order) => {
            if (
                !known.has(order.seller_order_id) &&
                !handledOrderIds.has(order.seller_order_id)
            )
                pendingOrders.push(order);
        });
    };
    const pollRegularOrders = () => pollOrders("regular");
    const pollWholesaleOrders = () => pollOrders("wholesale", true);
    const pollWholesaleCount = () =>
        axios
            .get(`${pendingOrdersUrl}?order_mode=wholesale`)
            .then((response) => {
                const orders = response.data.data || [];
                $("#wholesale-order-count").text(
                    response.data.count ?? orders.length,
                );
            });

    $("#new-order-accept").on("click", decideOrder);
    $("#new-order-dismiss").on("click", () => {
        if (!activeOrder) return;
        handledOrderIds.add(activeOrder.seller_order_id);
        activeOrder = null;
        window.clearInterval(timerHandle);
        stopAlertSound();
        modal.one("hidden.bs.modal", showNextOrder);
        modal.modal("hide");
    });
    modal.on(
        "input",
        "#new-order-scanned-barcode, #new-order-verified-quantity",
        (event) => {
            const field = $(event.currentTarget);
            field.removeClass("is-invalid is-valid");
            if (field.is("#new-order-scanned-barcode")) {
                $("#new-order-barcode-error").empty();
            } else {
                $("#new-order-quantity-error").empty();
            }
            if (activeOrder?.items[verificationIndex]) {
                activeOrder.items[verificationIndex].verification_status =
                    "pending";
                $("#new-order-checklist .new-order-checklist-item")
                    .eq(verificationIndex)
                    .removeClass("is-invalid")
                    .addClass("is-pending")
                    .find(".new-order-checklist-icon")
                    .text("○");
            }
            currentItemCheckPassed = false;
            $("#new-order-accept").text("Check item");
        },
    );
    modal.on("keydown", "#new-order-verified-quantity", (event) => {
        if (!["ArrowUp", "ArrowDown", "Tab"].includes(event.key))
            event.preventDefault();
    });
    modal.on("paste wheel", "#new-order-verified-quantity", (event) =>
        event.preventDefault(),
    );
    modal.on("keydown", "#new-order-scanned-barcode", (event) => {
        if (event.key === "Enter") {
            event.preventDefault();
            $("#new-order-accept").trigger("click");
        }
    });
    modal.on("input", "#new-order-bag-barcode", () => {
        $("#new-order-bag-barcode").removeClass("is-invalid");
        $("#new-order-bag-error").empty();
        $("#new-order-accept").text("Assign bag");
    });
    modal.on("keydown", "#new-order-bag-barcode", (event) => {
        if (event.key === "Enter") {
            event.preventDefault();
            $("#new-order-accept").trigger("click");
        }
    });
    modal.on("hidden.bs.modal", stopAlertSound);
    window.addEventListener("nagpurmart:notification", () => {
        pollRegularOrders().catch((error) =>
            console.error("Regular order polling failed:", error),
        );
        pollWholesaleOrders().catch((error) =>
            console.error("Wholesale order polling failed:", error),
        );
    });
    pollRegularOrders().catch((error) =>
        console.error("Regular order polling failed:", error),
    );
    pollWholesaleCount().catch((error) =>
        console.error("Wholesale order count failed:", error),
    );
    pollWholesaleOrders().catch((error) =>
        console.error("Wholesale order polling failed:", error),
    );
    window.setInterval(() => {
        pollRegularOrders().catch((error) =>
            console.error("Regular order polling failed:", error),
        );
        pollWholesaleCount().catch((error) =>
            console.error("Wholesale order count failed:", error),
        );
        pollWholesaleOrders().catch((error) =>
            console.error("Wholesale order polling failed:", error),
        );
    }, 5000);
});
