<template>
    <div class='calendar'>
        <FullCalendar ref="calendar" class='calendar' :options="calendarOptions"/>
    </div>
</template>

<script>
import '@fullcalendar/core/vdom'
import FullCalendar from '@fullcalendar/vue3';
import interactionPlugin from '@fullcalendar/interaction';
import resourceTimeGridPlugin from '@fullcalendar/resource-timegrid';

export default {
    name: 'Calendar',
    components: {
        FullCalendar,
    },

    setup(props, context) {
        console.log(props)
        console.log(context)

        const calendarOptions = {
            schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
            plugins: [interactionPlugin, resourceTimeGridPlugin],
            initialView: 'resourceTimeGridDay',
            headerToolbar: {
                left: 'title',
                center: '',
                right: 'today,prev,next'
            },
        }

        return {
            calendarOptions,
        }
    }
}
</script>

<style lang='css'>
/* FIXME: Alias ~ in Vite! */
@import '/node_modules/@fullcalendar/daygrid/main.css';
@import '/node_modules/@fullcalendar/timegrid/main.css';
</style>
