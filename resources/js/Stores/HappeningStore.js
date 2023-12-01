import { defineStore } from "pinia";

const baseUrl = `${import.meta.env.VITE_API_URL}`;

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
            return axios.post(`${baseUrl}/happening/add`, happening);
        },

        editHappening(happening) {
            return axios.post(`${baseUrl}/happening/update/${happening.id}`, happening);
        },

        verifyHappening(happening) {
            return axios.post(`${baseUrl}/happening/verify/${happening.id}`, happening);
        },

        deleteHappening(id) {
            return axios.delete(`${baseUrl}/happening/delete/${id}`);
        },
    },

    getters: {
        getHappeningResource: (state) => state.happening.resource,
        getHappeningStart: (state) => state.happening.start,
        getHappeningEnd: (state) => state.happening.end,
    },
});
