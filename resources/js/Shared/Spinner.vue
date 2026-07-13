<template>
    <!-- https://loading.io/ -->
    <div>
        <img :src="imageUrl" />
    </div>
</template>

<script setup lang="ts">
import { withBaseUrl } from "@/baseUrl";

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
const imageUrl = withBaseUrl(`/images/spinner_${props.size}.gif`);
</script>
