/*******************************************
 * Tailwind Config for Quiz App
 *******************************************/
module.exports = {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#f2f6ff',
          100: '#d9e5ff',
          200: '#b6ccff',
          300: '#8aabff',
          400: '#5f87ff',
          500: '#3c66f5',
          600: '#2c4fd3',
          700: '#2340a8',
          800: '#1f3888',
          900: '#1f326e',
        },
        accent: '#ff8a4c',
        dark: '#0b1021',
      },
      boxShadow: {
        card: '0 12px 45px rgba(12, 23, 52, 0.18)',
      },
    },
  },
  plugins: [],
};
