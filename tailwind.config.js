import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                heading: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                body: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#EAF1FB',
                    100: '#D0E0F6',
                    200: '#A1C1ED',
                    300: '#72A2E4',
                    400: '#4383DB',
                    500: '#1A63C4',
                    600: '#0047AB',
                    700: '#003A8C',
                    800: '#002D6D',
                    900: '#001D3F',
                    DEFAULT: '#0047AB',
                },
                secondary: {
                    50: '#FBF7E9',
                    100: '#F6EDC9',
                    200: '#EDDB93',
                    300: '#E4C95D',
                    400: '#DBB94A',
                    500: '#D4AF37',
                    600: '#B0902D',
                    700: '#8C7224',
                    800: '#68541A',
                    900: '#40340F',
                    DEFAULT: '#D4AF37',
                },
                tertiary: {
                    50: '#F3F4F6',
                    100: '#E5E7EB',
                    200: '#C4C8D0',
                    300: '#9CA3AF',
                    400: '#6B7280',
                    500: '#4B5563',
                    600: '#374151',
                    700: '#1F2937',
                    800: '#161C28',
                    900: '#111827',
                    DEFAULT: '#111827',
                },
                neutral: {
                    DEFAULT: '#F7F8FC',
                    50: '#F7F8FC',
                    100: '#EEF0F7',
                    200: '#E2E5F0',
                    300: '#CBD0E0',
                    400: '#A7AEC4',
                    500: '#7B839E',
                    600: '#565D75',
                    700: '#3B4055',
                    800: '#282C3C',
                    900: '#1E202C',
                },
            },
        },
    },

    plugins: [forms],
};
