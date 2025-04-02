<template>
    <IndexLayout
        model="resource_group"
        :title="$t('admin.resource_groups.index.title')"
        :description="$t('admin.resource_groups.index.description')"
    >
        <template #header>
            <IndexHeaderField>
                {{ $t("admin.resource_groups.index.table.header.title") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.resource_groups.index.table.header.slug") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.resource_groups.index.table.header.institution") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.resource_groups.index.table.header.description") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.resource_groups.index.table.header.is_active") }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>
        <template #body>
            <IndexRow v-for="resource_group in resource_groups" :key="resource_group.id">
                <IndexRowHeaderField>
                    {{ translate(resource_group.title) }}
                </IndexRowHeaderField>
                <IndexRowField>
                    {{ resource_group.slug }}
                </IndexRowField>
                <IndexRowField>
                    {{ translate(resource_group.institution.title) }}
                </IndexRowField>
                <IndexRowField>
                    {{ translate(resource_group.description) }}
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="resource_group.is_active" />
                </IndexRowField>
                <IndexRowField class="text-right">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_resource_groups', resource_group.institution_id)"
                            action="edit"
                            model="resource_group"
                            :params="{ id: resource_group.id }"
                        />
                        <RelationLink
                            v-if="hasPermission('view_resources', resource_group.institution_id)"
                            current="resource_group"
                            relation="resource"
                            :params="{ id: resource_group.id }"
                        />
                        <RelationLink
                            v-if="hasPermission('view_settings', resource_group.institution_id)"
                            current="resource_group"
                            relation="setting"
                            :params="{ settingable_type: 'resource_group', settingable_id: resource_group.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_resource_groups', resource_group.institution_id)"
                            action="delete"
                            model="resource_group"
                            :params="{ id: resource_group.id }"
                        />
                    </LinkGroup>
                </IndexRowField>
            </IndexRow>
        </template>
    </IndexLayout>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import RelationLink from "@/Components/Admin/Index/RelationLink.vue";
import IndexHeaderField from "@/Components/Admin/IndexHeaderField.vue";
import IndexLayout from "@/Components/Admin/IndexLayout.vue";
import IndexRow from "@/Components/Admin/IndexRow.vue";
import IndexRowField from "@/Components/Admin/IndexRowField.vue";
import IndexRowHeaderField from "@/Components/Admin/IndexRowHeaderField.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    // eslint-disable-next-line vue/prop-name-casing
    resource_groups: {
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
</script>
