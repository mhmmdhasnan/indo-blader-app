import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Local dev (`composer dev`) runs Reverb on its own port (8090) separate
// from the page (8000), so VITE_REVERB_HOST is set explicitly there and
// used as-is. In production nginx proxies Reverb's /app/ path on the exact
// same host/port as the page itself (see docker/nginx/default.conf), so
// those env vars are left unset and the connection is derived from
// window.location instead — the built bundle then works unchanged whether
// the page loads over the LAN IP or (later) a Cloudflare Tunnel domain,
// with no rebuild needed when that domain is added.
const explicitHost = import.meta.env.VITE_REVERB_HOST;
const onHttps = window.location.protocol === 'https:';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: explicitHost || window.location.hostname,
    wsPort: explicitHost
        ? (import.meta.env.VITE_REVERB_PORT ?? 80)
        : (window.location.port || (onHttps ? 443 : 80)),
    wssPort: explicitHost
        ? (import.meta.env.VITE_REVERB_PORT ?? 443)
        : (window.location.port || 443),
    forceTLS: explicitHost
        ? (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https'
        : onHttps,
    enabledTransports: ['ws', 'wss'],
});
