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
<template>
    <Head>
        <!--<title>{{ title ? `${title} - ${appName}` : `${appName}` }}</title>-->
        <title>{{ appName }}</title>
    </Head>
    <header
        class="flex items-center justify-between bg-white p-4 text-tub"
        :aria-label="$t('accessibility.aria_label.header')"
    >
        <a class="sr-only" href="#content">{{ $t("accessibility.skip_to_main_content") }}</a>

        <!-- BRAND START -->
        <div class="flex items-center justify-center text-xl font-bold">
            <div v-if="institution" class="mr-5">
                <img :src="institution?.logo_uri" class="w-36" alt="Institution Logo" />
            </div>
            <div class="flex items-center justify-center">
                <h1>
                    <a :href="route('start')" class="brand-name float-left">
                        {{ appName }}
                    </a>
                </h1>
            </div>
        </div>
        <!-- BRAND END -->
        <!-- NAVIGATION START -->
        <!-- RESPONSIVE START -->
        <div class="block text-tub focus:outline-none lg:hidden">
            <nav id="menu" role="navigation" :aria-label="$t('accessibility.aria_label.navigation.responsive')">
                <Drawer v-model:visible="isResponsive" header="Navigation" position="full">
                    <template #header> BLA </template>
                    <template #closebutton> FOO </template>
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
                    class="border-0 bg-white text-tub hover:bg-tub hover:text-white"
                    size="small"
                    aria-label="Open Navigation"
                    @click="isResponsive = true"
                >
                    <i class="pi pi-bars"></i>
                    <span class="sr-only">{{ $t("accessibility.open_navigation") }}</span>
                </Button>
            </nav>
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
