/** @type {import('tailwindcss').Config} */
export default {
  darkMode: ['class', '[data-theme="dark"]'],
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
  ],
  theme: {
    extend: {
      // ============================================
      // COLOR PALETTE - Floral Luxe Design System
      // ============================================
      colors: {
        // Primary - Velvet Rose Crimson
        primary: {
          50: '#FFF5F7',
          100: '#FEE8EC',
          200: '#F9D4D9',
          300: '#F4B4B9',
          400: '#F08A80',
          500: '#881337',
          600: '#C21936',
          700: '#9B1A34',
          800: '#7D162D',
          900: '#661224',
          950: '#450D18',
          DEFAULT: '#881337',
        },

        // Secondary - Dark Slate Rose
        secondary: {
          50: '#FFF8F0',
          100: '#FFF1E0',
          200: '#FFE4C4',
          300: '#FFD5A6',
          400: '#FFB97E',
          500: '#4C0519',
          600: '#3A0414',
          700: '#2F030E',
          800: '#24020A',
          900: '#1A0108',
          DEFAULT: '#4C0519',
        },

        // Accent - Rose Gold Blush & Amber Champagne
        accent: {
          50: '#FFF5F0',
          100: '#FFE8E0',
          200: '#FFD1D1',
          300: '#FFB9B9',
          400: '#FF9A9A',
          500: '#BE123C',
          600: '#A31034',
          700: '#880E2B',
          800: '#6D0A22',
          900: '#520619',
          DEFAULT: '#BE123C',
        },

        // Champagne accent
        champagne: {
          50: '#FAFDF9',
          100: '#F5FAF7',
          200: '#EAFAF4',
          300: '#DFF1ED',
          400: '#D5E8E5',
          500: '#D97706',
          600: '#C06905',
          700: '#A65C04',
          800: '#8C4F03',
          900: '#6D4202',
          DEFAULT: '#D97706',
        },

        // Gold
        gold: {
          50: '#FFFDF7',
          100: '#FFFBE5',
          200: '#FFF7C7',
          300: '#FFF3A6',
          400: '#FFEFA6',
          500: '#D97706',
          600: '#D97706',
          700: '#B85A05',
          800: '#973D04',
          900: '#762003',
          DEFAULT: '#D97706',
        },

        // Neutrals - Light Theme
        neutral: {
          50: '#FFFDF9',
          100: '#FFFBF5',
          200: '#FFF7ED',
          300: '#F3F4F6',
          400: '#E5E5E5',
          500: '#D1D5DB',
          600: '#9CA3AF',
          700: '#6B7280',
          800: '#374151',
          900: '#1F2937',
          950: '#111827',
          DEFAULT: '#FFFDF9',
        },

        // Neutrals - Dark Theme
        dark: {
          50: '#1C1014',
          100: '#2A1E26',
          200: '#3D2F3A',
          300: '#503F4B',
          400: '#695863',
          500: '#82717D',
          600: '#9A8A96',
          700: '#B3A3AE',
          800: '#CBBCEC',
          900: '#E4D3EB',
          950: '#F5F3F1',
          DEFAULT: '#1C1014',
        },

        // Semantic colors
        success: {
          500: '#15803D',
          600: '#147036',
        },
        warning: {
          500: '#B45309',
          600: '#A64707',
        },
        error: {
          500: '#991B1B',
          600: '#88151A',
        },
      },

      // ============================================
      // TYPOGRAPHY - Custom Font Stacks
      // ============================================
      fontFamily: {
        display: ['"Playfair Display"', 'Georgia', 'serif'],
        body: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
        mono: ['"Red Hat Mono"', 'monospace'],
      },

      // ============================================
      // TYPOGRAPHY - Font Sizes (8-point scale)
      // ============================================
      fontSize: {
        xs: ['0.75rem', { lineHeight: '1.25rem' }],
        sm: ['0.875rem', { lineHeight: '1.25rem' }],
        base: ['1rem', { lineHeight: '1.5rem' }],
        lg: ['1.125rem', { lineHeight: '1.625rem' }],
        xl: ['1.25rem', { lineHeight: '1.75rem' }],
        '2xl': ['1.5rem', { lineHeight: '2rem' }],
        '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
        '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
        '5xl': ['3rem', { lineHeight: '1', letterSpacing: '-0.025em' }],
        '6xl': ['3.75rem', { lineHeight: '1', letterSpacing: '-0.05em' }],
        '7xl': ['4.5rem', { lineHeight: '1', letterSpacing: '-0.05em' }],

        // Heading variants
        h1: ['2.25rem', { lineHeight: '1.1', letterSpacing: '-0.025em' }],
        h2: ['1.875rem', { lineHeight: '1.2', letterSpacing: '-0.01em' }],
        h3: ['1.5rem', { lineHeight: '1.25' }],
        h4: ['1.25rem', { lineHeight: '1.3' }],
        h5: ['1.125rem', { lineHeight: '1.35' }],
      },

      // ============================================
      // SPACING - 8-point scale, gutter values, section/page margins
      // ============================================
      spacing: {
        '0': '0rem',
        '1': '0.25rem',
        '2': '0.5rem',
        '3': '0.75rem',
        '4': '1rem',
        '5': '1.25rem',
        '6': '1.5rem',
        '7': '1.75rem',
        '8': '2rem',
        '9': '2.25rem',
        '10': '2.5rem',
        '11': '2.75rem',
        '12': '3rem',
        '14': '3.5rem',
        '16': '4rem',
        '20': '5rem',
        '24': '6rem',
        '28': '7rem',
        '32': '8rem',
        '36': '9rem',
        '40': '10rem',

        // Page/section margins
        'section': '4rem',
        'container': '1.5rem',
      },

      // ============================================
      // GUTTER VALUES
      // ============================================
      gap: {
        'xs': '0.25rem',
        'sm': '0.5rem',
        'md': '1rem',
        'lg': '1.5rem',
        'xl': '2rem',
      },

      // ============================================
      // BORDER RADIUS - sm: 6px, DEFAULT: 12px, md: 16px, lg: 20px, xl: 28px, full: 9999px
      // ============================================
      borderRadius: {
        'sm': '6px',
        DEFAULT: '12px',
        'md': '16px',
        'lg': '20px',
        'xl': '28px',
        '2xl': '32px',
        '3xl': '48px',
        full: '9999px',
        none: '0px',
      },

      // ============================================
      // SHADOWS - Soft Rose Ambient Shadows, Glassmorphic Glow
      // ============================================
      boxShadow: {
        // Soft Rose Ambient Shadows
        ambient-light: '0 4px 6px -1px rgba(136, 19, 55, 0.1), 0 2px 4px -2px rgba(136, 19, 55, 0.06)',
        ambient-medium: '0 10px 15px -3px rgba(136, 19, 55, 0.1), 0 4px 6px -4px rgba(136, 19, 55, 0.04)',
        ambient-heavy: '0 20px 25px -5px rgba(136, 19, 55, 0.1), 0 8px 10px -6px rgba(136, 19, 55, 0.04)',
        ambient-xl: '0 25px 50px -12px rgba(136, 19, 55, 0.15), 0 15px 25px -10px rgba(136, 19, 55, 0.1)',

        // Glassmorphic Glow
        glass: '0 8px 32px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04)',
        glass-sm: '0 4px 16px rgba(0, 0, 0, 0.05), 0 2px 8px rgba(0, 0, 0, 0.02)',
        glass-md: '0 12px 32px rgba(0, 0, 0, 0.08), 0 6px 16px rgba(0, 0, 0, 0.05)',
        glass-lg: '0 24px 48px rgba(0, 0, 0, 0.1), 0 12px 24px rgba(0, 0, 0, 0.06)',

        // Glow effects
        glow-primary: '0 0 0 3px rgba(136, 19, 55, 0.3), 0 0 12px rgba(136, 19, 55, 0.2)',
        glow-accent: '0 0 0 3px rgba(190, 18, 60, 0.3), 0 0 12px rgba(190, 18, 60, 0.2)',
        glow-gold: '0 0 0 3px rgba(217, 119, 6, 0.3), 0 0 12px rgba(217, 119, 6, 0.2)',

        // Card shadows
        card: '0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04)',
        card-hover: '0 4px 6px -1px rgba(0, 0, 0, 0.10), 0 2px 4px -2px rgba(0, 0, 0, 0.06)',
        card-strong: '0 10px 15px -3px rgba(0, 0, 0, 0.12), 0 4px 6px -4px rgba(0, 0, 0, 0.08)',

        // Inner shadows for pressed states
        inner: 'inset 0 2px 4px rgba(0, 0, 0, 0.08)',
        inner-md: 'inset 0 4px 8px rgba(0, 0, 0, 0.1)',

        // Legacy for compatibility
        xs: '0 1px 2px 0 rgba(0, 0, 0, 0.05), 0 1px 1px -0.5px rgba(0, 0, 0, 0.03)',
        sm: '0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -0.5px rgba(0, 0, 0, 0.05)',
        DEFAULT: '0 1px 3px 0 rgba(0, 0, 0, 0.10), 0 1px 2px -0.5px rgba(0, 0, 0, 0.08)',
        md: '0 4px 6px -1px rgba(0, 0, 0, 0.10), 0 2px 4px -2px rgba(0, 0, 0, 0.06)',
        lg: '0 10px 15px -3px rgba(0, 0, 0, 0.11), 0 4px 6px -4px rgba(0, 0, 0, 0.08)',
        xl: '0 20px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.09)',
        '2xl': '0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 15px 25px -10px rgba(0, 0, 0, 0.10)',
      },

      // ============================================
      // MOTION - state-change 150ms ease-out, surface-enter 250ms cubic-bezier(0.16,1,0.3,1), surface-exit 150ms cubic-bezier(0.7,0,0.84,0)
      // ============================================
      transitionTimingFunction: {
        'ease-out': 'cubic-bezier(0.16, 1, 0.3, 1)',
        'ease-in': 'cubic-bezier(0.7, 0, 0.84, 0)',
        'ease-in-out': 'cubic-bezier(0.4, 0, 0.6, 1)',
        'bounce': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
        'macro': 'cubic-bezier(0.16, 1, 0.3, 1)',
        'micro': 'cubic-bezier(0.7, 0, 0.84, 0)',
      },

      transitionDuration: {
        '75': '75ms',
        '100': '100ms',
        '150': '150ms',
        '200': '200ms',
        '250': '250ms',
        '300': '300ms',
        '350': '350ms',
        '400': '400ms',
        '450': '450ms',
        '500': '500ms',
        '750': '750ms',
        '1000': '1000ms',
      },

      animation: {
        'fade-in': 'fade-in 150ms var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1)) forwards',
        'fade-out': 'fade-out 150ms var(--ease-in, cubic-bezier(0.7, 0, 0.84, 0)) forwards',
        'slide-up': 'slide-up 250ms var(--macro, cubic-bezier(0.16, 1, 0.3, 1)) forwards',
        'slide-down': 'slide-down 250ms var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1)) forwards',
        'scale-in': 'scale-in 150ms var(--ease-out, cubic-bezier(0.34, 1.56, 0.64, 1)) forwards',
        'scale-out': 'scale-out 150ms var(--ease-in, cubic-bezier(0.34, 1.56, 0.64, 1)) forwards',
        'pulse-soft': 'pulse-soft 2s ease-in-out infinite',
        'bounce-subtle': 'bounce-subtle 2s ease-in-out infinite',
        'shimmer': 'shimmer 1.5s linear infinite',
        'ripple': 'ripple-effect 500ms ease-out',
      },

      keyframes: {
        'fade-in': { 'from': { opacity: '0' }, 'to': { opacity: '1' } },
        'fade-out': { 'from': { opacity: '1' }, 'to': { opacity: '0' } },
        'slide-up': { 'from': { opacity: '0', transform: 'translateY(10px)' }, 'to': { opacity: '1', transform: 'translateY(0)' } },
        'slide-down': { 'from': { opacity: '0', transform: 'translateY(-10px)' }, 'to': { opacity: '1', transform: 'translateY(0)' } },
        'scale-in': { 'from': { opacity: '0', transform: 'scale(0.95)' }, 'to': { opacity: '1', transform: 'scale(1)' } },
        'scale-out': { 'from': { opacity: '1', transform: 'scale(1)' }, 'to': { opacity: '0', transform: 'scale(0.95)' } },
        'pulse-soft': { '0%, 100%': { opacity: '1' }, '50%': { opacity: '0.6' } },
        'bounce-subtle': { '0%, 100%': { transform: 'translateY(-5px)', animationTimingFunction: 'cubic-bezier(0.34, 1.56, 0.64, 1)' }, '50%': { transform: 'translateY(0)', animationTimingFunction: 'cubic-bezier(0.34, 1.56, 0.64, 1)' } },
        'shimmer': { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
        'ripple-effect': { '0%': { width: '0', height: '0', opacity: '0.5' }, '100%': { width: '500px', height: '500px', opacity: '0' } },
        'spin': { 'from': { transform: 'rotate(0deg)' }, 'to': { transform: 'rotate(360deg)' } },
      },

      // ============================================
      // Z-INDICES
      // ============================================
      zIndex: {
        '0': '0',
        '1': '1',
        '2': '2',
        '3': '3',
        '4': '4',
        '5': '5',
        '10': '10',
        '15': '15',
        '20': '20',
        '25': '25',
        '30': '30',
        '40': '40',
        '50': '50',
        '100': '100',
        'auto': 'auto',
        notification: '70',
        modal: '80',
        toast: '100',
        sticky: 'var(--z-sticky, 10)',
      },

      // ============================================
      // CONTAINER - Custom container with gutters
      // ============================================
      container: {
        center: true,
        padding: '1.5rem',
        screens: {
          'sm': '640px',
          'md': '768px',
          'lg': '1024px',
          'xl': '1280px',
          '2xl': '1440px',
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/aspect-ratio'),
    require('@tailwindcss/typography'),
    require('@tailwindcss/line-clamp'),
  ],
};