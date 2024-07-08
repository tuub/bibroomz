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
    size: {
        type: String,
        required: true,
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const baseUrl = import.meta.env.VITE_API_URL;
const imageUrl = baseUrl + "/images/spinner_" + props.size + ".gif";
</script>
