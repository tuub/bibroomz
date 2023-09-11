<template>
    <nav class="w-full">
        <Brand />

        <div id="nav-footer-wrapper" class="block w-full h-8 pt-3.5">
            <div class="login-lang-wrapper w-20p">
                <CurrentLogin />
                <LangSwitch />
            </div>

            <slot />
        </div>
    </nav>
</template>

<script setup>
import Brand from "@/Shared/Brand.vue";
import CurrentLogin from "@/Shared/CurrentLogin.vue";
import LangSwitch from "@/Shared/LangSwitch.vue";
</script>

<style>
.w-20p {
    width: 20%;
}

nav {
    background-color: white;
    color: #c40d20;
    padding: 30px;
    height: 7em;
}

.login-lang-wrapper {
    display: inline-block;
    height: 2em;
}

@media only screen and (max-width: 600px) {
    .login-lang-wrapper {
        width: 50%;
    }
}

@media only screen and (max-width: 1150px) {
    .login-lang-wrapper {
        width: 180px;
    }
}
</style>
