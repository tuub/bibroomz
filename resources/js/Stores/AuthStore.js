import { useAppStore } from "@/Stores/AppStore";
import { useToastStore } from "@/Stores/ToastStore";

import { router } from "@inertiajs/vue3";
import dayjs from "dayjs";
import isoWeek from "dayjs/plugin/isoWeek";
import { trans } from "laravel-vue-i18n";
import { defineStore } from "pinia";

const baseUrl = `${import.meta.env.VITE_API_URL}`;

dayjs.extend(isoWeek);

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
        isProcessingLogin: false,
        allowedResourceGroups: [],
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
                this.allowedResourceGroups = response.data.allowedResourceGroups;

                this.fetchUserHappenings();
                this.subscribe();
            } catch (error) {
                this.$reset();
            }
        },

        async login(username, password) {
            const toastStore = useToastStore();

            // await this.csrf();

            const response = await axios.post(`${baseUrl}/login`, {
                username,
                password,
            });

            this.user = response.data.user;
            this.isAuthenticated = true;
            this.isAdmin = response.data.isAdmin;
            this.permissions = response.data.permissions;
            this.allowedResourceGroups = response.data.allowedResourceGroups;

            this.fetchUserHappenings();
            this.subscribe();

            toastStore.addAuthToast({ summary: trans("toast.login.success") });
        },

        async logout() {
            const toastStore = useToastStore();

            try {
                await axios.post(`${baseUrl}/logout`);

                this.unsubscribe();
                this.$reset();

                router.visit("/");

                toastStore.addAuthToast({ summary: trans("toast.logout.success") });
            } catch (error) {
                toastStore.addAuthToast({ severity: "error", summary: trans("toast.logout.error") });
            }
        },

        async fetchUserHappenings() {
            if (!this.isAuthenticated) {
                return;
            }

            const appStore = useAppStore();
            const resourceGroup = appStore.resourceGroup;

            if (!resourceGroup) {
                return;
            }

            try {
                const response = await axios.get(`${baseUrl}/my/happenings`, {
                    params: {
                        resource_group_id: resourceGroup.id,
                    },
                });

                this.userHappenings = response.data;
            } catch (error) {
                console.log("Error fetching user happenings:", error);
            }
        },

        addUserHappening(happening) {
            const index = this._findUserHappeningIndex(happening.id);

            // filter duplicate happenings
            if (index > -1) {
                return;
            }

            this.userHappenings.push(happening);
            this.userHappenings.sort((a, b) => a.start.localeCompare(b.start));
        },

        updateUserHappening(happening) {
            const index = this._findUserHappeningIndex(happening.id);

            this.userHappenings[index] = happening;
            this.userHappenings.sort((a, b) => a.start.localeCompare(b.start));
        },

        removeUserHappening(happening) {
            const index = this._findUserHappeningIndex(happening.id);

            if (index < 0) {
                return;
            }

            this.userHappenings.splice(index, 1);
        },

        _findUserHappeningIndex(id) {
            return this.userHappenings.findIndex((x) => x.id === id);
        },

        updateUserHappenings({ happening, callback, summary }) {
            const appStore = useAppStore();
            const resourceGroup = appStore.resourceGroup;
            const toastStore = useToastStore();

            // filter happenings from other institutions
            if (happening.resource.resourceGroupId !== resourceGroup.id) {
                return;
            }

            callback(happening);

            toastStore.addHappeningToast({ happening, summary });
        },

        subscribe() {
            if (!this.isAuthenticated) {
                return;
            }

            const userChannel = `happenings.${this.user.id}`;

            Echo.private(userChannel)
                .listen("HappeningCreatedEvent", (event) => {
                    this.updateUserHappenings({
                        happening: event.happening,
                        callback: this.addUserHappening,
                        summary: trans("toast.happening.event.created"),
                    });
                })
                .listen("HappeningUpdatedEvent", (event) => {
                    this.updateUserHappenings({
                        happening: event.happening,
                        callback: this.updateUserHappening,
                        summary: trans("toast.happening.event.updated"),
                    });
                })
                .listen("HappeningDeletedEvent", (event) => {
                    this.updateUserHappenings({
                        happening: event.happening,
                        callback: this.removeUserHappening,
                        summary: trans("toast.happening.event.deleted"),
                    });
                })
                .listen("HappeningVerifiedEvent", (event) => {
                    this.updateUserHappenings({
                        happening: event.happening,
                        callback: this.updateUserHappening,
                        summary: trans("toast.happening.event.verified"),
                    });
                })
                .listen("UnverifiedHappeningRemovedBySchedulerEvent", (event) => {
                    this.updateUserHappenings({
                        happening: event.happening,
                        callback: this.removeUserHappening,
                        summary: trans("toast.happening.event.scheduler"),
                    });
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
            const isSameWeek = (date) => currentDate.isSame(date, "isoWeek");

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
            const toastStore = useToastStore();
            const settings = useAppStore().settings["resource_group"];

            const quota_happening_block_hours = settings.quota_happening_block_hours;
            const quota_weekly_happenings = settings.quota_weekly_happenings;
            const quota_weekly_hours = settings.quota_weekly_hours;
            const quota_daily_hours = settings.quota_daily_hours;

            if (this.can("unlimited_quotas")) {
                return false;
            }

            if (this.isOverlappingUserHappening(start, end)) {
                const summary = trans("toast.concurrent_happening");
                toastStore.addQuotaToast({ summary });
                return true;
            }

            const selectLength = end.diff(start, "hours", true);

            const happening_block_hours = selectLength;
            if (quota_happening_block_hours > 0 && happening_block_hours > quota_happening_block_hours) {
                const summary = trans("toast.quota.happening_block_hours", {
                    limit: quota_happening_block_hours,
                });

                toastStore.addQuotaToast({ summary });

                return true;
            }

            const weekly_happenings = this.quotas.weekly_happenings + 1;
            if (quota_weekly_happenings > 0 && weekly_happenings > quota_weekly_happenings) {
                const remaining = quota_weekly_happenings - this.quotas.weekly_happenings;

                const summary = trans("toast.quota.weekly_happenings", {
                    remaining,
                    limit: quota_weekly_happenings,
                });

                toastStore.addQuotaToast({ summary });

                return true;
            }

            const weekly_hours = this.quotas.weekly_hours + selectLength;
            if (quota_weekly_hours > 0 && weekly_hours > quota_weekly_hours) {
                const remaining = quota_weekly_hours - this.quotas.weekly_hours;

                const summary = trans("toast.quota.weekly_hours", {
                    remaining,
                    limit: quota_weekly_hours,
                });

                toastStore.addQuotaToast({ summary });

                return true;
            }

            const daily_hours = this.quotas.daily_hours + selectLength;
            if (quota_daily_hours > 0 && daily_hours > quota_daily_hours) {
                const remaining = quota_daily_hours - this.quotas.daily_hours;

                const summary = trans("toast.quota.daily_hours", {
                    remaining,
                    limit: quota_daily_hours,
                });

                toastStore.addQuotaToast({ summary });

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

        isAllowedForResource(resource) {
            const isAllowed = this.allowedResourceGroups.includes(resource.resourceGroup);

            if (!isAllowed) {
                const appStore = useAppStore();
                const toastStore = useToastStore();
                const translate = appStore.translate;

                const summary = trans("toast.wrong_user_group", {
                    resource_type: translate(resource.translations.resourceGroup),
                    resource_title: translate(resource.translations.title),
                });

                toastStore.addUserGroupToast({ summary });
            }

            return isAllowed;
        },

        canViewInstitutions() {
            if (this.hasPermission("view_institutions")) {
                return true;
            }

            if (this.hasPermission("view_institution")) {
                return true;
            }

            return false;
        },
    },
});
