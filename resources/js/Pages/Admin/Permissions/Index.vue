<template>
    <IndexLayout
        :title="$t('admin.permissions.index.title')"
        :description="$t('admin.permissions.index.description')"
        :add-create-button="false"
    >
        <template #header>
            <IndexHeaderField>
                {{ $t("admin.permissions.index.table.header.name") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.permissions.index.table.header.description") }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>

        <template #body>
            <IndexRow v-for="permission in permissions" :key="permission.id">
                <IndexRowHeaderField>
                    {{ translate(permission.name) }}
                </IndexRowHeaderField>
                <IndexRowField>
                    {{ translate(permission.description) }}
                </IndexRowField>
                <IndexRowField class="text-right">
                    <ActionLink action="edit" model="permission" :params="{ id: permission.id }" />
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

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    permissions: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;
</script>
