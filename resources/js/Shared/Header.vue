<script setup lang="ts">
import NavigationBar from "@/Shared/Navigation/NavigationBar.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { Head, router } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import { inject, ref } from "vue";

const route = inject<ZiggyRouteFn>("ziggyRoute")!;
const appStore = useAppStore();
const authStore = useAuthStore();

const { appName, institution, isMultiTenancy } = storeToRefs(appStore);
const isResponsive = ref(false);
const { isPrivileged, isImpersonating, user } = storeToRefs(authStore);

function stopImpersonating() {
    router.post(
        route("admin.impersonate.stop"),
        {},
        {
            onSuccess: () => {
                window.location.reload();
            },
        },
    );
}
</script>
<template>
    <Head>
        <!--<title>{{ title ? `${title} - ${appName}` : `${appName}` }}</title>-->
        <title>{{ appName }}</title>
    </Head>
    <div
        v-if="isImpersonating"
        class="bg-feedback-danger-strong text-feedback-danger-contrast flex items-center justify-center gap-4 p-2 text-center text-sm"
    >
        <span>{{ $t("impersonation.banner.message", { name: user?.name ?? "" }) }}</span>
        <button class="font-medium underline" @click="stopImpersonating">
            {{ $t("impersonation.banner.stop", { name: user?.name ?? "" }) }}
        </button>
    </div>
    <header
        class="bg-app-surface text-tub dark:bg-app-surface flex items-center justify-between p-4"
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
        <div class="text-tub block focus:outline-hidden lg:hidden">
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
                    class="bg-app-surface text-tub hover:bg-tub hover:text-brand-contrast dark:bg-app-surface border-0"
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
            class="bg-app-page dark:bg-app-page absolute top-16 left-0 hidden w-full gap-4 p-4 shadow-md md:relative md:top-auto md:w-auto md:bg-transparent md:p-0 md:shadow-none lg:flex"
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
