/** @type {import('tailwindcss').Config} */
const colors = require('tailwindcss/colors');
module.exports = {
  content: [
    './templates/**/*.html.twig',
    './assets/**/*.js',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        ...colors,
        roseFonce: '#c4847C',
        jaunePale: '#EDCC8B',
        bleuMenthe: '#198d8f',
        creme: '#FEFCFA',
        vertSauge: '#7B9E87',
        roseClair: '#E5C1BD',
        marromr:'#2a2828',
        fondDark: '#3A322F',
        texteClair: '#f3f3f3',
        menthe: '#84D3CE',
      },
      fontFamily: {
        serif: ['Playfair Display', 'serif'],
        sans: ['Quicksand', 'sans-serif'],
      },
    },
  },
  plugins: [],
};