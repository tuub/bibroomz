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
</template>

<script setup>
import Breadcrumbs from "@/Shared/Breadcrumbs.vue";
</script>
