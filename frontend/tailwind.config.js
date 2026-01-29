/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        dark: '#0f172a',
        premium: {
          primary: '#6366f1',
          secondary: '#a855f7',
          accent: '#10b981',
        }
      },
      fontFamily: {
        outfit: ['Outfit', 'sans-serif'],
      },
      // Responsive breakpoints (using Tailwind defaults)
      // sm: 640px - Large phones, small tablets
      // md: 768px - Tablets
      // lg: 1024px - Desktops, laptops
      // xl: 1280px - Large desktops
      // 2xl: 1536px - Extra large screens
      aspectRatio: {
        'square': '1',
        'video': '16 / 9',
      },
    },
  },
  plugins: [],
}
