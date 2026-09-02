import type { TranslatableValue } from "@/Stores/AppStore";
import type { ApiError } from "@/Types/Api";
import { withBaseUrl } from "@/baseUrl";

import type { Dayjs } from "dayjs";
import { defineStore } from "pinia";

export type HappeningResource = {
    id?: number | string;
    resourceGroupId?: number | string;
    resourceGroup?: TranslatableValue;
    institution?: TranslatableValue;
    title?: TranslatableValue;
    location?: TranslatableValue;
    location_uri?: string;
    capacity?: number;
    description?: TranslatableValue;
    [key: string]: unknown;
};

export type Happening = {
    id?: number | string;
    resource?: HappeningResource;
    start?: string | Dayjs;
    end?: string | Dayjs;
    user_01?: string;
    user_02?: string;
    user_id_01?: number | string;
    user_id_02?: number | string;
    verifier?: string;
    isVerified?: boolean;
    isVerificationRequired?: boolean;
    label?: TranslatableValue;
    can?: {
        verify?: boolean;
        edit?: boolean;
        delete?: boolean;
    };
    [key: string]: unknown;
};

// A happening being actively edited in the create/edit form and its modal wrapper:
// resource and label are always populated by the time either component uses them.
export type HappeningEditPayload = Happening & {
    resource: HappeningResource;
    label: Record<string, string>;
};

type HappeningStoreState = {
    happening: {
        resource: HappeningResource;
        start: string;
        end: string;
    };
    error: ApiError;
};

export const useHappeningStore = defineStore("happening", {
    persist: true,

    state: (): HappeningStoreState => {
        return {
            happening: {
                resource: {},
                start: "",
                end: "",
            },
            error: null,
        };
    },

    actions: {
        addHappening(happening: Happening) {
            return axios.post(withBaseUrl("/happening/add"), happening);
        },

        editHappening(happening: Happening) {
            return axios.post(withBaseUrl(`/happening/update/${happening.id}`), happening);
        },

        verifyHappening(happening: Happening) {
            return axios.post(withBaseUrl(`/happening/verify/${happening.id}`), happening);
        },

        deleteHappening(id: number | string) {
            return axios.delete(withBaseUrl(`/happening/delete/${id}`));
        },
    },

    getters: {
        getHappeningResource: (state) => state.happening.resource,
        getHappeningStart: (state) => state.happening.start,
        getHappeningEnd: (state) => state.happening.end,
    },
});
