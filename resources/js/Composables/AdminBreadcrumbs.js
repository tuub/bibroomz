import { useAppStore } from "@/Stores/AppStore";

import { usePage } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed, inject } from "vue";

function pushInstitutionsRoot(ctx) {
    ctx.push(ctx.t("institutions"), ctx.route("admin.institution.index"));
}

function pushInstitution(ctx, institution) {
    if (!institution) return;
    ctx.push(
        ctx.translate(institution.title),
        ctx.route("admin.resource_group.index", { institution_id: institution.id }),
    );
}

function pushResourceGroup(ctx, resourceGroup) {
    if (!resourceGroup) return;
    ctx.push(
        ctx.translate(resourceGroup.title),
        ctx.route("admin.resource.index", { resource_group_id: resourceGroup.id }),
    );
}

function buildHappeningCrumbs({ props, push, t, translate, route, isCreate, isEdit }) {
    push(t("happenings"), route("admin.happening.index"));
    if (isCreate) push(t("create"));
    if (isEdit) push(translate(props.happening?.label) || t("edit"));
}

function buildInstitutionCrumbs({ props, push, t, translate, route, isCreate, isEdit }) {
    push(t("institutions"), route("admin.institution.index"));
    if (isCreate) push(t("create"));
    if (isEdit) push(translate(props.institution?.title) || t("edit"));
}

function buildResourceGroupCrumbs(ctx) {
    const { props, push, t, translate, route, isCreate, isEdit } = ctx;
    const institution = props.institution ?? props.resource_group?.institution;

    pushInstitutionsRoot(ctx);
    pushInstitution(ctx, institution);
    push(
        t("resource_groups"),
        institution ? route("admin.resource_group.index", { institution_id: institution.id }) : null,
    );
    if (isCreate) push(t("create"));
    if (isEdit) push(translate(props.resource_group?.title) || t("edit"));
}

function buildResourceCrumbs(ctx) {
    const { props, push, t, translate, route, isCreate, isEdit } = ctx;
    const resourceGroup = props.resourceGroup;
    const institution = resourceGroup?.institution;

    pushInstitutionsRoot(ctx);
    pushInstitution(ctx, institution);
    pushResourceGroup(ctx, resourceGroup);
    push(t("resources"), resourceGroup ? route("admin.resource.index", { resource_group_id: resourceGroup.id }) : null);
    if (isCreate) push(t("create"));
    if (isEdit) push(translate(props.resource?.title) || t("edit"));
}

function buildClosingCrumbs(ctx) {
    const { props, push, t, translate, route, isCreate, isEdit } = ctx;
    const closable = props.closable;
    const closableType = props.closable_type;

    if (closableType === "institution" && closable) {
        pushInstitutionsRoot(ctx);
        push(translate(closable.title), route("admin.institution.edit", { id: closable.id }));
    } else if (closableType === "resource" && closable) {
        const resourceGroup = props.resource_group;

        pushInstitutionsRoot(ctx);
        pushInstitution(ctx, props.institution);
        pushResourceGroup(ctx, resourceGroup);
        push(
            t("resources"),
            resourceGroup ? route("admin.resource.index", { resource_group_id: resourceGroup.id }) : null,
        );
        push(translate(closable.title), route("admin.resource.edit", { id: closable.id }));
    }

    push(
        t("closings"),
        closable && closableType
            ? route("admin.closing.index", { closable_type: closableType, closable_id: closable.id })
            : null,
    );
    if (isCreate) push(t("create"));
    if (isEdit) push(t("edit"));
}

function buildSettingCrumbs(ctx) {
    const { props, push, t, translate, route, isEdit } = ctx;
    const settingable = props.settingable;
    const settingableType = props.settingable_type;

    if (settingableType === "institution" && settingable) {
        pushInstitutionsRoot(ctx);
        push(translate(settingable.title), route("admin.institution.edit", { id: settingable.id }));
    } else if (settingableType === "resource_group" && settingable) {
        pushInstitutionsRoot(ctx);
        pushInstitution(ctx, props.institution);
        push(translate(settingable.title), route("admin.resource_group.edit", { id: settingable.id }));
    }

    push(
        t("settings"),
        settingable && settingableType
            ? route("admin.setting.index", { settingable_type: settingableType, settingable_id: settingable.id })
            : null,
    );
    if (isEdit) push(trans(`admin.settings.keys.${props.setting?.key}.label`));
}

