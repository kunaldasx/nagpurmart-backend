importScripts("https://www.gstatic.com/firebasejs/11.0.1/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/11.0.1/firebase-messaging-compat.js");

async function initFirebase() {
    try {
        const response = await fetch('/api/settings/firebase-config');
        const firebaseConfig = await response.json();


        firebase.initializeApp(firebaseConfig.data)

        const messaging = firebase.messaging();

        messaging.onBackgroundMessage((payload) => {
            console.log('[firebase-messaging-sw.js] Background message:', payload);

            const notificationTitle = payload.notification?.title || 'New Notification';
            const notificationOptions = {
                body: payload.notification?.body,
                icon: '/favicon.ico',
            };

            self.registration.showNotification(notificationTitle, notificationOptions);
        });

    } catch (error) {
        console.error('Firebase SW init error:', error);
    }
}

initFirebase();
