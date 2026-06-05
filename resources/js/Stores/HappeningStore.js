import { withBaseUrl } from "@/baseUrl";

import { defineStore } from "pinia";

export const useHappeningStore = defineStore({
    id: "happening",
    persist: true,

    state: () => {
        return {
            happening: {
                resource: {},
                start: "",
                end: "",
            },
            error: "",
        };
    },

    actions: {
        addHappening(happening) {
            return axios.post(withBaseUrl("/happening/add"), happening);
        },

        editHappening(happening) {
            return axios.post(withBaseUrl(`/happening/update/${happening.id}`), happening);
        },

        verifyHappening(happening) {
            return axios.post(withBaseUrl(`/happening/verify/${happening.id}`), happening);
        },

        deleteHappening(id) {
            return axios.delete(withBaseUrl(`/happening/delete/${id}`));
        },
    },

    getters: {
        getHappeningResource: (state) => state.happening.resource,
        getHappeningStart: (state) => state.happening.start,
        getHappeningEnd: (state) => state.happening.end,
    },
});
