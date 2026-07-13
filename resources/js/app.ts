import { useTheme } from "@/Composables/Theme";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import "@/bootstrap";
import { stripEmpty } from "@/stripEmpty";
import { Ziggy } from "@/ziggy";

import { Head, Link, createInertiaApp } from "@inertiajs/vue3";
//import { definePreset } from "@primevue/themes";
import Material from "@primevue/themes/material";
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
import type { DefineComponent } from "vue";
import { createApp, h } from "vue";
import type { Config } from "ziggy-js";
import { ZiggyVue, route } from "ziggy-js";

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);

createInertiaApp({
    // https://laracasts.com/series/build-modern-laravel-apps-using-inertia-js/episodes/14?reply=22692
    // Cast needed because @inertiajs/vue3's ComponentResolver type doesn't account for
    // async resolvers returning the Vite module namespace object ({ default: Component }).
    resolve: (async (name: string) => {
        const page = await resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<{ default: DefineComponent }>("./Pages/**/*.vue"),
        );
        page.default.layout = page.default.layout || (name.startsWith("Admin/") ? AdminLayout : MainLayout);

        return page;
    }) as unknown as (name: string) => DefineComponent,
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

        /*
        const TubPreset = definePreset(Material, {
            components: {
                // custom button tokens and additional style
                button: {
                    padding: {
                        x: '50px',
                        y: '20px',
                    },
                }
            }
        });
        */

        app.use(plugin)
            // PrimeVue
            .use(PrimeVue, {
                theme: {
                    preset: Material,
                    options: {
                        darkModeSelector: ".dark",
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
                fallbackLang: "en",
                resolve: async (lang: string) => {
                    const mod = await import(`../../lang/php_${lang}.json`);
                    return { default: stripEmpty(mod.default ?? mod) };
                },
            })
            // Ziggy
            .use(ZiggyVue, Ziggy as Config)
            .provide(
                "ziggyRoute",
                (name: string, params?: unknown, absolute?: boolean, config: Config = Ziggy as Config) => {
                    return route(name, params as never, absolute, config);
                },
            )
            // Custom components
            .component("Head", Head)
            .component("Link", Link);

        useTheme().init();

        app.mount(el);
    },
});
