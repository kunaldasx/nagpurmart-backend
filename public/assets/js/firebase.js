import {initializeApp} from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js";
import {getMessaging, getToken, onMessage} from "https://www.gstatic.com/firebasejs/11.0.1/firebase-messaging.js";

async function initFirebase() {
    try {
        // 🔹 Check if config is cached
        let firebaseConfig = JSON.parse(localStorage.getItem('firebase_config'));

        // 🔹 If not found, call API once
        if (!firebaseConfig) {
            const { data } = await axios.get('/api/settings/firebase-config');
            firebaseConfig = data.data;
            localStorage.setItem('firebase_config', JSON.stringify(firebaseConfig));
        }

        // 🔹 Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        // 🔹 Ask for notification permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            console.warn('Notification permission not granted');
            return;
        }

        // 🔹 Fetch FCM token
        const vapidKey = firebaseConfig.vapidKey;
        const token = await getToken(messaging, {vapidKey});
        localStorage.setItem('fcm_token', token);

        // 🔹 Listen for messages when tab is active
        onMessage(messaging, (payload) => {
            console.log('Message received in foreground:', payload);

            const { title, body, image } = payload.notification || {};

            // Create toast container if not already present
            let toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toastContainer';
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }

            // Create toast element
            const toastEl = document.createElement('div');
            toastEl.className = 'toast align-items-center text-bg-blue border-0 show mb-2 shadow';
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');

            // Toast inner HTML
            toastEl.innerHTML = `
        <div class="toast-header">
            ${image ? `<img src="${image}" class="rounded me-2" alt="Notification Image" style="width:30px;height:30px;object-fit:cover;">` : ''}
            <strong class="me-auto">${title || 'Notification'}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${body || ''}
        </div>
    `;

            toastContainer.appendChild(toastEl);

            // Play custom notification sound (foreground only)
            try {
                const audio = new Audio('/assets/sound/notification.wav');
                audio.volume = 1.0;
                // Attempt to play immediately; browsers may block if not user-initiated
                audio.play().catch((err) => {
                    // As a fallback, try after a brief user interaction or show a console hint
                    console.warn('Autoplay blocked for notification sound:', err);
                });
            } catch (e) {
                console.warn('Failed to play notification sound:', e);
            }

            // Show using Bootstrap's JS
            // const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            // toast.show();
        });


    } catch (err) {
        console.error('Error initializing Firebase:', err);
    }
}

initFirebase();
