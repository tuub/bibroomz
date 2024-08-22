<template>
    <IndexLayout
        :title="$t('admin.permission_groups.index.title')"
        :description="$t('admin.permission_groups.index.description')"
        :add-create-button="false"
    >
        <template #header>
            <IndexHeaderField>
                {{ $t("admin.permission_groups.index.table.header.name") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.permission_groups.index.table.header.description") }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>

        <template #body>
            <IndexRow v-for="permissionGroup in permissionGroups" :key="permissionGroup.id">
                <IndexRowHeaderField>
                    {{ translate(permissionGroup.name) }}
                </IndexRowHeaderField>
                <IndexRowField>
                    {{ translate(permissionGroup.description) }}
                </IndexRowField>
                <IndexRowField class="text-right">
                    <ActionLink action="edit" model="permission_group" :params="{ id: permissionGroup.id }" />
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
    permissionGroups: {
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
