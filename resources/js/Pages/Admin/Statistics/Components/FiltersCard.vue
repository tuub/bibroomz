<script setup lang="ts">
import { useAppStore } from "@/Stores/AppStore";

defineProps<{
    rangeOptions: { id: string; label: string }[];
}>();

const appStore = useAppStore();

const selectedRange = defineModel<string>("range", { required: true });
const comparisonEnabled = defineModel<boolean>("comparisonEnabled", { required: true });
const customFrom = defineModel<Date | null>("customFrom", { required: true });
const customTo = defineModel<Date | null>("customTo", { required: true });
const compareFrom = defineModel<Date | null>("compareFrom", { required: true });
const compareTo = defineModel<Date | null>("compareTo", { required: true });

defineEmits<{
    apply: [];
}>();
</script>

<template>
    <div class="border-app-border bg-app-surface dark:border-app-border dark:bg-app-surface border p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xl font-bold">{{ $t("admin.statistics.index.title") }}</div>
                <div class="italic">{{ $t("admin.statistics.index.description") }}</div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <Select
                    v-model="selectedRange"
                    :options="rangeOptions"
                    option-label="label"
                    option-value="id"
                    data-test="range-select"
                />
                <div class="flex items-center gap-2">
                    <ToggleSwitch
                        v-model="comparisonEnabled"
                        input-id="statistics-comparison-toggle"
                        data-test="comparison-toggle"
                    />
                    <label for="statistics-comparison-toggle" class="text-sm font-medium">
                        {{ $t("admin.statistics.index.comparison.toggle_label") }}
                    </label>
                </div>
            </div>
        </div>

        <div v-if="selectedRange === 'custom' || comparisonEnabled" class="mt-3 flex flex-wrap items-end gap-3">
            <template v-if="selectedRange === 'custom'">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">{{ $t("admin.statistics.index.range.from") }}</label>
                    <DatePicker
                        v-model="customFrom"
                        show-icon
                        :date-format="appStore.primeDateFormat"
                        show-button-bar
                        class="w-full"
                        data-test="range-from-date-picker"
                    />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">{{ $t("admin.statistics.index.range.to") }}</label>
                    <DatePicker
                        v-model="customTo"
                        show-icon
                        :date-format="appStore.primeDateFormat"
                        show-button-bar
                        class="w-full"
                        data-test="range-to-date-picker"
                    />
                </div>
            </template>
            <div v-if="comparisonEnabled" class="contents" data-test="comparison-fields">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">{{
                        $t("admin.statistics.index.comparison.compare_from")
                    }}</label>
                    <DatePicker
                        v-model="compareFrom"
                        show-icon
                        :date-format="appStore.primeDateFormat"
                        show-button-bar
                        class="w-full"
                        data-test="compare-from-date-picker"
                    />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">{{ $t("admin.statistics.index.comparison.compare_to") }}</label>
                    <DatePicker
                        v-model="compareTo"
                        show-icon
                        :date-format="appStore.primeDateFormat"
                        show-button-bar
                        class="w-full"
                        data-test="compare-to-date-picker"
                    />
                </div>
            </div>
            <Button :label="$t('admin.general.form.apply')" data-test="apply" @click="$emit('apply')" />
        </div>
    </div>
</template>
