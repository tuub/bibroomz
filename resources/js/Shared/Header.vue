<template>
    <Head>
        <!--<title>{{ title ? `${title} - ${appName}` : `${appName}` }}</title>-->
        <title>{{ appName }}</title>
    </Head>
    <header class="flex items-center justify-between bg-white p-4 text-tub">
        <!-- BRAND START -->
        <div class="flex items-center justify-center text-xl font-bold">
            <div v-if="institution" class="mr-5">
                <img :src="institution?.logo_uri" class="w-36" />
            </div>
            <div class="flex items-center justify-center">
                <a :href="route('start')" class="brand-name float-left">
                    {{ appName }}
                </a>
            </div>
        </div>
        <!-- BRAND END -->
        <!-- NAVIGATION START -->
        <!-- RESPONSIVE START -->
        <div class="block text-tub focus:outline-none lg:hidden">
            <Drawer v-model:visible="isResponsive" header="Navigation" position="full">
                <template #header>
                    BLA
                </template>
                <template #closebutton>
                    FOO
                </template>
                <template #container="{ closeCallback }">
                    <NavigationBar
                        :is-responsive="isResponsive"
                        :is-privileged="isPrivileged"
                        :is-multi-tenancy="isMultiTenancy"
                        @click="closeCallback"
                    />
                </template>
            </Drawer>
            <Button
                icon="pi pi-bars"
                class="border-0 bg-white text-tub hover:bg-tub hover:text-white"
                size="small"
                @click="isResponsive = true"
            />
        </div>
        <!-- RESPONSIVE END -->
        <!-- DESKTOP START -->
        <div
            class="absolute left-0 top-16 hidden w-full gap-4 bg-gray-800 p-4 shadow-md md:relative md:top-auto md:w-auto md:bg-transparent md:p-0 md:shadow-none lg:flex"
        >
            <NavigationBar
                :is-responsive="isResponsive"
                :is-privileged="isPrivileged"
                :is-multi-tenancy="isMultiTenancy"
            />
        </div>
        <!-- DESKTOP END -->
        <!-- NAVIGATION END -->
    </header>
</template>
<script setup>
import NavigationBar from "@/Shared/Navigation/NavigationBar.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { Head } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import { inject, ref } from "vue";

const route = inject("ziggyRoute");
const appStore = useAppStore();
const authStore = useAuthStore();

const { appName, institution, isMultiTenancy } = storeToRefs(appStore);
const isResponsive = ref(false);
const { isPrivileged } = storeToRefs(authStore);
</script>
