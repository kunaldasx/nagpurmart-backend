import QRCode from 'qrcode';

document.querySelectorAll('.js-rider-qr').forEach((button) => {
    let refreshTimer;

    button.addEventListener('click', async () => {
        clearTimeout(refreshTimer);
        const modalElement = document.getElementById('riderQrModal');
        const errorElement = document.getElementById('riderQrError');
        const expiryElement = document.getElementById('riderQrExpiry');
        const canvas = document.getElementById('riderQrCanvas');
        errorElement.textContent = '';
        expiryElement.textContent = 'Loading QR credential…';
        document.getElementById('riderQrTitle').textContent = `${button.dataset.name} — rider login QR`;

        try {
            const modal = window.bootstrap?.Modal;
            if (!modal) {
                throw new Error('QR display is unavailable because the seller dialog failed to load.');
            }
            modal.getOrCreateInstance(modalElement).show();

            const response = await fetch(button.dataset.url, {
                method: button.dataset.method || 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Could not load this rider QR credential.');
            }

            await QRCode.toCanvas(canvas, result.data.qr_payload, { width: 240, margin: 2 });
            expiryElement.textContent = `Valid until ${new Date(result.data.expires_at).toLocaleString()}`;
            refreshTimer = setTimeout(() => {
                if (modalElement.classList.contains('show')) {
                    button.click();
                }
            }, Math.max(1000, new Date(result.data.expires_at).getTime() - Date.now() + 1000));
        } catch (error) {
            expiryElement.textContent = '';
            errorElement.textContent = error.message;
        }
    });
});
