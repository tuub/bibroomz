import {defineStore} from "pinia";

const baseUrl = `${import.meta.env.VITE_API_URL}`;

export const useHappeningStore = defineStore({
    id: 'happening',
    state: () => {
        return {
            happening: {
                resource: {},
                start: '',
                end: '',
            },
        }
    },
    actions: {
        addHappening(happening) {
            return axios.post(`${baseUrl}/happenings/add`, happening);
        },
        editHappening(happening) {
            return axios.post(`${baseUrl}/happenings/update/${happening.id}`, happening);
        },
        deleteHappening(id) {
            return axios.delete(`${baseUrl}/happenings/delete/${id}`);
        }
    },
    getters: {
        getHappeningResource: (state) => state.happening.resource,
        getHappeningStart: (state) => state.happening.start,
        getHappeningEnd: (state) => state.happening.end,
        getValidationErrors: (state) => state.validationErrors,
    },
});
