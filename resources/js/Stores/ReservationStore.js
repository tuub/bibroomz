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
            doRefreshCalendar: false,
            doRefreshInterface: false,
        }
    },
    actions: {
        addReservation(reservationData) {
            axios.post('/reservations/add', reservationData)
                .then((response) => {
                    console.log('API response:')
                    console.log(response)
                    this.validationErrors = {}
                    this.doRefreshCalendar = true
                    this.doRefreshInterface = true
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
        getDoRefreshCalendar: (state) => state.doRefreshCalendar,
        getDoRefreshInterface: (state) => state.doRefreshInterface,
    },
});
