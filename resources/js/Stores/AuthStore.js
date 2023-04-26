import { defineStore } from 'pinia';

const baseUrl = `${import.meta.env.VITE_API_URL}`;

export const useAuthStore = defineStore({
    id: 'auth',
    //persist: true,
    state: () => ({
        user: null,
        isAuthenticated: false,
        doRefreshInterface: false,
        userEvents: {},
    }),
    actions: {
        async csrf() {
            return await axios.get(`${baseUrl}/sanctum/csrf-cookie`)
        },
        async check() {
            await axios.post(`${baseUrl}/check`).then((response) => {
                this.user = response.data.user
                this.fetchUserEvents()
                this.isAuthenticated = response.data.status
                this.doRefreshInterface = true
            })
        },
        async login(username, password) {
            await this.csrf()

            await axios.post(`${baseUrl}/login`, {username, password}).then((response) => {
                this.user = response.data
                this.isAuthenticated = true;
                this.fetchUserEvents()
                console.log(this.isAuthenticated)

            })
        },
        async logout() {
            await axios.post(`${baseUrl}/logout`).then(() => {
                this.user = null;
                this.isAuthenticated = false;
                this.userEvents = {};
            })
        },
        async fetchUserEvents() {
            await axios.get(`${baseUrl}/my/events`).then((response) => {
                this.userEvents = response.data
            }).catch((response) => {
                console.log('API Error:')
                console.log(response)
            });
        },
    },
    getters: {
        getIsAuthenticated: (state) => state.isAuthenticated,
    },
});
