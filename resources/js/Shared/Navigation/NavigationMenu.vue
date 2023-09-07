<template>
    <div class="navigation-menu-wrapper">
        <button
            data-collapse-toggle="mobile-menu-2"
            class="mobile-menu-button"
            type="button"
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

        <div id="mobile-menu-2" class="w-70p mt-3px float-right">
            <slot></slot>
        </div>
    </div>
</template>
<style>
.w-70p {
    width: 70%;
}

.mt-3px {
    margin-top: -3px;
}

/* .navigation-menu-wrapper { */

/* } */

.mobile-menu-button {
    display: block;
}

#mobile-menu-2 > ul > li {
    float: right;
    margin-left: 20px;
}

@media only screen and (max-width: 1000px) {
    #mobile-menu-2 {
        display: none;
    }
}
</style>
