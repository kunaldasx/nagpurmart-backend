document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('subscription-feature-form');

    if (!form) {
        console.error('Subscription feature form not found.');
        return;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const action = form.getAttribute('action');
        const method = (form.getAttribute('method') || 'POST').toUpperCase();
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');

        if (!submitButton) {
            console.error('Submit button not found in the form.');
            return;
        }

        submitButton.disabled = true;
        const originalButtonContent = submitButton.innerHTML;
        submitButton.innerHTML = `<div class="spinner-border text-white me-2" role="status"><span class="visually-hidden">Loading...</span></div> ${originalButtonContent}`;

        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };

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

                const data = response.data;

                if (data.success === false) {
                    return Toast.fire({
                        icon: 'error',
                        title: data.message
                    });
                }

                if (data.data && data.data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = data.data.redirect_url;
                    }, 1500);
                }

                return Toast.fire({
                    icon: 'success',
                    title: data.message
                });
            })
            .catch(function (error) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;

                if (error.response && error.response.status === 422) {
                    const validationErrors = error.response.data.data || error.response.data.errors;

                    if (validationErrors) {
                        const firstErrorMessage = error.response.data.message ||
                            Object.values(validationErrors).flat()[0] ||
                            'Validation failed';

                        return Toast.fire({
                            icon: 'error',
                            title: firstErrorMessage
                        });
                    }
                }

                if (error.response && error.response.data && error.response.data.message) {
                    return Toast.fire({
                        icon: 'error',
                        title: error.response.data.message
                    });
                }

                return Toast.fire({
                    icon: 'error',
                    title: 'An error occurred while submitting the form.'
                });
            });
    });
});
