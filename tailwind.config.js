/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        cream: '#FEFAF5',
        'cream-dark': '#F5EDE0',
        'cream-darker': '#EDE0CF',
        terracotta: {
          DEFAULT: '#C67B5C',
          dark: '#B56748',
          darker: '#9E5238',
          light: '#F0D5C8',
          lighter: '#F8EBE4',
        },
        olive: {
          DEFAULT: '#7D8A5A',
          dark: '#6B7A4A',
          darker: '#57613B',
          light: '#E6EBDA',
          lighter: '#F3F5ED',
        },
        brown: {
          DEFAULT: '#4A3222',
          light: '#7A6255',
          lighter: '#B8A599',
          dark: '#2E1F14',
        },
      },
      fontFamily: {
        hand: ["'Caveat'", 'cursive'],
        body: ["'Nunito'", 'sans-serif'],
      },
    },
  },
  plugins: [],
}
