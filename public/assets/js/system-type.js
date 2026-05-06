document.addEventListener('DOMContentLoaded', () => {
    let form = document.getElementById('system-select-form');
    if (!form) {
        console.error("System select form not found.");
        return;
    }
    const singleFields = document.getElementById('single-seller-fields');
    // Toggle single vendor extra fields visibility
    const radios = form.querySelectorAll('input[name="systemVendorType"]');
    const syncSingleFieldsVisibility = () => {
        const selected = form.querySelector('input[name="systemVendorType"]:checked');
        if (!selected) return;
        const isSingle = selected.value && selected.value.toLowerCase().includes('single');
        if (singleFields) {
            singleFields.style.display = isSingle ? '' : 'none';
            // Optionally disable inputs when hidden
            const inputs = singleFields.querySelectorAll('input');
            inputs.forEach(el => {
                if (isSingle) {
                    el.removeAttribute('disabled');
                } else {
                    el.setAttribute('disabled', 'disabled');
                }
            });
        }
    };
    radios.forEach(r => r.addEventListener('change', syncSingleFieldsVisibility));
    // Initialize on load
    syncSingleFieldsVisibility();
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const action = form.getAttribute('action');
        const method = (form.getAttribute('method') || 'POST').toUpperCase();
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        if (!submitButton) {
            console.error("Submit button not found in the form.");
            return;
        }
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
            headers: headers,
            data: formData
        };

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
                setTimeout(() => {
                   window.location.href = '/admin/system-type';
                });
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
                    return Toast.fire({
                        icon: "error",
                        title: "An error occurred while submitting the form."
                    });
                }

            });
    });

});


