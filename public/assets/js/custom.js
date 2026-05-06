const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const panel = document.getElementById('panel') ? document.getElementById('panel').getAttribute('data-panel') : 'admin';

const root = getComputedStyle(document.documentElement);
const primaryColor = root.getPropertyValue('--tblr-primary').trim();

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form.form-submit').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const action = form.getAttribute('action');
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            const originalButtonContent = submitButton.innerHTML;
            submitButton.innerHTML = `<div class="spinner-border text-white me-2" role="status"><span class="visually-hidden">Loading...</span></div> ${originalButtonContent}`;

            // Prepare headers
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            };

            // Prepare axios config
            const config = {
                method: method,
                url: action,
                headers: headers
            };

            if (method === 'GET') {
                // For GET, append form data as query params
                const params = new URLSearchParams(formData).toString();
                config.url = params ? `${action}${action.includes('?') ? '&' : '?'}${params}` : action;
            } else {
                config.data = formData;
            }

            axios(config)
                .then(function (response) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;
                    let data = response.data;

                    if (data.success === false) {
                        return Toast.fire({
                            icon: "error",
                            title: data.message
                        });
                    }
                    try {
                        $('.modal').modal('hide');
                        $('.data-table').DataTable().ajax.reload(null, false);
                    } catch (e) {
                        console.log(e);
                    }
                    clearValidationErrors(form);
                    if (data.data && data.data.redirect_url) {
                        Toast.fire({
                            icon: "success",
                            title: data.message
                        });
                        setTimeout(function () {
                            window.location.href = data.data.redirect_url;
                        }, 2000)
                        return;
                    }
                    return Toast.fire({
                        icon: "success",
                        title: data.message
                    });
                    // Handle success UI update here
                })
                .catch(function (error) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonContent;

                    if (error.response && error.response.status === 422) {
                        // Handle validation errors
                        const validationErrors = error.response.data.data || error.response.data.errors;
                        if (validationErrors) {
                            displayValidationErrors(form, validationErrors);

                            // Show toast with first error or generic message
                            const firstErrorMessage = error.response.data.message ||
                                Object.values(validationErrors).flat()[0] ||
                                "Validation failed";

                            return Toast.fire({
                                icon: "error",
                                title: firstErrorMessage
                            });
                        }
                    }

                    if (error.response && error.response.data && error.response.data.message) {
                        return Toast.fire({
                            icon: "error",
                            title: error.response.data.message
                        });
                    } else {
                        console.error('Error:', error);
                        return Toast.fire({
                            icon: "error",
                            title: "An error occurred while submitting the form."
                        });
                    }

                });
        });
    });

});

document.addEventListener('DOMContentLoaded', () => {
    let loginForm = document.getElementById('login-form');
    loginForm?.addEventListener('submit', function (e) {
        e.preventDefault();

        const action = loginForm.getAttribute('action');
        const formData = new FormData(loginForm);
        const submitButton = loginForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        const originalButtonContent = submitButton.innerHTML;
        console.log(originalButtonContent);
        submitButton.innerHTML = `<div class="spinner-border text-white me-2" role="status"><span class="visually-hidden">Loading...</span></div> ${originalButtonContent}`;


        // Prepare headers
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };

        // Prepare axios config
        const config = {
            method: 'POST',
            url: action,
            headers: headers
        };
        config.data = formData;

        axios(config)
            .then(function (response) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;
                let data = response.data;
                if (data.success === false) {
                    return Toast.fire({
                        icon: "error",
                        title: data.message
                    });
                }
                setTimeout(function () {
                    location.reload();
                }, 1500);
                return Toast.fire({
                    icon: "success",
                    title: data.message
                });
                // Handle success UI update here
            })
            .catch(function (error) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;
                if (error.response && error.response.data && error.response.data.message) {
                    return Toast.fire({
                        icon: "error",
                        title: error.response.data.message
                    });
                } else {
                    console.error('Error:', error);
                    return Toast.fire({
                        icon: "error",
                        title: "An error occurred while submitting the form."
                    });
                }
            });
    });
});


$(document).on('hidden.bs.modal', '.modal', function () {
    const forms = this.querySelectorAll('form.form-submit');
    forms.forEach(function (form) {
        clearValidationErrors(form);
    });
});

// Clear validation errors when modal is shown (optional - for fresh start)
$(document).on('shown.bs.modal', '.modal', function () {
    const forms = this.querySelectorAll('form.form-submit');
    forms.forEach(function (form) {
        clearValidationErrors(form);
    });
});

// Function to handle FilePond validation errors
function handleFilePondValidation(field, filepond, fieldErrors) {
    const filepond_wrapper = field.closest('.filepond--wrapper') || field.parentNode.querySelector('.filepond--wrapper');

    if (filepond_wrapper) {
        // Add error class to FilePond wrapper
        filepond_wrapper.classList.add('filepond-error');

        // Create error message for FilePond
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback d-block'; // d-block to make it visible
        errorDiv.innerHTML = Array.isArray(fieldErrors) ? fieldErrors.join('<br>') : fieldErrors;

        // Insert error message after FilePond wrapper
        if (filepond_wrapper.nextSibling) {
            filepond_wrapper.parentNode.insertBefore(errorDiv, filepond_wrapper.nextSibling);
        } else {
            filepond_wrapper.parentNode.appendChild(errorDiv);
        }
    }

    // Also add error styling to the original input (for consistency)
    field.classList.add('is-invalid');
}

// Function to handle standard field validation
function handleStandardFieldValidation(field, fieldErrors) {
    // Add is-invalid class
    field.classList.add('is-invalid');

    // Check if error message already exists for this field
    let existingError = field.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }

    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.innerHTML = Array.isArray(fieldErrors) ? fieldErrors.join('<br>') : fieldErrors;

    // Insert error message after the field
    if (field.nextSibling) {
        field.parentNode.insertBefore(errorDiv, field.nextSibling);
    } else {
        field.parentNode.appendChild(errorDiv);
    }
}

