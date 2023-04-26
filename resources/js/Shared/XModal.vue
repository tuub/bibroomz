<template>
    <Transition
        enter-from-class="opacity-0 scale-125"
        enter-to-class="opacity-100 scale-100"
        enter-active-class="transition duration-300"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-125"
        leave-active-class="transition duration-200"
    >
        <!-- isOpen is reactive and taken from the store, define if it is rendered or not -->
        <div v-if="isOpen" class="modal modal-open">
            <div class="modal-box relative">
                <!-- @click handles the event to close the modal calling the action directly in store -->
                <label
                    class="btn btn-sm btn-circle absolute right-2 top-2"
                    @click="modal.close()"
                >✕</label
                >

                <!-- dynamic components, using model to share values payload -->
                <component :is="view" :content="content" v-model:payload="payload"></component>

                <div class="modal-action">
                    <!-- render all actions and pass the model payload as parameter -->
                    <button
                        v-for="action in actions"
                        class="btn"
                        @click="action.callback(payload)"
                    >
                        {{ action.label }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script lang="ts" setup>
import { storeToRefs } from "pinia";
import { useModal } from "../Stores/Modal.ts";

const modal = useModal();

// reactive container to save the payload returned by the mounted view
//const payload = reactive({});

// convert all state properties to reactive references to be used on view
const { isOpen, view, content, payload, actions } = storeToRefs(modal);
</script>

