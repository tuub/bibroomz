<template>
    <Breadcrumbs></Breadcrumbs>

    <h1 class="mt-5 text-xl font-bold">{{ $t("site_credits.paragraphs.contact.header") }}</h1>
    <p>{{ $t("site_credits.paragraphs.contact.content") }}</p>

    <h1 class="mt-5 text-xl font-bold">{{ $t("site_credits.paragraphs.requests.header") }}</h1>
    <p>{{ $t("site_credits.paragraphs.requests.content") }}</p>

    <h1 class="mt-5 text-xl font-bold">{{ $t("site_credits.paragraphs.about.header") }}</h1>
    <p>{{ $t("site_credits.paragraphs.about.content") }}</p>

    <h1 class="mt-5 text-xl font-bold">{{ $t("site_credits.paragraphs.authority.header") }}</h1>
    <p>{{ $t("site_credits.paragraphs.authority.content") }}</p>

    <h1 class="mt-5 text-xl font-bold">{{ $t("site_credits.paragraphs.tax_id.header") }}</h1>
    <p>{{ $t("site_credits.paragraphs.tax_id.content") }}</p>

    <h1 class="mt-5 text-xl font-bold">{{ $t("site_credits.paragraphs.source_code.header") }}</h1>
    <p>
        {{ $t("site_credits.paragraphs.source_code.content") }}
        <a
            :href="$t('site_credits.paragraphs.source_code.link')"
            target="_blank"
            rel="noopener noreferrer"
            class="text-tub dark:text-app-text hover:underline"
        >
            <i class="ri-github-fill" aria-hidden="true"></i>
            {{ $t("site_credits.paragraphs.source_code.link_label") }} </a
        >.
    </p>

    <h1 class="mt-5 text-xl font-bold">{{ $t("site_credits.paragraphs.third_party.header") }}</h1>
    <p>{{ $t("site_credits.paragraphs.third_party.content") }}</p>
    <ul class="list-inside list-disc">
        <li v-for="work in works" :key="work">
            {{ $t(`site_credits.paragraphs.third_party.${work}.content`) }}
            <a
                :href="$t(`site_credits.paragraphs.third_party.${work}.link`)"
                target="_blank"
                rel="noopener noreferrer"
                class="text-tub dark:text-app-text hover:underline"
            >
                {{ $t(`site_credits.paragraphs.third_party.${work}.link_label`) }} </a
            >.
        </li>
    </ul>
    <p class="mt-2">
        {{ $t("site_credits.paragraphs.third_party.notices.content") }}
        <a
            :href="noticesUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="text-tub dark:text-app-text hover:underline"
        >
            {{ $t("site_credits.paragraphs.third_party.notices.link_label") }} </a
        >.
    </p>
</template>

<script setup lang="ts">
import credits from "@/credits.json";

import Breadcrumbs from "@/Shared/Breadcrumbs.vue";

// The file the Vite build fills with the license text of everything in the bundle.
defineProps<{
    noticesUrl: string;
}>();

// The works this page names, in the order it lists them. Not a matter of taste:
// the build fails when a package ships a file of its own into the bundle and
// credits.json does not name it. See vite.config.ts.
const works = credits.map((credit) => credit.key);
</script>
