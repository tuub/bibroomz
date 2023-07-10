<template>
    <!-- https://loading.io/ -->
    <div>
        <img :src="imageUrl" />
    </div>
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    size: String,
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
let imageUrl = '/images/spinner_' + props.size + '.gif'
</script>