// Enhanced function to clear previous validation errors (updated for FilePond)
function clearValidationErrors(form) {
    if (!form) return;

    // Remove is-invalid class from all form controls
    form.querySelectorAll('.is-invalid').forEach(function (element) {
        element.classList.remove('is-invalid');
    });

    // Remove FilePond error classes
    form.querySelectorAll('.filepond-error').forEach(function (element) {
        element.classList.remove('filepond-error');
    });

    // Remove any existing error messages
    form.querySelectorAll('.invalid-feedback').forEach(function (element) {
        element.remove();
    });

    // Also remove any error messages that might have different classes
    form.querySelectorAll('.error-message, .field-error, .validation-error').forEach(function (element) {
        element.remove();
    });
}


// Enhanced function to display validation errors
function displayValidationErrors(form, errors) {
    // Clear errors again just to be absolutely sure
    clearValidationErrors(form);

    Object.keys(errors).forEach(function (fieldName) {
        const fieldErrors = errors[fieldName];

        // Find the field element
        let field = form.querySelector(`[name="${fieldName}"]`);

        // Handle array fields (like fieldName[])
        if (!field) {
            field = form.querySelector(`[name="${fieldName}[]"]`);
        }

        // Handle nested fields (like fieldName[0], fieldName[key])
        if (!field) {
            field = form.querySelector(`[name^="${fieldName}"]`);
        }

        if (field) {
            // Check if this is a FilePond field
            const filepond = FilePond.find(field);
            if (filepond) {
                // Handle FilePond validation
                handleFilePondValidation(field, filepond, fieldErrors);
            } else {
                // Handle standard form field validation
                handleStandardFieldValidation(field, fieldErrors);
            }
        }
    });
}

document.getElementById('view-category-offcanvas')?.addEventListener('show.bs.offcanvas', function (event) {
    const triggerButton = event.relatedTarget;
    const categoryId = triggerButton.getAttribute('data-id');
    const url = `${base_url}/${panel}/categories/${categoryId}/edit`

    axios.get(url)
        .then(response => {
            const data = response.data;
            if (data.success) {
                const category = data.data;

                document.getElementById('banner-image').src = category.banner || ''; // Fallback image
                document.getElementById('card-image').src = category.image || ''; // Fallback image
                document.getElementById('icon-image').src = category.icon || ''; // Fallback image
                document.getElementById('active-icon-image').src = category.active_icon || ''; // Fallback image

                // Handle background display
                const backgroundType = category.background_type || 'None';
                document.getElementById('background-type').textContent = backgroundType;

                const backgroundColorDisplay = document.getElementById('background-color-display');
                const backgroundImageDisplay = document.getElementById('background-image-display');

                if (backgroundType === 'color' && category.background_color) {
                    backgroundColorDisplay.style.display = 'block';
                    backgroundImageDisplay.style.display = 'none';
                    document.getElementById('background-color-value').textContent = category.background_color;
                    document.getElementById('background-color-preview').style.backgroundColor = category.background_color;
                } else if (backgroundType === 'image' && category.background_image) {
                    backgroundColorDisplay.style.display = 'none';
                    backgroundImageDisplay.style.display = 'block';
                    document.getElementById('background-image-preview').src = category.background_image;
                } else {
                    backgroundColorDisplay.style.display = 'none';
                    backgroundImageDisplay.style.display = 'none';
                }

                // Handle font color display
                if (category.font_color) {
                    document.getElementById('font-color-value').textContent = category.font_color;
                    document.getElementById('font-color-preview').style.backgroundColor = category.font_color;
                } else {
                    document.getElementById('font-color-value').textContent = 'Not set';
                    document.getElementById('font-color-preview').style.backgroundColor = '#ffffff';
                }

                document.getElementById('category-name').textContent = category.title || '';
                document.getElementById('category-description').textContent = category.description || 'No description available';
                document.getElementById('category-status').textContent = category.status || 'active';
                document.getElementById('category-status').className = `badge ${category.status === 'active' ? 'bg-green-lt' : 'bg-red-lt'} text-uppercase fw-medium`;
                document.getElementById('parent-category').textContent = category.parent !== null ? category.parent.title : 'None';
                document.getElementById('category-commission').textContent = category.commission || '0';
            } else {
                throw new Error('Failed to fetch valid category data');
            }
        })
        .catch(error => {
            console.error('Error fetching category data:', error);
            document.getElementById('banner-image').src = '';
            document.getElementById('card-image').src = '';
            document.getElementById('icon-image').src = '';
            document.getElementById('active-icon-image').src = '';
            document.getElementById('background-type').textContent = 'None';
            document.getElementById('background-color-display').style.display = 'none';
            document.getElementById('background-image-display').style.display = 'none';
            document.getElementById('category-name').textContent = '';
            document.getElementById('category-description').textContent = 'Failed to load category data.';
            document.getElementById('category-status').textContent = '';
            document.getElementById('category-status').className = 'badge bg-red-lt text-uppercase fw-medium';
            document.getElementById('parent-category').textContent = 'None';
            document.getElementById('category-commission').textContent = '0';
        });
});


document.querySelectorAll('.select-all')?.forEach(selectAllCheckbox => {
    selectAllCheckbox.addEventListener('change', function () {
        const groupId = this.getAttribute('data-group-id');
        const checkboxes = document.querySelectorAll(`.permission-checkbox[data-group-id="${groupId}"]`);
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    });
});


