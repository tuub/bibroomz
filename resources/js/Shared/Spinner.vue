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
const imageUrl = "/images/spinner_" + props.size + ".gif";
</script>
