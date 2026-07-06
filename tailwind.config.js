// tailwind.config.js
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./vendor/robsontenorio/mary/src/View/Components/**/*.php",
        "./resources/views/vendor/flux/**/*.blade.php",
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}