import { defineStore } from 'pinia';

const baseUrl = `${import.meta.env.VITE_API_URL}`;

export const useAuthStore = defineStore({
    id: 'auth',
    //persist: true,
    state: () => ({
        user: null,
        isAuthenticated: false,
        doRefreshInterface: false,
        userHappenings: {},
    }),
    actions: {
        async csrf() {
            return await axios.get(`${baseUrl}/sanctum/csrf-cookie`)
        },
        async check() {
            await axios.post(`${baseUrl}/check`).then((response) => {
                this.user = response.data.user
                this.fetchUserHappenings()
                this.isAuthenticated = response.data.status
                this.doRefreshInterface = true
            })
        },
        async login(username, password) {
            await this.csrf()

            await axios.post(`${baseUrl}/login`, {username, password}).then((response) => {
                this.user = response.data
                this.isAuthenticated = true;
                this.fetchUserHappenings()
                console.log(this.isAuthenticated)

            })
        },
        async logout() {
            await axios.post(`${baseUrl}/logout`).then(() => {
                this.user = null;
                this.isAuthenticated = false;
                this.userHappenings = {};
            })
        },
        async fetchUserHappenings() {
            await axios.get(`${baseUrl}/my/happenings`).then((response) => {
                this.userHappenings = response.data
            }).catch((response) => {
                console.log('API Error:')
                console.log(response)
            });
        },
        async deleteUserHappening(id) {
            await axios.delete(`${baseUrl}/happenings/${id}`).then((response) => {
                console.log(response)
                this.fetchUserHappenings()
                this.doRefreshInterface = true
            }).catch((response) => {
                console.log('API Error:')
                console.log(response)
            });
        },
    },
    getters: {
        //getIsAuthenticated: (state) => state.isAuthenticated,
    },
});
