class CategorySortManager {
    constructor() {
        this.originalOrder = [];
        this.initSortable();
        this.initEvents();
    }

    initSortable() {
        const container = $('#categories-sortable-list');
        if (container.length) {
            container.sortable({
                group: 'categories-list',
                animation: 200,
                ghostClass: 'ghost',
                onSort: () => console.log('Category order changed')
            });

            this.originalOrder = container.sortable('toArray');
        }
    }

    initEvents() {
        $('#save-category-order').on('click', () => this.saveOrder());
        $('#reset-order').on('click', () => this.resetOrder());
    }

    async saveOrder() {
        try {
            const container = $('#categories-sortable-list');
            const ids = container.sortable('toArray');
            if (ids.length === 0) {
                Toast.fire({ icon: 'warning', title: 'Nothing to sort' });
                return;
            }

            const response = await axios.post(`${base_url}/admin/categories/sort`, { categories: ids }, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const { data } = response;
            Toast.fire({ icon: data.success === false ? 'error' : 'success', title: data.message });
        } catch (error) {
            const message = error.response?.data?.message || 'An error occurred while saving order.';
            Toast.fire({ icon: 'error', title: message });
            console.error('Category sort error:', error);
        }
    }

    resetOrder() {
        const container = $('#categories-sortable-list');
        if (this.originalOrder.length) {
            const items = container.children().detach();
            const map = {};
            items.each(function () { map[$(this).data('id')] = $(this); });
            this.originalOrder.forEach(id => { container.append(map[id]); });
        }
    }
}

$(document).ready(() => new CategorySortManager());
