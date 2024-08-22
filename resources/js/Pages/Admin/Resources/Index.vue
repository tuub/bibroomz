<template>
    <IndexLayout
        model="resource"
        :title="$t('admin.resources.index.title')"
        :description="$t('admin.resources.index.description')"
        :create-params="{ resource_group_id: resourceGroup.id }"
    >
        <template #header>
            <IndexHeaderField>
                {{ $t("admin.resources.index.table.header.title") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.resources.index.table.header.location") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.resources.index.table.header.business_hours") }}
            </IndexHeaderField>
            <IndexHeaderField class="text-center">
                {{ $t("admin.resources.index.table.header.capacity") }}
            </IndexHeaderField>
            <IndexHeaderField class="text-center">
                {{ $t("admin.resources.index.table.header.is_active") }}
            </IndexHeaderField>
            <IndexHeaderField class="text-center">
                {{ $t("admin.resources.index.table.header.is_verification_required") }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>

        <template #body>
            <IndexRow v-for="resource in resources" :key="resource.id">
                <IndexRowHeaderField>
                    {{ translate(resource.title) }}
                </IndexRowHeaderField>
                <IndexRowField>
                    {{ translate(resource.location) }}
                    <template v-if="resource.location_uri">
                        <a :href="resource.location_uri" target="_blank">
                            <i class="ri-external-link-line inline"></i>
                        </a>
                    </template>
                </IndexRowField>
                <IndexRowField>
                    <p v-for="business_hour in resource.business_hours" :key="business_hour.id">
                        {{ formatBusinessHourDates(business_hour.start_date, business_hour.end_date) }}
                        {{ getBusinessHourTime(business_hour.start) + "-" + getBusinessHourTime(business_hour.end) }}
                        {{
                            "(" +
                            business_hour.week_days
                                .map((week_day) => trans("admin.general.week_days." + week_day.key + ".short_label"))
                                .join(", ") +
                            ")"
                        }}
                    </p>
                </IndexRowField>
                <IndexRowField class="text-center">
                    {{ resource.capacity }}
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="resource.is_active" />
                </IndexRowField>
                <IndexRowField class="text-center">
                    <BooleanField :is-true="resource.is_verification_required" />
                </IndexRowField>
                <IndexRowField class="text-right">
                    <LinkGroup>
                        <!-- FIXME: resource.resource_group.institution.id? -->
                        <ActionLink
                            v-if="hasPermission('edit_resources', resource.institution_id)"
                            action="edit"
                            model="resource"
                            :params="{ id: resource.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('create_resources', resource.institution_id)"
                            action="clone"
                            model="resource"
                            :params="{ id: resource.id }"
                        />
                        <RelationLink
                            v-if="hasPermission('view_closings', resource.institution_id)"
                            current="resource"
                            relation="closing"
                            :params="{ closable_type: 'resource', closable_id: resource.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_resources', resource.institution_id)"
                            action="delete"
                            model="resource"
                            :params="{ id: resource.id }"
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

import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";
import { trans } from "laravel-vue-i18n";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    resourceGroup: {
        type: Object,
        required: true,
    },
    resources: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
// FIXME: Why do we still need this?
dayjs.extend(customParseFormat);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const { formatDate, formatTime, translate } = useAppStore();
const { hasPermission } = useAuthStore();

// ------------------------------------------------
// Methods
// ------------------------------------------------
const getBusinessHourTime = (datetime) => {
    return formatTime(datetime, false, "HH:mm:ss");
};

const formatBusinessHourDates = (startDate, endDate) => {
    let formatString = "";

    if (startDate) {
        formatString += formatDate(startDate);
    }

    if (startDate || endDate) {
        formatString += "-";
    }

    if (endDate) {
        formatString += formatDate(endDate);
    }

    if (startDate || endDate) {
        formatString += ":";
    }

    return formatString;
};
</script>
