<template>
    <IndexLayout
        :title="
            $t('admin.settings.index.title', {
                type: $t('admin.settings.types.' + settingable_type),
                title: translate(settingable.title),
            })
        "
        :description="$t('admin.settings.index.description')"
        :add-create-button="false"
    >
        <template #header>
            <IndexHeaderField>
                {{ $t("admin.settings.index.table.header.key") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.settings.index.table.header.description") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.settings.index.table.header.value") }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>

        <template #body>
            <IndexRow v-for="setting in settings" :key="setting.id">
                <IndexRowHeaderField>
                    {{ $t("admin.settings.keys." + setting.key + ".label") }}
                </IndexRowHeaderField>
                <IndexRowField>
                    {{ $t("admin.settings.keys." + setting.key + ".description") }}
                </IndexRowField>
                <IndexRowField>
                    {{ setting.value }}
                </IndexRowField>
                <IndexRowField class="text-right">
                    <ActionLink
                        v-if="hasPermission('edit_settings', institutionId)"
                        action="edit"
                        model="setting"
                        :params="{ id: setting.id }"
                    />
                </IndexRowField>
            </IndexRow>
        </template>
    </IndexLayout>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import IndexHeaderField from "@/Components/Admin/IndexHeaderField.vue";
import IndexLayout from "@/Components/Admin/IndexLayout.vue";
import IndexRow from "@/Components/Admin/IndexRow.vue";
import IndexRowField from "@/Components/Admin/IndexRowField.vue";
import IndexRowHeaderField from "@/Components/Admin/IndexRowHeaderField.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    settingable: {
        type: Object,
        default: () => ({}),
    },
    // eslint-disable-next-line vue/prop-name-casing
    settingable_type: {
        type: String,
        default: "",
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;
const { hasPermission } = authStore;

const institutionId = computed(() => {
    if (props.settingable_type === "institution") {
        return props.settingable.id;
    }

    return props.settingable.institution_id;
});
</script>
