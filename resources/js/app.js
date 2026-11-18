import { createApp } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import LiveNotifications from './components/LiveNotifications.vue';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'eu',
    wsHost: import.meta.env.VITE_PUSHER_HOST || '127.0.0.1',
    wsPort: Number(import.meta.env.VITE_PUSHER_PORT || 8080),
    forceTLS: false,
    enabledTransports: ['ws'],
    disableStats: true,
});
createApp(LiveNotifications).mount('#live-notifications');
