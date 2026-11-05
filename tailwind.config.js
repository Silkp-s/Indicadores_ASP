export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    950: '#0f1424',
                    800: '#1b2438',
                    100: '#e8eefc',
                },
                accent: {
                    500: '#2f6fed',
                },
                status: {
                    'ok-bg': '#e7f9ef',
                    'ok-fg': '#16a34a',
                    'warn-bg': '#fff4e5',
                    'warn-fg': '#f97316',
                    'bad-bg': '#fdedec',
                    'bad-fg': '#e11d2e',
                },
            },
        },
    },
    plugins: [],
};
