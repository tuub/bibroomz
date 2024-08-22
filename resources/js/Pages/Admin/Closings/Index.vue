<template>
    <IndexLayout
        model="closing"
        :title="
            $t('admin.closings.index.title', {
                type: $t('admin.closings.types.' + closable_type),
                title: translate(closable.title),
            })
        "
        :description="$t('admin.closings.index.description')"
        :create-params="{ closable_type: closable_type, closable_id: closable.id }"
    >
        <template #header>
            <IndexHeaderField>
                {{ $t("admin.closings.index.table.header.start") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.closings.index.table.header.end") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.closings.index.table.header.description") }}
            </IndexHeaderField>
            <IndexHeaderField class="text-center">
                {{ $t("admin.closings.index.table.header.is_over") }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>

        <template #body>
            <IndexRow v-for="closing in closings" :key="closing.id">
                <IndexRowField>
                    {{ getClosingDateTime(closing.start) }}
                </IndexRowField>
                <IndexRowField>
                    {{ getClosingDateTime(closing.end) }}
                </IndexRowField>
                <IndexRowField>
                    {{ translate(closing.description) }}
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="isPastClosing(closing)" />
                </IndexRowField>
                <IndexRowField class="text-right">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_closings', institutionId)"
                            action="edit"
                            model="closing"
                            :params="{ id: closing.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_closings', institutionId)"
                            action="delete"
                            model="closing"
                            :params="{
                                id: closing.id,
                                closable_id: closing.closable_id,
                                closable_type: closing.closable_type,
                            }"
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
import IndexHeaderField from "@/Components/Admin/IndexHeaderField.vue";
import IndexLayout from "@/Components/Admin/IndexLayout.vue";
import IndexRow from "@/Components/Admin/IndexRow.vue";
import IndexRowField from "@/Components/Admin/IndexRowField.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    closable: {
        type: Object,
        default: () => ({}),
    },
    // eslint-disable-next-line vue/prop-name-casing
    closable_type: {
        type: String,
        default: "",
    },
    closings: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);

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
    if (props.closable_type === "institution") {
        return props.closable.id;
    }

    return props.closable.institution_id;
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getClosingDateTime = (dateTime) => {
    return [appStore.formatDate(dateTime, true), appStore.formatTime(dateTime, true)].join(" ");
};

const isPastClosing = (closing) => {
    return dayjs(closing.end).isBefore(dayjs().utcOffset(0, true));
};
</script>
