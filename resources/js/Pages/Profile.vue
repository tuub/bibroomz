<template>
    <Head title="My Profile" />

    <!--<button id="show-modal" @click="showModal = true">Show Modal</button>-->
    <button id="show-modal" @click="showTheModal">Show Modal</button>

    <Teleport to="body">
        <!-- use the modal component, pass in the prop -->
        <Modal
            :show="showModal"
            title="Add the effing reservation"
            @close="showModal = false">
            <template #header>
                <h1>Add reservation</h1>
            </template>
            <template #body>
                <form @submit.prevent="">
                    <input type="text" name="bla" />
                    <ReservationInfo :data="reservationData"></ReservationInfo>
                    <UserInfo :data="userData"></UserInfo>
                </form>
            </template>
        </Modal>
    </Teleport>

    <!-- 2xl == 42rems; 42 * 16 (root font-size, e.g. 16) = 672px -->
    <!-- 3xl == 48rems; 48 * 16 (root font-size, e.g. 16) = 768px -->
    <div class="max-w-3xl mx-auto">
        <h1 class="text-xl font-bold">Profile</h1>
        <p>The current user is {{ user }}.</p>
        <p>The current user reservations are {{ user_reservations }}.</p>
        <p>The current time is {{ time }}.</p>
        <p><Link href="/my" preserve-scroll>Refresh</Link></p>
    </div>
</template>

<script setup>
import Modal from '../Shared/ModalFoo.vue'
import ReservationInfo from '../Components/ReservationInfo.vue';
import UserInfo from '../Components/UserInfo.vue';
import { ref } from 'vue'

const showModal = ref(false)
let reservationData = {
    resource: '1',
    start: '15:30:00',
    end: '18:00:00',
}
let userData = {
    id: '99',
    name: 'dummy',
}
const showTheModal = () => {
    console.log('Modal open!');
    showModal.value = true
}

defineProps({
    time: String,
    user: Object,
    user_reservations: Object,
})
</script>
