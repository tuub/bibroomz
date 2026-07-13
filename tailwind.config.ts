import typography from "@tailwindcss/typography";
import type { Config } from "tailwindcss";
import tailwindcssPrimeui from "tailwindcss-primeui";

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
                tub: "#c40d1e",
            },
            fontSize: {
                tiny: "0.55rem",
            },
        },
    },
    plugins: [typography, tailwindcssPrimeui],
} satisfies Config;
