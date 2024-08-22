<template>
    <IndexLayout model="role" :title="$t('admin.roles.index.title')" :description="$t('admin.roles.index.description')">
        <template #header>
            <IndexHeaderField>
                {{ $t("admin.roles.index.table.header.name") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.roles.index.table.header.description") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.roles.index.table.header.permissions") }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>

        <template #body>
            <IndexRow v-for="role in roles" :key="role.id">
                <IndexRowHeaderField>
                    {{ translate(role.name) }}
                </IndexRowHeaderField>
                <IndexRowHeaderField>
                    {{ translate(role.description) }}
                </IndexRowHeaderField>
                <IndexRowField class="text-left">
                    {{
                        role.permissions
                            .sort((a, b) => translate(a.name).localeCompare(translate(b.name)))
                            .map((permission) => translate(permission.name))
                            .join(", ")
                    }}
                </IndexRowField>
                <IndexRowField class="text-right">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_roles')"
                            action="edit"
                            model="role"
                            :params="{ id: role.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_roles')"
                            action="delete"
                            model="role"
                            :entity="role"
                            :params="{ id: role.id }"
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
    roles: {
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
