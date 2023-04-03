import { defineStore } from 'pinia';

const baseUrl = `${import.meta.env.VITE_API_URL}`;

export const useAuthStore = defineStore({
    id: 'auth',
    //persist: true,
    state: () => ({
        // initialize state from local storage to enable user to stay logged in
        user: null,//JSON.par.getItem('user')),
        isAuthenticated: false, //JSON.parse(localStorage.getItem('isAuthenticated')),
        returnUrl: null,
    }),
    actions: {
        async csrf() {
            return await axios.get(`${baseUrl}/sanctum/csrf-cookie`)
        },
        async check() {
            console.log('Checking for valid backend session');
            const response = await axios.post(`${baseUrl}/check`)
            console.log(response)
            this.user = response.data.user
            this.isAuthenticated = response.data.status
        },
        async login(username, password) {
            console.log('Authenticating ' + username);
            await this.csrf()

            const response = await axios.post(`${baseUrl}/login`, { username, password })
            const user = response.data

            // update pinia state
            this.user = user;
            this.isAuthenticated = true;

            // store user details and jwt in local storage to keep user logged in between page refreshes
            //localStorage.setItem('user', JSON.stringify(user));
            //localStorage.setItem('isAuthenticated', 'true');

            // redirect to previous url or default to home page
            //router.push(this.returnUrl || '/');
        },
        async logout() {
            const user = await axios.post(`${baseUrl}/logout`)
            this.user = null;
            this.isAuthenticated = false;
            //localStorage.removeItem('user');
            //localStorage.removeItem('isAuthenticated');

            //router.push('/login');
        }
    }
});
