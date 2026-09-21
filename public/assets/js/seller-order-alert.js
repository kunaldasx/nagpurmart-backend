$(document).ready(function () {
    const modal = $("#newRegularOrderModal");
    const pendingOrdersUrl = modal.data("pending-url");
    if (!modal.length || !pendingOrdersUrl) return;

    const timerRing = $("#new-order-timer-ring");
    const timerText = $("#new-order-timer");
    let pendingOrders = [];
    let activeOrder = null;
    const handledOrderIds = new Set();
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
        $("#new-order-accept").prop("disabled", false);
        $("#new-order-error").addClass("d-none").text("");
        $("#new-order-summary").html(
            `<div class="new-order-summary-card">` +
                `<div class="order-number">Order #${escapeHtml(activeOrder.order_number || activeOrder.order_id)}</div>` +
                `<div class="customer-line mt-1">${escapeHtml(activeOrder.customer.name)} · <a href="tel:${escapeHtml(activeOrder.customer.phone)}">${escapeHtml(activeOrder.customer.phone)}</a></div>` +
                `<div class="mt-2">${escapeHtml(activeOrder.customer.address)}</div>` +
                `<div class="new-order-meta">` +
                `<div class="new-order-meta-item"><span class="new-order-meta-label">Ordered</span><span class="new-order-meta-value">${escapeHtml(formatOrderTime(activeOrder.created_at))}</span></div>` +
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
                        `<div class="d-flex justify-content-between border-top py-2"><span>${escapeHtml(item.product)}${item.variant ? ` (${escapeHtml(item.variant)})` : ""} × ${item.quantity}</span><span>${escapeHtml(item.subtotal)}</span></div>`,
                )
                .join(""),
        );
        updateTimer();
        timerHandle = window.setInterval(updateTimer, 1000);
        modal.modal("show");
        startAlertSound();
    };
    const finishOrder = () => {
        window.clearInterval(timerHandle);
        stopAlertSound();
        if (activeOrder) handledOrderIds.add(activeOrder.seller_order_id);
        activeOrder = null;
        modal.one("hidden.bs.modal", showNextOrder);
        modal.modal("hide");
    };
    const decideOrder = () => {
        if (!activeOrder) return;
        $("#new-order-accept").prop("disabled", true);
        const items = [...activeOrder.items];
        const processNext = () => {
            if (items.length === 0) {
                finishOrder();
                return;
            }
            const item = items.shift();
            axios
                .post(`/seller/orders/${item.order_item_id}/accept`)
                .then((response) => {
                    if (response.data?.success === false)
                        throw new Error(
                            response.data.message || "Order acceptance failed.",
                        );
                    return axios.post(
                        `/seller/orders/${item.order_item_id}/preparing`,
                    );
                })
                .then((response) => {
                    if (response.data?.success === false)
                        throw new Error(
                            response.data.message ||
                                "Order preparation failed.",
                        );
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
                                "Order update failed. Please try again.",
                        );
                    $("#new-order-accept").prop("disabled", false);
                });
        };
        processNext();
    };
    const pollRegularOrders = () =>
        axios.get(`${pendingOrdersUrl}?order_mode=regular`).then((response) => {
            const orders = response.data.data || [];
            $("#regular-order-count").text(
                response.data.count ?? orders.length,
            );
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
            showNextOrder();
        });
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
    modal.on("hidden.bs.modal", stopAlertSound);
    window.addEventListener("nagpurmart:notification", () => {
        pollRegularOrders().catch((error) =>
            console.error("Regular order polling failed:", error),
        );
    });
    pollRegularOrders().catch((error) =>
        console.error("Regular order polling failed:", error),
    );
    pollWholesaleCount().catch((error) =>
        console.error("Wholesale order count failed:", error),
    );
    window.setInterval(() => {
        pollRegularOrders().catch((error) =>
            console.error("Regular order polling failed:", error),
        );
        pollWholesaleCount().catch((error) =>
            console.error("Wholesale order count failed:", error),
        );
    }, 5000);
});
