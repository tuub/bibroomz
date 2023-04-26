import {defineStore} from "pinia";

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
        }
    },
    actions: {
        addHappening(happeningData) {
            axios.post('/happenings/add', happeningData)
                .then((response) => {
                    console.log('API response:')
                    console.log(response)
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
        editHappening(id) {
            /*
            axios.post(`${baseUrl}/happenings/edit/${id}`, happeningData)
                .then((response) => {
                    console.log(response)
                })
                .catch((error) => {
                    console.log('API Error:');
                    this.validationErrors = error.response.data.errors
                    console.log(this.validationErrors)
                })
             */
        },

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
