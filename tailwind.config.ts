import typography from "@tailwindcss/typography";
import type { Config } from "tailwindcss";
import tailwindcssPrimeui from "tailwindcss-primeui";

const colorVar = (name: string) => `rgb(var(${name}) / <alpha-value>)`;

export default {
    darkMode: "selector",
    content: [
        "./index.html",
        "./resources/**/*.{vue,js,ts,jsx,tsx}",
        "./node_modules/primevue/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            colors: {
                app: {
                    page: colorVar("--color-app-page"),
                    surface: colorVar("--color-app-surface"),
                    field: colorVar("--color-app-field"),
                    border: colorVar("--color-app-border"),
                    text: colorVar("--color-app-text"),
                    muted: colorVar("--color-app-muted"),
                    subtle: colorVar("--color-app-subtle"),
                },
                button: {
                    secondary: colorVar("--color-button-secondary"),
                    "secondary-hover": colorVar("--color-button-secondary-hover"),
                    "secondary-contrast": colorVar("--color-button-secondary-contrast"),
                },
                brand: {
                    tub: colorVar("--color-brand-tub"),
                    contrast: colorVar("--color-brand-contrast"),
                },
                feedback: {
                    danger: {
                        DEFAULT: colorVar("--color-feedback-danger"),
                        contrast: colorVar("--color-feedback-danger-contrast"),
                        ring: colorVar("--color-feedback-danger-ring"),
                        soft: colorVar("--color-feedback-danger-soft"),
                        "soft-hover": colorVar("--color-feedback-danger-soft-hover"),
                        strong: colorVar("--color-feedback-danger-strong"),
                        "strong-hover": colorVar("--color-feedback-danger-strong-hover"),
                        text: colorVar("--color-feedback-danger-text"),
                    },
                    success: {
                        DEFAULT: colorVar("--color-feedback-success"),
                        contrast: colorVar("--color-feedback-success-contrast"),
                        hover: colorVar("--color-feedback-success-hover"),
                        soft: colorVar("--color-feedback-success-soft"),
                        strong: colorVar("--color-feedback-success-strong"),
                    },
                },
                link: colorVar("--color-link"),
                notice: {
                    warning: {
                        border: colorVar("--color-notice-warning-border"),
                        icon: colorVar("--color-notice-warning-icon"),
                        surface: colorVar("--color-notice-warning-surface"),
                        text: colorVar("--color-notice-warning-text"),
                    },
                },
                quota: {
                    contrast: colorVar("--color-quota-contrast"),
                    count: colorVar("--color-quota-count"),
                    label: colorVar("--color-quota-label"),
                },
                status: {
                    block: {
                        bg: colorVar("--color-status-block-bg"),
                        fg: colorVar("--color-status-block-fg"),
                    },
                    booking: {
                        bg: colorVar("--color-status-booking-bg"),
                        fg: colorVar("--color-status-booking-fg"),
                    },
                    closing: {
                        bg: colorVar("--color-status-closing-bg"),
                        fg: colorVar("--color-status-closing-fg"),
                    },
                    reservation: {
                        bg: colorVar("--color-status-reservation-bg"),
                        fg: colorVar("--color-status-reservation-fg"),
                    },
                },
                tub: colorVar("--color-brand-tub"),
            },
            fontSize: {
                tiny: "0.55rem",
            },
        },
    },
    plugins: [typography, tailwindcssPrimeui],
} satisfies Config;
