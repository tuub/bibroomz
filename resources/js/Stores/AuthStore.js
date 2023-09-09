import HappeningToast from "@/Components/HappeningToast.vue";
import { useAppStore } from "@/Stores/AppStore";

import { router } from "@inertiajs/vue3";
import dayjs from "dayjs";
import { trans } from "laravel-vue-i18n";
import { defineStore } from "pinia";
import { useToast } from "vue-toastification";

const baseUrl = `${import.meta.env.VITE_API_URL}`;

const toast = useToast();

export const useAuthStore = defineStore({
    id: "auth",
    persist: true,

    state: () => ({
        user: null,
        isAuthenticated: false,
        isAdmin: false,
        permissions: {},
        userHappenings: [],
        quotas: {},
        error: null,
    }),

    getters: {
        isPrivileged: (state) => {
            return state.isAdmin || Object.values(state.permissions)?.flat().length > 0;
        },
    },

    actions: {
        async csrf() {
            return await axios.get(`${baseUrl}/sanctum/csrf-cookie`);
        },

        async check() {
            try {
                const response = await axios.post(`${baseUrl}/check`);

                this.user = response.data.user;
                this.isAuthenticated = true;
                this.isAdmin = response.data.isAdmin;
                this.permissions = response.data.permissions;

                this.fetchUserHappenings();
                this.subscribe();
            } catch (error) {
                this.$reset();
            }
        },

        async login(username, password) {
            // await this.csrf();

            const response = await axios.post(`${baseUrl}/login`, {
                username,
                password,
            });

            this.user = response.data.user;
            this.isAuthenticated = true;
            this.isAdmin = response.data.isAdmin;
            this.permissions = response.data.permissions;

            this.fetchUserHappenings();
            this.subscribe();

            toast.success(trans("toast.login.success"));
        },

        async logout() {
            try {
                await axios.post(`${baseUrl}/logout`);

                this.unsubscribe();
                this.$reset();

                router.visit("/");

                toast.success(trans("toast.logout.success"));
            } catch (error) {
                toast.error(trans("toast.logout.error"));
            }
        },

        async fetchUserHappenings() {
            if (!this.isAuthenticated) {
                return;
            }

            const appStore = useAppStore();
            const institution = appStore.institution;

            if (!institution) {
                return;
            }

            try {
                const response = await axios.get(`${baseUrl}/my/happenings`, {
                    params: {
                        institution_id: institution.id,
                    },
                });

                this.userHappenings = response.data;
            } catch (error) {
                console.log("Error fetching user happenings:", error);
            }
        },

        addUserHappening(happening) {
            const index = this._findUserHappeningIndex(happening.id);
            if (index === -1) {
                this.userHappenings.push(happening);
                // Order userHappenings array by start datetime
                this.userHappenings.sort((a, b) => a.start.localeCompare(b.start));
            }
        },

        updateUserHappening(happening) {
            const index = this._findUserHappeningIndex(happening.id);
            this.userHappenings[index] = happening;
            // Order userHappenings array by start datetime
            this.userHappenings.sort((a, b) => a.start.localeCompare(b.start));
        },

        removeUserHappening(happening) {
            const index = this._findUserHappeningIndex(happening.id);
            if (index >= 0) {
                this.userHappenings.splice(index, 1);
            }
        },

        _findUserHappeningIndex(id) {
            return this.userHappenings.findIndex((x) => x.id === id);
        },

        subscribe() {
            if (!this.isAuthenticated) {
                return;
            }

            const userChannel = `happenings.${this.user.id}`;

            const showHappeningToast = (message, happening) => {
                toast.success({
                    component: HappeningToast,
                    props: { message, happening },
                });
            };

            Echo.private(userChannel)
                .listen("HappeningCreated", (event) => {
                    const happening = event.happening;
                    this.addUserHappening(happening);
                    const message = trans("toast.happening.event.created");
                    showHappeningToast(message, happening);
                })
                .listen("HappeningVerified", (event) => {
                    const happening = event.happening;
                    this.updateUserHappening(happening);
                    const message = trans("toast.happening.event.verified");
                    showHappeningToast(message, happening);
                })
                .listen("HappeningUpdated", (event) => {
                    const happening = event.happening;
                    this.updateUserHappening(happening);
                    const message = trans("toast.happening.event.updated");
                    showHappeningToast(message, happening);
                })
                .listen("HappeningDeleted", (event) => {
                    const happening = event.happening;
                    this.removeUserHappening(happening);
                    const message = trans("toast.happening.event.deleted");
                    showHappeningToast(message, happening);
                })
                .listen("UnverifiedHappeningRemovedByScheduler", (event) => {
                    const happening = event.happening;
                    this.removeUserHappening(happening);
                    const message = trans("toast.happening.event.scheduler");
                    showHappeningToast(message, happening);
                });
        },

        unsubscribe() {
            if (!this.isAuthenticated) {
                return;
            }

            const userChannel = `happenings.${this.user?.id}`;
            Echo.leave(userChannel);
        },

        _filteredUserHappenings() {
            return this.userHappenings.filter((happening) => {
                if (happening.user_01 === this.user.name) {
                    return true;
                }

                if (happening.user_02 === this.user.name && happening.isVerified) {
                    return true;
                }

                return false;
            });
        },

        updateQuotas(currentDate) {
            currentDate = dayjs(currentDate);

            const happenings = this._filteredUserHappenings();

            const isSameDay = (date) => currentDate.isSame(date, "day");
            const isSameWeek = (date) => currentDate.isSame(date, "week");

            const happeningHoursSum = (hours, happening) =>
                hours + dayjs(happening.end).diff(happening.start, "hours", true);

            const sameDayHappenings = happenings.filter((happening) => isSameDay(happening.start));
            const sameWeekHappenings = happenings.filter((happening) => isSameWeek(happening.start));

            this.quotas.daily_hours = sameDayHappenings.reduce(happeningHoursSum, 0);
            this.quotas.weekly_hours = sameWeekHappenings.reduce(happeningHoursSum, 0);
            this.quotas.weekly_happenings = sameWeekHappenings.length;
        },

        isOverlappingUserHappening(start, end) {
            return this._filteredUserHappenings().some((happening) => {
                const happeningStart = dayjs(happening.start);
                const happeningEnd = dayjs(happening.end);

                if (happeningStart >= start && happeningStart < end) {
                    return true;
                }

                if (happeningStart < start && happeningEnd > start) {
                    return true;
                }

                return false;
            });
        },

        isExceedingQuotas(start, end) {
            const institution = useAppStore().institution;

            const quota_happening_block_hours = institution.settings.quota_happening_block_hours;
            const quota_weekly_happenings = institution.settings.quota_weekly_happenings;
            const quota_weekly_hours = institution.settings.quota_weekly_hours;
            const quota_daily_hours = institution.settings.quota_daily_hours;

            if (this.can("unlimited quotas")) {
                return false;
            }

            const toastOptions = {
                id: this.$quotaToast,
            };

            if (this.isOverlappingUserHappening(start, end)) {
                toast.error(trans("toast.concurrent_happening"), toastOptions);
                return true;
            }

            const selectLength = end.diff(start, "hours", true);

            const happening_block_hours = selectLength;
            if (quota_happening_block_hours > 0 && happening_block_hours > quota_happening_block_hours) {
                toast.error(
                    trans("toast.quota.happening_block_hours", {
                        limit: quota_happening_block_hours,
                    }),
                    toastOptions,
                );

                return true;
            }

            const weekly_happenings = this.quotas.weekly_happenings + 1;
            if (quota_weekly_happenings > 0 && weekly_happenings > quota_weekly_happenings) {
                const remaining = quota_weekly_happenings - this.quotas.weekly_happenings;

                toast.error(
                    trans("toast.quota.weekly_happenings", {
                        remaining,
                        limit: quota_weekly_happenings,
                    }),
                    toastOptions,
                );

                return true;
            }

            const weekly_hours = this.quotas.weekly_hours + selectLength;
            if (quota_weekly_hours > 0 && weekly_hours > quota_weekly_hours) {
                const remaining = quota_weekly_hours - this.quotas.weekly_hours;

                toast.error(
                    trans("toast.quota.weekly_hours", {
                        remaining,
                        limit: quota_weekly_hours,
                    }),
                    toastOptions,
                );

                return true;
            }

            const daily_hours = this.quotas.daily_hours + selectLength;
            if (quota_daily_hours > 0 && daily_hours > quota_daily_hours) {
                const remaining = quota_daily_hours - this.quotas.daily_hours;

                toast.error(
                    trans("toast.quota.daily_hours", {
                        remaining,
                        limit: quota_daily_hours,
                    }),
                    toastOptions,
                );

                return true;
            }

            return false;
        },

        can(ability) {
            const appStore = useAppStore();
            const institution = appStore.institution;

            return this.hasPermission(ability, institution.id);
        },

        hasPermission(name, institution) {
            if (this.isAdmin) {
                return true;
            }

            if (!institution) {
                for (const permission of Object.values(this.permissions).flat()) {
                    if (permission === name) {
                        return true;
                    }
                }
            }

            if (this.permissions[institution]?.includes(name)) {
                return true;
            }

            return false;
        },

        canViewInstitutions() {
            if (this.hasPermission("view institutions")) {
                return true;
            }

            if (this.hasPermission("view institution")) {
                return true;
            }

            return false;
        },
    },
});
