<template>
    <!-- https://loading.io/ -->
    <div>
        <img :src="imageUrl" />
    </div>
</template>

<script setup>
const props = defineProps({
    size: String,
});

let imageUrl = '/images/spinner_' + props.size + '.gif'
</script>
