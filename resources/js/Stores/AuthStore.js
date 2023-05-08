import { defineStore } from 'pinia';

const baseUrl = `${import.meta.env.VITE_API_URL}`;

export const useAuthStore = defineStore({
    id: 'auth',
    //persist: true,
    state: () => ({
        user: null,
        isAuthenticated: false,
        userHappenings: [],
    }),
    actions: {
        async csrf() {
            return await axios.get(`${baseUrl}/sanctum/csrf-cookie`)
        },
        async check() {
            await axios.post(`${baseUrl}/check`).then((response) => {
                this.user = response.data.user
                this.isAuthenticated = response.data.status
                this.fetchUserHappenings()
                this.subscribe();
            })
        },
        async login(username, password) {
            await this.csrf()

            let response = await axios.post(`${baseUrl}/login`, {
                username,
                password,
            });

            if (response) {
                this.user = response.data.user;
                this.isAuthenticated = true;
                await this.fetchUserHappenings();
                this.subscribe();
            }
        },
        async logout() {
            let response = await axios.post(`${baseUrl}/logout`)

            if (response) {
                this.unsubscribe();
                this.user = null;
                this.isAuthenticated = false;
                this.userHappenings = [];
            }
        },
        async fetchUserHappenings() {
            await axios.get(`${baseUrl}/my/happenings`).then((response) => {
                this.userHappenings = response.data
            }).catch((response) => {
                console.log('API Error:')
                console.log(response)
            });
        },
        addUserHappening(happening) {
            this.userHappenings.push(happening)
            // Order userHappenings array by start datetime
            this.userHappenings.sort((a, b) => a.start.localeCompare(b.start));
        },
        updateUserHappening(happening) {
            let existingIndex = this.userHappenings.findIndex(
                (obj) => obj.id === happening.id
            );
            this.userHappenings[existingIndex] = happening;
            // Order userHappenings array by start datetime
            this.userHappenings.sort((a, b) => a.start.localeCompare(b.start));
        },
        removeUserHappening(id) {
            let index = this.userHappenings.findIndex(x => x.id === id)
            if (index >= 0) {
                this.userHappenings.splice(index, 1)
            }
        },
        subscribe() {
            if (this.isAuthenticated) {
                let userChannel = `happenings.${this.user.id}`;
                Echo.private(userChannel)
                    .listen("HappeningCreated", (e) => {
                        this.addUserHappening(e.happening);
                    })
                    .listen("HappeningUpdated", (e) => {
                        this.updateUserHappening(e.happening);
                    })
                    .listen("HappeningDeleted", (e) => {
                        this.removeUserHappening(e.id);
                    });
            } else {
                console.log('Could not subscribe to private channel, no auth')
            }
        },
        unsubscribe() {
            if (this.isAuthenticated) {
                let userChannel = `happenings.${this.user.id}`;
                Echo.leave(userChannel);
            } else {
                console.log('Could not unsubscribe to private channel, no auth')
            }
        },

    },
});
