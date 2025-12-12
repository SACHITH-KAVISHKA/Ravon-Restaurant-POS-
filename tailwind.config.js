import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', ...defaultTheme.fontFamily.sans],
                display: ['"Playfair Display"', 'serif'],
            },
            colors: {
                ravon: {
                    // Main Purple Gradient Colors
                    primary: '#667eea',
                    primaryEnd: '#764ba2',
                    primaryHover: '#5a6fd8',
                    primaryHoverEnd: '#6a4190',
                    
                    // Success Green Gradient
                    success: '#28a745',
                    successEnd: '#20c997',
                    successHover: '#218838',
                    successHoverEnd: '#1ea085',
                    
                    // Status Colors
                    danger: '#dc3545',
                    warning: '#ffc107',
                    info: '#17a2b8',
                    secondary: '#6c757d',
                    
                    // Backgrounds
                    bg: '#f8f9fa',
                    surface: '#ffffff',
                    light: '#f8f9fa',
                    
                    // Text Colors
                    text: '#495057',
                    textDark: '#343a40',
                    textMuted: '#6c757d',
                    textWhite: '#ffffff',
                    
                    // Subtle Backgrounds
                    purple: '#f3e8ff',
                    purpleLight: '#faf5ff',
                    blueLight: '#e3f2fd',
                    orangeLight: '#fff3e0',
                    yellowLight: '#fff3cd',
                    disabled: '#e9ecef',
                }
            },
            boxShadow: {
                'ravon-soft': '0 4px 12px rgba(102, 126, 234, 0.3)',
                'ravon-subtle': '0 2px 10px rgba(0, 0, 0, 0.1)',
                'ravon-card': '0 10px 30px rgba(0, 0, 0, 0.2)',
            }
        },
    },
    plugins: [],
};
