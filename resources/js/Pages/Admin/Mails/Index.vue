<template>
    <IndexLayout
        model="mail"
        :title="$t('admin.mails.index.title', { title: translate(institution.title) })"
        :description="$t('admin.mails.index.description')"
        :create-params="{ institution_id: institution.id }"
    >
        <template #header>
            <IndexHeaderField>
                {{ $t("admin.mails.index.table.header.mail_type") }}
            </IndexHeaderField>
            <IndexHeaderField>
                {{ $t("admin.mails.index.table.header.subject") }}
            </IndexHeaderField>
            <IndexHeaderField class="text-center">
                {{ $t("admin.mails.index.table.header.is_active") }}
            </IndexHeaderField>
            <IndexHeaderField :is-label-visible="false">
                {{ $t("admin.general.table.actions") }}
            </IndexHeaderField>
        </template>

        <template #body>
            <IndexRow v-for="mail in mails" :key="mail.id">
                <IndexRowHeaderField>
                    {{ $t("admin.mails.mail_types." + mail.mail_type.key) }}
                </IndexRowHeaderField>
                <IndexRowField class="align-top">
                    {{ translate(mail.subject) }}
                </IndexRowField>
                <IndexRowField class="text-center align-top">
                    <BooleanField :is-true="mail.is_active" />
                </IndexRowField>
                <IndexRowField class="text-right align-top">
                    <LinkGroup>
                        <ActionLink
                            v-if="hasPermission('edit_mails', mail.institution_id)"
                            action="edit"
                            model="mail"
                            :params="{ id: mail.id }"
                        />
                        <PopupLink
                            v-if="hasPermission('delete_mails', mail.institution_id)"
                            action="delete"
                            model="mail"
                            :params="{ id: mail.id }"
                        />
                    </LinkGroup>
                </IndexRowField>
            </IndexRow>
        </template>
    </IndexLayout>
</template>

<script setup>
import ActionLink from "@/Components/Admin/Index/ActionLink.vue";
import BooleanField from "@/Components/Admin/Index/BooleanField.vue";
import LinkGroup from "@/Components/Admin/Index/LinkGroup.vue";
import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import IndexHeaderField from "@/Components/Admin/IndexHeaderField.vue";
import IndexLayout from "@/Components/Admin/IndexLayout.vue";
import IndexRow from "@/Components/Admin/IndexRow.vue";
import IndexRowField from "@/Components/Admin/IndexRowField.vue";
import IndexRowHeaderField from "@/Components/Admin/IndexRowHeaderField.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    institution: {
        type: Object,
        default: () => ({}),
    },
    mails: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;
const { hasPermission } = authStore;
</script>
