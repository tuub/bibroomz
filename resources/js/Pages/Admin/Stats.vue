<template>
    <PageHead :title="$t('admin.stats.title')" page-type="admin" />
    <BodyHead :title="$t('admin.stats.title')" :description="$t('admin.stats.description')"></BodyHead>

    <DoughnutChart :chart-data="testData" />
</template>

<script setup lang="ts">
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";

import { Chart, registerables } from "chart.js";
import { DoughnutChart } from "vue-chart-3";

Chart.register(...registerables);

const testData = {
    labels: ["Paris", "Nîmes", "Toulon", "Perpignan", "Autre"],
    datasets: [
        {
            data: [30, 40, 60, 70, 5],
            backgroundColor: ["#77CEFF", "#0079AF", "#123E6B", "#97B0C4", "#A5C8ED"],
        },
    ],
};
</script>
