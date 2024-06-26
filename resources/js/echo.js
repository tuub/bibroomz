import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: import.meta.env.VITE_BROADCAST_DRIVER ?? "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY ?? import.meta.env.VITE_PUSHER_APP_KEY ?? "bibroomz",
    wsHost:
        import.meta.env.VITE_REVERB_HOST ??
        import.meta.env.VITE_PUSHER_HOST ??
        new URL(import.meta.env.VITE_API_URL).hostname,
    wsPort:
        import.meta.env.VITE_REVERB_PORT ??
        import.meta.env.VITE_PUSHER_PORT ??
        new URL(import.meta.env.VITE_API_URL).port ??
        80,
    wssPort:
        import.meta.env.VITE_REVERB_PORT ??
        import.meta.env.VITE_PUSHER_PORT ??
        new URL(import.meta.env.VITE_API_URL).port ??
        443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? import.meta.env.VITE_PUSHER_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
});
