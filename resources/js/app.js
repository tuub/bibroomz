import MainLayout from "@/Layouts/MainLayout.vue";

import "./bootstrap";
import { Ziggy } from "./ziggy";

import { Head, Link, createInertiaApp } from "@inertiajs/vue3";
import Aura from "@primevue/themes/aura";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { i18nVue } from "laravel-vue-i18n";
import { createPinia } from "pinia";
import piniaPluginPersistedstate from "pinia-plugin-persistedstate";
import BadgeDirective from "primevue/badgedirective";
import PrimeVue from "primevue/config";
import Ripple from "primevue/ripple";
import StyleClass from "primevue/styleclass";
import ToastService from "primevue/toastservice";
import Tooltip from "primevue/tooltip";
import "remixicon/fonts/remixicon.css";
import { createApp, h } from "vue";
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
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin)
            // PrimeVue
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: "system",
                        cssLayer: {
                            name: "primevue",
                            order: "tailwind-base, primevue, tailwind-utilities",
                        },
                        ripple: true,
                    },
                },
            })
            .use(ToastService)
            .directive("tooltip", Tooltip)
            .directive("badge", BadgeDirective)
            .directive("ripple", Ripple)
            .directive("styleclass", StyleClass)
            // Pinia
            .use(pinia)
            // i18n
            .use(i18nVue, {
                lang: "de",
                fallback: "en",
                resolve: (lang) => import(`../../lang/php_${lang}.json`),
            })
            // Ziggy
            .use(ZiggyVue, Ziggy)
            .provide("ziggyRoute", (name, params, absolute, config = Ziggy) => {
                return route(name, params, absolute, config);
            })
            // Custom components
            .component("Head", Head)
            .component("Link", Link);

        app.mount(el);
    },
});