function handleDelete(event, selector, urlPrefix, confirmationText) {
    const deleteBtn = event.target.closest(selector);
    if (!deleteBtn) return;

    const id = deleteBtn.getAttribute('data-id');
    Swal.fire({
        title: "Are you sure?",
        html: confirmationText,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: primaryColor,
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.isConfirmed) {
            const url = `${base_url}${urlPrefix}${id}`;
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                        if (data.data && data.data.redirect_url) {
                            setTimeout(function () {
                                window.location.href = data.data.redirect_url;
                            }, 2000)
                        }
                        return Swal.fire("Deleted!", data.message, "success");
                    } else {
                        return Swal.fire("Error!", data.message, "error");
                    }
                })
                .catch(error => {
                    Swal.fire("Error!", "There was a problem deleting the item.", "error");
                });
        }
    });
}


document.addEventListener("DOMContentLoaded", function () {

    const button = document.querySelector('.password-button');
    const input = document.querySelector('.input-group input[name="password"]');
    if (!button || !input) return;
    button.addEventListener('click', function () {
        const digits = "0123456789";
        const letters = "abcdefghijklmnopqrstuvwxyz";
        const specials = "!@#$%^&*()_+-=";
        const allChars = digits + letters + specials;
        let password = '';
        for (let i = 0; i < 8; i++) {
            password += allChars.charAt(Math.floor(Math.random() * allChars.length));
        }
        input.value = password;
    });

    // tom select
    new TomSelect("#select-roles");
});
// password toggle
let passwordInput = document.getElementById("password");
let passwordToggle = document.getElementById("password-toggle");
let passwordConfirmationInput = document.getElementById("password_confirmation");
let passwordConfirmationToggle = document.getElementById("password-confirmation-toggle");
passwordToggle?.addEventListener("click", function () {
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        passwordToggle.textContent = "Hide";
    } else {
        passwordInput.type = "password";
        passwordToggle.textContent = "Show";
    }
})
passwordConfirmationToggle?.addEventListener("click", function () {
    if (passwordConfirmationInput.type === "password") {
        passwordConfirmationInput.type = "text";
        passwordConfirmationToggle.textContent = "Hide";
    } else {
        passwordConfirmationInput.type = "password";
        passwordConfirmationToggle.textContent = "Show";
    }
})


