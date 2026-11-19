<!DOCTYPE html>
<html lang="id" class="scroll-touch-none">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berkah Kreatif - SIBERKAH</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['"Playfair Display"', 'Georgia', 'serif'],
                        body: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#FAF5F0',
                            100: '#F5EBE6',
                            200: '#F2E8E1',
                            300: '#E7DBD5',
                            400: '#D2B8C2',
                            500: '#881337',
                            600: '#72102F',
                            700: '#5C0D26',
                            800: '#480A1D',
                            900: '#3A0815',
                            950: '#25030A',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .animate-fade-in { animation: fadeIn 800ms ease-out forwards; }
        .animate-fade-in-up { animation: fadeInUp 600ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
        .animate-fade-in-down { animation: fadeInDown 600ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
        .animate-pulse-soft { animation: pulse 2s ease-in-out infinite; }
    </style>
</head>
<body class="bg-[color:var(--color-neutral-50)] dark:bg-[color:var(--color-neutral-950)] font-body text-[color:var(--color-neutral-900)] dark:text-[color:var(--color-neutral-50)] flex items-center justify-center min-h-screen relative overflow-hidden">

    <!-- Background Elements -->
    <div class="absolute top-0 left-0 w-64 h-64 bg-[color:var(--color-primary-50)] dark:bg-[color:var(--color-primary-950)] rounded-full mix-blend-multiply filter blur-32 animate-pulse-soft opacity-20"></div>
    <div class="absolute bottom-0 right-0 w-64 h-64 bg-[color:var(--color-accent-500)] dark:bg-[color:var(--color-accent-900)] rounded-full mix-blend-multiply filter blur-32 animate-pulse-soft opacity-20"></div>

    <!-- Content -->
    <div class="text-center z-10 animate-fade-in-up">
        <!-- Logo/Monogram -->
        <div class="mb-8 flex justify-center items-center">
            <div class="relative">
                <svg class="w-32 h-32 text-[color:var(--color-primary-500)] dark:text-[color:var(--color-primary-500)]" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" stroke-width="5" stroke="currentColor" fill="none" />
                    <path d="M30 50 L45 65 L70 40" stroke-width="6" stroke="currentColor" fill="none" stroke-linecap="round" />
                    <circle cx="50" cy="50" r="8" fill="currentColor" />
                </svg>
            </div>
        </div>

        <!-- Headline -->
        <h1 class="text-display-lg md:text-6xl lg:text-7xl font-bold tracking-tight mb-4">
            <span class="bg-clip-text text-transparent bg-gradient-to-r from-[color:var(--color-primary-600)] to-[color:var(--color-secondary-500)]">
                Berkah Kreatif
            </span>
        </h1>

        <!-- Subtitle -->
        <p class="text-body-lg text-neutral-600 dark:text-neutral-400 mb-8 max-w-md mx-auto">
            Platform sewa perlengkapan wisuda & dekorasi kreatif dengan estetik floral mewah
        </p>

        <!-- Loading Dots -->
        <div class="flex justify-center items-center gap-2">
            <div class="w-3 h-3 bg-[color:var(--color-primary-500)] rounded-full animate-pulse"></div>
            <div class="w-3 h-3 bg-[color:var(--color-primary-500)] rounded-full animate-pulse" style="animation-delay: 150ms;"></div>
            <div class="w-3 h-3 bg-[color:var(--color-primary-500)] rounded-full animate-pulse" style="animation-delay: 300ms;"></div>
        </div>
    </div>

    <script>
        // Auto redirect after 3 seconds
        setTimeout(() => {
            window.location.href = '/';
        }, 3000);
    </script>
</body>
</html>