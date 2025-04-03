<template>
    <div class="flex flex-col border-b pb-2">
        <div class="flex flex-row" :class="isPast ? 'opacity-50' : 'opacity-100'">
            <div class="flex w-3/12 items-center justify-center">
                <FancyDate :happening="happening" :css-class="getStatusClass"></FancyDate>
            </div>
            <div class="ml-2 flex w-9/12 flex-col justify-center">
                <div class="text-xs uppercase">
                    <span v-if="isPast" class="rounded-r-full bg-gray-100 text-xs">
                        {{ $t("user_happenings.item.past_happening") }}
                    </span>
                    <span v-else-if="isPresent" class="rounded-r-full bg-gray-100 text-xs">
                        {{ $t("user_happenings.item.present_happening") }}
                    </span>
                    <span v-else class="rounded-r-full bg-gray-100 text-xs">
                        {{ $t("user_happenings.item.future_happening") }}
                    </span>
                </div>
                <div class="text-xs font-bold">
                    <span class="pr-1">{{ happening.resource.resourceGroup }}</span>
                    {{ happening.resource.title }}
                    ({{ happening.resource.institution }})
                </div>
                <div v-if="isLabelPresent" class="truncate text-xs italic leading-[1.5]">
                    {{ translate(happening.label) }}
                </div>
                <div class="text-xs">
                    <!--<i class="pi pi-clock mr-1"></i>-->
                    {{ happeningStart }} - {{ happeningEnd }}
                    <SidebarButton
                        v-if="happening.can.edit"
                        icon="pi pi-pencil"
                        type="edit"
                        :label="$t('user_happenings.item.form.edit')"
                        @click="editUserHappening(happening)"
                    />
                </div>
                <div class="flex text-xs">
                    <!--<i class="pi pi-users mr-1"></i>-->
                    {{ happening.user_01 }}
                    <template v-if="happening.user_02"> & {{ happening.user_02 }} </template>
                    <div>
                        <SidebarButton
                            v-if="happening.can.verify"
                            type="verify"
                            :label="$t('user_happenings.item.form.verify')"
                            @click="verifyUserHappening(happening)"
                        />
                        <SidebarButton
                            v-if="happening.can.delete"
                            icon="pi pi-trash"
                            type="delete"
                            :label="$t('user_happenings.item.form.delete')"
                            @click="deleteUserHappening(happening)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--
    <div class="flex items-center space-x-4 p-1" :class="[isPresent ? 'rounded-xl border border-pink-300' : '']">
        <div class="min-w-0 flex-1" :class="[isPast ? 'text-gray-400' : 'text-gray-900']">
            <span
                v-if="isPast"
                class="mr-1 inline-block rounded bg-gray-200 px-2 py-0.5 text-xs font-normal uppercase text-black last:mr-0"
            >
                {{ $t("user_happening.past_happening") }}
            </span>
            <span
                v-else-if="isPresent"
                class="mr-1 inline-block rounded bg-pink-300 px-2 py-0.5 text-xs font-normal uppercase text-black last:mr-0"
            >
                {{ $t("user_happening.present_happening") }}
            </span>
            <p class="truncate pb-1 font-bold dark:text-white">
                <i class="ri-calendar-event-line" :title="$t('user_happening.date_time')"></i>
                {{ happeningDate }}
                <i class="ri-arrow-right-line"></i>
                {{ happeningStart }} - {{ happeningEnd }}
            </p>
            <p v-if="isLabelPresent" class="truncate">
                <i class="ri-price-tag-3-fill"></i>
                {{ translate(happening.label) }}
            </p>
            <p class="pb-1 text-sm font-medium">
                <i class="ri-home-line mr-1" :title="translate(happening.resource.resourceGroup)"></i>
                <template v-if="happening.resource.locationUri">
                    <a class="underline" :href="happening.resource.locationUri" target="_blank">
                        {{ happening.resource.resourceGroup }}
                        {{ happening.resource.title }}
                    </a>
                </template>
                <template v-else>
                    {{ happening.resource.resourceGroup }}
                    {{ happening.resource.title }}
                </template>
            </p>
            <p class="truncate pb-1 text-sm font-medium">
                <i class="ri-map-pin-fill" :title="$t('user_happening.location')"></i>
                {{ happening.resource.location }}
            </p>
            <p class="pb-1 text-sm font-medium">
                <i class="ri-user-fill" :title="$t('user_happening.user_01')"></i>
                {{ happening.user_01 }}
            </p>
            <p v-if="happening.isVerificationRequired" class="pb-1 text-sm font-medium">
                <i class="ri-user-follow-fill" :title="$t('user_happening.user_02')"></i>
                {{ happening.user_02 }}
                <span
                    v-if="!isPast && happening.isVerified"
                    class="mr-1 inline-block rounded bg-green-300 px-2 py-0.5 text-xs font-normal uppercase text-black last:mr-0"
                >
                    <i class="ri-check-line"></i>
                    {{ $t("user_happening.verified") }}
                </span>
                <span
                    v-if="!isPast && !happening.isVerified"
                    class="mr-1 inline-block rounded bg-red-300 px-2 py-0.5 text-xs font-normal uppercase text-black last:mr-0"
                >
                    <i class="ri-close-line"></i>
                    {{ $t("user_happening.unverified") }}
                </span>
            </p>
        </div>
        <div class="text-base font-bold text-gray-900">
            <p>
                <a
                    v-if="happening.can.delete"
                    href="#"
                    class="mb-2 inline-flex items-center rounded-lg border border-gray-200 bg-red-200 px-2 py-1 text-sm font-medium text-gray-900 hover:bg-red-100 hover:text-red-700 focus:z-10 focus:text-blue-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                    @click="deleteUserHappening(happening)"
                >
                    <i class="ri-delete-bin-line mr-1"></i>
                    {{ $t("user_happening.form.delete") }}
                </a>
            </p>
            <p>
                <a
                    v-if="happening.can.verify"
                    href="#"
                    class="mb-2 inline-flex items-center rounded-lg border border-gray-200 bg-white px-2 py-1 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-red-700 focus:z-10 focus:text-blue-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                    @click="verifyUserHappening(happening)"
                >
                    <i class="ri-check-double-line mr-1"></i>
                    {{ $t("user_happening.form.verify") }}
                </a>
            </p>
            <p>
                <a
                    v-if="happening.can.edit"
                    href="#"
                    class="mb-2 inline-flex items-center rounded-lg border border-gray-200 bg-white px-2 py-1 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-red-700 focus:z-10 focus:text-blue-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                    @click="editUserHappening(happening)"
                >
                    <i class="ri-edit-line mr-1"></i>
                    {{ $t("user_happening.form.edit") }}
                </a>
            </p>
        </div>
    </div>
    -->
