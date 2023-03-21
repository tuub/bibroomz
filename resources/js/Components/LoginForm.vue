<template>
    <h1 class="text-xl font-bold">Login</h1>

    {{ $page.props.errors }}

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">
        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="username">
                Barcode
            </label>
            <input v-model="form.username"
                   class="border border-gray-400 p-2 w-full"
                   type="text"
                   name="username"
                   id="username"
            >
        </div>
        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="password">
                Password
            </label>
            <input v-model="form.password"
                   class="border border-gray-400 p-2 w-full"
                   type="text"
                   name="password"
                   id="password"
            >
        </div>
        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="form.processing">
                Submit
            </button>
        </div>
        <p>Credentials: {{ form }}</p>
    </form>

</template>

<script setup>
import {router, useForm} from "@inertiajs/vue3";

let form = useForm({
    username: '',
    password: '',
})

let submitForm = () => {
    console.log('AUTH')
    router.post('/login', form);
}

/*
import {ref, reactive} from 'vue'

export default {
    name: 'LoginForm',
    setup(props, context) {
        let auth = reactive({
            email: '',
            password: ''
        })
        let validationErrors = ref({})
        let isProcessing = ref(false)

        function sleep(milliseconds) {
            const date = Date.now();
            let currentDate = null;
            do {
                currentDate = Date.now();
            } while (currentDate - date < milliseconds);
        }

        async function postLogin() {
            // Set processing state
            isProcessing.value = true
            console.log(isProcessing)
            //sleep(3000)
            // Cookie auth
            await axios.get('/sanctum/csrf-cookie')
            // Login
            await axios.post('/login', auth).then(({data}) => {
                //this.signIn()
                console.log(data)
            }).catch(({response}) => {
                if (response.status === 422) {
                    validationErrors.value = response.data.errors
                } else {
                    validationErrors.value = {}
                    alert(response.data.message)
                }
            }).finally(() => {
                isProcessing.value = false
            })
        }

        return {
            auth,
            validationErrors,
            isProcessing,
            postLogin,
        }
    }
}
*/
</script>
