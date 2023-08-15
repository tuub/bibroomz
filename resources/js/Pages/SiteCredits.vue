<template>
    <PageHead :title="$t('site_credits.title')" />
    <BodyHead :title="$t('site_credits.title')"
              :description="$t('site_credits.description')" />

    <p>{{ $t('site_credits.paragraphs.01') }}</p>
    <p>{{ $t('site_credits.paragraphs.02') }}</p>
    <p>{{ $t('site_credits.paragraphs.03') }}</p>
    <p>{{ $t('site_credits.paragraphs.04') }}</p>
    <p>{{ $t('site_credits.paragraphs.05') }}</p>
</template>

<script setup>
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";

</script>
