/// <reference types="vite/client" />

declare module "*.vue" {
    import type { DefineComponent } from "vue";

    const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;
    export default component;
}

interface Window {
    axios: import("axios").AxiosStatic;
    Pusher: typeof import("pusher-js").default;
    Echo: import("laravel-echo").default<"reverb" | "pusher">;
}

// eslint-disable-next-line no-var
declare var axios: Window["axios"];
// eslint-disable-next-line no-var
declare var Echo: Window["Echo"];
