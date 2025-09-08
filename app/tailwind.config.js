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
        roseFonce: '#9e5e57',
        jaunePale: '#EDCC8B',
        bleuMenthe: '#198d8f',
        creme: '#FEFCFA',
        vertSauge: '#487a59',
        roseClair: '#E5C1BD',
        marromr:'#2a2828',
        fondDark: '#3A322F',
        texteClair: '#f3f3f3',
        menthe: '#038c71',
      },
      fontFamily: {
        serif: ['Playfair Display', 'serif'],
        sans: ['Quicksand', 'sans-serif'],
      },
    },
  },
  plugins: [],
};