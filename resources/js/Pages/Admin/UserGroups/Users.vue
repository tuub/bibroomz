<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <DataTable
            :value="users"
            size="medium"
            striped-rows
            removable-sort
            table-style="min-width: 50rem"
            class="text-app-muted dark:text-app-subtle w-full text-left text-sm"
        >
            <template #header>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-xl font-bold">
                            {{ $t("admin.user_groups.users.title", { title: translate(user_group.title) }) }}
                        </div>
                    </div>
                </div>
            </template>

            <Column field="name" sortable :header="$t('admin.user_groups.users.table.header.name')" />
            <Column field="email" sortable :header="$t('admin.user_groups.users.table.header.email')" />

            <Column field="pivot.valid_from" sortable :header="$t('admin.user_groups.users.table.header.valid_from')">
                <template #body="{ data }">
                    {{ formatDate(data.pivot.valid_from) }}
                </template>
            </Column>

            <Column field="pivot.valid_until" sortable :header="$t('admin.user_groups.users.table.header.valid_until')">
                <template #body="{ data }">
                    {{ formatDate(data.pivot.valid_until) }}
                </template>
            </Column>

            <Column :columnheader="$t('admin.general.actions')">
                <template #body="{ data }">
                    <LinkGroup>
                        <PopupLink
                            action="remove"
                            :label="$t('admin.user_groups.users.table.actions.remove')"
                            model="user_group.users"
                            :params="{ id: user_group.id, users: [data.id] }"
                        />
                    </LinkGroup>
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import { useAppStore } from "@/Stores/AppStore";
import type { AdminUser, UserGroup } from "@/Types/Admin";

import dayjs from "dayjs";
import "dayjs/locale/de";
import LocalizedFormat from "dayjs/plugin/localizedFormat";
import { storeToRefs } from "pinia";

type UserGroupUser = AdminUser & {
    pivot?: {
        valid_from?: string | null;
        valid_until?: string | null;
    };
};

/* DayJS */
dayjs.extend(LocalizedFormat);

/* Stores */
const app = useAppStore();

const { locale } = storeToRefs(app);
const translate = app.translate;

/* Props */
withDefaults(
    defineProps<{
        // eslint-disable-next-line vue/prop-name-casing
        user_group?: UserGroup;
        users?: UserGroupUser[];
    }>(),
    {
        user_group: () => ({}),
        users: () => [],
    },
);

/* Methods */
function formatDate(date?: string | null) {
    return date ? dayjs(date).locale(locale.value).format("L") : "";
}
</script>
