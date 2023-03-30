import {defineStore} from "pinia";

export const useUserStore = defineStore('userStore', {
    state: () => {
        return {
            user: {},
            user_reservations: {},
        }
    },
    actions: {
        addUser(user) {
            this.user = user;
        },
        addUserReservation(reservation) {
            this.user_reservations.push(reservation)
        }
    },
    getters: {
        userName: (state) => state.user.name,
        userReservations: (state) => state.user_reservations,
    },
});
