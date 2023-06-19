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
            return axios.post(`${baseUrl}/happening/add`, happening);
        },
        editHappening(happening) {
            return axios.post(`${baseUrl}/happening/update/${happening.id}`, happening);
        },
        confirmHappening(happening) {
            console.log(happening)
            return axios.post(`${baseUrl}/happening/confirm/${happening.id}`, happening);
        },
        deleteHappening(id) {
            return axios.delete(`${baseUrl}/happening/delete/${id}`);
        }
    },
    getters: {
        getHappeningResource: (state) => state.happening.resource,
        getHappeningStart: (state) => state.happening.start,
        getHappeningEnd: (state) => state.happening.end,
        getValidationErrors: (state) => state.validationErrors,
    },
});
