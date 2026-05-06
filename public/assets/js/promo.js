function viewPromo(id) {
    axios.get(`/admin/promos/${id}`)
        .then(response => {
            if (response.data.success) {
                const promo = response.data.data;
                let start_date = new Date(promo.start_date);
                let end_date = new Date(promo.end_date);
                let discount_type = promo.discount_type.replace('_', ' ');
                document.getElementById('promo-code').textContent = promo.code;
                document.getElementById('promo-description').textContent = promo.description || 'N/A';
                document.getElementById('promo-discount-type').textContent = discount_type;
                document.getElementById('promo-discount-amount').textContent = promo.discount_amount;
                document.getElementById('promo-start-date').textContent = start_date.toISOString().split('T')[0];
                document.getElementById('promo-end-date').textContent = end_date.toISOString().split('T')[0];
                document.getElementById('promo-usage-count').textContent = promo.usage_count;
                document.getElementById('promo-max-total-usage').textContent = promo.max_total_usage || 'Unlimited';
                document.getElementById('promo-max-usage-per-user').textContent = promo.max_usage_per_user || 'Unlimited';
                document.getElementById('promo-min-order-total').textContent = promo.min_order_total || 'No minimum';
                document.getElementById('promo-max-discount-value').textContent = promo.max_discount_value || 'N/A';
            }
        })
        .catch(error => console.error('Error:', error));
}

function editPromo(id) {
    axios.get(`/admin/promos/${id}`)
        .then(response => {
            if (response.data.success) {
                const promo = response.data.data;
                const form = document.querySelector('#promo-modal form');

                // Update form action for editing
                form.action = `/admin/promos/${id}`;
                form.insertAdjacentHTML('afterbegin', '<input type="hidden" name="_method" value="PUT">');

                // Populate form fields
                document.getElementById('promo-id').value = promo.id;
                document.querySelector('input[name="code"]').value = promo.code;
                document.querySelector('textarea[name="description"]').value = promo.description || '';
                document.querySelector('select[name="discount_type"]').value = promo.discount_type;
                document.querySelector('input[name="discount_amount"]').value = promo.discount_amount;
                document.querySelector('input[name="max_discount_value"]').value = promo.max_discount_value || '';
                document.querySelector('input[name="start_date"]').value = promo.start_date ? promo.start_date.slice(0, 16) : '';
                document.querySelector('input[name="end_date"]').value = promo.end_date ? promo.end_date.slice(0, 16) : '';
                document.querySelector('input[name="min_order_total"]').value = promo.min_order_total || '';
                document.querySelector('select[name="promo_mode"]').value = promo.promo_mode || '';
                document.querySelector('input[name="max_total_usage"]').value = promo.max_total_usage || '';
                document.querySelector('input[name="max_usage_per_user"]').value = promo.max_usage_per_user || '';
                // document.querySelector('input[name="individual_use"]').checked = promo.individual_use == 1;

                // Update modal title and button text
                document.querySelector('#promo-modal .modal-title').textContent = 'Edit Promo';
                document.querySelector('#promo-modal button[type="submit"]').innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Update Promo
                        `;
            }
        })
        .catch(error => console.error('Error:', error));
}

document.getElementById('promo-modal').addEventListener('hidden.bs.modal', function () {
    const form = this.querySelector('form');
    form.reset();
    form.action = '/admin/promos';
    form.querySelector('input[name="_method"]')?.remove();
    document.getElementById('promo-id').value = '';

    // Reset modal title and button text
    this.querySelector('.modal-title').textContent = 'Create Promo';
    this.querySelector('button[type="submit"]').innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2">
                    <path d="M12 5l0 14"/>
                    <path d="M5 12l14 0"/>
                </svg>
                Create New Promo
            `;
});

document.addEventListener('click', function (event) {
    handleDelete(event, '.delete-promo-code', `/admin/promos/`, 'You are about to delete this Promo Code.');
});

function deletePromo(id) {
    if (confirm('Are you sure you want to delete this promo?')) {
        fetch(`/admin/promos/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the DataTable
                    $('#promos-table').DataTable().ajax.reload();
                    // Show success message (you can implement a toast notification here)
                    alert('Promo deleted successfully!');
                } else {
                    alert('Error deleting promo: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting promo');
            });
    }
}

