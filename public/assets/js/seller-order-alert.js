$(document).ready(function () {
    const modal = $("#newRegularOrderModal");
    const pendingOrdersUrl = modal.data("pending-url");
    if (!modal.length || !pendingOrdersUrl) return;

    const timerRing = $("#new-order-timer-ring");
    const timerText = $("#new-order-timer");
    let pendingOrders = [];
    let activeOrder = null;
    let timerHandle = null;
    let startedAt = 0;
    const timerDuration = 60;

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
            console.warn("Order alert sound was blocked:", error);
        }
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
        updateTimer();
        timerHandle = window.setInterval(updateTimer, 1000);
        modal.modal("show");
        playAlert();
    };
    const finishOrder = () => {
        window.clearInterval(timerHandle);
        activeOrder = null;
        modal.modal("hide");
        showNextOrder();
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
                if (!known.has(order.seller_order_id))
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