document.addEventListener('click', function (event) {
    // soft Delete seller
    handleDelete(event, '.delete-seller', `/${panel}/sellers/`, 'You are about to delete this Seller. It will be soft deleted.');

    // Delete tax rate
    handleDelete(event, '.delete-tax-rate', `/${panel}/tax-rates/`, 'You are about to delete this Tax Rate.');

    // Delete tax class
    handleDelete(event, '.delete-tax-class', `/${panel}/tax-classes/`, 'You are about to permanently delete this Tax Group.');

    // Delete role
    handleDelete(event, '.delete-role', `/${panel}/roles/`, 'You are about to delete this Role.');

    // Delete system user
    handleDelete(event, '.delete-system-user', `/${panel}/system-users/`, 'You are about to delete this System User.');

    // delete soft store
    handleDelete(event, '.delete-store', `/${panel}/stores/`, 'You are about to delete this Store.');

    handleDelete(event, '.delete-subscription-plan', `/${panel}/subscription-plans/`, 'You are about to delete this Plan.');
});
document.addEventListener('show.bs.modal', function (event) {
    if (event.target.id === 'tax-rate-modal') {
        const triggerButton = event.relatedTarget;
        const taxRateId = triggerButton.getAttribute('data-id');
        let url = `${base_url}/${panel}/tax-rates/${taxRateId}/edit`;

        const form = document.querySelector('.form-submit');
        const modalTitle = document.querySelector('#tax-rate-modal .modal-title');
        const submitButton = document.querySelector('#tax-rate-modal button[type="submit"]');

        if (taxRateId) {

            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(responseData => {
                    const data = responseData.data;
                    // Fill form fields
                    form.querySelector('input[name="title"]').value = data.title || '';
                    form.querySelector('input[name="rate"]').value = data.rate || '';

                    // Change form action to update route
                    form.setAttribute('action', base_url + `/admin/tax-rates/${taxRateId}`);

                    // Update modal title and button
                    modalTitle.textContent = 'Edit Tax Rate';
                    submitButton.textContent = 'Update Tax Rate';
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {

            if (form) form.reset();
            // Remove _method input if it exists
            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) methodInput.parentNode.removeChild(methodInput);

            // Set action for create
            form.setAttribute('action', `${base_url}/${panel}/tax-rates`);
            modalTitle.textContent = 'Add Tax Rate';
            submitButton.textContent = 'Add Tax Rate';
        }
    }
    if (event.target.id === 'tax-class-modal') {
        const triggerButton = event.relatedTarget;
        const taxClassId = triggerButton.getAttribute('data-id');
        let url = `${base_url}/${panel}/tax-classes/${taxClassId}/edit`;

        const form = document.querySelector('.tax-class-form');
        const modalTitle = document.querySelector('#tax-class-modal .modal-title');
        const submitButton = document.querySelector('#tax-class-modal button[type="submit"]');
        const selectElement = document.getElementById('select-tax-rate');
        let tomSelect = selectElement.tomselect;
        if (taxClassId) {

            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(responseData => {
                    const data = responseData.data;
                    // Fill form fields
                    document.getElementById('class-title').value = data.title || '';
                    if (!tomSelect) {
                        tomSelect = new TomSelect(selectElement);
                    }
                    data.tax_rates.forEach(item => {
                        tomSelect.addOption({value: item.id, title: item.title});
                    });
// Optional: set value(s)
                    const allIds = data.tax_rates.map(item => item.id);
                    tomSelect.setValue(allIds);
                    // Change form action to update route
                    form.setAttribute('action', base_url + `/admin/tax-classes/${taxClassId}`);

                    // Update modal title and button
                    modalTitle.textContent = 'Edit Tax Class';
                    submitButton.textContent = 'Update Tax Class';
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {

            if (form) form.reset();
            // Remove _method input if it exists
            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) methodInput.parentNode.removeChild(methodInput);
            tomSelect.clearOptions();
            tomSelect.clear();

            // Set action for create
            form.setAttribute('action', `${base_url}/${panel}/tax-classes`);
            modalTitle.textContent = 'Create Tax Class';
            submitButton.textContent = 'Create Tax Class';
        }
    }
    if (event.target.id === 'role-modal') {
        const triggerButton = event.relatedTarget;
        const roleId = triggerButton.getAttribute('data-id');
        let url = `${base_url}/${panel}/roles/${roleId}/edit`;

        const form = document.querySelector('.form-submit');
        const modalTitle = document.querySelector('#role-modal .modal-title');
        const submitButton = document.querySelector('#role-modal button[type="submit"]');
        if (roleId) {

            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(responseData => {
                    const data = responseData.data;
                    // Fill form fields
                    form.querySelector('input[name="name"]').value = data.name || '';


                    // Change form action to update route
                    form.setAttribute('action', `${base_url}/${panel}/roles/${roleId}`);

                    // Update modal title and button
                    modalTitle.textContent = 'Edit Role';
                    submitButton.textContent = 'Update Role';
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {

            if (form) form.reset();
            form.setAttribute('action', `${base_url}/${panel}/roles`);
            // Remove _method input if it exists
            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) methodInput.parentNode.removeChild(methodInput);

            // Set action for create
            modalTitle.textContent = 'Add New Role';
            submitButton.textContent = 'Add new Role';
        }
    }
    if (event.target.id === 'system-user-modal') {
        const triggerButton = event.relatedTarget;
        const userId = triggerButton.getAttribute('data-id');
        let url = `${base_url}/${panel}/system-users/${userId}/edit`;

        const form = document.querySelector('.form-submit');
        const modalTitle = document.querySelector('#system-user-modal .modal-title');
        const submitButton = document.querySelector('#system-user-modal button[type="submit"]');
        const selectElement = document.getElementById('select-roles');
        let selectRoles = selectElement.tomselect;

        if (userId) {

            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(responseData => {
                    const data = responseData.data;
                    // Fill form fields
                    form.querySelector('input[name="name"]').value = data.user.name || '';
                    form.querySelector('input[name="email"]').value = data.user.email || '';
                    form.querySelector('input[name="mobile"]').value = data.user.mobile || '';
                    form.querySelector('input[name="email"]').disabled = true;
                    let roles = [];
                    data.user.roles.forEach(item => {
                        roles.push(item.name);
                    })
                    selectRoles.setValue(roles);


                    // Change form action to update route
                    form.setAttribute('action', `${base_url}/${panel}/system-users/${userId}`);

                    // Update modal title and button
                    modalTitle.textContent = 'Edit User';
                    submitButton.textContent = 'Update User';
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {

            if (form) form.reset();
            form.querySelector('input[name="name"]').value = '';
            form.querySelector('input[name="mobile"]').value = '';
            form.querySelector('input[name="email"]').value = '';
            form.querySelector('input[name="email"]').disabled = false;
            // Remove _method input if it exists
            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) methodInput.parentNode.removeChild(methodInput);
            selectRoles.clear();
            // Set action for create
            form.setAttribute('action', `${base_url}/${panel}/system-users`);
            modalTitle.textContent = 'Add New User';
            submitButton.textContent = 'Add new User';
        }
    }
});

document.addEventListener("DOMContentLoaded", function () {
    let selectCountries = document.getElementById("select-countries");
    let selectedCountry = document.getElementById('selected-country');

    if (selectCountries) {
        var el;
        window.TomSelect &&
        new TomSelect((el = document.getElementById("select-countries")), {
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: "Search for a country",
            render: {
                item: function (data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
                    }
                    return "<div>" + escape(data.text) + "</div>";
                },
                option: function (data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
                    }
                    return "<div>" + escape(data.text) + "</div>";
                },
            },
            load: function (query, callback) {
                if (!query.length) return callback();
                let url = base_url + "/countries?search=" + encodeURIComponent(query);
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    }).catch(() => {
                    callback();
                });
            }
        });
        if (selectedCountry && selectedCountry.value) {
            let parsed;
            try {
                parsed = JSON.parse(selectedCountry.value);
            } catch {
                parsed = selectedCountry.value;
            }
            loadCountryAndSetValue(selectCountries.tomselect, parsed);
        }
    }

    let selectCurrency = document.getElementById("select-currency");
    let selectedCurrency = document.getElementById('selected-currency');

    if (selectCurrency) {
        var el;
        window.TomSelect &&
        new TomSelect((el = document.getElementById("select-currency")), {
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: "USD, EUR, INR, etc.",
            render: {
                item: function (data, escape) {
                    document.getElementById('currency-symbol').value = data.currency_symbol;
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
                    }
                    return "<div>" + escape(data.text) + "</div>";
                },
                option: function (data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
                    }
                    return "<div>" + escape(data.text) + "</div>";
                },
            },
            load: function (query, callback) {
                if (!query.length) return callback();
                let url = base_url + "/currency?search=" + encodeURIComponent(query);
                axios.get(url)
                    .then(response => {
                        callback(response.data);
                    })
                    .catch(() => {
                        callback();
                    })
            }
        });
        if (selectedCurrency && selectedCurrency.value) {
            loadCurrencyAndSetValue(selectCurrency.tomselect, selectedCurrency.value);
        }
    }

    let selectSeller = document.getElementById("select-seller");
    if (selectSeller) {
        var el;
        window.TomSelect &&
        new TomSelect((el = document.getElementById('select-seller')), {
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: "Search With Seller",
            render: {
                item: function (data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
                    }
                    return "<div>" + escape(data.text) + "</div>";
                },
                option: function (data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
                    }
                    return "<div>" + escape(data.text) + "</div>";
                },
            },
            load: function (query, callback) {
                if (!query.length) return callback();
                let url = `${base_url}/${panel}/sellers/search?search=${encodeURIComponent(query)}`;
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    }).catch(() => {
                    callback();
                });
            }
        });

        // Add event listener for the filter button
        const btnFilter = document.getElementById('btn-filter');
        if (btnFilter) {
            btnFilter.addEventListener('click', function () {
                const storesTable = $('#stores-table').DataTable();
                storesTable.settings()[0].ajax.data = function (d) {
                    const tomSelect = selectSeller.tomselect;
                    if (tomSelect) {
                        let selectedSellerIds = tomSelect.getValue();
                        if (selectedSellerIds && selectedSellerIds.length > 0) {
                            d.seller_id = selectedSellerIds;
                        }
                    }
                    d.visibility_status = $('#visibility-status').val();
                    d.verification_status = $('#verification-status').val();
                };
                storesTable.ajax.reload();
            });
        }
    }
});