</template>

<script setup>
import SidebarButton from "@/Components/SidebarButton.vue";
import FancyDate from "@/Components/UserHappening/FancyDate.vue";
import { useHappeningDeleteModal, useHappeningEditModal, useHappeningVerifyModal } from "@/Composables/ModalActions";
import { useAppStore } from "@/Stores/AppStore";
import { useModal } from "@/Stores/Modal";

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    happening: {
        type: Object,
        default: () => ({}),
    },
    isPast: {
        type: Boolean,
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const modal = useModal();
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;

// ------------------------------------------------
// Computed
// ------------------------------------------------
const happening = computed(() => ({
    ...props.happening,
    resource: {
        ...props.happening.resource,
        title: translate(props.happening.resource.title),
        location: translate(props.happening.resource.location),
        description: translate(props.happening.resource.description),
        resourceGroup: translate(props.happening.resource.resourceGroup),
        institution: props.happening.resource.institution,
    },
}));

const happeningStart = computed(() => {
    return appStore.formatTime(props.happening.start, true);
});

const happeningEnd = computed(() => {
    return appStore.formatTime(props.happening.end, true);
});

//const isPast = computed(() => {
//    return dayjs(props.happening.end).isBefore(dayjs.utc());
//});

const isPresent = computed(() => {
    return dayjs(props.happening.start).isBefore(dayjs.utc()) && dayjs(props.happening.end).isAfter(dayjs.utc());
});

const isLabelPresent = computed(() => {
    return props.happening.label && !Array.isArray(props.happening.label);
});

const getStatusClass = computed(() => {
    if (props.isPast) {
        return "over";
    } else if (props.happening.isVerified) {
        return "booked";
    } else {
        return "reserved";
    }
});

// ------------------------------------------------
// Modal Actions
// ------------------------------------------------
const editUserHappening = (happening) => {
    const editModal = useHappeningEditModal(happening);
    modal.open(editModal.view, editModal.content, editModal.payload, editModal.actions);
};

const verifyUserHappening = (happening) => {
    const verifyModal = useHappeningVerifyModal(happening);
    modal.open(verifyModal.view, verifyModal.content, verifyModal.payload, verifyModal.actions);
};

const deleteUserHappening = (happening) => {
    const deleteModal = useHappeningDeleteModal(happening);
    modal.open(deleteModal.view, deleteModal.content, deleteModal.payload, deleteModal.actions);
};
</script>
