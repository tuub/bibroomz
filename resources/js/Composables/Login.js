import { useLoginModal } from "@/Composables/ModalActions";
import { useAuthStore } from "@/Stores/AuthStore";
import useModal from "@/Stores/Modal";

export function useLogin() {
    const authStore = useAuthStore();
    const modal = useModal();

    const loginUser = async () => {
        const loginModal = useLoginModal();
        modal.open(loginModal.view, loginModal.content, loginModal.payload, loginModal.actions);
    };

    const logoutUser = async () => {
        try {
            return await authStore.logout();
        } catch (error) {
            return console.log(error);
        }
    };

    return {
        loginUser,
        logoutUser,
    };
}
