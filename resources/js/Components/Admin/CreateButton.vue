<template>
    <Link v-if="hasPermission('create_' + model + 's')"
          :href="route('admin.' + model + '.create')">
        <i class="ri-add-circle-line"></i>
        {{ $t('admin.' + model + 's.index.table.actions.create') }}
    </Link>
</template>

<script setup>
import { useAuthStore } from "@/Stores/AuthStore";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    model: {
        type: String,
        default: '',
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { hasPermission } = authStore;

</script>