async function loadCurrencyAndSetValue(tomSelectInstance, currency) {
    let parentOption = tomSelectInstance.options[currency];
    if (!parentOption) {
        try {
            const res = await fetch(`${base_url}/currency?find=${currency}`);
            const json = await res.json();
            if (json && json.length) {
                tomSelectInstance.addOption(json[0]);
            }
        } catch (error) {
            console.error(error);
        }
    }
    tomSelectInstance.setValue(currency);
}

async function loadCountryAndSetValue(tomSelectInstance, country) {
    // If country is an array, process each item
    if (Array.isArray(country)) {
        const values = [];
        for (const item of country) {
            let parentOption = tomSelectInstance.options[item];
            if (!parentOption) {
                try {
                    const res = await fetch(`${base_url}/countries?find=${item}`);
                    const json = await res.json();
                    if (json && json.length) {
                        tomSelectInstance.addOption(json[0]);
                    }
                } catch (error) {
                    console.error(error);
                }
            }
            values.push(item);
        }
        tomSelectInstance.setValue(values); // Set all selected values
    } else {
        let parentOption = tomSelectInstance.options[country];
        if (!parentOption) {
            try {
                const res = await fetch(`${base_url}/countries?find=${country}`);
                const json = await res.json();
                if (json && json.length) {
                    tomSelectInstance.addOption(json[0]);
                }
            } catch (error) {
                console.error(error);
            }
        }
        tomSelectInstance.setValue(country);
    }
}

try {
    let options = {
        selector: ".hugerte-mytextarea",
        height: 300,
        menubar: false,
        statusbar: false,
        license_key: "gpl",
        plugins: [
            "advlist",
            "autolink",
            "lists",
            "link",
            "image",
            "charmap",
            "preview",
            "anchor",
            "searchreplace",
            "visualblocks",
            "code",
            "fullscreen",
            "insertdatetime",
            "media",
            "table",
            "code",
            "help",
            "wordcount",
        ],
        toolbar:
            "undo redo | formatselect | " +
            "bold italic backcolor | alignleft aligncenter " +
            "alignright alignjustify | bullist numlist outdent indent | " +
            "removeformat",
        content_style:
            "body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }",
    };
    if (localStorage.getItem("tablerTheme") === "dark") {
        options.skin = "oxide-dark";
        options.content_css = "dark";
    }
    hugeRTE.init(options);
} catch (e) {
    console.log(e)
}


document.addEventListener("DOMContentLoaded", function () {
    let selectCategory = document.getElementById("select-category");
    if (selectCategory) {
        let el;
        window.TomSelect && new TomSelect((el = document.getElementById('select-category')), {
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: "Search Category",
            render: {
                item: function (data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
                    }
                    try {
                        const tree = $('#categories-tree').jstree(true);
                        if (tree) {
                            tree.deselect_all();
                            tree.select_node(data.value);
                        }
                    } catch (e) {
                        console.log("Error selecting node in jstree:", e);
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
                let url = `${base_url}/${panel}/categories/search?search=${encodeURIComponent(query)}`;
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    }).catch(() => {
                    callback();
                });
            }
        });
    }

    let selectBrand = document.getElementById("select-brand");
    let selectedBrand = document.getElementById('selected-brand');
    if (selectBrand) {
        let el;
        window.TomSelect && new TomSelect((el = document.getElementById('select-brand')), {
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: "Search Brand",
            render: {
                item: function (data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
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
                let url = `${base_url}/${panel}/brands/search?search=${encodeURIComponent(query)}`;
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    }).catch(() => {
                    callback();
                });
            }
        });
        if (selectedBrand && selectedBrand.value) {
            loadBrandAndSetValue(selectBrand.tomselect, selectedBrand.value);
        }
    }

    let selectTaxGoup = document.getElementById("select-tax-group");


    if (selectTaxGoup) {
        let el;
        window.TomSelect && new TomSelect((el = document.getElementById('select-tax-group')), {
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: "Search Tax Group",
            render: {
                item: function (data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
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
                let url = `${base_url}/${panel}/tax-classes/search?search=${encodeURIComponent(query)}`;
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    }).catch(() => {
                    callback();
                });
            }
        });
    }
    let selectProduct = document.getElementById("select-product");
    if (selectProduct) {
        let el;
        window.TomSelect && new TomSelect((el = document.getElementById('select-product')), {
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: "Search Product",
            render: {
                item: function (data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + "</span>" + escape(data.text) + "</div>";
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
                let url = `${base_url}/${panel}/products/search?search=${encodeURIComponent(query)}`;
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    }).catch(() => {
                    callback();
                });
            }
        });
    }
});

async function loadBrandAndSetValue(tomSelectInstance, brand) {
    try {
        const res = await fetch(`${base_url}/${panel}/brands/search?find=${brand}`);
        const json = await res.json();
        if (json && json.length) {
            tomSelectInstance.addOption(json[0]);
        }
        tomSelectInstance.setValue(brand);
    } catch (error) {
        console.error(error);
    }
}

