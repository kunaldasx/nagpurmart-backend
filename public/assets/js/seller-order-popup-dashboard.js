(() => {
    const root = document.getElementById("seller-popup-users");
    if (!root) return;

    const escapeHtml = (value) =>
        String(value ?? "").replace(/[&<>"']/g, (character) => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;",
        })[character]);

    const badge = (label, tone) =>
        `<span class="badge bg-${tone}-lt text-${tone}">${escapeHtml(label)}</span>`;

    const formatDate = (value) => {
        if (!value) return "Never";
        const parsed = new Date(value);
        return Number.isNaN(parsed.getTime()) ? "Unknown" : parsed.toLocaleString();
    };

    const reasonLabels = {
        account_inactive: "Account inactive",
        system_user_inactive: "System user inactive",
        login_not_approved_or_expired: "Login approval missing or expired",
        order_popup_permission_missing: "Popup permission missing",
        popup_presence_inactive: "Popup presence inactive",
        location_stale: "Location report stale",
        location_or_store_unavailable: "Location or store unavailable",
        outside_200m_radius: "Outside 200 m radius",
        order_in_progress: "Already handling an order",
    };

    const renderUser = (user) => {
        const activeBadge = user.is_active ? badge("Active", "green") : badge("Inactive", "secondary");
        const eligibilityBadge = user.is_eligible
            ? badge("Eligible", "blue")
            : badge(reasonLabels[user.ineligible_reason] || "Not eligible", "yellow");
        const nearestStore = user.nearest_store
            ? `${escapeHtml(user.nearest_store.name)} · ${escapeHtml(user.nearest_store.distance_m)} m`
            : "Location/store unavailable";
        const offer = user.current_offer
            ? `#${escapeHtml(user.current_offer.order_number || user.current_offer.seller_order_id)} · ${escapeHtml(user.current_offer.order_mode)} (${escapeHtml(user.current_offer.status)})`
            : "None";
        const roles = (user.roles || []).map(escapeHtml).join(", ") || "No roles";

        return `<tr>
            <td><div class="fw-semibold">${escapeHtml(user.name)}</div>
                <div class="text-secondary">${escapeHtml(user.email)}</div>
                <div class="text-secondary">${escapeHtml(user.mobile || "No mobile")} · ${roles}</div>
            </td>
            <td>${escapeHtml(user.system_user_status || "inactive")}<div class="mt-1">${escapeHtml(user.login_approval_status || "not approved")}</div>
                <div class="text-secondary small">Approved until ${escapeHtml(formatDate(user.login_approved_until))}</div>
            </td>
            <td>${activeBadge} ${eligibilityBadge}
                <div class="text-secondary small">Last location ${escapeHtml(formatDate(user.last_seen_at))}</div>
                <div class="text-secondary small">${user.has_order_popup_permission ? "Popup permission granted" : "Popup permission missing"}</div>
            </td>
            <td>${nearestStore}</td>
            <td>${offer}</td>
            <td>${user.has_fcm_token ? badge("Registered", "green") : badge("No token", "secondary")}</td>
        </tr>`;
    };

    const refresh = async () => {
        const errorElement = document.getElementById("seller-popup-users-error");
        try {
            const response = await fetch(root.dataset.url, {
                headers: { Accept: "application/json" },
            });
            const body = await response.json();
            if (!response.ok || !body.success) {
                throw new Error(body.message || "Could not load system user availability.");
            }

            const data = body.data;
            document.getElementById("popup-total-count").textContent = data.summary.total_system_users;
            document.getElementById("popup-active-count").textContent = data.summary.active_users;
            document.getElementById("popup-eligible-count").textContent = data.summary.eligible_users;
            document.getElementById("popup-inactive-count").textContent = data.summary.inactive_users;

            const rows = document.getElementById("seller-popup-users-rows");
            rows.innerHTML = data.users.length
                ? data.users.map(renderUser).join("")
                : '<tr><td colspan="6" class="text-secondary p-4">No system users are set up for this seller.</td></tr>';
            document.getElementById("seller-popup-users-updated").textContent =
                `Updated ${new Date().toLocaleTimeString()}. Refreshes automatically every 30 seconds. Eligible means active, approved, permitted, reporting a fresh location, within ${data.pickup_radius_meters} m of a seller store, and not already handling an order.`;
            errorElement.classList.add("d-none");
            errorElement.textContent = "";
        } catch (error) {
            errorElement.classList.remove("d-none");
            errorElement.textContent = error.message;
        }
    };

    document.getElementById("seller-popup-users-refresh").addEventListener("click", refresh);
    refresh();
    window.setInterval(refresh, 30000);
})();