function buildAppSettingCrumbs({ push, t, route, isEdit }) {
    push(t("app_settings"), route("admin.app_setting.index"));
    if (isEdit) push(t("edit"));
}

function buildMailCrumbs({ props, push, t, translate, route, isCreate, isEdit }) {
    const institution = props.institution;
    const institutionId = props.institution_id ?? institution?.id;

    push(t("institutions"), route("admin.institution.index"));
    if (institution) {
        push(translate(institution.title), route("admin.institution.edit", { id: institution.id }));
    }
    push(t("mails"), institutionId ? route("admin.mail.index", { institution_id: institutionId }) : null);
    if (isCreate) push(t("create"));
    if (isEdit) {
        const mailTypeKey = props.mail?.mail_type?.key;
        push(mailTypeKey ? trans(`admin.mails.mail_types.${mailTypeKey}`) : t("edit"));
    }
}

function buildUserGroupCrumbs({ props, routeName, push, t, translate, route }) {
    const userGroup = props.user_group;

    push(t("user_groups"), route("admin.user_group.index"));

    if (routeName === "admin.user_group.create") {
        push(t("create"));
    } else if (routeName === "admin.user_group.edit") {
        push(translate(userGroup?.title) || t("edit"));
    } else if (routeName === "admin.user_group.import") {
        if (userGroup) push(translate(userGroup.title), route("admin.user_group.users", { id: userGroup.id }));
        push(t("import"));
    } else if (routeName === "admin.user_group.users") {
        push(translate(userGroup?.title));
    }
}

function buildUserCrumbs({ props, push, t, route, isCreate, isEdit }) {
    push(t("users"), route("admin.user.index"));
    if (isCreate) push(t("create"));
    if (isEdit) push(props.user?.name || t("edit"));
}

function buildRoleCrumbs({ props, push, t, translate, route, isCreate, isEdit }) {
    push(t("roles"), route("admin.role.index"));
    if (isCreate) push(t("create"));
    if (isEdit) push(translate(props.role?.name) || t("edit"));
}

const breadcrumbSections = [
    { prefix: "admin.happening.", build: buildHappeningCrumbs },
    { prefix: "admin.institution.", build: buildInstitutionCrumbs },
    { prefix: "admin.resource_group.", build: buildResourceGroupCrumbs },
    { prefix: "admin.resource.", build: buildResourceCrumbs },
    { prefix: "admin.closing.", build: buildClosingCrumbs },
    { prefix: "admin.setting.", build: buildSettingCrumbs },
    { prefix: "admin.app_setting.", build: buildAppSettingCrumbs },
    { prefix: "admin.mail.", build: buildMailCrumbs },
    { prefix: "admin.user_group.", build: buildUserGroupCrumbs },
    { prefix: "admin.user.", build: buildUserCrumbs },
    { prefix: "admin.role.", build: buildRoleCrumbs },
];

export function useAdminBreadcrumbs() {
    const route = inject("ziggyRoute");
    const page = usePage();
    const appStore = useAppStore();

    const t = (key) => trans(`admin.breadcrumbs.${key}`);
    const translate = (value) => appStore.translate(value);

    const items = computed(() => {
        const props = page.props;
        const routeName = props.route;

        if (typeof routeName !== "string" || !routeName.startsWith("admin.")) {
            return [];
        }

        const crumbs = [];
        const push = (label, url = null) => {
            if (label) crumbs.push({ label, url });
        };

        push(t("dashboard"), route("admin.dashboard"));

        const section = breadcrumbSections.find(({ prefix }) => routeName.startsWith(prefix));
        section?.build({
            props,
            routeName,
            push,
            t,
            translate,
            route,
            isCreate: routeName.endsWith(".create"),
            isEdit: routeName.endsWith(".edit"),
        });

        if (crumbs.length > 0) {
            crumbs[crumbs.length - 1].url = null;
        }

        return crumbs;
    });

    const home = computed(() => ({
        icon: "pi pi-home",
        url: route("start"),
    }));

    return { items, home };
}