document.addEventListener("DOMContentLoaded", function () {
    let el = document.getElementById("select-parent-category");

    if (el && window.TomSelect) {
        new TomSelect(el, {
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: "Search Category",
            load: function (query, callback) {
                if (!query.length) return callback();

                let categoryInput = document.querySelector('#category-id');
                let exceptId = categoryInput ? categoryInput.value : '';

                fetch(`${base_url}/admin/categories/search?q=${encodeURIComponent(query)}&exceptId=${encodeURIComponent(exceptId)}`)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    })
                    .catch(() => {
                        callback();
                    });
            },
            render: {
                item: function (data, escape) {
                    return "<div>" + escape(data.text) + "</div>";
                },
                option: function (data, escape) {
                    return "<div>" + escape(data.text) + "</div>";
                },
            },
        });
    } else {
        console.warn("TomSelect init skipped: #select-parent-category not found");
    }
});

document.addEventListener("DOMContentLoaded", function () {
    let el = document.getElementById("select-root-category");
    if (el && window.TomSelect) {
        new TomSelect(el, {
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            valueField: "value",
            labelField: "text",
            searchField: "text",
            placeholder: "Search Category",
            load: function (query, callback) {
                if (!query.length) return callback();
                fetch(`${base_url}/admin/categories/search?q=${encodeURIComponent(query)}&type=root`)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    })
                    .catch(() => {
                        callback();
                    });
            },
            render: {
                item: function (data, escape) {
                    return "<div>" + escape(data.text) + "</div>";
                },
                option: function (data, escape) {
                    return "<div>" + escape(data.text) + "</div>";
                },
            },
        });
    } else {
        console.warn("TomSelect init skipped: #select-root-category not found");
    }
});

function toggleScopeFields() {
    const bannerScopeEl = document.getElementById('scopeType');
    if (!bannerScopeEl)
        return;
    const type = bannerScopeEl.value;
    document.getElementById('scopeCategoryField').style.display = 'none';
    if (type === 'category') {
        document.getElementById('scopeCategoryField').style.display = '';
    }
}

toggleScopeFields();
document.getElementById('scopeType')?.addEventListener('change', toggleScopeFields);

$(document).ready(function () {

    const faqTable = $('#faqs-table').DataTable();

// Reload table when filters change
    $('#statusFilter').on('change', function () {
        faqTable.ajax.reload(null, false);
    });

// Add filter params to AJAX request
    $('#faqs-table').on('preXhr.dt', function (e, settings, data) {
        data.status = $('#statusFilter').val();
    });
});

// Seller store status toggle handler
(document.addEventListener || function (e, t) {
    return window.addEventListener(e, t)
})("DOMContentLoaded", function () {
    try {
        const toggle = document.getElementById('seller-store-status-switch');
        if (toggle) {
            toggle.addEventListener('change', async function () {
                const storeId = this.dataset.storeId;
                const status = this.checked ? 'online' : 'offline';
                const url = `${typeof base_url !== 'undefined' ? base_url : ''}/seller/stores/${storeId}/update-status`;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({status})
                    });
                    const json = await res.json();
                    if (json && json.success) {
                        const label = document.getElementById('seller-store-status-label');
                        if (label) {
                            label.textContent = status === 'online' ? (this.dataset.onlineLabel || 'Online') : (this.dataset.offlineLabel || 'Offline');
                        }
                    } else {
                        alert((json && json.message) ? json.message : (typeof window.i18n_store_status_update_failed !== 'undefined' ? window.i18n_store_status_update_failed : 'Failed to update store status.'));
                        this.checked = !this.checked;
                    }
                } catch (e) {
                    console.error(e);
                    alert(typeof window.i18n_store_status_update_failed !== 'undefined' ? window.i18n_store_status_update_failed : 'Failed to update store status.');
                    this.checked = !this.checked;
                }
            });
        }
    } catch (e) {
        console.error('Error binding seller store status toggle:', e);
    }
});

/**
 * Initialize the daily purchase history chart
 */
function initializeDailyPurchaseChart(purchaseData) {
    if (!window.ApexCharts || !document.getElementById("chart-development-activity")) {
        return;
    }

    // Prepare data for the chart
    const dates = purchaseData.daily.map(item => item.date);
    const orderCounts = purchaseData.daily.map(item => item.order_count);

    new ApexCharts(document.getElementById("chart-development-activity"), {
        chart: {
            type: "area",
            fontFamily: "inherit",
            height: 192,
            sparkline: {
                enabled: true,
            },
            animations: {
                enabled: false,
            },
        },
        dataLabels: {
            enabled: false,
        },
        fill: {
            colors: ["color-mix(in srgb, transparent, var(--tblr-primary) 16%)", "color-mix(in srgb, transparent, var(--tblr-primary) 16%)"],
            type: "solid",
        },
        stroke: {
            width: 2,
            lineCap: "round",
            curve: "smooth",
        },
        series: [
            {
                name: "Order Count",
                data: orderCounts,
            },
        ],
        tooltip: {
            theme: "dark",
            x: {
                format: 'yyyy-MM-dd'
            },
            y: {
                formatter: function (value) {
                    return value + " Items";
                }
            }
        },
        grid: {
            strokeDashArray: 4,
        },
        xaxis: {
            labels: {
                padding: 0,
            },
            tooltip: {
                enabled: false,
            },
            axisBorder: {
                show: false,
            },
            type: "datetime",
        },
        yaxis: {
            labels: {
                padding: 4,
            },
        },
        labels: dates,
        colors: ["color-mix(in srgb, transparent, var(--tblr-primary) 100%)"],
        legend: {
            show: false,
        },
        point: {
            show: false,
        },
    }).render();
}

/**
 * Initialize the revenue chart (replaces traffic summary)
 */
