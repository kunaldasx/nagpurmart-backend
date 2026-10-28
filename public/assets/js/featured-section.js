// Featured Section Management - Refactored for better maintainability
class FeaturedSectionManager {
    constructor() {
        this.initializeSortable();
        this.initializeEventListeners();
        this.initializeProductPicker();
    }

    // Initialize sortable functionality
    initializeSortable() {
        try {
            // Initialize sortable for each sortable container
            $('.sortable-container').each(function() {
                const container = $(this);
                const group = container.data('group');

                container.sortable({
                    group: group === 'global' ? 'global-list' : `category-${container.data('category-id')}-list`,
                    animation: 200,
                    ghostClass: 'ghost',
                    onSort: () => console.log(`The sort order has changed for ${group} group`),
                });
            });
        } catch (e) {
            console.error('Error initializing sortable:', e);
        }
    }

    // Initialize all event listeners
    initializeEventListeners() {
        $('#get-section-order').on('click', () => this.handleSortOrder());
        $('#background_type').on('change', (e) => this.handleBackgroundTypeChange(e.target.value));
        document.addEventListener('show.bs.modal', (e) => this.handleModalShow(e));
        document.addEventListener('click', (e) => this.handleDelete(e));
        document.getElementById('featured-products-list')?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-product-action]');
            if (!button) return;
            this.handleProductAction(button);
        });
        this.initializeCollapseHandlers();
    }

    initializeProductPicker() {
        const select = document.getElementById('featured-product-search');
        if (!select || !window.TomSelect) return;

        this.productSelect = new TomSelect(select, {
            valueField: 'value',
            labelField: 'text',
            searchField: 'text',
            dropdownParent: 'body',
            maxOptions: 10,
            load: (query, callback) => {
                if (!query) return callback();
                fetch(`${base_url}/${panel}/products/search?search=${encodeURIComponent(query)}`)
                    .then((response) => {
                        if (!response.ok) throw new Error(`Product search failed (${response.status})`);
                        return response.json();
                    })
                    .then(callback)
                    .catch((error) => {
                        callback();
                        Toast.fire({ icon: 'error', title: error.message || 'Unable to search products' });
                    });
            },
        });

        document.getElementById('add-featured-product')?.addEventListener('click', () => {
            this.addSelectedProduct();
        });
    }

    addSelectedProduct(product = null) {
        const productId = product?.id || this.productSelect?.getValue();
        if (!productId) {
            Toast.fire({ icon: 'warning', title: 'Select a product first' });
            return;
        }

        const list = document.getElementById('featured-products-list');
        if (Array.from(list.children).some((row) => row.dataset.productId === String(productId))) {
            Toast.fire({ icon: 'warning', title: 'This product has already been added' });
            return;
        }

        const selected = product || this.productSelect?.options[productId];
        if (!selected) {
            Toast.fire({ icon: 'error', title: 'Unable to load the selected product' });
            return;
        }
        this.renderSelectedProduct({
            id: productId,
            title: selected.title || selected.text,
        });
        this.productSelect?.clear();
    }

    renderSelectedProduct(product) {
        const list = document.getElementById('featured-products-list');
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 border rounded p-2 mb-2';
        row.dataset.productId = product.id;

        const title = document.createElement('span');
        title.className = 'flex-grow-1';
        title.textContent = product.title;

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'products[]';
        hidden.value = product.id;

        row.append(hidden, title);
        [
            { action: 'up', label: 'Move up', text: 'Up' },
            { action: 'down', label: 'Move down', text: 'Down' },
            { action: 'remove', label: 'Remove product', text: 'Remove' },
        ].forEach(({ action, label, text }) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = action === 'remove' ? 'btn btn-sm btn-outline-danger' : 'btn btn-sm btn-outline-secondary';
            button.dataset.productAction = action;
            button.setAttribute('aria-label', label);
            button.textContent = text;
            row.appendChild(button);
        });

        list.appendChild(row);
    }

    handleProductAction(button) {
        const row = button.closest('[data-product-id]');
        if (!row) return;

        if (button.dataset.productAction === 'remove') {
            row.remove();
            return;
        }

        if (button.dataset.productAction === 'up' && row.previousElementSibling) {
            row.parentNode.insertBefore(row, row.previousElementSibling);
        } else if (button.dataset.productAction === 'down' && row.nextElementSibling) {
            row.parentNode.insertBefore(row.nextElementSibling, row);
        }
    }

    setSelectedProducts(products = []) {
        const list = document.getElementById('featured-products-list');
        if (!list) return;
        list.replaceChildren();
        products.forEach((product) => this.renderSelectedProduct(product));
    }

    resetProductPicker() {
        this.setSelectedProducts();
        this.productSelect?.clear();
        this.productSelect?.clearOptions();
    }

    // Handle sort order submission
    async handleSortOrder() {
        try {
            const sortData = {};

            // Collect global sections
            const globalContainer = $('#global-sortable-list');
            if (globalContainer.length) {
                const globalSections = globalContainer.sortable('toArray');
                if (globalSections.length > 0) {
                    sortData.global_sections = globalSections;
                }
            }

            // Collect category sections
            const categorySections = {};
            $('.sortable-container[data-group="category"]').each(function() {
                const container = $(this);
                const categoryId = container.data('category-id');
                const sectionIds = container.sortable('toArray');

                if (sectionIds.length > 0) {
                    categorySections[categoryId] = sectionIds;
                }
            });

            if (Object.keys(categorySections).length > 0) {
                sortData.category_sections = categorySections;
            }

            // Check if we have any data to send
            if (!sortData.global_sections && !sortData.category_sections) {
                Toast.fire({
                    icon: "warning",
                    title: "No sections to sort"
                });
                return;
            }

            const response = await axios.post(`${base_url}/admin/featured-sections/sort`, sortData, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const { data } = response;
            Toast.fire({
                icon: data.success === false ? "error" : "success",
                title: data.message
            });
        } catch (error) {
            const message = error.response?.data?.message || "An error occurred while submitting the form.";
            Toast.fire({
                icon: "error",
                title: message
            });
            console.error('Sort order error:', error);
        }
    }

    // Initialize collapse/expand handlers
    initializeCollapseHandlers() {
        // Handle collapse events for all collapsible sections
        $('[data-bs-toggle="collapse"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('data-bs-target');
            const collapseElement = $(target);
            const icon = $(this).find('.collapse-icon');

            // Toggle the collapse
            collapseElement.collapse('toggle');

            // Handle icon rotation
            collapseElement.on('show.bs.collapse', function() {
                icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $(this).closest('.section-group-header').attr('aria-expanded', 'true');
            });

            collapseElement.on('hide.bs.collapse', function() {
                icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $(this).closest('.section-group-header').attr('aria-expanded', 'false');
            });
        });

        // Store collapse state in localStorage (optional)
        $('[data-bs-toggle="collapse"]').on('click', function() {
            const target = $(this).attr('data-bs-target');
            const isExpanded = $(this).attr('aria-expanded') === 'true';
            localStorage.setItem(`collapse-state-${target}`, !isExpanded);
        });

        // Restore collapse state from localStorage
        this.restoreCollapseState();
    }

    // Restore collapse state from localStorage
    restoreCollapseState() {
        $('[data-bs-toggle="collapse"]').each(function() {
            const target = $(this).attr('data-bs-target');
            const savedState = localStorage.getItem(`collapse-state-${target}`);

            if (savedState === 'false') {
                const collapseElement = $(target);
                const icon = $(this).find('.collapse-icon');

                collapseElement.removeClass('show');
                icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $(this).attr('aria-expanded', 'false');
            }
        });
    }

    // Handle background type change
    handleBackgroundTypeChange(backgroundType) {
        const colorField = $('#background-color-field');
        const imageField = $('#background-image-field');

        colorField.hide();
        imageField.hide();

        if (backgroundType === 'color') {
            colorField.show();
        } else if (backgroundType === 'image') {
            imageField.css('display', 'flex');
        }
    }

    // Handle modal show event
    handleModalShow(event) {
        if (event.target.id !== 'featured-section-modal') return;

        const triggerButton = event.relatedTarget;
        const featuredId = triggerButton?.getAttribute('data-id');

        if (featuredId) {
            this.setupEditMode(featuredId);
        } else {
            this.setupCreateMode();
        }
    }

    // Setup modal for editing existing featured section
    async setupEditMode(featuredId) {
        try {
            this.productSelect?.clear();
            const url = `${base_url}/${panel}/featured-sections/${featuredId}`;
            const response = await fetch(url, { method: 'GET' });
            const responseData = await response.json();

            this.populateFormWithData(responseData.data, featuredId);
        } catch (error) {
            console.error('Error fetching featured section data:', error);
            Toast.fire({
                icon: "error",
                title: "Error loading featured section data"
            });
        }
    }

    // Setup modal for creating new featured section
    setupCreateMode() {
        const elements = this.getModalElements();

        if (elements.form) elements.form.reset();
        toggleScopeFields();

        this.removeMethodInput(elements.form);
        this.clearTomSelects(elements);
        this.resetProductPicker();
        this.hideBackgroundFields(elements);
        this.clearFilePond(elements.imageUpload4k);
        this.clearFilePond(elements.imageUploadFhd);
        this.clearFilePond(elements.imageUploadMobile);
        this.clearFilePond(elements.imageUploadTablet);

        elements.form.setAttribute('action', `${base_url}/${panel}/featured-sections`);
        elements.modalTitle.textContent = 'Add Featured Section';
        elements.submitButton.textContent = 'Add';
    }

    // Populate form with fetched data
    populateFormWithData(data, featuredId) {
        const elements = this.getModalElements();

        this.clearFilePond(elements.imageUpload4k);
        this.clearFilePond(elements.imageUploadFhd);
        this.clearFilePond(elements.imageUploadMobile);
        this.clearFilePond(elements.imageUploadTablet);
        this.fillBasicFormFields(elements.form, data);
        this.handleBackgroundType(elements, data);
        this.setupTomSelects(elements, data);
        this.setSelectedProducts(data.selected_products || []);
        this.setupFilePond(elements, data);
        elements.form.setAttribute('action', `${base_url}/${panel}/featured-sections/${featuredId}`);
        elements.modalTitle.textContent = 'Edit Featured Section';
        elements.submitButton.textContent = 'Update';
    }

    // Get all modal elements
    getModalElements() {
        return {
            form: document.querySelector('.form-submit'),
            modalTitle: document.querySelector('#featured-section-modal .modal-title'),
            submitButton: document.querySelector('#featured-section-modal button[type="submit"]'),
            selectScopeElement: document.getElementById('select-root-category'),
            colorField: document.getElementById('background-color-field'),
            imageField: document.getElementById('background-image-field'),
            imageUpload4k: document.querySelector('#desktop_4k_background_image'),
            imageUploadFhd: document.querySelector('#desktop_fdh_background_image'),
            imageUploadTablet: document.querySelector('#tablet_background_image'),
            imageUploadMobile: document.querySelector('#mobile_background_image')
        };
    }

    // Fill basic form fields
    fillBasicFormFields(form, data) {
        const fields = [
            { name: 'title', value: data.title || '' },
            { name: 'short_description', value: data.short_description || '' },
            { name: 'section_type', value: data.section_type || '' },
            { name: 'style', value: data.style || '' },
            { name: 'background_type', value: data.background_type || '' },
            { name: 'text_color', value: data.text_color || '' },
            { name: 'scope_type', value: data.scope_type || '' }
        ];

        fields.forEach(field => {
            const element = form.querySelector(`[name="${field.name}"]`);
            if (element) {
                if (element.type === 'checkbox') {
                    element.checked = field.value === 'active';
                } else {
                    element.value = field.value;
                }
            }
        });

        const statusField = form.querySelector('input[name="status"]');
        if (statusField) statusField.checked = data.status === 'active';

        toggleScopeFields();
    }

    // Handle background type specific logic
    handleBackgroundType(elements, data) {
        const backgroundType = data.background_type;

        if (backgroundType === 'color') {
            elements.colorField.style.display = 'block';
            elements.imageField.style.display = 'none';
            const colorInput = elements.form.querySelector('input[name="background_color"]');
            if (colorInput) colorInput.value = data.background_color || '';
        } else if (backgroundType === 'image') {
            elements.colorField.style.display = 'none';
            elements.imageField.style.display = 'flex';
        } else {
            elements.colorField.style.display = 'none';
            elements.imageField.style.display = 'none';
        }
    }

    // Setup TomSelect dropdowns
    setupTomSelects(elements, data) {
        let tomSelectScope = elements.selectScopeElement.tomselect || new TomSelect(elements.selectScopeElement);

        // Handle scope selection
        if (data.scope_type === 'category') {
            tomSelectScope.addOption({
                value: data.scope_id,
                text: data.scope_category_title,
            });
            tomSelectScope.setValue(data.scope_id);
        } else {
            tomSelectScope.clearOptions();
            tomSelectScope.clear();
        }

    }

    // Setup FilePond for image upload
    setupFilePond(elements, data) {
        if (typeof FilePond === 'undefined') return;

        const backgroundType = data.background_type;
        if (backgroundType !== 'image') return;

        const map = [
            {el: elements.imageUpload4k, key: 'desktop_4k_background_image'},
            {el: elements.imageUploadFhd, key: 'desktop_fdh_background_image'},
            {el: elements.imageUploadTablet, key: 'tablet_background_image'},
            {el: elements.imageUploadMobile, key: 'mobile_background_image'},
        ];

        map.forEach(({el, key}) => {
            if (!el) return;
            const pond = FilePond.find(el);
            if (!pond) return;
            pond.removeFiles();
            if (data[key]) {
                pond.addFile(data[key]);
            }
        });
    }

    // Utility functions
    removeMethodInput(form) {
        const methodInput = form.querySelector('input[name="_method"]');
        if (methodInput) methodInput.remove();
    }

    clearTomSelects(elements) {
        const tomSelectScope = elements.selectScopeElement.tomselect;

        if (tomSelectScope) {
            tomSelectScope.clearOptions();
            tomSelectScope.clear();
        }
    }

    hideBackgroundFields(elements) {
        elements.colorField.style.display = 'none';
        elements.imageField.style.display = 'none';
    }

    clearFilePond(imageUpload) {
        if (typeof FilePond !== 'undefined' && imageUpload) {
            const pond = FilePond.find(imageUpload);
            if (pond) pond.removeFiles();
        }
    }

    // Handle delete functionality
    handleDelete(event) {
        handleDelete(event, '.delete-featured-section', `/${panel}/featured-sections/`, 'You are about to delete this Featured Section.');
    }
}

// Initialize the Featured Section Manager when DOM is ready
$(document).ready(() => {
    new FeaturedSectionManager();
    const table = $('#featured-table').DataTable();

    $('#typeFilter, #statusFilter,#scopeTypeFilter').on('change', function () {
        table.ajax.reload(null, false);
    });

    // Add filter params to AJAX request
    $('#featured-table').on('preXhr.dt', function (e, settings, data) {
        data.type = $('#typeFilter').val();
        data.visibility_status = $('#statusFilter').val();
        data.scope_type = $('#scopeTypeFilter').val();
    });
});
