/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./src/Resources/**/*.blade.php", "./src/Resources/**/*.js"],

    theme: {
        container: {
            center: true,

            screens: {
                "2xl": "1920px",
            },

            padding: {
                DEFAULT: "16px",
            },
        },

        screens: {
            sm: "525px",
            md: "768px",
            lg: "1024px",
            xl: "1240px",
            "2xl": "1920px",
        },

        extend: {
            colors: {
                /*
                 | Hayest brand identity
                 | Primary Navy  : #262F8F
                 | Primary Gold  : #F2C216
                 | The default Tailwind `blue` scale is remapped to the brand
                 | navy so every existing `blue-*` utility across the admin
                 | adopts the Hayest identity automatically.
                 */
                blue: {
                    50: '#EEEEF6',
                    100: '#DCDEED',
                    200: '#BABCDB',
                    300: '#8E93C5',
                    400: '#5E65AC',
                    500: '#3C449A',
                    600: '#262F8F',
                    700: '#21297E',
                    800: '#1C236A',
                    900: '#171C56',
                    950: '#10143C',
                },

                navy: {
                    50: '#EEEEF6',
                    100: '#DCDEED',
                    200: '#BABCDB',
                    300: '#8E93C5',
                    400: '#5E65AC',
                    500: '#3C449A',
                    600: '#262F8F',
                    700: '#21297E',
                    800: '#1C236A',
                    900: '#171C56',
                    950: '#10143C',
                    DEFAULT: '#262F8F',
                },

                gold: {
                    50: '#FEFAEC',
                    100: '#FDF5DA',
                    200: '#FBEBB4',
                    300: '#F8DF86',
                    400: '#F5D253',
                    500: '#F3C82D',
                    600: '#F2C216',
                    700: '#D5AB13',
                    800: '#B39010',
                    900: '#91740D',
                    950: '#665109',
                    DEFAULT: '#F2C216',
                },

                darkGreen: '#40994A',
                darkBlue: '#262F8F',
                darkPink: '#F85156',
            },

            fontFamily: {
                inter: ['Inter'],
                icon: ['icomoon']
            }
        },
    },
    
    darkMode: 'class',

    plugins: [],

    safelist: [
        {
            pattern: /icon-/,
        }
    ]
};
