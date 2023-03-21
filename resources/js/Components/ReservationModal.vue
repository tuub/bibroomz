<template>
    <vue-final-modal
        classes="modal fade outline-none overflow-x-hidden overflow-y-auto flex items-center justify-center h-screen"
        content-class="relative flex flex-col w-1/4 max-h-full mx-4 p-4 border dark:border-gray-800 rounded bg-white dark:bg-gray-900 rounded-lg"
    >
        <!-- button close -->
        <button @click="closeModal"
                class="absolute -top-3 -right-3 h-10 w-10 rounded-full bg-red-500 text-2xl text-white hover:bg-red-600 focus:outline-none"
        >
            &cross;
        </button>
        <!-- header -->
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-600">{{ modalTitle }}</h2>
        </div>

        <!-- body -->
        <div class="w-full p-3 italic">
            <div v-if="isProcessed">
                {{ statusText }}
            </div>
            <div v-else>
                <UserInfo :userData="userData"></UserInfo>
                <ReservationInfo :reservationData="reservationData"></ReservationInfo>
            </div>
        </div>

        <!-- footer -->
        <div class="absolute bottom-0 left-0 px-4 py-3 border-t border-gray-200 w-full flex justify-end items-center gap-3">
            <div v-if="isProcessed">
                <button @click="closeModal" class="bg-gray-500 hover:bg-gray-600 px-4 py-2 rounded text-white focus:outline-none">
                    OK
                </button>
            </div>
            <div v-else>
                <button @click="submitForm" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded text-white focus:outline-none">
                    {{ modalButtonSubmit }}
                </button>
                <button @click="closeModal" class="bg-gray-500 hover:bg-gray-600 px-4 py-2 rounded text-white focus:outline-none">
                    {{ modalButtonCancel }}
                </button>
            </div>
        </div>
    </vue-final-modal>

</template>

<script>
import {$vfm} from "vue-final-modal";
import {inject, ref} from "vue";
import ReservationInfo from './ReservationInfo.vue';
import UserInfo from './UserInfo.vue';

export default {
    name: "ReservationModal",
    props: {
        calendarApi: Object,
        modalTitle: String,
        modalContent: Object,
        modalButtonSubmit: String,
        modalButtonCancel: String,
    },
    setup(props) {

        // ------------------------------------------------
        // Event Bus
        // ------------------------------------------------
        const emitter = inject('emitter');

        const reservationData = ref(props.modalContent)
        const userData = ref(props.modalContent)
        let isProcessed = ref(false)
        let statusText = ref('')

        const submitForm = () => {
            console.log(reservationData)

            axios.post('/api/reservation/add', reservationData).then((response) => {
                let apiMsg = response.data
                console.log(apiMsg)
                isProcessed.value = true
                statusText.value = apiMsg
                //closeModal()
            }).catch(function (errors) {
                let apiMsg = errors.response.data
                console.log(apiMsg)
                isProcessed.value = true
                statusText.value = 'NOT WORKING: ' + apiMsg
                //closeModal()
            }).finally(function () {
                emitter.emit('reservation-added', {'eventContent': 'We have done it!'})
            })

            //$vfm.hideAll()
        }

        const closeModal = () => {
            $vfm.hideAll()
        }

        return {
            ReservationInfo,
            UserInfo,
            reservationData,
            userData,
            isProcessed,
            statusText,
            submitForm,
            closeModal,
        }
    }
};
</script>

