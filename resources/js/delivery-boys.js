import { Modal } from 'bootstrap';
import QRCode from 'qrcode';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

async function readJsonResponse(response, fallbackMessage) {
    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message || fallbackMessage);
    }

    return result;
}

document.querySelectorAll('.js-rider-approval').forEach((button) => {
    button.addEventListener('click', () => {
        const status = button.dataset.status || 'not requested';
        const approvedUntil = button.dataset.approvedUntil || '';
        const isApproved = status === 'approved'
            && approvedUntil
            && new Date(approvedUntil.replace(' ', 'T')).getTime() > Date.now();
        const statusElement = document.getElementById('riderApprovalStatus');
        const form = document.getElementById('riderApprovalForm');
        const approveButton = document.getElementById('riderApproveButton');
        const disapproveButton = document.getElementById('riderDisapproveButton');

        document.getElementById('riderApprovalName').textContent = button.dataset.name || '';
        statusElement.textContent = isApproved
            ? `Approved until ${approvedUntil}`
            : status === 'approved' ? 'Expired' : status.replaceAll('_', ' ');
        statusElement.className = `badge ${isApproved ? 'bg-green-lt' : status === 'pending' ? 'bg-yellow-lt' : 'bg-red-lt'}`;
        form.action = button.dataset.disapproveUrl;
        approveButton.formAction = button.dataset.approveUrl;
        disapproveButton.formAction = button.dataset.disapproveUrl;
        Modal.getOrCreateInstance(document.getElementById('riderApprovalModal')).show();
    });
});

document.querySelectorAll('.js-rider-edit').forEach((button) => {
    button.addEventListener('click', () => {
        const form = document.getElementById('editRiderForm');
        form.action = button.dataset.updateUrl;
        document.getElementById('edit_rider_name').value = button.dataset.name || '';
        document.getElementById('edit_rider_email').value = button.dataset.email || '';
        document.getElementById('edit_rider_mobile').value = button.dataset.mobile || '';
        document.getElementById('edit_rider_zone').value = button.dataset.zone || '';
        document.getElementById('edit_rider_password').value = '';
        Modal.getOrCreateInstance(document.getElementById('editRiderModal')).show();
    });
});

document.querySelectorAll('.js-rider-delete').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById('deleteRiderForm').action = button.dataset.deleteUrl;
        document.getElementById('deleteRiderName').textContent = button.dataset.name || '';
        Modal.getOrCreateInstance(document.getElementById('deleteRiderModal')).show();
    });
});

const qrModalElement = document.getElementById('riderQrModal');
const qrModal = qrModalElement ? Modal.getOrCreateInstance(qrModalElement) : null;
const qrCanvas = document.getElementById('riderQrCanvas');
const qrLoading = document.getElementById('riderQrLoading');
const qrExpiry = document.getElementById('riderQrExpiry');
const qrError = document.getElementById('riderQrError');
const qrDuration = document.getElementById('riderQrDuration');
let activeQrButton = null;
let qrRefreshTimer = null;

async function requestQr(url, method, durationHours = null) {
    const options = {
        method,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
    };

    if (method === 'POST') {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify({ duration_hours: durationHours });
    }

    const response = await fetch(url, options);
    return readJsonResponse(response, 'Could not load this rider QR credential.');
}

async function renderQr(result) {
    await QRCode.toCanvas(qrCanvas, result.data.qr_payload, { width: 240, margin: 2 });
    qrCanvas.hidden = false;
    qrLoading.hidden = true;
    qrExpiry.textContent = `Valid until ${new Date(result.data.expires_at).toLocaleString()}`;
    qrError.textContent = '';

    clearTimeout(qrRefreshTimer);
    const delay = new Date(result.data.expires_at).getTime() - Date.now() + 1000;
    qrRefreshTimer = setTimeout(() => {
        if (qrModalElement.classList.contains('show') && activeQrButton) {
            loadCurrentQr();
        }
    }, Math.max(1000, delay));
}

async function loadCurrentQr() {
    if (!activeQrButton) return;
    qrCanvas.hidden = true;
    qrLoading.hidden = false;
    qrLoading.textContent = 'Loading QR credential…';
    qrExpiry.textContent = '';
    qrError.textContent = '';

    try {
        const result = await requestQr(activeQrButton.dataset.showUrl, 'GET');
        await renderQr(result);
    } catch (error) {
        qrLoading.hidden = true;
        qrError.textContent = error.message;
    }
}

document.querySelectorAll('.js-rider-qr').forEach((button) => {
    button.addEventListener('click', () => {
        activeQrButton = button;
        clearTimeout(qrRefreshTimer);
        qrCanvas.hidden = true;
        qrLoading.hidden = false;
        qrLoading.textContent = button.dataset.mode === 'regenerate'
            ? 'Load the current QR first, then regenerate it with the selected validity.'
            : 'Loading QR credential…';
        document.getElementById('riderQrTitle').textContent = `${button.dataset.name} — rider login QR`;
        qrDuration.value = qrDuration.max;
        qrError.textContent = '';
        qrModal.show();
        loadCurrentQr();
    });
});

document.getElementById('riderQrRotate')?.addEventListener('click', async () => {
    if (!activeQrButton) return;
    const durationHours = Number(qrDuration.value);
    if (!qrDuration.reportValidity()) return;
    if (!Number.isInteger(durationHours) || durationHours < 1 || durationHours > Number(qrDuration.max)) {
        qrDuration.setCustomValidity(`Choose between 1 and ${qrDuration.max} hours.`);
        qrDuration.reportValidity();
        qrDuration.setCustomValidity('');
        return;
    }

    qrCanvas.hidden = true;
    qrLoading.hidden = false;
    qrLoading.textContent = 'Generating a new QR credential…';
    qrExpiry.textContent = '';
    qrError.textContent = '';
    clearTimeout(qrRefreshTimer);

    try {
        const result = await requestQr(activeQrButton.dataset.rotateUrl, 'POST', durationHours);
        await renderQr(result);
    } catch (error) {
        qrLoading.hidden = true;
        qrError.textContent = error.message;
    }
});

qrModalElement?.addEventListener('hidden.bs.modal', () => {
    clearTimeout(qrRefreshTimer);
    activeQrButton = null;
});
