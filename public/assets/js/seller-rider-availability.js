(() => {
    const root = document.getElementById("rider-availability");
    if (!root) return;

    const escapeHtml = (value) =>
        String(value ?? "").replace(/[&<>"']/g, (character) => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;",
        })[character]);
    const reasonLabels = {
        inactive: "Inactive",
        not_verified: "Not verified",
        no_fcm_token: "App notification token missing",
        location_stale: "Location is older than 2 minutes",
        outside_pickup_radius: "Outside 200 m pickup radius",
        no_pickup_store: "No pickup store mapped in rider zone",
        pending_offer: "Has a pending order offer",
        active_delivery: "Already on a delivery",
    };

    const refresh = async () => {
        const errorElement = document.getElementById("rider-availability-error");
        try {
            const response = await fetch(root.dataset.url, {
                headers: { Accept: "application/json" },
            });
            const body = await response.json();
            if (!response.ok || !body.success) {
                throw new Error(body.message || "Could not load rider availability.");
            }

            const { summary, riders } = body.data;
            document.getElementById("active-rider-count").textContent = summary.active_riders;
            document.getElementById("available-rider-count").textContent = summary.available_riders_in_radius;
            document.getElementById("inactive-rider-count").textContent = summary.inactive_riders;

            const rows = document.getElementById("rider-availability-rows");
            rows.innerHTML = riders.length
                ? riders.map((rider) => {
                    const statusClass = rider.status === "active"
                        ? "bg-success-lt text-success"
                        : "bg-secondary-lt text-secondary";
                    const availability = rider.is_available_in_radius
                        ? '<span class="badge bg-success-lt text-success">Available</span>'
                        : `<span class="text-secondary">${escapeHtml(reasonLabels[rider.unavailable_reason] || "Unavailable")}</span>`;
                    const distance = rider.nearest_pickup_distance_m === null
                        ? (rider.location_age_seconds === null
                            ? "No location reported"
                            : (rider.location_age_seconds > 120
                                ? "Location is stale"
                                : "No pickup store in rider zone"))
                        : `${escapeHtml(rider.nearest_pickup_distance_m)} m`;
                    const lastSeen = rider.location_age_seconds === null
                        ? "Never reported"
                        : `${escapeHtml(rider.location_age_seconds)} sec ago`;
                    return `<tr>
                        <td>${escapeHtml(rider.name || `Rider #${rider.id}`)} <span class="text-secondary">#${escapeHtml(rider.id)}</span></td>
                        <td><span class="badge ${statusClass}">${escapeHtml(rider.status)}</span></td>
                        <td>${escapeHtml(rider.delivery_zone || "—")}</td>
                        <td>${distance}</td>
                        <td>${lastSeen}</td>
                        <td>${availability}</td>
                    </tr>`;
                }).join("")
                : '<tr><td colspan="6" class="text-secondary">No riders are registered in your pickup zones.</td></tr>';
            document.getElementById("rider-availability-updated").textContent =
                `Updated ${new Date().toLocaleTimeString()}. Refreshes automatically every 30 seconds.`;
            errorElement.classList.add("d-none");
            errorElement.textContent = "";
        } catch (error) {
            errorElement.textContent = error.message;
            errorElement.classList.remove("d-none");
        }
    };

    document.getElementById("rider-availability-refresh").addEventListener("click", refresh);
    refresh();
    window.setInterval(refresh, 30000);
})();
