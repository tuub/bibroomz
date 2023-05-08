import {defineStore} from "pinia";
import useModal from "./Modal";

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
            validationErrors: {},
            doModalClose: false,
            modal: useModal(),
        }
    },
    actions: {
        async addHappening(happeningData) {
            return axios.post(`${baseUrl}/happenings/add`, happeningData)
                .then(() => {
                    this.validationErrors = {}
                    this.modal.close()
                }).catch((error) => {
                    console.log('API Error:')
                    this.validationErrors = error.response.data.errors
                    console.log(this.validationErrors)
                })
        },
        async editHappening(happeningData) {
            let id = happeningData.id
            return axios.post(`${baseUrl}/happenings/update/${id}`, happeningData)
                .then(() => {
                    this.validationErrors = {}
                    this.modal.close()
                })
                .catch((error) => {
                    console.log('API Error:');
                    this.validationErrors = error.response.data.errors
                    console.log(this.validationErrors)
                })
        },
        async deleteHappening(id) {
            return await axios.delete(`${baseUrl}/happenings/delete/${id}`).then((response) => {
                // FIXME: status message
                this.modal.close()
            }).catch((error) => {
                console.log('API Error:')
                console.log(error)
            })
        }
    },
    getters: {
        getHappeningResource: (state) => state.happening.resource,
        getHappeningStart: (state) => state.happening.start,
        getHappeningEnd: (state) => state.happening.end,
        getValidationErrors: (state) => state.validationErrors,
    },
});
