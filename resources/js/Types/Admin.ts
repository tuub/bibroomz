export type Translatable = Record<string, string>;

export type Permission = {
    id?: number | string;
    name?: Translatable;
    description?: Translatable;
    group_id?: number | string | null;
};

export type LabeledCheckboxUpdatePayload = {
    value: number | string;
    checked: boolean;
};

export type PermissionGroup = {
    id?: number | string;
    name?: Translatable;
};

export type Role = {
    id?: number | string;
    name?: Translatable;
    description?: Translatable;
    permissions?: Permission[];
};

export type DataTableRef = {
    processedData?: unknown[];
    d_sortField?: string | null;
} | null;

export type AdminHappening = {
    id?: number | string;
    resource_id?: number | string;
    institution?: Translatable;
    resource_group?: Translatable;
    resource?: Translatable;
    start?: string;
    end?: string;
    start_date?: string;
    start_time?: string;
    end_date?: string;
    end_time?: string;
    user_id_01?: number | string;
    user_id_02?: number | string;
    verifier?: string;
    is_verified?: boolean;
    label?: Translatable;
};

export type BusinessHour = {
    id?: number | string;
    resource_id?: number | string;
    start?: string;
    end?: string;
    start_date?: string | null;
    end_date?: string | null;
    week_days?: (number | string)[];
};

export type BusinessHourFieldRemovePayload = {
    time_slot: BusinessHour;
};

export type BusinessHourFieldUpdatePayload = {
    id?: number | string;
    start?: string;
    end?: string;
    startDate?: string | null;
    endDate?: string | null;
    checkedWeekDays: (number | string)[];
};

export type WeekDay = {
    id?: number | string;
    key?: string;
};

export type AdminResource = {
    id?: number | string;
    title?: Translatable;
    location?: Translatable;
    location_uri?: string;
    description?: Translatable;
    capacity?: number | string;
    order?: number | string;
    is_active?: boolean;
    institution_id?: number | string;
    resource_group_id?: number | string;
    is_verification_required?: boolean;
    business_hours?: BusinessHour[];
};

export type ResourceGroup = {
    id?: number | string;
    institution_id?: number | string;
    title?: Translatable;
    description?: Translatable;
    slug?: string;
    term_singular?: Translatable;
    term_plural?: Translatable;
    help_uri?: string;
    is_active?: boolean;
    order?: number | string;
    resources_count?: number;
    user_groups?: UserGroup[];
    institution?: AdminInstitution;
};

export type AdminUserRoleAssignment = {
    institution_id?: number | string;
    role_id?: number | string;
};

export type AdminUser = {
    id?: number | string;
    name?: string;
    email?: string;
    is_admin?: boolean;
    is_system_user?: boolean;
    current_password?: string;
    password?: string;
    password_confirm?: string;
    banned_at?: string | null;
    roles?: AdminUserRoleAssignment[];
    permissions?: Record<string, string[]>;
    user_groups?: UserGroup[];
};

export type AdminInstitution = {
    id?: number | string;
    title?: Translatable;
    short_title?: string;
    slug?: string;
    location?: string;
    week_days?: WeekDay[];
    home_uri?: string;
    email?: string;
    logo_uri?: string;
    teaser_uri?: string;
    order?: number | string;
    is_active?: boolean;
    resources_count?: number;
    resource_groups_count?: number;
    user_groups?: UserGroup[];
};

export type AppSetting = {
    key?: string;
    value?: unknown;
};

export type Closable = {
    id?: number | string;
    title?: Translatable;
};

export type Settingable = {
    id?: number | string;
    institution_id?: number | string;
    title?: Translatable;
};

export type Closing = {
    id?: number | string;
    closable_id?: number | string;
    closable_type?: string;
    start?: string;
    end?: string;
    start_date?: string;
    start_time?: string;
    end_date?: string;
    end_time?: string;
    description?: Translatable;
    notify_users?: boolean;
};

export type MailType = {
    id?: number | string;
    key?: string;
};

export type Mail = {
    id?: number | string;
    institution_id?: number | string;
    mail_type_id?: number | string;
    mail_type?: MailType;
    subject?: Translatable;
    title?: Translatable;
    salutation?: Translatable;
    intro?: Translatable;
    outro?: Translatable;
    action_uri?: string;
    action_uri_label?: string;
    farewell?: Translatable;
    is_active?: boolean;
};

export type InstitutionStatistic = {
    id: number | string;
    title: Translatable;
    count: number;
    active: number;
    cancelled: number;
    cancellationRate: number;
};

export type ResourceGroupStatistic = {
    id: number | string;
    title: Translatable;
    institution_id: number | string;
    count: number;
    active: number;
    cancelled: number;
    cancellationRate: number;
};

export type ResourceStatistic = {
    id: number | string;
    title: Translatable;
    resource_group_id: number | string;
    count: number;
    active: number;
    cancelled: number;
    cancellationRate: number;
};

export type TimeSeriesEntry = {
    label: string;
    count: number;
};

export type PeakTimesHeatmapCell = {
    dayOfWeek: number;
    hour: number;
    count: number;
    percentage: number;
};

export type PeakTimesHeatmap = {
    cells: PeakTimesHeatmapCell[];
    maxCount: number;
    totalCount: number;
};

export type StatisticsComparison = {
    from: string;
    to: string;
    currentCount: number;
    comparisonCount: number;
    deltaPct: number;
    timeSeries: TimeSeriesEntry[];
    institutions: InstitutionStatistic[];
    resourceGroups: ResourceGroupStatistic[];
    resources: ResourceStatistic[];
};

export type CancellationStatistic = {
    cancelled: number;
    active: number;
    rate: number;
    retentionDays: number;
    retentionExceeded: boolean;
};

export type UserGroup = {
    id?: number | string;
    institution_id?: number | string;
    title?: Translatable;
    institution?: AdminInstitution;
};
