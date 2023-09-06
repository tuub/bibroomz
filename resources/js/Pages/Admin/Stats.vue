<template>
    <PageHead :title="$t('admin.stats.title')" page-type="admin" />
    <BodyHead :title="$t('admin.stats.title')" :description="$t('admin.stats.description')"></BodyHead>

    <DoughnutChart :chartData="testData" />
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { DoughnutChart } from 'vue-chart-3';
import { Chart, registerables } from "chart.js";
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";

Chart.register(...registerables);

export default defineComponent({
    name: 'Home',
    components: {PageHead, BodyHead, DoughnutChart },
    setup() {
        const testData = {
            labels: ['Paris', 'Nîmes', 'Toulon', 'Perpignan', 'Autre'],
            datasets: [
                {
                    data: [30, 40, 60, 70, 5],
                    backgroundColor: ['#77CEFF', '#0079AF', '#123E6B', '#97B0C4', '#A5C8ED'],
                },
            ],
        };

        return { testData };
    },
});
</script>
