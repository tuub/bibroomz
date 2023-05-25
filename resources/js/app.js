import './bootstrap';

import { createApp, h } from 'vue'
import {createInertiaApp, Head, Link} from "@inertiajs/vue3";
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import mitt from 'mitt'
import '../css/app.css'
import MainLayout from "./Layouts/MainLayout.vue";
import {resolvePageComponent} from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from 'ziggy';
import { Ziggy } from './ziggy';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

const emitter = mitt()

const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)

createInertiaApp({
    // Default style
    resolve_default: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    // https://laracasts.com/series/build-modern-laravel-apps-using-inertia-js/episodes/13?reply=21564
    resolve_v1: async (name) => {
        let page = await import(`./Pages/${name}.vue`);
        // If not present in the Page, use MainLayout
        page.default.layout ??= MainLayout;
        return page.default;
    },
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
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .provide('emitter', emitter)
            .use(pinia)
            // FIXME: Necessary?
            .component('Head', Head)
            .component('Link', Link)
            .use(plugin)
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
            })
            .mount(el)
    },
    title: title => `Roomz - ${title}`,
})
