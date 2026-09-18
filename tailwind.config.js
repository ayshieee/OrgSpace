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
                    50: '#F5F6F9',
                    100: '#E0E3EE',
                    200: '#B6BDD7',
                    300: '#8C96C0',
                    400: '#6270AA',
                    500: '#435498',
                    600: '#2E418D',
                    700: '#273778',
                    800: '#202E63',
                    900: '#172147',
                    DEFAULT: '#2E418D',
                },
                secondary: {
                    50: '#FFFDF3',
                    100: '#FFF8DC',
                    200: '#FFEFAE',
                    300: '#FFE67F',
                    400: '#FFDB45',
                    500: '#FFD217',
                    600: '#D9B314',
                    700: '#B39310',
                    800: '#8C740D',
                    900: '#594A08',
                    DEFAULT: '#FFD217',
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
            boxShadow: {
                soft: '0 1px 2px rgba(23, 33, 71, 0.04), 0 2px 8px rgba(23, 33, 71, 0.04)',
                elevated: '0 4px 12px rgba(23, 33, 71, 0.08), 0 2px 4px rgba(23, 33, 71, 0.06)',
            },
            keyframes: {
                'fade-in-up': {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'heart-pop': {
                    '0%': { transform: 'scale(1)' },
                    '40%': { transform: 'scale(1.35)' },
                    '100%': { transform: 'scale(1)' },
                },
            },
            animation: {
                'fade-in-up': 'fade-in-up 0.35s cubic-bezier(0.16, 1, 0.3, 1) both',
                'heart-pop': 'heart-pop 0.35s cubic-bezier(0.16, 1, 0.3, 1)',
            },
            transitionTimingFunction: {
                ios: 'cubic-bezier(0.16, 1, 0.3, 1)',
            },
        },
    },

    plugins: [forms],
};
