<template>
    <PageHead :title="$t('site_credits.title')" />
    <BodyHead :title="$t('site_credits.title')" :description="$t('site_credits.description')" />

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
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";
</script>
