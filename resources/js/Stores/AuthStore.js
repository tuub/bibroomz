import { defineStore } from 'pinia';

const baseUrl = `${import.meta.env.VITE_API_URL}`;

export const useAuthStore = defineStore({
    id: 'auth',
    //persist: true,
    state: () => ({
        user: null,
        isAuthenticated: false,
        doRefreshInterface: false,
        userHappenings: [],
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
                this.userHappenings = [];
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
        async fetchUserHappening(id) {
            await axios.get(`${baseUrl}/my/happenings`).then((response) => {
                this.userHappenings = response.data
            }).catch((response) => {
                console.log('API Error:')
                console.log(response)
            });
        },
        addUserHappening(happening) {
            console.log('Happening to add via websockets:')
            console.log(happening)
            this.userHappenings.push(happening)
            // Order userHappenings array by start datetime
            this.userHappenings.sort((a, b) => a.start.localeCompare(b.start));
        },
        updateUserHappening(happening) {
            console.log('Happening to update via websockets:')
            let existingIndex = this.userHappenings.findIndex((obj => obj.id === happening.id));
            this.userHappenings[existingIndex] = happening;
            // Order userHappenings array by start datetime
            this.userHappenings.sort((a, b) => a.start.localeCompare(b.start));
        },
        removeUserHappening(id) {
            console.log('ID to delete via websockets: ' + id)
            let index = this.userHappenings.findIndex(x => x.id === id)
            this.userHappenings.splice(index, 1)
        }
    },
});
