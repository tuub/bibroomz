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
            doRefreshCalendar: false,
            doRefreshInterface: false,
            doModalClose: false,
            modal: useModal(),
        }
    },
    actions: {
        async addHappening(happeningData) {
            return axios.post(`${baseUrl}/happenings/add`, happeningData)
                .then((response) => {
                    console.log('Response from backend:')
                    console.log(response)
                    this.doRefreshCalendar = true
                    this.modal.close()
                }).catch((error) => {
                    console.log('API Error:')
                    console.log(error)
                })
            //this.doRefreshInterface = true
        },
        addHappeningOld(happeningData) {
            axios.post(`${baseUrl}/happenings/add`, happeningData)
                .then((response) => {
                    this.validationErrors = {}
                    this.doRefreshCalendar = true
                    this.doRefreshInterface = true
                    return true
                })
                .catch((error) => {
                    console.log('API Error:');
                    this.validationErrors = error.response.data.errors
                    console.log(this.validationErrors)
                })
        },
        editHappening(happeningData) {
            let id = happeningData.id
            axios.post(`${baseUrl}/happenings/update/${id}`, happeningData)
                .then((response) => {
                    this.validationErrors = {}
                    this.doRefreshCalendar = true
                    this.doRefreshInterface = true
                    return true
                })
                .catch((error) => {
                    console.log('API Error:');
                    this.validationErrors = error.response.data.errors
                    console.log(this.validationErrors)
                })
        },
        async deleteHappening(id) {
            return await axios.delete(`${baseUrl}/happenings/delete/${id}`).then((response) => {
                this.modal.close()
            }).catch((error) => {
                console.log('API Error:')
                console.log(error)
            })
            //this.doRefreshInterface = true
        }
        /*
        async deleteHappening(modal, id) {
            try {
                await axios.delete(`${baseUrl}/happenings/delete/${id}`)
                modal.close()
            } catch (error) {
                console.log('API Error:')
                console.log(error)
            }
            //this.doRefreshInterface = true
        },
         */
    },
    getters: {
        getHappeningResource: (state) => state.happening.resource,
        getHappeningStart: (state) => state.happening.start,
        getHappeningEnd: (state) => state.happening.end,
        getValidationErrors: (state) => state.validationErrors,
        getDoRefreshCalendar: (state) => state.doRefreshCalendar,
        getDoRefreshInterface: (state) => state.doRefreshInterface,
    },
});
