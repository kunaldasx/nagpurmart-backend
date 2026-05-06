document.addEventListener('show.bs.modal', function (event) {
    if (event.target.id === 'category-modal') {
        const triggerButton = event.relatedTarget;
        const categoryId = triggerButton.getAttribute('data-id');
        let url = base_url + '/admin/categories/' + categoryId + '/edit';

        const form = document.querySelector('.form-submit');
        const imageUpload = document.querySelector('#image-upload');
        const bannerUpload = document.querySelector('#banner-upload');
        const iconUpload = document.querySelector('#icon-upload');
        const activeIconUpload = document.querySelector('#active-icon-upload');
        const backgroundImageUpload = document.querySelector('#background-image-upload');
        const backgroundTypeSelect = document.querySelector('#background-type-select');
        const modalTitle = document.querySelector('#category-modal .modal-title');
        const submitButton = document.querySelector('#category-modal button[type="submit"]');
        const parentSelect = document.getElementById("select-parent-category");
        const tomSelectInstance = parentSelect && parentSelect.tomselect; // TomSelect instance
// Remove files from FilePond if available
        if (typeof FilePond !== 'undefined') {
            const pond = FilePond.find(imageUpload);
            if (pond) pond.removeFiles();
            const bannerPond = FilePond.find(bannerUpload);
            if (bannerPond) bannerPond.removeFiles();
            const iconPond = FilePond.find(iconUpload);
            if (iconPond) iconPond.removeFiles();
            const activeIconPond = FilePond.find(activeIconUpload);
            if (activeIconPond) activeIconPond.removeFiles();
            const backgroundImagePond = FilePond.find(backgroundImageUpload);
            if (backgroundImagePond) backgroundImagePond.removeFiles();
        }
        if (categoryId) {
            // Fetch category data
            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(async responseData => {
                    const data = responseData.data;
                    // Fill form fields
                    form.querySelector('input[name="title"]').value = data.title || '';
                    form.querySelector('input[id="category-id"]').value = categoryId;
                    form.querySelector('textarea[name="description"]').value = data.description || '';
                    form.querySelector('input[name="status"]').checked = data.status === 'active';
                    form.querySelector('input[name="requires_approval"]').checked = !!data.requires_approval;
                    form.querySelector('input[name="commission"]').value = data.commission || 0;

                    // Set background fields
                    if (backgroundTypeSelect) {
                        backgroundTypeSelect.value = data.background_type || '';
                        toggleBackgroundFields(data.background_type);
                    }
                    if (data.background_color) {
                        form.querySelector('input[name="background_color"]').value = data.background_color;
                    }
                    if (data.font_color) {
                        form.querySelector('input[name="font_color"]').value = data.font_color;
                    } else {

                        form.querySelector('input[name="font_color"]').value = '#00000';
                    }

                    // Set parent_id in TomSelect (auto-select)
                    if (tomSelectInstance) {
                        // If the parent is not in the options yet, load it
                        if (data.parent) {
                            let parentOption = tomSelectInstance.options[data.parent.id];
                            if (!parentOption) {
                                // Fetch the parent (if not already loaded)
                                await fetch(base_url + '/admin/categories/search' + `?q=${data.parent.title}`)
                                    .then(res => res.json())
                                    .then(json => {
                                        if (json && json.length) {
                                            tomSelectInstance.addOption(json[0]);
                                        }
                                    });
                            }
                            tomSelectInstance.setValue(data.parent_id);
                        } else {
                            tomSelectInstance.clear();
                        }
                    }

                    // Image upload via FilePond
                    if (typeof FilePond !== 'undefined') {
                        if (data.image !== null && data.image !== undefined && imageUpload && data.image !== '') {
                            const pond = FilePond.find(imageUpload);
                            if (pond) {
                                pond.addFile(data.image);
                            }
                        }
                        if (data.banner !== null && data.banner !== undefined && bannerUpload && data.banner !== '') {
                            const bannerPond = FilePond.find(bannerUpload);
                            if (bannerPond) {
                                bannerPond.addFile(data.banner);
                            }
                        }
                        if (data.icon !== null && data.icon !== undefined && iconUpload && data.icon !== '') {
                            const iconPond = FilePond.find(iconUpload);
                            if (iconPond) {
                                iconPond.addFile(data.icon);
                            }
                        }
                        if (data.active_icon !== null && data.active_icon !== undefined && activeIconUpload && data.active_icon !== '') {
                            const activeIconPond = FilePond.find(activeIconUpload);
                            if (activeIconPond) {
                                activeIconPond.addFile(data.active_icon);
                            }
                        }
                        if (data.background_image !== null && data.background_image !== undefined && backgroundImageUpload && data.background_image !== '') {
                            const backgroundImagePond = FilePond.find(backgroundImageUpload);
                            if (backgroundImagePond) {
                                backgroundImagePond.addFile(data.background_image);
                            }
                        }
                    }

                    // Change form action to update route
                    form.setAttribute('action', base_url + `/admin/categories/${categoryId}`);

                    // Update modal title and button
                    modalTitle.textContent = 'Edit Category';
                    submitButton.textContent = 'Update Category';
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {
            // New category mode
            if (form) form.reset();
            // Remove _method input if it exists
            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) methodInput.parentNode.removeChild(methodInput);

            // Reset TomSelect
            if (tomSelectInstance) tomSelectInstance.clear();

            // Reset background fields
            if (backgroundTypeSelect) {
                backgroundTypeSelect.value = '';
                toggleBackgroundFields('');
            }
            form.querySelector('input[name="background_color"]').value = '';

            // Set action for create
            form.querySelector('input[id="category-id"]').value = "";
            form.setAttribute('action', base_url + '/admin/categories');
            modalTitle.textContent = 'Create Category';
            submitButton.textContent = 'Create new Category';
        }
    }
    if (event.target.id === 'faq-modal') {
        const triggerButton = event.relatedTarget;
        const conditionId = triggerButton ? triggerButton.getAttribute('data-id') : null;
        let url = `${base_url}/${panel}/faqs/${conditionId}/edit`;

        const form = document.querySelector('#faq-modal .form-submit');
        const modalTitle = document.querySelector('#faq-modal .modal-title');
        const submitButton = document.querySelector('#faq-modal button[type="submit"]');

        if (conditionId) {
            // Edit mode: Fetch and populate data
            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(async responseData => {
                    const data = responseData.data;

                    // Fill form fields
                    form.querySelector('textarea[name="question"]').value = data.question || '';
                    form.querySelector('textarea[name="answer"]').value = data.answer || '';
                    form.querySelector('select[name="status"]').value = data.status || '';

                    // Change form action to update route
                    form.setAttribute('action', `${base_url}/${panel}/faqs/${conditionId}`);

                    // Update modal title and button
                    modalTitle.textContent = 'Edit Faq';
                    submitButton.innerHTML = '<i class="ti ti-edit me-1"></i> Update';
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {
            // New condition mode: Reset fields
            if (form) form.reset();
            form.querySelector('textarea[name="question"]').value = '';
            form.querySelector('textarea[name="answer"]').value = '';
            form.querySelector('select[name="status"]').value = 'active';
            // Set action for create
            form.setAttribute('action', `${base_url}/${panel}/faqs`);
            modalTitle.textContent = 'Add Faq';
            submitButton.innerHTML = '<i class="ti ti-plus me-1"></i> Add';
        }
    }
});

// Delete category
document.addEventListener('click', function (event) {
    handleDelete(event, '.delete-category', `/${panel}/categories/`, 'You are about to delete this Category.');
    handleDelete(event, '.delete-faq', `/${panel}/faqs/`, 'You are about to delete this Faq.');
});

// Background type toggle function
function toggleBackgroundFields(backgroundType) {
    const backgroundColorField = document.getElementById('background-color-field');
    const backgroundImageField = document.getElementById('background-image-field');

    if (backgroundType === 'color') {
        backgroundColorField.style.display = 'block';
        backgroundImageField.style.display = 'none';
    } else if (backgroundType === 'image') {
        backgroundColorField.style.display = 'none';
        backgroundImageField.style.display = 'block';
    } else {
        backgroundColorField.style.display = 'none';
        backgroundImageField.style.display = 'none';
    }
}

// Background type select event listener
document.addEventListener('change', function (event) {
    if (event.target.id === 'background-type-select') {
        toggleBackgroundFields(event.target.value);
    }
});

let tomSelectInstance;

try {
    tomSelectInstance = new TomSelect('.search-labels', {
        create: true,
        maxItems: 3
    });
} catch (e) {
}
document.querySelector('.generate-search-labels-button')?.addEventListener('click', function () {
    if (!tomSelectInstance) return;

    // Pool of random keywords
    const keywords = [
        'Grocery', 'Electronics', 'Daily Essentials', 'Fashion',
        'Beauty', 'Toys', 'Stationery', 'Books', 'Sports', 'Furniture'
    ];

    // Shuffle and pick 3 random keywords
    const randomKeywords = keywords.sort(() => 0.5 - Math.random()).slice(0, 3);

    // Clear old selections
    tomSelectInstance.clear();

    // Add "Search for ..." items
    randomKeywords.forEach(keyword => {
        const label = `Search for ${keyword}`;
        const value = label.toLowerCase().replace(/\s+/g, '_');
        tomSelectInstance.addOption({value, text: label});
        tomSelectInstance.addItem(value);
    });
});
function addField(containerId, keyName, valueName, keyPlaceholder, valuePlaceholder) {
    const container = document.getElementById(containerId);
    const fieldDiv = document.createElement('div');
    fieldDiv.className = 'row mb-2 ' + containerId.replace('Fields', '') + '-field';
    fieldDiv.innerHTML = `
        <div class="col-md-5">
            <input type="text" class="form-control" name="${keyName}[]" placeholder="${keyPlaceholder}" />
        </div>
        <div class="col-md-5">
            <input type="text" class="form-control" name="${valueName}[]" placeholder="${valuePlaceholder}" />
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger btn-sm remove-field">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M4 7l16 0" />
                    <path d="M10 11l0 6" />
                    <path d="M14 11l0 6" />
                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                </svg>
            </button>
        </div>
    `;
    container.appendChild(fieldDiv);
}

document.addEventListener('DOMContentLoaded', function() {
    // Add field event listeners
    const addHeaderFieldBtn = document.getElementById('addHeaderField');
    const addParamsFieldBtn = document.getElementById('addParamsField');
    const addBodyFieldBtn = document.getElementById('addBodyField');

    if (addHeaderFieldBtn) {
        addHeaderFieldBtn.addEventListener('click', () => {
            addField('headerFields', 'customSmsHeaderKey', 'customSmsHeaderValue', 'Header Key', 'Header Value');
        });
    }

    if (addParamsFieldBtn) {
        addParamsFieldBtn.addEventListener('click', () => {
            addField('paramsFields', 'customSmsParamsKey', 'customSmsParamsValue', 'Parameter Key', 'Parameter Value');
        });
    }

    if (addBodyFieldBtn) {
        addBodyFieldBtn.addEventListener('click', () => {
            addField('bodyFields', 'customSmsBodyKey', 'customSmsBodyValue', 'Body Key', 'Body Value');
        });
    }

    // Remove field event listener
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-field') || e.target.closest('.remove-field')) {
            e.target.closest('.row').remove();
        }
    });

    // Toggle elements - select them after DOM is loaded
    const customSmsToggle = document.querySelector('input[name="customSms"]');
    const customSmsFields = document.getElementById('customSmsFields');
    const firebaseToggle = document.querySelector('input[name="firebase"]');
    const firebaseFields = document.getElementById('firebaseFields');
    const googleLoginToggle = document.querySelector('input[name="googleLogin"]');
    const appleLoginToggle = document.querySelector('input[name="appleLogin"]');

    console.log('Elements found:', {
        customSmsToggle: !!customSmsToggle,
        firebaseToggle: !!firebaseToggle,
        gatewayElement: !!document.getElementById('activeSmsGateway')
    });

    const toggleCustomSmsFields = () => {
        if (customSmsFields) {
            customSmsFields.style.display = customSmsToggle.checked ? 'block' : 'none';
        }
    };

    const toggleFirebaseFields = () => {
        if (firebaseFields) {
            firebaseFields.style.display = firebaseToggle.checked ? 'block' : 'none';
        }
    };

    // Validation function to show SMS gateway priority
    const validateMutuallyExclusiveSettings = () => {
        const customSmsEnabled = customSmsToggle ? customSmsToggle.checked : false;
        const firebaseEnabled = firebaseToggle ? firebaseToggle.checked : false;
        const googleLoginEnabled = googleLoginToggle ? googleLoginToggle.checked : false;

        // Remove any existing warnings
        const warning = document.getElementById('validation-warning');
        if (warning) {
            warning.remove();
        }

        // Enable submit button - no restrictions
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.title = '';
        }

        return true;
    };

    // Function to update SMS gateway display with priority logic
    const updateSmsGatewayDisplay = () => {
        const customSmsEnabled = customSmsToggle ? customSmsToggle.checked : false;
        const firebaseEnabled = firebaseToggle ? firebaseToggle.checked : false;
        const gatewayElement = document.getElementById('activeSmsGateway');

        console.log('updateSmsGatewayDisplay called:', {
            customSmsEnabled,
            firebaseEnabled,
            gatewayElement: !!gatewayElement,
            customSmsToggle: !!customSmsToggle,
            firebaseToggle: !!firebaseToggle
        });

        if (!gatewayElement) {
            console.log('Gateway element not found!');
            return;
        }

        let gateway = '';
        let badgeClass = 'bg-secondary';

        // Custom SMS has priority over Firebase
        if (customSmsEnabled) {
            gateway = 'Custom (Priority)';
            badgeClass = 'bg-success text-white';
        } else if (firebaseEnabled) {
            gateway = 'Firebase';
            badgeClass = 'bg-success text-white';
        } else {
            gateway = 'Not Set';
            badgeClass = 'bg-secondary text-white';
        }

        console.log('Setting gateway to:', gateway, badgeClass);
        gatewayElement.textContent = gateway;
        gatewayElement.className = `badge ${badgeClass}`;
    };

    // Add change event listeners
    if (customSmsToggle) {
        customSmsToggle.addEventListener('change', () => {
            console.log('Custom SMS toggle changed, checked:', customSmsToggle.checked);
            toggleCustomSmsFields();
            validateMutuallyExclusiveSettings();
            updateSmsGatewayDisplay();
        });
    } else {
        console.log('Custom SMS toggle not found!');
    }

    if (firebaseToggle) {
        firebaseToggle.addEventListener('change', () => {
            console.log('Firebase toggle changed, checked:', firebaseToggle.checked);
            toggleFirebaseFields();
            validateMutuallyExclusiveSettings();
            updateSmsGatewayDisplay();
        });
    } else {
        console.log('Firebase toggle not found!');
    }

    if (googleLoginToggle) {
        googleLoginToggle.addEventListener('change', validateMutuallyExclusiveSettings);
    }

    if (appleLoginToggle) {
        appleLoginToggle.addEventListener('change', validateMutuallyExclusiveSettings);
    }

    // Form submission validation
    const form = document.querySelector('.form-submit');
    if (form) {
        form.addEventListener('submit', (e) => {
            if (!validateMutuallyExclusiveSettings()) {
                e.preventDefault();
                const warning = document.getElementById('validation-warning');
                if (warning) {
                    warning.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
        });
    }

    // Initialize with a small delay to ensure all elements are loaded
    setTimeout(() => {
        console.log('Initializing authentication settings...');
        toggleCustomSmsFields();
        toggleFirebaseFields();
        validateMutuallyExclusiveSettings();
        updateSmsGatewayDisplay();
        console.log('Initialization complete');
    }, 100);
});
