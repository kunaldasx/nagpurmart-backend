'use strict';
(function ($) {
    // Function to create swatche value field based on type
    function createswatcheValueField(type, container) {
        const $valueContainer = container.find('.swatche-value-container');
        const $currentInput = $valueContainer.find('.swatche-value');
        const name = $currentInput.attr('name');
        const currentValue = $currentInput.val();

        let newInput;
        switch (type) {
            case 'color':
                newInput = $('<input>', {
                    type: 'color',
                    class: 'form-control form-control-color w-100 swatche-value',
                    name: name,
                    value: currentValue || '#000000',
                });
                break;
            case 'image':
                // Create a container for the image preview
                const imageContainer = $('<div>', {
                    class: 'd-flex flex-column gap-2'
                });

                newInput = $('<input>', {
                    type: 'file',
                    class: 'form-control swatche-value',
                    name: name,
                    accept: 'image/*',
                });

                const previewElement = $('<div>', {
                    class: 'image-preview mt-2 d-none',
                    style: 'max-width: 100px; max-height: 100px;'
                });

                imageContainer.append(newInput).append(previewElement);

                // Add preview functionality
                newInput.on('change', function (e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (event) {
                            if (event.target.result != null) {
                                previewElement.html(`<img src="${event.target.result}" class="img-thumbnail" style="max-width: 100%; max-height: 100%;">`);
                                previewElement.removeClass('d-none');
                            }
                        };
                        reader.readAsDataURL(file);
                    } else {
                        previewElement.addClass('d-none').empty();
                    }
                });

                return $currentInput.replaceWith(imageContainer);

            default: // text type
                newInput = $('<input>', {
                    type: 'text',
                    class: 'form-control swatche-value',
                    name: name,
                    value: currentValue || '',
                    placeholder: 'Enter swatche value',
                });
        }
        $currentInput.replaceWith(newInput);
    }

    // Function to get translations from data attributes
    function getTranslations($form) {
        return {
            value: $form.data('value-text'),
            swatcheType: $form.data('swatche-type-text'),
            selectswatcheType: $form.data('select-swatche-type-text'),
            swatcheValue: $form.data('swatche-value-text'),
        };
    }

    // Template for new field group with improved styling
    function getFieldGroupTemplate() {
        return `
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="attribute-group-title fw-bold">New Attribute Value</span>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-field">
                        <i class="ti ti-minus me-1"></i> Remove
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <div class="">
                                <label for="value-field">Value</label>
                                <input type="text" name="values[]" class="form-control" id="value-field" placeholder="e.g., red">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-0 swatche-value-container">
                                <label class="form-label">swatche Value</label>
                                <input type="text" name="swatche_value[]" class="form-control swatche-value"
                                    placeholder="Enter swatche value">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Function to update remove buttons visibility
    function updateRemoveButtonsVisibility($container) {
        const $groups = $container.find('.field-group');
        const $removeButtons = $container.find('.remove-field');

        if ($groups.length <= 1) {
            $removeButtons.addClass('disabled').attr('disabled', true);
        } else {
            $removeButtons.removeClass('disabled').attr('disabled', false);
        }
    }

    // Initialize the form functionality
    $.fn.attributeValueForm = function () {
        return this.each(function () {
            const $form = $(this);
            const $container = $form.find('#dynamic-fields-container');
            const translations = getTranslations($form);

            // Replace initial field set with improved template
            $container.find('.field-group').first().html(getFieldGroupTemplate(translations));

            // Update field IDs to be unique when adding new fields
            function updateFieldIds() {
                $container.find('.field-group').each(function (index) {
                    const $group = $(this);

                    // Update form control IDs
                    $group.find('input[id], select[id]').each(function () {
                        const baseId = $(this).attr('id').split('-')[0];
                        $(this).attr('id', `${baseId}-${index}`);
                    });

                    // Update labels' for attributes
                    $group.find('label[for]').each(function () {
                        const baseFor = $(this).attr('for').split('-')[0];
                        $(this).attr('for', `${baseFor}-${index}`);
                    });

                    // Set group number in title
                    $group.find('.attribute-group-title').text(`Attribute Value #${index + 1}`);
                });
            }

            // Handle swatche type button group selection
            $form.on('change', '.btn-check', function () {
                const $fieldGroup = $(this).closest('.field-group');
                const value = $(this).val();

                // Update the hidden input with the selected value
                $fieldGroup.find('.swatche-type-hidden').val(value);

                // Handle the swatche value field
                createswatcheValueField(value, $fieldGroup);
            });

            // Handle add more fields with animation
            $form.on('click', '#add-more-fields', function () {
                // Create a new field group with unique IDs
                const fieldCount = $container.find('.field-group').length;
                const $newFieldGroup = $('<div>', {
                    class: 'field-group mb-4',
                    style: 'display: none;' // Hide initially for animation
                }).html(getFieldGroupTemplate(translations));
                // Replace radio button IDs with unique IDs
                $newFieldGroup.find('.btn-check').each(function (i) {
                    const oldId = $(this).attr('id');
                    const newId = `${oldId}-${fieldCount}-${i}`;
                    $(this).attr('id', newId);
                    $(this).next('label').attr('for', newId);
                });

                $container.append($newFieldGroup);
                $newFieldGroup.slideDown(300); // Animate the appearance

                // If an attribute is already selected, set the swatche type and value field for the new group
                const attributeSelect = $form.find('#attribute_id')[0];
                if (attributeSelect && attributeSelect.tomselect) {
                    const value = attributeSelect.tomselect.getValue();
                    if (value) {
                        const selectedOption = attributeSelect.tomselect.options[value];
                        if (selectedOption && selectedOption.swatche_type) {
                            // Set the swatche type dropdown to match the attribute's swatche type
                            $newFieldGroup.find('select[name="swatche_type[]"]').val(selectedOption.swatche_type);

                            // Update the swatche value field based on the swatche type
                            createswatcheValueField(selectedOption.swatche_type, $newFieldGroup);
                        }
                    }
                }

                updateFieldIds();
                updateRemoveButtonsVisibility($container);
            });

            // Handle remove field with animation
            $form.on('click', '.remove-field', function () {
                const $fieldGroup = $(this).closest('.field-group');

                $fieldGroup.slideUp(300, function () {
                    $(this).remove();
                    updateFieldIds();
                    updateRemoveButtonsVisibility($container);
                });
            });


            // Initialize form state
            updateFieldIds();
            updateRemoveButtonsVisibility($container);

            // Add styling to modal
            const modal = $form.closest('.modal');
            modal.addClass('fade');
            modal.find('.modal-content').addClass('shadow border-0');
            modal.find('.modal-header').addClass('border-bottom-0 pb-0');
            modal.find('.modal-footer').addClass('border-top-0 pt-0');

            // Initialize Tom Select for attribute dropdown
            if (window.TomSelect) {
                const attributeSelect = $form.find('#attribute_id')[0];
                if (attributeSelect && !attributeSelect.tomselect) {
                    const tomSelect = new TomSelect(attributeSelect, {
                        valueField: 'value',
                        labelField: 'text',
                        searchField: 'text',
                        placeholder: translations.selectswatcheType || 'Select attribute',
                        dropdownParent: 'body',
                        render: {
                            item: function (data, escape) {
                                if (data.customProperties) {
                                    return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
                                }
                                if (data && data.swatche_type) {
                                    // Update all swatche type fields in the form
                                    $form.find('.field-group').each(function () {
                                        const $fieldGroup = $(this);
                                        // Set the swatche type dropdown to match the attribute's swatche type
                                        $fieldGroup.find('select[name="swatche_type[]"]').val(data.swatche_type);
                                        // Update the swatche value field based on the swatche type
                                        createswatcheValueField(data.swatche_type, $fieldGroup);
                                    });
                                }
                                return "<div>" + escape(data.text) + "</div>";
                            }, option: function (data, escape) {
                                if (data.customProperties) {
                                    return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
                                }
                                return "<div>" + escape(data.text) + "</div>";
                            },
                        },
                        load: function (query, callback) {
                            if (!query.length) return callback();

                            const url = `${base_url}/${panel}/attributes/search?search=${encodeURIComponent(query)}`;
                            fetch(url)
                                .then(response => response.json())
                                .then(json => {
                                    callback(json);
                                }).catch(() => {
                                callback();
                            });
                        },
                        onChange: function (value) {
                            if (!value) return;

                            // Get the selected attribute's swatche type
                            const selectedOption = this.options[value];
                            if (selectedOption && selectedOption.swatche_type) {
                                // Update all swatche type fields in the form
                                $form.find('.field-group').each(function () {
                                    const $fieldGroup = $(this);
                                    // Set the swatche type dropdown to match the attribute's swatche type
                                    $fieldGroup.find('select[name="swatche_type[]"]').val(selectedOption.swatche_type);
                                    // Update the swatche value field based on the swatche type
                                    createswatcheValueField(selectedOption.swatche_type, $fieldGroup);
                                });
                            }
                        }
                    });
                }
            }
        });
    };

})(jQuery);

