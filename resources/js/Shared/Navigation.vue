<template>
    <nav class="bg-white border-gray-200 dark:bg-gray-900">
        <div
            class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4"
        >
            <a href="https://flowbite.com/" class="flex items-center">
                <img
                    src="https://flowbite.com/docs/images/logo.svg"
                    class="h-8 mr-3"
                    alt="Flowbite Logo"
                />
                <span
                    class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white"
                    >Roomz</span
                >
            </a>
            <div v-show="isAuthenticated" class="flex items-center md:order-2">
                <button
                    id="user-menu-button"
                    type="button"
                    class="flex mr-3 text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                    aria-expanded="false"
                    data-dropdown-toggle="user-dropdown"
                    data-dropdown-placement="bottom"
                >
                    <span class="sr-only">Open user menu</span>

                    <div
                        class="relative w-10 h-10 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-600"
                    >
                        <svg
                            class="absolute w-12 h-12 text-gray-400 -left-1"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                    </div>
                </button>
                <!-- Dropdown menu -->
                <div
                    id="user-dropdown"
                    class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow dark:bg-gray-700 dark:divide-gray-600"
                >
                    <div class="px-4 py-3">
                        <span
                            class="block text-sm text-gray-900 dark:text-white"
                            >{{ user?.name }}</span
                        >
                        <span
                            class="block text-sm text-gray-500 truncate dark:text-gray-400"
                            >{{ user?.email }}</span
                        >
                    </div>
                    <ul class="py-2" aria-labelledby="user-menu-button">
                        <li>
                            <a
                                href="#"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                                @click="logout"
                                >Logout</a
                            >
                        </li>
                    </ul>
                </div>
                <button
                    data-collapse-toggle="mobile-menu-2"
                    type="button"
                    class="inline-flex items-center p-2 ml-1 text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                    aria-controls="mobile-menu-2"
                    aria-expanded="false"
                >
                    <span class="sr-only">Open main menu</span>
                    <svg
                        class="w-6 h-6"
                        aria-hidden="true"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                            clip-rule="evenodd"
                        ></path>
                    </svg>
                </button>
            </div>
            <div
                id="mobile-menu-2"
                class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1"
            >
                <ul
                    class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:flex-row md:space-x-8 md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700"
                >
                    <li>
                        <NavLink
                            :href="route('home')"
                            :active="$page.component === 'App'"
                            >Home</NavLink
                        >
                    </li>
                    <li v-if="isAuthenticated">
                        <NavLink
                            :href="route('user.profile.get')"
                            :active="$page.component === 'Profile'"
                            >Profile</NavLink
                        >
                    </li>
                    <li v-if="isAuthenticated">
                        <NavLink
                            :href="route('admin.dashboard')"
                            :active="$page.component === 'Admin/Dashboard'"
                            >Admin</NavLink
                        >
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { useAuthStore } from "../Stores/AuthStore";
import NavLink from "./NavLink.vue";
import { initFlowbite } from "flowbite";
import { storeToRefs } from "pinia";
import { onMounted } from "vue";

const authStore = useAuthStore();
let { isAuthenticated, user } = storeToRefs(authStore);

onMounted(() => {
    initFlowbite();
});

const logout = () => authStore.logout();
</script>
