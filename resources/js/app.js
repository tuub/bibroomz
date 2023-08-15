import './bootstrap';

import {createApp, h} from 'vue'
import {createInertiaApp, Head, Link} from "@inertiajs/vue3";
import {createPinia} from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import mitt from 'mitt'
import MainLayout from "./Layouts/MainLayout.vue";
import {resolvePageComponent} from "laravel-vite-plugin/inertia-helpers";
import {ZiggyVue} from 'ziggy';
import {Ziggy} from './ziggy';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import {i18nVue} from 'laravel-vue-i18n';
import "flag-icons/css/flag-icons.min.css";

const emitter = mitt()

const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)

// FIXME: check cookie for current language

createInertiaApp({
    // https://laracasts.com/series/build-modern-laravel-apps-using-inertia-js/episodes/14?reply=22692
    resolve: async (name) => {
        // Resolve the page component asynchronously
        const page = await resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        );
        // Add the layout to the page component if there is no default layout set
        if (page.layout === undefined) {
            page.default.layout = MainLayout;
        }
        // Return the page component
        return page;
    },
    progress: {
        // The delay after which the progress bar will appear during navigation, in milliseconds.
        delay: 250,
        // The color of the progress bar.
        color: '#c40d1e',
        // Whether to include the default NProgress styles.
        includeCSS: true,
        // Whether the NProgress spinner will be shown.
        showSpinner: true,
    },
    setup({el, App, props, plugin}) {
        const app = createApp({render: () => h(App, props)})
            .provide('emitter', emitter)
            .use(pinia)
            .component('Head', Head)
            .component('Link', Link)
            .use(plugin)
            .use(i18nVue, {
                lang: 'de',
                fallback: 'en',
                resolve: lang => import(`../../lang/php_${lang}.json`),
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
                    const isQuotaToast = (toast) => toast.content.match(/quota limit exceeded/);

                    if (isQuotaToast(toast) && toasts.filter(isQuotaToast).length > 0) {
                        return false;
                    }

                    return toast;
                },
            });

        /*
        app.config.globalProperties.$filters = {
            currencyUSD(value) {
                return '$' + value
            }
        }
        */

        app.mount(el)
    },
})