// Initialize when document is ready
$(document).ready(function () {
    if ($('#attribute-value-create-update-modal').length > 0) {
        $('#attribute-value-create-update-modal').attributeValueForm();
    }
});

document.addEventListener('show.bs.modal', function (event) {
    if (event.target.id === 'attribute-create-update-modal') {
        const triggerButton = event.relatedTarget;
        const attributeId = triggerButton.getAttribute('data-id');
        let url = `${base_url}/${panel}/attributes/${attributeId}/edit`;

        const form = document.querySelector('.form-submit');
        const modalTitle = document.querySelector('#attribute-create-update-modal .modal-title');
        const submitButton = document.querySelector('#attribute-create-update-modal button[type="submit"]');

        if (attributeId) {
            // Fetch category data
            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(responseData => {
                    const data = responseData.data;
                    // Fill form fields
                    form.querySelector('input[name="title"]').value = data.title || '';
                    form.querySelector('input[name="label"]').value = data.label || '';
                    form.querySelector('select[name="swatche_type"]').value = data.swatche_type;

                    // Change form action to update route
                    form.setAttribute('action', `${base_url}/${panel}/attributes/${attributeId}`);

                    // Update modal title and button
                    modalTitle.textContent = 'Edit Attribute';
                    submitButton.textContent = 'Update Attribute';
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {
            // New category mode
            if (form) form.reset();
            form.querySelector('input[name="title"]').value = '';
            form.querySelector('input[name="label"]').value = '';
            form.querySelector('select[name="swatche_type"]').value = "";
            // Remove _method input if it exists
            // Set action for create
            form.setAttribute('action', `${base_url}/${panel}/attributes`);
            modalTitle.textContent = 'Create New Attribute';
            submitButton.textContent = 'Create New Attribute';
        }
    }
    if (event.target.id === 'attribute-value-create-update-modal') {
        const triggerButton = event.relatedTarget;
        const attributeValueId = triggerButton.getAttribute('data-id');
        let url = `${base_url}/${panel}/attribute/values/${attributeValueId}/edit`;

        const form = document.querySelector('.attribute-value-form');
        const modalTitle = document.querySelector('#attribute-value-create-update-modal .modal-title');
        const submitButton = document.querySelector('#attribute-value-create-update-modal button[type="submit"]');

        if (attributeValueId) {
            // Fetch attribute value data
            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(responseData => {
                    if (responseData.success && responseData.data) {
                        const data = responseData.data;

                        // Set attribute_id and trigger change to load the swatche type
                        if (data.global_attribute_id) {
                            const attributeSelect = document.getElementById('attribute_id');
                            if (attributeSelect && attributeSelect.tomselect) {
                                // First, check if the option exists
                                if (!attributeSelect.tomselect.options[data.global_attribute_id]) {
                                    // If not, fetch the attribute details and add it
                                    fetch(`${base_url}/${panel}/attributes/${data.global_attribute_id}/edit`)
                                        .then(response => response.json())
                                        .then(attrData => {
                                            if (attrData.success && attrData.data) {
                                                attributeSelect.tomselect.addOption({
                                                    value: attrData.data.id,
                                                    text: attrData.data.title,
                                                    swatche_type: attrData.data.swatche_type
                                                });
                                                attributeSelect.tomselect.setValue(data.global_attribute_id);
                                            }
                                        });
                                } else {
                                    attributeSelect.tomselect.setValue(data.global_attribute_id);
                                }
                            }
                        }

                        const fieldGroup = form.querySelector('.field-group');

                        // Set title in the values fields
                        const valueField = fieldGroup.querySelector('[name="values[]"]');
                        if (valueField) valueField.value = data.title || '';

                        // Set the swatche type in the dropdown
                        const swatcheTypeField = fieldGroup.querySelector('select[name="swatche_type[]"]');
                        if (swatcheTypeField) swatcheTypeField.value = data.attribute.swatche_type;

                        // Create the appropriate swatche value field based on swatche_type
                        if (typeof createswatcheValueField === 'function') {
                            createswatcheValueField(data.attribute.swatche_type, $(fieldGroup));
                        }

                        // Handle the swatche value based on swatche_type
                        if (data.attribute.swatche_type === 'image') {
                            // For image type, show a preview of the existing image
                            const previewElement = fieldGroup.querySelector('.image-preview');
                            if (previewElement) {
                                if (data.swatche_value !== null && data.swatche_value !== '') {
                                    previewElement.innerHTML = `<img src="${data.swatche_value}" class="img-thumbnail" style="max-width: 100%; max-height: 100%;">`;
                                    previewElement.classList.remove('d-none');
                                }

                                // Make the file input not required since we already have an image
                                const fileInput = fieldGroup.querySelector('[name="swatche_value[]"]');
                                if (fileInput) fileInput.required = false;
                            }
                        } else {
                            // For text or color, set the value directly
                            const swatcheValueField = fieldGroup.querySelector('[name="swatche_value[]"]');
                            if (swatcheValueField) swatcheValueField.value = data.swatche_value || '';
                        }

                        // Remove any additional field groups
                        const additionalGroups = form.querySelectorAll('.field-group:not(:first-child)');
                        additionalGroups.forEach(group => group.remove());

                        // Change form action to update route
                        form.setAttribute('action', `${base_url}/${panel}/attribute/values/${attributeValueId}`);

                        // Add method input for PATCH request
                        let methodInput = form.querySelector('input[name="_method"]');
                        if (methodInput) {
                            methodInput.value = 'POST';
                        } else {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = '_method';
                            input.value = 'POST';
                            form.appendChild(input);
                        }

                        // Update modal title and button
                        modalTitle.textContent = 'Edit Attribute Value';
                        submitButton.innerHTML = '<i class="ti ti-edit fs-3 me-1"></i> Update Attribute Value';
                    }
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {
            // New attribute value mode
            if (form) form.reset();

            // Reset the form fields
            const fieldGroup = form.querySelector('.field-group');
            if (fieldGroup) {
                const valueField = fieldGroup.querySelector('[name="values[]"]');
                if (valueField) valueField.value = '';

                const swatcheTypeField = fieldGroup.querySelector('select[name="swatche_type[]"]');
                if (swatcheTypeField) swatcheTypeField.value = '';
                const swatcheValueField = fieldGroup.querySelector('[name="swatche_value[]"]');
                if (swatcheValueField) {
                    if (swatcheValueField.type === 'file') {
                        swatcheValueField.value = '';
                        const previewElement = fieldGroup.querySelector('.image-preview');
                        if (previewElement) {
                            previewElement.classList.add('d-none');
                            previewElement.innerHTML = '';
                        }
                    } else {
                        swatcheValueField.value = '';
                    }
                }
            }

            // Remove any additional field groups
            const additionalGroups = form.querySelectorAll('.field-group:not(:first-child)');
            additionalGroups.forEach(group => group.remove());

            // Remove _method input if it exists
            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) methodInput.remove();

            // Reset the attribute select
            const attributeSelect = document.getElementById('attribute_id');
            if (attributeSelect && attributeSelect.tomselect) {
                attributeSelect.tomselect.clear();
            }

            // Set action for create
            form.setAttribute('action', `${base_url}/${panel}/attribute/values`);
            modalTitle.textContent = 'Create New Attribute Value';
            submitButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2"><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg> Create New Attribute Value';
        }
    }
});

document.addEventListener('hidden.bs.modal', function (event) {
    if (event.target.id === 'attribute-value-create-update-modal') {
        document.querySelectorAll('.image-preview')?.forEach(element => {
            element.remove();
        });
    }
})

document.addEventListener('click', function (event) {
    // delete attribute
    handleDelete(event, '.delete-attribute-create-update', `/${panel}/attributes/`, 'You are about to delete this Attribute.');

    // delete attribute value
    handleDelete(event, '.delete-attribute-value-create-update', `/${panel}/attribute/values/`, 'You are about to delete this Attribute Value.');
});

$(document).ready(function () {

    let table = $('#attributes-table').DataTable();
    let tableValue = $('#attribute-values-table').DataTable();

// Filters
    $('#typeFilter').on('change', function () {
        table.ajax.reload(null, false);
        tableValue.ajax.reload(null, false);
    });

// Custom filter parameters
    table.on('preXhr.dt', function (e, settings, data) {
        data.swatche_type = $('#typeFilter').val();
    });
    tableValue.on('preXhr.dt', function (e, settings, data) {
        data.swatche_type = $('#typeFilter').val();
    });
});

