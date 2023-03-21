<template>
    <Head>
        <title>Roomz</title>
        <meta
            type="description"
            content="Roomz - Raumverwaltung"
            head-key="description">
    </Head>
    <section id="header" class="p-6 bg-gray-200">
        <header class="flex justify-between">
            <h1 class="text-2xl font-bold">ROOMZ</h1>
            <Navigation></Navigation>
        </header>
    </section>
    <section id="content" class="p-6">
        <slot />
    </section>
    <modals-container></modals-container>
</template>

<script>
import Navigation from "../Shared/Navigation.vue";

export default {
    components: {
        Navigation,
    }
}
</script>
