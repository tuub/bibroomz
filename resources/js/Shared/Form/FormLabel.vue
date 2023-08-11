<template>
    <label :for="field" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
        {{ $t(fieldKey + '.label') }}
    </label>
    <div class="text-xs mb-2">{{ $t(fieldKey + '.hint') }}</div>
</template>

<script setup>

let props = defineProps({
    field: String,
    fieldKey: String,
})

</script>
