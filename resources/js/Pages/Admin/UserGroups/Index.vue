<template>
    <IndexLayout
        model="user_group"
        :title="$t('admin.user_groups.index.title')"
        :description="$t('admin.user_groups.index.description')"
    >
        <template #header>
            <IndexHeaderField>
                {{ $t("admin.user_groups.index.table.header.title") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.user_groups.index.table.header.institution") }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>
        <template #body>
            <IndexRow v-for="user_group in user_groups" :key="user_group.id">
                <IndexRowHeaderField>
                    {{ translate(user_group.title) }}
                </IndexRowHeaderField>
                <IndexRowField>
                    {{ translate(user_group.institution.title) }}
                </IndexRowField>
                <IndexRowField class="text-right">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_user_groups', user_group.institution_id)"
                            action="import"
                            model="user_group"
                            :params="{ id: user_group.id }"
                        />
                        <ActionLink
                            v-if="hasPermission('edit_user_groups', user_group.institution_id)"
                            action="edit"
                            model="user_group"
                            :params="{ id: user_group.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_user_groups', user_group.institution_id)"
                            action="delete"
                            model="user_group"
                            :params="{ id: user_group.id }"
                        />
                    </LinkGroup>
                </IndexRowField>
            </IndexRow>
        </template>
    </IndexLayout>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
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
    user_groups: {
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