function initializeRevenueChart(revenueData) {

    if (!window.ApexCharts || !document.getElementById("chart-revenue")) {
        return;
    }

    // ✅ Parse and clean
    const dates = revenueData.daily.map(item => {
        // Ensure valid ISO or timestamp format
        return new Date(item.date).toISOString();
    });

    const revenues = revenueData.daily.map(item => {
        // Remove ₹, commas, spaces, etc., and convert to number
        const num = parseFloat(String(item.revenue).replace(/[^\d.-]/g, ""));
        return isNaN(num) ? 0 : num;
    });

    new ApexCharts(document.getElementById("chart-revenue"), {
        chart: {
            type: "area",
            height: 240,
            toolbar: {show: false},
            animations: {enabled: false},
        },
        dataLabels: {enabled: false},
        fill: {opacity: 0.16, type: "solid"},
        stroke: {width: 2, curve: "smooth"},
        series: [
            {
                name: "Daily Revenue",
                data: revenues,
            },
        ],
        xaxis: {
            type: "datetime",
            categories: dates,
            labels: {padding: 0},
        },
        yaxis: {
            labels: {
                formatter: (value) => currencySymbol + value.toLocaleString("en-IN"),
            },
        },
        tooltip: {
            x: {format: "dd MMM yyyy"},
            y: {
                formatter: (value) => currencySymbol + value.toLocaleString("en-IN"),
            },
        },
        colors: ["color-mix(in srgb, transparent, var(--tblr-primary) 100%)"],
        legend: {show: false},
    }).render();
}


/**
 * Initialize the store orders chart (replaces campaigns chart)
 */
function initializeStoreOrdersChart(storeData) {
    if (!window.ApexCharts || !document.getElementById("chart-campaigns")) {
        return;
    }
    try {

        // Prepare data for the chart
        const storeNames = storeData.stores.map(store => store.name);
        const percentages = storeData.stores.map(store => store.percentage);


        new ApexCharts(document.getElementById("chart-campaigns"), {
            chart: {
                type: 'radialBar',
                height: 350,
            },
            plotOptions: {
                radialBar: {
                    size: undefined,
                    inverseOrder: true,
                    hollow: {
                        margin: 5,
                        size: '48%',
                        background: 'transparent',

                    },
                    track: {
                        show: false,
                    },
                    startAngle: -180,
                    endAngle: 180,
                    dataLabels: {
                        total: {
                            show: true,
                            label: "Total Orders",
                            formatter: function () {
                                return storeData.total;
                            },
                        },
                    },

                },
            },
            series: percentages,
            labels: storeNames,
            tooltip: {
                theme: "dark",
                y: {
                    formatter: function (value, {seriesIndex}) {
                        if (storeData.stores.length > 0 && seriesIndex < storeData.stores.length) {
                            return `${storeData.stores[seriesIndex].order_count} orders (${value}%)`;
                        }
                        return `${value}%`;
                    }
                }
            },
            stroke: {
                lineCap: 'round'
            },
            legend: {
                show: true,
                floating: true,
                position: 'right',
                offsetX: 70,
                offsetY: 230
            },
        }).render();
    } catch (error) {
        console.error("Error initializing store orders chart:", error);
    }
}

/**
 * Initialize the revenue background chart
 */
function initializeRevenueBackgroundChart(revenueData) {
    if (!window.ApexCharts || !document.getElementById("chart-revenue-bg")) {
        return;
    }

    const revenues = revenueData.daily.map(item => item.revenue);

    new ApexCharts(document.getElementById("chart-revenue-bg"), {
        chart: {
            type: "area",
            fontFamily: "inherit",
            height: 40,
            sparkline: {
                enabled: true,
            },
            animations: {
                enabled: false,
            },
        },
        dataLabels: {
            enabled: false,
        },
        fill: {
            colors: ["color-mix(in srgb, transparent, var(--tblr-primary) 16%)", "color-mix(in srgb, transparent, var(--tblr-primary) 16%)"],
            opacity: 0.16,
            type: "solid",
        },
        stroke: {
            width: 2,
            lineCap: "round",
            curve: "smooth",
        },
        series: [
            {
                name: "Revenue",
                data: revenues,
            },
        ],
        tooltip: {
            enabled: false,
        },
        grid: {
            strokeDashArray: 4,
        },
        xaxis: {
            labels: {
                show: false,
            },
            tooltip: {
                enabled: false,
            },
            axisBorder: {
                show: false,
            },
        },
        yaxis: {
            labels: {
                show: false,
            },
        },
        colors: ["color-mix(in srgb, transparent, var(--tblr-primary) 100%)"],
        legend: {
            show: false,
        },
    }).render();
}

/**
 * Initialize the wallet balance chart
 */
function initializeWalletChart() {
    if (!window.ApexCharts || !document.getElementById("chart-new-clients")) {
        return;
    }

    new ApexCharts(document.getElementById("chart-new-clients"), {
        chart: {
            type: "line",
            fontFamily: "inherit",
            height: 40,
            sparkline: {
                enabled: true,
            },
            animations: {
                enabled: false,
            },
        },
        fill: {
            opacity: 1,
        },
        stroke: {
            width: 2,
            lineCap: "round",
            curve: "smooth",
        },
        series: [
            {
                name: "Wallet Activity",
                data: [6, 15, 13, 13, 5, 7, 17, 20, 19],
            },
        ],
        tooltip: {
            enabled: false,
        },
        grid: {
            strokeDashArray: 4,
        },
        xaxis: {
            labels: {
                show: false,
            },
            tooltip: {
                enabled: false,
            },
            axisBorder: {
                show: false,
            },
        },
        yaxis: {
            labels: {
                show: false,
            },
        },
        colors: ["color-mix(in srgb, transparent, var(--tblr-primary) 100%)"],
        legend: {
            show: false,
        },
    }).render();
}

/**
 * Initialize star ratings for feedback
 */
