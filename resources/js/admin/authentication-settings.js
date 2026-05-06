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
            badgeClass = 'bg-success';
        } else if (firebaseEnabled) {
            gateway = 'Firebase';
            badgeClass = 'bg-success';
        } else {
            gateway = 'Not Set';
            badgeClass = 'bg-secondary';
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
