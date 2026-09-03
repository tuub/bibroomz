<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    value: unknown[] | null | undefined;
    options: unknown[] | null | undefined;
}>();

const allSelected = computed(
    () => (props.options?.length ?? 0) > 0 && (props.value?.length ?? 0) === (props.options?.length ?? 0),
);
</script>

<template>
    <div
        class="text-app-muted pointer-events-none absolute top-3 left-[46px] flex h-[18px] items-center text-xs font-medium"
        data-test="multiselect-toggle-all-label"
    >
        {{ allSelected ? $t("admin.general.form.deselect_all") : $t("admin.general.form.select_all") }}
    </div>
</template>
