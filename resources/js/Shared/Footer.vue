<script setup lang="ts">
import ExternalLink from "@/Shared/Navigation/ExternalLink.vue";
import InternalLink from "@/Shared/Navigation/InternalLink.vue";
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { inject } from "vue";

const route = inject<ZiggyRouteFn>("ziggyRoute");
</script>
<template>
    <footer
        class="mt-auto flex items-center justify-center bg-gray-800 p-4 text-center text-white"
        :aria-label="$t('accessibility.aria_label.footer')"
    >
        <div class="">
            <ExternalLink href="https://www.tu.berlin/datenschutz">
                {{ $t("navigation.privacy_statement") }}
            </ExternalLink>
        </div>
        <div class="p-2">|</div>
        <div>
            <InternalLink :href="route('site_credits')">
                {{ $t("navigation.site_credits") }}
            </InternalLink>
        </div>
    </footer>
</template>
