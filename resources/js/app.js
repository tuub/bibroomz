import './bootstrap';

import { createApp, h } from 'vue'
import {createInertiaApp, Head, Link, usePage} from "@inertiajs/vue3";
import App from './Pages/App.vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import mitt from 'mitt'
import '../css/app.css'
import VueFinalModal from 'vue-final-modal';
import HappeningInfo from "./Components/HappeningInfo.vue";
import UserInfo from "./Components/UserInfo.vue";
import MainLayout from "./Layouts/MainLayout.vue";
import {resolvePageComponent} from "laravel-vite-plugin/inertia-helpers";

const emitter = mitt()

const opts = {
    key: "$vfm",
    componentName: "VueFinalModal",
    dynamicContainerName: "ModalsContainer"
};

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
            .use(VueFinalModal)
            .use(pinia)
            // FIXME: Necessary?
            .component('Head', Head)
            .component('Link', Link)
            .use(plugin)
            .mount(el)
    },
    title: title => `Roomz - ${title}`,
})
