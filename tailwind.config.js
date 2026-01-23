import flowbitePlugin from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./node_modules/flowbite/**/*.js"
    ],
    theme: {
        extend: {
            colors: {
                // Naranja vibrante de los botones y secciones (Acción)
                primary: {
                    "50": "#fff7ed",
                    "100": "#ffedd5",
                    "200": "#fed7aa",
                    "300": "#fdba74",
                    "400": "#fb923c",
                    "500": "#f97316",
                    "600": "#f26419", // COLOR BASE DE LA IMAGEN
                    "700": "#c2410c",
                    "800": "#9a3412",
                    "900": "#7c2d12",
                    "950": "#431407",
                },
                // Verde azulado oscuro para encabezados y fondos oscuros
                secondary: {
                    "DEFAULT": "#132c33", // COLOR DE LOS TEXTOS "UN ENFOQUE..."
                    "light": "#1d414a",
                    "dark": "#0a191d",
                },
                // Fondo crema/hueso para la interfaz general
                brand: {
                    "cream": "#f2ede4", // FONDO DEL HERO EN LA IMAGEN
                    "bone": "#faf9f6",
                }
            }
        },
    },
    plugins: [
        flowbitePlugin
    ],
}
