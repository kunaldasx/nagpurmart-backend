$(document).ready(function() {
    // Initialize variables
    let currentNotificationId = null;
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Refresh button
    $('#refresh').on('click', function() {
        if ($('#notifications-table').length) {
            $('#notifications-table').DataTable().ajax.reload();
        }
    });

    // Mark all as read button
    $('#mark-all-read-btn').on('click', function() {
        $('#markAllReadModal').modal('show');
    });

    // Confirm mark all as read
    $('#confirmMarkAllRead').on('click', function() {
        // Disable the button and show loading state
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        // Process mark all as read using axios
        axios.post(`${base_url}/${panel}/notifications/mark-all-read`, {}, {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(function(response) {
            const data = response.data;
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
                $('#notifications-table').DataTable().ajax.reload();
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            Toast.fire({
                icon: 'error',
                title: error.response?.data?.message || 'An error occurred while marking notifications as read'
            });
        })
        .finally(function() {
            // Reset the button state
            $('#confirmMarkAllRead').prop('disabled', false).html('Yes, Mark All');

            // Hide the modal
            $('#markAllReadModal').modal('hide');
        });
    });

    // Mark single notification as read
    $(document).on('click', '.mark-read-btn', function(e) {
        e.preventDefault();
        const notificationId = $(this).data('id');
        const button = $(this);

        // Disable the button and show loading state
        button.prop('disabled', true);

        // Process mark as read using axios
        axios.post(`${base_url}/${panel}/notifications/${notificationId}/mark-read`, {}, {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(function(response) {
            const data = response.data;
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
                $('#notifications-table').DataTable().ajax.reload();
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            Toast.fire({
                icon: 'error',
                title: error.response?.data?.message || 'An error occurred while marking notification as read'
            });
        })
        .finally(function() {
            // Reset the button state
            button.prop('disabled', false);
        });
    });

    // Mark single notification as unread
    $(document).on('click', '.mark-unread-btn', function(e) {
        e.preventDefault();
        const notificationId = $(this).data('id');
        const button = $(this);

        // Disable the button and show loading state
        button.prop('disabled', true);

        // Process mark as unread using axios
        axios.post(`${base_url}/${panel}/notifications/${notificationId}/mark-unread`, {}, {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(function(response) {
            const data = response.data;
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
                $('#notifications-table').DataTable().ajax.reload();
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            Toast.fire({
                icon: 'error',
                title: error.response?.data?.message || 'An error occurred while marking notification as unread'
            });
        })
        .finally(function() {
            // Reset the button state
            button.prop('disabled', false);
        });
    });

    // View notification details
    $(document).on('click', '.view-notification-btn', function(e) {
        e.preventDefault();
        const notificationId = $(this).data('id');

        // Fetch notification details using axios
        axios.get(`${base_url}/${panel}/notifications/${notificationId}`, {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(function(response) {
            const data = response.data;
            if (data.success) {
                const notification = data.data;

                // Populate modal with notification details
                $('#modal-title').text(notification.title);
                $('#modal-type').text(notification.type);
                $('#modal-sent-to').text(notification.sent_to);
                $('#modal-status').html(notification.is_read ?
                    '<span class="badge delivered">Read</span>' :
                    '<span class="badge inactive">Unread</span>'
                );
                $('#modal-message').text(notification.message);
                $('#modal-created-at').text(new Date(notification.created_at).toLocaleString());

                // Show the modal
                $('#viewNotificationModal').modal('show');
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            Toast.fire({
                icon: 'error',
                title: error.response?.data?.message || 'An error occurred while fetching notification details'
            });
        });
    });

    // Delete notification
    $(document).on('click', '.delete-notification-btn', function(e) {
        e.preventDefault();
        currentNotificationId = $(this).data('id');
        $('#deleteNotificationModal').modal('show');
    });

    // Confirm delete notification
    $('#confirmDeleteNotification').on('click', function() {
        if (!currentNotificationId) return;

        // Disable the button and show loading state
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        // Process delete using axios
        axios.delete(`${base_url}/${panel}/notifications/${currentNotificationId}`, {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(function(response) {
            const data = response.data;
            if (data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
                $('#notifications-table').DataTable().ajax.reload();
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            Toast.fire({
                icon: 'error',
                title: error.response?.data?.message || 'An error occurred while deleting the notification'
            });
        })
        .finally(function() {
            // Reset the button state
            $('#confirmDeleteNotification').prop('disabled', false).html('Yes, Delete');

            // Hide the modal
            $('#deleteNotificationModal').modal('hide');

            // Reset current notification ID
            currentNotificationId = null;
        });
    });

    // Reset modals when they are hidden
    $('#markAllReadModal').on('hidden.bs.modal', function() {
        $('#confirmMarkAllRead').prop('disabled', false).html('Yes, Mark All');
    });

    $('#deleteNotificationModal').on('hidden.bs.modal', function() {
        $('#confirmDeleteNotification').prop('disabled', false).html('Yes, Delete');
        currentNotificationId = null;
    });

    $('#viewNotificationModal').on('hidden.bs.modal', function() {
        // Clear modal content
        $('#modal-title').text('');
        $('#modal-type').text('');
        $('#modal-sent-to').text('');
        $('#modal-status').html('');
        $('#modal-message').text('');
        $('#modal-created-at').text('');
    });
});
