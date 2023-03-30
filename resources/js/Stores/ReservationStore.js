import {defineStore} from "pinia";

export const useReservationStore = defineStore('reservationStore', {
    state: () => {
        return {
            resource: '',
            start: '',
            end: '',
        }
    },
    actions: {
        addCurrentReservation(reservationData) {
            this.resource = reservationData.resource;
            this.start = reservationData.start;
            this.end = reservationData.end;
        },
    },
    getters: {
        getReservationResource: (state) => state.resource,
        getReservationStart: (state) => state.start,
        getReservationEnd: (state) => state.end,
    },
});
