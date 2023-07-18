import * as dayjs from 'dayjs';
import { defineStore } from 'pinia';
import { useToast } from 'vue-toastification';
import { router } from '@inertiajs/vue3'
import {useAppStore} from "@/Stores/AppStore";

const baseUrl = `${import.meta.env.VITE_API_URL}`;

const toast = useToast();

export const useAuthStore = defineStore({
    id: 'auth',
    //persist: true,
    state: () => ({
        user: null,
        isAuthenticated: false,
        isAdmin: false,
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
                this.isAdmin = response.data.admin
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
                this.isAdmin = response.data.admin;
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
        },
        async logout() {
            let response = await axios.post(`${baseUrl}/logout`)

            if (response) {
                this.unsubscribe();
                this.user = null;
                this.isAuthenticated = false;
                this.isAdmin = false;
                this.userHappenings = [];

                router.visit('/');

                toast.success(`${response.data.message}`);
            }
        },
        async fetchUserHappenings() {
            if (this.isAuthenticated) {
                let institution = useAppStore().institution
                await axios.get(`${baseUrl}/my/happenings`, {params: {institution_id: institution.id}}).then((response) => {
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
            let index = this._findUserHappeningIndex(happening.id)
            if (index === -1) {
                this.userHappenings.push(happening)
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
        isExceedingQuotas(start, end) {
            let institution = useAppStore().institution

            const quota_happening_block_hours = institution.settings.quota_happening_block_hours;
            const quota_weekly_happenings = institution.settings.quota_weekly_happenings;
            const quota_weekly_hours = institution.settings.quota_weekly_hours;
            const quota_daily_hours = institution.settings.quota_daily_hours;

            if (this.isAdmin) {
                return false;
            }

            const happening_block_hours = end.diff(start, 'hours', true);
            if (happening_block_hours > quota_happening_block_hours) {
                return true;
            }

            let weekly_happenings = 1;
            let weekly_hours = happening_block_hours;
            let daily_hours = happening_block_hours;

            const happenings = this.userHappenings.filter(happening => {
                if (happening.user_01 === this.user.name) {
                    return true;
                }

                return happening.user_02 === this.user.name && happening.is_confirmed;
            });

            for (let happening of happenings) {
                if (start.isSame(happening.start, 'week')) {
                    weekly_happenings += 1;
                    weekly_hours += dayjs(happening.end).diff(happening.start, 'hours', true);
                }

                if (start.isSame(happening.start, 'day')) {
                    daily_hours += dayjs(happening.end).diff(happening.start, 'hours', true);
                }
            }

            if (weekly_happenings > quota_weekly_happenings) {
                return true;
            } else if (weekly_hours > quota_weekly_hours) {
                return true;
            } else if (daily_hours > quota_daily_hours) {
                return true;
            }

            return false;
        }
    },
});