function initializeStarRatings() {
    if (typeof StarRating === 'undefined') {
        return;
    }

    document.querySelectorAll('.rating-stars').forEach(function (element) {
        const rating = new StarRating(element, {
            tooltip: false,
            clearable: false,
            readOnly: true,
            initialRating: element.dataset.rating,
            stars: function (el, item, index) {
                el.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon gl-star-full icon-1"><path d="M8.243 7.34l-6.38 .925l-.113 .023a1 1 0 0 0 -.44 1.684l4.622 4.499l-1.09 6.355l-.013 .11a1 1 0 0 0 1.464 .944l5.706 -3l5.693 3l.1 .046a1 1 0 0 0 1.352 -1.1l-1.091 -6.355l4.624 -4.5l.078 -.085a1 1 0 0 0 -.633 -1.62l-6.38 -.926l-2.852 -5.78a1 1 0 0 0 -1.794 0l-2.853 5.78z" /></svg>`;
            },
            classNames: {},
        });
    });
}

/**
 * Update sales data in the UI
 */
function updateSalesData(data) {
    // Update conversion rate
    const rateElement = document.querySelector('.card:nth-child(1) .h1.mb-3');
    if (rateElement) {
        rateElement.textContent = `${data.rate}%`;
    }

    // Update trend percentage
    const trendElement = document.querySelector('.card:nth-child(1) .d-flex.mb-2 .ms-auto span');
    if (trendElement) {
        trendElement.textContent = `${Math.abs(data.percentage_change)}%`;
        trendElement.className = `text-${data.is_increase ? 'green' : 'red'} d-inline-flex align-items-center lh-1`;

        // Update trend icon
        const trendIcon = trendElement.querySelector('svg');
        if (trendIcon) {
            const paths = trendIcon.querySelectorAll('path');
            if (paths.length >= 2) {
                if (data.is_increase) {
                    paths[0].setAttribute('d', 'M3 17l6 -6l4 4l8 -8');
                    paths[1].setAttribute('d', 'M14 7l7 0l0 7');
                } else {
                    paths[0].setAttribute('d', 'M3 7l6 6l4 -4l8 8');
                    paths[1].setAttribute('d', 'M21 7l0 7l-7 0');
                }
            }
        }
    }

    // Update delivered orders text
    const detailsElement = document.querySelector('.card:nth-child(1) .text-secondary.mb-2');
    if (detailsElement) {
        detailsElement.textContent = `${data.delivered_orders} Delivered out of total orders ${data.total_orders}`;
    }

    // Update progress bar
    const progressBar = document.querySelector('.card:nth-child(1) .progress-bar');
    if (progressBar) {
        progressBar.style.width = `${data.rate}%`;
        progressBar.setAttribute('aria-valuenow', data.rate);
        progressBar.setAttribute('aria-label', `${data.rate}% Complete`);

        const progressBarText = progressBar.querySelector('span');
        if (progressBarText) {
            progressBarText.textContent = `${data.rate}% Complete`;
        }
    }
}

/**
 * Update revenue data in the UI
 */
function updateRevenueData(data) {
    // Update total revenue
    const revenueTotalElement = document.getElementById('revenue-total');
    if (revenueTotalElement) {
        revenueTotalElement.textContent = data.formatted_total;
    }

    // Update days
    const revenueDaysElement = document.getElementById('revenue-days');
    if (revenueDaysElement) {
        revenueDaysElement.innerHTML = `${data.daily.length} Days <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon ms-1 icon-2"><path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z"/><path d="M16 3v4"/><path d="M8 3v4"/><path d="M4 11h16"/><path d="M11 15h1"/><path d="M12 15v3"/></svg>`;
    }

    // Update revenue background chart if needed
    if (typeof initializeRevenueBackgroundChart === 'function') {
        // Clear existing chart
        const chartElement = document.getElementById('chart-revenue-bg');
        if (chartElement) {
            while (chartElement.firstChild) {
                chartElement.removeChild(chartElement.firstChild);
            }

            // Reinitialize chart with new data
            initializeRevenueBackgroundChart(data);
        }
    }
}

/**
 * Update active users data in the UI
 */
function updateActiveUsersData(data) {
    // Update active users count
    const countElement = document.querySelector('.card:nth-child(4) .h1.mb-3.me-2');
    if (countElement) {
        countElement.textContent = data.count;
    }

    // Update trend percentage
    const trendElement = document.querySelector('.card:nth-child(4) .d-flex.align-items-baseline .ms-auto span');
    if (trendElement) {
        trendElement.textContent = `${Math.abs(data.percentage_change)}%`;
        trendElement.className = `text-${data.is_increase ? 'green' : 'red'} d-inline-flex align-items-center lh-1`;

        // Update trend icon
        const trendIcon = trendElement.querySelector('svg');
        if (trendIcon) {
            const paths = trendIcon.querySelectorAll('path');
            if (paths.length >= 2) {
                if (data.is_increase) {
                    paths[0].setAttribute('d', 'M3 17l6 -6l4 4l8 -8');
                    paths[1].setAttribute('d', 'M14 7l7 0l0 7');
                } else {
                    paths[0].setAttribute('d', 'M3 7l6 6l4 -4l8 8');
                    paths[1].setAttribute('d', 'M21 7l0 7l-7 0');
                }
            }
        }
    }

    // Update active users chart if needed
    // This would require implementing a chart initialization function similar to the others
}

$(document).ready(function () {
    try {
        const catEl = document.getElementById('deliveryBoySearch');
        if (catEl) {
            window.TomSelect && new TomSelect(catEl, {
                copyClassesToDropdown: false,
                dropdownParent: 'body',
                controlInput: '<input>',
                valueField: 'value',
                labelField: 'text',
                searchField: 'text',
                placeholder: 'Delivery Boy',
                load: function (query, callback) {
                    if (!query.length) return callback();
                    const url = `${base_url}/${panel}/delivery-boys/search?search=${encodeURIComponent(query)}`;
                    fetch(url)
                        .then(response => response.json())
                        .then(json => callback(json))
                        .catch(() => callback());
                }
            });
        }
    } catch (e) {
        console.error(e);
    }
});
