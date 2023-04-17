import {defineStore} from "pinia";

export const useReservationStore = defineStore({
    id: 'reservation',
    state: () => {
        return {
            reservation: {
                resource: {},
                start: '',
                end: '',
            },

            validationErrors: {},
            isModalOpSuccessful: false,
            doCalendarRefetch: false,
        }
    },
    actions: {
        addReservation(reservationData) {
            axios.post('/reservations/add', reservationData)
                .then((response) => {
                    console.log('API response:')
                    console.log(response)
                    this.validationErrors = {}
                    this.doCalendarRefetch = true
                    this.isModalOpSuccessful = true
                    console.log('Done.')
                    return true
                })
                .catch((error) => {
                    console.log('API Error:');
                    this.validationErrors = error.response.data.errors
                    console.log(this.validationErrors)
                })
        }
    },
    getters: {
        getReservationResource: (state) => state.reservation.resource,
        getReservationStart: (state) => state.reservation.start,
        getReservationEnd: (state) => state.reservation.end,
        getValidationErrors: (state) => state.validationErrors,
        getDoRefetch: (state) => state.doCalendarRefetch,
        getIsModalOpSuccessful: (state) => state.isModalOpSuccessful,
    },
});
