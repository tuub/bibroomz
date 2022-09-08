<template>
    <div class="flex flex-row gap-5">
        <div id="sidebar" class="basis-1/5 md:basis-1/5">
            <LoginForm></LoginForm>
        </div>
        <div id="calendar" class="basis-4/5 md:basis-4/5">
            <Calendar></Calendar>
        </div>
    </div>
</template>

<script>
import Calendar from "../Components/Calendar.vue";
import LoginForm from "../Components/LoginForm.vue";

export default {
    components: {
        Calendar,
        LoginForm,
    },
}


</script>

<style scoped>
#sidebar {
    background-color: #ffffff;
}
</style>
