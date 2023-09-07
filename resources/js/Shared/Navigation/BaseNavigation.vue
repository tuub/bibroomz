<template>
    <nav class="w-full">
        <div>
            <Brand></Brand>
        </div>

        <div id="nav-footer-wrapper" class="block w-full h-8 pt-3.5">
            <div class="LangSwitch-CurrentLogin-wapper w-20p">
                <CurrentLogin></CurrentLogin>
                <LangSwitch></LangSwitch>
            </div>

            <slot></slot>
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
.LangSwitch-CurrentLogin-wapper {
    display: inline-block;
    height: 2em;
}

@media only screen and (max-width: 600px) {
    .LangSwitch-CurrentLogin-wapper {
        width: 50%;
    }
}
@media only screen and (max-width: 1000px) {
    nav {
        background-color: white;
        color: #c40d20;
        padding: 30px;
        height: 7em;
    }
    .LangSwitch-CurrentLogin-wapper {
        width: 40%;
    }
}
</style>
