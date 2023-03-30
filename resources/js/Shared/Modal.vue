<template>
    <Transition
        enter-from-class="opacity-0 scale-125"
        enter-to-class="opacity-100 scale-100"
        enter-active-class="transition duration-300"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-125"
        leave-active-class="transition duration-200"
    >
        <div v-if="show" class="modal-mask">
            <div class="modal-container">
                <header>
                    <h1 class="text-xl font-bold">
                        <slot name="header">DEFAULT HEADER</slot>
                    </h1>
                </header>
                <div>
                    <slot name="content">DEFAULT CONTENT</slot>
                </div>
                <footer>
                    <slot name="footer">
                        <button @click="hideModal">
                            DEFAULT CLOSE
                        </button>
                    </slot>
                </footer>
            </div>
        </div>
    </Transition>
</template>

<script setup>

/*
 props
 */
const props = defineProps({
    show: Boolean,
})

/*
 emits
 */
const emit = defineEmits(['hideModal'])

const hideModal = () => {
    emit('hideModal')
}

</script>

<style>
.modal-mask {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: grid;
    place-items: center;
}

.modal-container {
    background: white;
    padding: 1rem;
    width: 80vm;
    max-width: 500px;
    border-radius: 12px;
}

footer {
    margin-top: 1rem;
    padding-top: 0.5rem;
    border-top: 1px solid #333;
    font-size: 0.8rem;
}

footer button {
    background-color: #ddd;
    padding: .25rem .5rem;
    border-radius: 20px;
}
</style>
