<template>
    <Head>
        <title>Roomz</title>
        <meta
            type="description"
            content="Roomz - Raumverwaltung"
            head-key="description">
    </Head>
    <section id="header" class="p-6 bg-gray-200">
        <Navigation></Navigation>
    </section>
    <section id="content" class="p-6">
        <slot />
    </section>
</template>

<script>
import Navigation from "../Shared/Navigation.vue";

export default {
    components: {
        Navigation,
    }
}
</script>
