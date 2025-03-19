/** @type {import('tailwindcss').Config} */
module.exports = {
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
        },
    },
    plugins: [
        require("@tailwindcss/typography"),
        require("tailwindcss-primeui"),
    ],
}
