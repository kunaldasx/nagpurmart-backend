$(document).ready(function () {
    // Initialize variables
    let currentNotificationId = null;
    const csrfToken = $('meta[name="csrf-token"]').attr("content");

    const loadBroadcasts = function () {
        if (!$("#broadcasts-table").length) {
            return;
        }

        axios
            .get(`${base_url}/${panel}/notifications/customer-broadcasts`, {
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
            })
            .then(function (response) {
                const items = response.data?.data?.items ?? [];
                const rows = items
                    .map(function (item) {
                        const statusBadge =
                            item.status === "sent"
                                ? '<span class="badge bg-success">Sent</span>'
                                : '<span class="badge bg-warning text-dark">Draft</span>';
                        return `
                    <tr>
                        <td>
                            <div class="fw-semibold">${item.title || ""}</div>
                            <div class="text-muted small">${item.description || ""}</div>
                        </td>
                        <td>${statusBadge}</td>
                        <td>${item.recipient_count ?? 0}</td>
                        <td>${item.sent_at ? new Date(item.sent_at).toLocaleString() : "Not sent yet"}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary resend-broadcast-btn" data-id="${item.id}">Resend</button>
                        </td>
                    </tr>`;
                    })
                    .join("");
                $("#broadcasts-table tbody").html(
                    rows ||
                        '<tr><td colspan="5" class="text-center text-muted">No campaigns yet.</td></tr>',
                );
            })
            .catch(function (error) {
                console.error("Error:", error);
            });
    };

    // Refresh button
    $("#refresh").on("click", function () {
        if ($("#notifications-table").length) {
            $("#notifications-table").DataTable().ajax.reload();
        }
    });

    // Mark all as read button
    $("#mark-all-read-btn").on("click", function () {
        $("#markAllReadModal").modal("show");
    });

    // Confirm mark all as read
    $("#confirmMarkAllRead").on("click", function () {
        // Disable the button and show loading state
        $(this)
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...',
            );

        // Process mark all as read using axios
        axios
            .post(
                `${base_url}/${panel}/notifications/mark-all-read`,
                {},
                {
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                },
            )
            .then(function (response) {
                const data = response.data;
                if (data.success) {
                    Toast.fire({
                        icon: "success",
                        title: data.message,
                    });
                    $("#notifications-table").DataTable().ajax.reload();
                } else {
                    Toast.fire({
                        icon: "error",
                        title: data.message,
                    });
                }
            })
            .catch(function (error) {
                console.error("Error:", error);
                Toast.fire({
                    icon: "error",
                    title:
                        error.response?.data?.message ||
                        "An error occurred while marking notifications as read",
                });
            })
            .finally(function () {
                // Reset the button state
                $("#confirmMarkAllRead")
                    .prop("disabled", false)
                    .html("Yes, Mark All");

                // Hide the modal
                $("#markAllReadModal").modal("hide");
            });
    });

    // Mark single notification as read
    $(document).on("click", ".mark-read-btn", function (e) {
        e.preventDefault();
        const notificationId = $(this).data("id");
        const button = $(this);

        // Disable the button and show loading state
        button.prop("disabled", true);

        // Process mark as read using axios
        axios
            .post(
                `${base_url}/${panel}/notifications/${notificationId}/mark-read`,
                {},
                {
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                },
            )
            .then(function (response) {
                const data = response.data;
                if (data.success) {
                    Toast.fire({
                        icon: "success",
                        title: data.message,
                    });
                    $("#notifications-table").DataTable().ajax.reload();
                } else {
                    Toast.fire({
                        icon: "error",
                        title: data.message,
                    });
                }
            })
            .catch(function (error) {
                console.error("Error:", error);
                Toast.fire({
                    icon: "error",
                    title:
                        error.response?.data?.message ||
                        "An error occurred while marking notification as read",
                });
            })
            .finally(function () {
                // Reset the button state
                button.prop("disabled", false);
            });
    });

    // Mark single notification as unread
    $(document).on("click", ".mark-unread-btn", function (e) {
        e.preventDefault();
        const notificationId = $(this).data("id");
        const button = $(this);

        // Disable the button and show loading state
        button.prop("disabled", true);

        // Process mark as unread using axios
        axios
            .post(
                `${base_url}/${panel}/notifications/${notificationId}/mark-unread`,
                {},
                {
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                },
            )
            .then(function (response) {
                const data = response.data;
                if (data.success) {
                    Toast.fire({
                        icon: "success",
                        title: data.message,
                    });
                    $("#notifications-table").DataTable().ajax.reload();
                } else {
                    Toast.fire({
                        icon: "error",
                        title: data.message,
                    });
                }
            })
            .catch(function (error) {
                console.error("Error:", error);
                Toast.fire({
                    icon: "error",
                    title:
                        error.response?.data?.message ||
                        "An error occurred while marking notification as unread",
                });
            })
            .finally(function () {
                // Reset the button state
                button.prop("disabled", false);
            });
    });

    // View notification details
    $(document).on("click", ".view-notification-btn", function (e) {
        e.preventDefault();
        const notificationId = $(this).data("id");

        // Fetch notification details using axios
        axios
            .get(`${base_url}/${panel}/notifications/${notificationId}`, {
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
            })
            .then(function (response) {
                const data = response.data;
                if (data.success) {
                    const notification = data.data;

                    // Populate modal with notification details
                    $("#modal-title").text(notification.title);
                    $("#modal-type").text(notification.type);
                    $("#modal-sent-to").text(notification.sent_to);
                    $("#modal-status").html(
                        notification.is_read
                            ? '<span class="badge delivered">Read</span>'
                            : '<span class="badge inactive">Unread</span>',
                    );
                    $("#modal-message").text(notification.message);
                    $("#modal-created-at").text(
                        new Date(notification.created_at).toLocaleString(),
                    );

                    // Show the modal
                    $("#viewNotificationModal").modal("show");
                } else {
                    Toast.fire({
                        icon: "error",
                        title: data.message,
                    });
                }
            })
            .catch(function (error) {
                console.error("Error:", error);
                Toast.fire({
                    icon: "error",
                    title:
                        error.response?.data?.message ||
                        "An error occurred while fetching notification details",
                });
            });
    });

    $("#create-broadcast-btn").on("click", function () {
        $("#broadcast-form")[0].reset();
        $("#broadcastModal").modal("show");
    });

    $("#save-broadcast-btn").on("click", function () {
        const button = $(this);
        button
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...',
            );

        const formData = new FormData(
            document.getElementById("broadcast-form"),
        );

        axios
            .post(
                `${base_url}/${panel}/notifications/customer-broadcasts`,
                formData,
                {
                    headers: {
                        "Content-Type": "multipart/form-data",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                },
            )
            .then(function (response) {
                const data = response.data;
                if (data.success) {
                    Toast.fire({ icon: "success", title: data.message });
                    loadBroadcasts();
                    $("#broadcastModal").modal("hide");
                } else {
                    Toast.fire({ icon: "error", title: data.message });
                }
            })
            .catch(function (error) {
                console.error("Error:", error);
                Toast.fire({
                    icon: "error",
                    title:
                        error.response?.data?.message ||
                        "An error occurred while creating the notification campaign",
                });
            })
            .finally(function () {
                button.prop("disabled", false).html("Send to customers");
            });
    });

    $(document).on("click", ".resend-broadcast-btn", function (e) {
        e.preventDefault();
        const broadcastId = $(this).data("id");
        const button = $(this);
        button.prop("disabled", true).text("Sending...");

        axios
            .post(
                `${base_url}/${panel}/notifications/customer-broadcasts/${broadcastId}/resend`,
                {},
                {
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                },
            )
            .then(function (response) {
                const data = response.data;
                if (data.success) {
                    Toast.fire({ icon: "success", title: data.message });
                    loadBroadcasts();
                } else {
                    Toast.fire({ icon: "error", title: data.message });
                }
            })
            .catch(function (error) {
                console.error("Error:", error);
                Toast.fire({
                    icon: "error",
                    title:
                        error.response?.data?.message ||
                        "An error occurred while resending the notification campaign",
                });
            })
            .finally(function () {
                button.prop("disabled", false).text("Resend");
            });
    });

    // Delete notification
    $(document).on("click", ".delete-notification-btn", function (e) {
        e.preventDefault();
        currentNotificationId = $(this).data("id");
        $("#deleteNotificationModal").modal("show");
    });

    // Confirm delete notification
    $("#confirmDeleteNotification").on("click", function () {
        if (!currentNotificationId) return;

        // Disable the button and show loading state
        $(this)
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...',
            );

        // Process delete using axios
        axios
            .delete(
                `${base_url}/${panel}/notifications/${currentNotificationId}`,
                {
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                },
            )
            .then(function (response) {
                const data = response.data;
                if (data.success) {
                    Toast.fire({
                        icon: "success",
                        title: data.message,
                    });
                    $("#notifications-table").DataTable().ajax.reload();
                } else {
                    Toast.fire({
                        icon: "error",
                        title: data.message,
                    });
                }
            })
            .catch(function (error) {
                console.error("Error:", error);
                Toast.fire({
                    icon: "error",
                    title:
                        error.response?.data?.message ||
                        "An error occurred while deleting the notification",
                });
            })
            .finally(function () {
                // Reset the button state
                $("#confirmDeleteNotification")
                    .prop("disabled", false)
                    .html("Yes, Delete");

                // Hide the modal
                $("#deleteNotificationModal").modal("hide");

                // Reset current notification ID
                currentNotificationId = null;
            });
    });

    loadBroadcasts();

    // Reset modals when they are hidden
    $("#markAllReadModal").on("hidden.bs.modal", function () {
        $("#confirmMarkAllRead").prop("disabled", false).html("Yes, Mark All");
    });

    $("#deleteNotificationModal").on("hidden.bs.modal", function () {
        $("#confirmDeleteNotification")
            .prop("disabled", false)
            .html("Yes, Delete");
        currentNotificationId = null;
    });

    $("#viewNotificationModal").on("hidden.bs.modal", function () {
        // Clear modal content
        $("#modal-title").text("");
        $("#modal-type").text("");
        $("#modal-sent-to").text("");
        $("#modal-status").html("");
        $("#modal-message").text("");
        $("#modal-created-at").text("");
    });
});
