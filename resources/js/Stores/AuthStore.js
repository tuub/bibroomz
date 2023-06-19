import { defineStore } from 'pinia';
import { useToast } from 'vue-toastification';
// import dayjs from 'dayjs';

const baseUrl = `${import.meta.env.VITE_API_URL}`;

const toast = useToast();

export const useAuthStore = defineStore({
    id: 'auth',
    //persist: true,
    state: () => ({
        user: null,
        isAuthenticated: false,
        userHappenings: [],
        errors: [],
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

            axios.post(`${baseUrl}/login`, {
                username,
                password,
            }).then(async (response) => {
                this.user = response.data.user;
                this.isAuthenticated = true;
                await this.fetchUserHappenings();
                this.subscribe();

                toast.success(`${response.data.message}`);
            }).catch((error) => {
                if (error.response.data.errors) {
                    this.errors.value = Object.values(error.response.data.errors).flat()
                }

                    toast.error(`Login attempt failed: ${error.response.data.errors}`);
            });

            /*
            let response = await axios.post(`${baseUrl}/login`, {
                username,
                password,
            });

            if (response) {
                this.user = response.data.user;
                this.isAuthenticated = true;
                await this.fetchUserHappenings();
                this.subscribe();
            } else {
                console.log('AUTH ERROR')
            }
             */
        },
        async logout() {
            let response = await axios.post(`${baseUrl}/logout`)

            if (response) {
                this.unsubscribe();
                this.user = null;
                this.isAuthenticated = false;
                this.userHappenings = [];

                toast.success(`${response.data.message}`);
            }
        },
        async fetchUserHappenings() {
            if (this.isAuthenticated) {
                await axios.get(`${baseUrl}/my/happenings`).then((response) => {
                    this.userHappenings = response.data
                }).catch((response) => {
                    console.log('API Error:')
                    console.log(response)
                });
            } else {
                console.log('Only fetch user happenings when authenticated!')
            }
        },
        addUserHappening(happening) {

            console.log(happening.id)
            console.log(this.userHappenings)

            let index = this._findUserHappeningIndex(happening.id)
            if (index === -1) {
                this.userHappenings.push(happening)
                console.log(happening)
                // Order userHappenings array by start datetime
                this.userHappenings.sort((a, b) => a.start.localeCompare(b.start));
            } else {
                console.log('Attempt to add multiple identical entries. Skipping.')
            }
        },
        updateUserHappening(happening) {
            let index = this._findUserHappeningIndex(happening.id)
            this.userHappenings[index] = happening;
            // Order userHappenings array by start datetime
            this.userHappenings.sort((a, b) => a.start.localeCompare(b.start));
        },
        removeUserHappening(id) {
            let index = this._findUserHappeningIndex(id)
            if (index >= 0) {
                this.userHappenings.splice(index, 1)
            }
        },
        _findUserHappeningIndex(id) {
            console.log(id)
            console.log(this.userHappenings)
            return this.userHappenings.findIndex(x => x.id === id)
        },
        subscribe() {
            if (this.isAuthenticated) {
                let userChannel = `happenings.${this.user.id}`;
                Echo.private(userChannel)
                    .listen("HappeningCreated", (e) => {
                        this.addUserHappening(e.happening);
                        toast.success(`Happening created! ${e.happening.id}`);
                    })
                    .listen("HappeningConfirmed", (e) => {
                        this.updateUserHappening(e.happening);
                        toast.success(`Happening confirmed! ${e.happening.id}`);
                    })
                    .listen("HappeningUpdated", (e) => {
                        this.updateUserHappening(e.happening);
                        toast.success(`Happening updated! ${e.happening.id}`);
                    })
                    .listen("HappeningDeleted", (e) => {
                        this.removeUserHappening(e.id);
                        toast.success(`Happening deleted! ${e.id}`);
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
                console.log('Could not unsubscribe from private channel, no auth')
            }
        },
    },
});
