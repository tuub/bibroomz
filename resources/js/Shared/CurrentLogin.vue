<template>
    <!--div v-show="isAuthenticated" class="flex items-center md:order-2">
        <button id="user-menu-button"
                type="button"
                class="flex mr-3 text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                aria-expanded="false"
                data-dropdown-toggle="user-dropdown"
                data-dropdown-placement="bottom">
            <span class="sr-only">Open user menu</span>
            <div class="relative w-10 h-10 bg-gray-100 rounded-full dark:bg-gray-600">
                <svg class="absolute w-12 h-12 text-gray-400 -left-1"
                     fill="currentColor"
                     viewBox="0 0 20 20"
                     xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd"
                          d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                          clip-rule="evenodd">
                    </path>
                </svg>
            </div>
        </button>

        <div id="user-dropdown"
             class="">
            <div class="px-4 py-3">
                <span class="">
                    {{ currentUser?.name }}
                </span>
                <span class="">
                    {{ currentUser?.email }}
                </span>
            </div>
            <ul class="py-2" aria-labelledby="user-menu-button">
                <li>
                    <a href="#"
                       class=""
                       @click="logoutUser">
                        {{ $t('navigation.current_login.logout') }}
                    </a>
                </li>
            </ul>
        </div>
    </div-->
        <span class="current-User">
            {{ currentUser?.name }}
        </span> 
        <ul class="py-2 logoutUser" aria-labelledby="user-menu-button">
            <li class="logoutUser">
                <a href="#"
                    class=""
                    @click="logoutUser">
                    {{ $t('navigation.current_login.logout') }}
                </a>
            </li>
        </ul>
</template>

<script setup>
import {useAuthStore} from "@/Stores/AuthStore";
import {storeToRefs} from "pinia";
import { initFlowbite } from "flowbite";
import {onMounted} from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
let { isAuthenticated, user: currentUser } = storeToRefs(authStore);

// ------------------------------------------------
// Methods
// ------------------------------------------------
let logoutUser = async () => {
    try {
        return await authStore.logout();
    } catch (error) {
        return console.log(error);
    }
};

// ------------------------------------------------
// Mount
// ------------------------------------------------
onMounted(() => {
    initFlowbite();
});
</script>

<style>

.current-User{
    padding-right: 12px;
    float: left;
}
.logoutUser{
    display: contents;
}
</style>