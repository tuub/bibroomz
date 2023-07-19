<template>
    <div v-show="isAuthenticated" class="flex items-center md:order-2">
        <button id="user-menu-button"
                type="button"
                class="flex mr-3 text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                aria-expanded="false"
                data-dropdown-toggle="user-dropdown"
                data-dropdown-placement="bottom">
            <span class="sr-only">Open user menu</span>
            <div class="relative w-10 h-10 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600">
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
        <!-- Dropdown menu -->
        <div id="user-dropdown"
             class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow dark:bg-gray-700 dark:divide-gray-600">
            <div class="px-4 py-3">
                        <span class="block text-sm text-gray-900 dark:text-white">
                            {{ currentUser?.name }}
                        </span>
                <span class="block text-sm text-gray-500 truncate dark:text-gray-400">
                            {{ currentUser?.email }}
                        </span>
            </div>
            <ul class="py-2" aria-labelledby="user-menu-button">
                <li>
                    <a href="#"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                       @click="logoutUser">
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
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

