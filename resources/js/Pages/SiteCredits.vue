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
</template>

<script setup lang="ts">
import Breadcrumbs from "@/Shared/Breadcrumbs.vue";
</script>
