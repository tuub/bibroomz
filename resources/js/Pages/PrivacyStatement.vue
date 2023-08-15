<template>
    <PageHead :title="$t('privacy_statement.title')" />
    <BodyHead :title="$t('privacy_statement.title')"
              :description="$t('privacy_statement.description')" />

    <p>{{ $t('privacy_statement.paragraphs.01') }}</p>
    <p>{{ $t('privacy_statement.paragraphs.02') }}</p>
    <p>{{ $t('privacy_statement.paragraphs.03') }}</p>
    <p>{{ $t('privacy_statement.paragraphs.04') }}</p>
    <p>{{ $t('privacy_statement.paragraphs.05') }}</p>
</template>

<script setup>
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";

</script>
