export type ApiError = {
    data?: {
        message?: string;
        errors?: Record<string, string[]>;
    };
} | null;
