import MainLayout from "@/Layouts/MainLayout.vue";

import "./bootstrap";
import { Ziggy } from "./ziggy";

import { Head, Link, createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { i18nVue } from "laravel-vue-i18n";
import { createPinia } from "pinia";
import piniaPluginPersistedstate from "pinia-plugin-persistedstate";
import "remixicon/fonts/remixicon.css";
import { createApp, h } from "vue";
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import { ZiggyVue, route } from "ziggy-js";

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);

createInertiaApp({
    // https://laracasts.com/series/build-modern-laravel-apps-using-inertia-js/episodes/14?reply=22692
    resolve: async (name) => {
        const page = await resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob("./Pages/**/*.vue"));
        page.default.layout = page.default.layout || MainLayout;

        return page;
    },
    progress: {
        // The delay after which the progress bar will appear during navigation, in milliseconds.
        delay: 250,
        // The color of the progress bar.
        color: "#c40d1e",
        // Whether to include the default NProgress styles.
        includeCSS: true,
        // Whether the NProgress spinner will be shown.
        showSpinner: true,
    },
    setup({ el, App, props, plugin }) {
        const quotaToast = Math.floor(Math.random() * 1000000);

        pinia.use(({ store }) => {
            store.$quotaToast = quotaToast;
        });

        const app = createApp({ render: () => h(App, props) });

        app.use(pinia)
            .component("Head", Head)
            .component("Link", Link)
            .use(plugin)
            .use(i18nVue, {
                lang: "de",
                fallback: "en",
                resolve: (lang) => import(`../../lang/php_${lang}.json`),
            })
            .use(ZiggyVue, Ziggy)
            .use(Toast, {
                maxToasts: 10,
                newestOnTop: true,
                position: "bottom-right",
                timeout: 5000,
                closeOnClick: true,
                pauseOnFocusLoss: false,
                pauseOnHover: false,
                draggable: true,
                draggablePercent: 0.6,
                showCloseButtonOnHover: true,
                closeButton: "button",
                icon: true,
                rtl: false,
                filterBeforeCreate: (toast, toasts) => {
                    const isQuotaToast = toast.id === quotaToast;
                    if (isQuotaToast && toasts.filter((toast) => toast.id === quotaToast).length > 0) {
                        return false;
                    }

                    return toast;
                },
            });

        app.provide("ziggyRoute", (name, params, absolute, config = Ziggy) => {
            return route(name, params, absolute, config);
        });

        app.mount(el);
    },
});
