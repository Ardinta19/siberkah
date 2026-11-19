<!DOCTYPE html>
<html lang="id" class="h-full [&:not(:focus-visible):outline-none]" lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>SIBERKAH | Berkah Kreatif</title>

    <!-- Preload Critical Fonts -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Red+Hat+Mono:wght@500;600&display=swap" as="style" crossorigin>
    <link rel="preload" href="https://fonts.gstatic.com/s/plusjakartasans/v10/6NuL9_J5227cYdOSRgNbKCdbqXVo.woff2" as="style" crossorigin>
    <link rel="preload" href="https://fonts.gstatic.com/s/redhatmono/v10/7cHtv4U7p1TTs1V1TnEcXUZUDLqQJN0.woff2" as="style" crossorigin>
    <link rel="preload" href="https://fonts.gstatic.com/s/materialsymbolsoutlined/v267/kJEhBvYX7BgnkSrU3T8AKVwsXW8j00l-DYCf9PI1R.woff2" as="style" crossorigin>

    <!-- Critical CSS Inline (for instant rendering) -->
    <style>
      @layer base {
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
          -webkit-text-size-adjust: 100%;
          -moz-tab-size: 4;
          tab-size: 4;
        }

        body {
          @apply bg-primary-50 text-primary-900 font-body antialiased min-h-screen;
          @apply transition-colors duration-normal ease-in-out;
          font-feature-settings: "cv02", "cv03", "cv04", "cv11";
          -webkit-font-smoothing: antialiased;
          -moz-osx-font-smoothing: grayscale;
        }

        /* Focus visible */
        :focus-visible {
          @apply outline-none ring-2 ring-primary ring-offset-2 ring-offset-primary-500;
        }

        /* Selection */
        ::selection {
          @apply bg-primary-500 text-primary-500;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--color-primary-500); border-radius: 9999px; }
        ::-webkit-scrollbar-corner { background: transparent; }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
          *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
          }
        }
      }

      @layer components {
        /* Buttons */
        .btn {
          @apply inline-flex items-center justify-center gap-2 rounded-DEFAULT
               font-label text-label-md font-semibold
               transition-all duration-fast ease-in-out
               focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-primary-500
               disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:transform-none disabled:hover:shadow-none
               active:scale-[0.98];
        }

        .btn-primary { @apply btn bg-primary text-on-primary shadow-sm hover:bg-primary/90 hover:shadow-sm active:bg-primary active:shadow-sm; }
        .btn-secondary { @apply btn bg-secondary text-on-secondary shadow-sm hover:bg-secondary/90 hover:shadow-sm; }
        .btn-outline { @apply btn bg-transparent text-primary border-2 border-primary hover:bg-primary/5 hover:shadow-xs active:bg-primary/10; }
        .btn-ghost { @apply btn bg-transparent text-on-surface-variant hover:bg-surface-container hover:text-on-surface active:bg-surface-container-high; }
        .btn-danger { @apply btn bg-error text-on-error shadow-sm hover:bg-error/90 hover:shadow-sm; }
        .btn-success { @apply btn bg-success text-on-success shadow-sm hover:bg-success/90 hover:shadow-sm; }

        .btn-lg { @apply px-7 py-3.5 text-label-lg min-h-[52px] gap-3; }
        .btn-sm { @apply px-3.5 py-2 min-h-[36px] text-label-sm gap-1.5; }
        .btn-xs { @apply px-3 py-1.5 min-h-[32px] text-caption gap-1; }
        .btn-icon { @apply p-2.5 min-h-[44px] min-w-[44px]; }

        /* Material Symbols */
        .material-symbols-outlined { @apply inline-flex items-center justify-center font-normal; font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .material-symbols-filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .material-symbols-rounded { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .material-symbols-sharp { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }

        .text-gradient { @apply bg-clip-text text-transparent bg-gradient-to-r from-primary via-primary/80 to-accent; }
        .text-gradient-rose { @apply bg-clip-text text-transparent bg-gradient-to-r from-primary/90 via-primary to-accent; }
        .text-subtle { @apply bg-clip-text text-transparent bg-gradient-to-r from-on-surface via-on-surface/70 to-on-surface-variant; }
      }
    </style>

    <!-- Full CSS async -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}" media="print" onload="this.media='all'">
</head>
<body class="h-full overflow-hidden bg-primary-50 text-primary-900">

    <!-- Splash Screen -->
    <div id="splash-screen" class="splash-container h-full flex flex-col items-center justify-center" role="status" aria-label="Memuat SIBERKAH">
        <!-- Background Particles -->
        <div class="splash-particles absolute inset-0 pointer-events-none overflow-hidden">
            <div class="particle particle-1 absolute top-10 left-5 w-12 h-12 rounded-full bg-primary-500/10 opacity-30 animate-pulse" aria-hidden="true" style="animation-delay: 0s; animation-duration: 25s;"></div>
            <div class="particle particle-2 absolute bottom-10 right-5 w-16 h-16 rounded-full bg-primary-500/10 opacity-30 animate-pulse" aria-hidden="true" style="animation-delay: -5s; animation-duration: 25s;"></div>
            <div class="particle particle-3 absolute top-20 left-20 w-10 h-10 rounded-full bg-primary-500/10 opacity-30 animate-pulse" aria-hidden="true" style="animation-delay: -2s; animation-duration: 22s;"></div>
            <div class="particle particle-4 absolute bottom-20 left-20 w-12 h-12 rounded-full bg-primary-500/10 opacity-30 animate-pulse" aria-hidden="true" style="animation-delay: -8s; animation-duration: 28s;"></div>
            <div class="particle particle-5 absolute center -top-15 left-50 -translate-x-1/2 w-14 h-14 rounded-full bg-primary-500/10 opacity-30 animate-pulse" aria-hidden="true" style="animation-delay: -3s; animation-duration: 26s;"></div>
            <div class="particle particle-6 absolute bottom-10 left-80 w-18 h-18 rounded-full bg-primary-500/10 opacity-30 animate-pulse" aria-hidden="true" style="animation-delay: -12s; animation-duration: 24s;"></div>
            <div class="particle particle-7 absolute top-80 left-80 w-12 h-12 rounded-full bg-primary-500/10 opacity-30 animate-pulse" aria-hidden="true" style="animation-delay: -15s; animation-duration: 32s;"></div>
            <div class="particle particle-8 absolute center -bottom-15 left-50 -translate-x-1/2 w-16 h-16 rounded-full bg-primary-500/10 opacity-30 animate-pulse" aria-hidden="true" style="animation-delay: -10s; animation-duration: 20s;"></div>
        </div>

        <!-- Floating Petals -->
        <div class="splash-petals absolute inset-0 pointer-events-none overflow-hidden">
            @for ($i = 0; $i < 12; $i++)
                <div class="petal petal-{{ $i }}" style="--delay: {{ $i * 0.3 }}s; --duration: {{ 8 + $i * 0.5 }}s; --x: {{ rand(10, 90) }}%; --size: {{ rand(8, 24) }}px;" aria-hidden="true"></div>
            @endfor
        </div>

        <!-- Main Content -->
        <main class="splash-main text-center p-6 md:p-8 lg:p-10">
            <!-- Logo Animation -->
            <div class="splash-logo-wrapper mb-8 animate-bounce-in">
                <div class="splash-logo-ring" aria-hidden="true">
                    <div class="ring-segment ring-1"></div>
                    <div class="ring-segment ring-2"></div>
                    <div class="ring-segment ring-3"></div>
                </div>
                <div class="splash-logo-core">
                    <img src="{{ asset('images/logo-berkah-kreatif.svg') }}" alt="Berkah Kreatif" class="logo-img" width="80" height="80">
                </div>
                <div class="splash-logo-glow" aria-hidden="true"></div>
            </div>

            <!-- Brand Name -->
            <div class="splash-brand animate-slide-up" style="animation-delay: 300ms;">
                <h1 class="font-display text-display-lg-mobile sm:text-display-lg lg:text-display-lg font-bold text-primary leading-tight text-center gradient-text-rose">
                    Berkah Kreatif
                </h1>
                <p class="font-label text-label-md font-semibold text-primary/70 uppercase tracking-widest mt-2 text-center">
                    SIBERKAH Rental System
                </p>
            </div>

            <!-- Tagline -->
            <div class="splash-tagline animate-slide-up" style="animation-delay: 500ms;">
                <p class="font-body text-body-md text-on-surface-variant text-center max-w-xs leading-relaxed">
                    Sewa Papan Akrilik, Buket Wisuda & Dekorasi Estetik<br class="hidden sm:block"> dengan Hati & Teknologi
                </p>
            </div>

            <!-- Progress Indicator -->
            <div class="splash-progress mt-10 animate-slide-up" style="animation-delay: 700ms;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="Memuat aplikasi">
                <div class="progress-track">
                    <div class="progress-fill" id="splash-progress-bar" style="width: 0%"></div>
                    <div class="progress-glow" aria-hidden="true"></div>
                </div>
                <div class="progress-info flex items-center justify-between mt-3">
                    <span class="font-mono text-mono-md text-primary" id="splash-progress-text">0%</span>
                    <span class="font-body text-body-sm text-on-surface-variant" id="splash-progress-label">Memuat aset...</span>
                </div>
            </div>

            <!-- Feature Highlights -->
            <div class="splash-features mt-10 animate-fade-in" style="animation-delay: 1000ms;">
                <div class="features-carousel" id="splash-features-carousel">
                    <div class="feature-slide active" data-index="0">
                        <span class="material-symbols-outlined text-4xl text-primary mb-3 block">verified</span>
                        <h3 class="font-headline text-headline-sm font-bold text-on-surface">Anti Double Booking</h3>
                        <p class="font-body text-body-sm text-on-surface-variant mt-1">Sistem kunci database real-time</p>
                    </div>
                    <div class="feature-slide" data-index="1">
                        <span class="material-symbols-outlined text-4xl text-accent mb-3 block">timer</span>
                        <h3 class="font-headline text-headline-sm font-bold text-on-surface">Bayar 30 Menit</h3>
                        <p class="font-body text-body-sm text-on-surface-variant mt-1">QRIS instant, inventaris aman</p>
                    </div>
                    <div class="feature-slide" data-index="2">
                        <span class="material-symbols-outlined text-4xl text-success mb-3 block">shield</span>
                        <h3 class="font-headline text-headline-sm font-bold text-on-surface">QC Foto 1-6</h3>
                        <p class="font-body text-body-sm text-on-surface-variant mt-1">Dokumentasi fisik sebelum/sesudah</p>
                    </div>
                </div>
                <div class="carousel-dots mt-4 flex justify-center gap-2" id="carousel-dots">
                    <button class="carousel-dot active w-2 h-2 rounded-full bg-primary" data-index="0" aria-label="Fitur 1"></button>
                    <button class="carousel-dot w-2 h-2 rounded-full bg-primary/30" data-index="1" aria-label="Fitur 2"></button>
                    <button class="carousel-dot w-2 h-2 rounded-full bg-primary/30" data-index="2" aria-label="Fitur 3"></button>
                </div>
            </div>

            <!-- Version & Legal -->
            <div class="splash-footer mt-12 animate-fade-in" style="animation-delay: 1200ms;">
                <p class="font-body text-caption text-on-surface-variant/50">
                    Versi {{ config('app.version', '1.0.0') }} • © {{ date('Y') }} Berkah Kreatif
                </p>
            </div>
        </main>

        <!-- Decorative Elements -->
        <div class="splash-decoration absolute inset-0 pointer-events-none overflow-hidden">
            <div class="decoration-petal decoration-1"></div>
            <div class="decoration-petal decoration-2"></div>
            <div class="decoration-petal decoration-3"></div>
        </div>
    </div>

    <!-- App Container (Hidden initially) -->
    <div id="app-container" class="hidden h-full" aria-hidden="true">
        @yield('content')
    </div>

    <!-- Splash Script -->
    <script>
      // Splash Screen Controller - Progressive Loading System
      (function() {
        'use strict';

        // ==========================================
        // SPLASH SCREEN CONTROLLER
        // ==========================================

        const SplashController = {
          // State
          progress: 0,
          targetProgress: 0,
          assetsLoaded: 0,
          totalAssets: 0,
          startTime: performance.now(),
          minDisplayTime: 1800, // Minimum 1.8s untuk branding
          animationFrameId: null,
          featureCarouselInterval: null,
          currentFeatureIndex: 0,

          // DOM Elements
          elements: {},

          // Asset manifest untuk preloading
          assetManifest: [
            { type: 'font', url: 'https://fonts.gstatic.com/s/playfairdisplay/v30/nuFvD-vYSZviVYUb_rj3ij__anPXJzDwcbmjWBN2PKdFvUDXbtY.woff2' },
            { type: 'font', url: 'https://fonts.gstatic.com/s/plusjakartasans/v10/6NuL9_J5227cYdOSRgNbKCdbqXVo.woff2' },
            { type: 'font', url: 'https://fonts.gstatic.com/s/redhatmono/v10/7cHtv4U7p1TTs1V1TnEcXUZUDLqQJN0.woff2' },
            { type: 'font', url: 'https://fonts.gstatic.com/s/materialsymbolsoutlined/v267/kJEhBvYX7BgnkSrU3T8AKVwsXW8j00l-DYCf9PI1R.woff2' },
            { type: 'image', url: '/images/logo-berkah-kreatif.svg' },
            { type: 'image', url: '/images/hero-pattern.svg' },
          ],

          init() {
            this.cacheElements();
            this.bindEvents();
            this.startLoading();
            this.startProgressAnimation();
            this.startFeatureCarousel();
            this.setupIntersectionObserver();
          },

          cacheElements() {
            this.elements = {
              screen: document.getElementById('splash-screen'),
              appContainer: document.getElementById('app-container'),
              progressBar: document.getElementById('splash-progress-bar'),
              progressText: document.getElementById('splash-progress-text'),
              progressLabel: document.getElementById('splash-progress-label'),
              logoCore: document.querySelector('.splash-logo-core'),
              featureCarousel: document.getElementById('splash-features-carousel'),
              carouselDots: document.getElementById('carousel-dots'),
            };
          },

          bindEvents() {
            // Carousel dot clicks
            this.elements.carouselDots?.querySelectorAll('.carousel-dot').forEach((dot, index) => {
              dot.addEventListener('click', () => this.goToFeature(index));
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
              if (e.key === 'ArrowLeft') this.prevFeature();
              if (e.key === 'ArrowRight') this.nextFeature();
            });

            // Touch swipe for carousel
            let touchStartX = 0;
            this.elements.featureCarousel?.addEventListener('touchstart', (e) => {
              touchStartX = e.touches[0].clientX;
            }, { passive: true });
            this.elements.featureCarousel?.addEventListener('touchend', (e) => {
              const touchEndX = e.changedTouches[0].clientX;
              const diff = touchStartX - touchEndX;
              if (Math.abs(diff) > 50) {
                diff > 0 ? this.nextFeature() : this.prevFeature();
              }
            });

            // Page visibility change
            document.addEventListener('visibilitychange', () => {
              if (document.hidden) {
                this.pauseAnimations();
              } else {
                this.resumeAnimations();
              }
            });
          },

          startLoading() {
            this.totalAssets = this.assetManifest.length;
            this.assetsLoaded = 0;

            // Preload assets dengan prioritas
            this.assetManifest.forEach((asset, index) => {
              const delay = index * 50; // Stagger loading
              setTimeout(() => this.loadAsset(asset), delay);
            });

            // Simulate app initialization steps
            this.simulateAppInit();
          },

          loadAsset(asset) {
            return new Promise((resolve) => {
              if (asset.type === 'image') {
                const img = new Image();
                img.onload = img.onerror = () => {
                  this.onAssetLoaded();
                  resolve();
                };
                img.src = asset.url;
              } else if (asset.type === 'font') {
                // Fonts sudah di-preload via link rel=preload di HTML
                // Cek apakah font sudah loaded via FontFace API
                if ('fonts' in document) {
                  document.fonts.ready.then(() => {
                    this.onAssetLoaded();
                    resolve();
                  });
                } else {
                  setTimeout(() => { this.onAssetLoaded(); resolve(); }, 100);
                }
              } else {
                setTimeout(() => { this.onAssetLoaded(); resolve(); }, 50);
              }
            });
          },

          onAssetLoaded() {
            this.assetsLoaded++;
            this.targetProgress = Math.min(90, Math.round((this.assetsLoaded / this.totalAssets) * 90));
          },

          simulateAppInit() {
            // Simulasi steps inisialisasi app
            const steps = [
              { label: 'Memuat konfigurasi...', progress: 15, delay: 200 },
              { label: 'Menyiapkan database...', progress: 30, delay: 400 },
              { label: 'Memuat preferensi pengguna...', progress: 45, delay: 600 },
              { label: 'Menyiapkan antarmuka...', progress: 60, delay: 800 },
              { label: 'Memverifikasi sesi...', progress: 75, delay: 1000 },
              { label: 'Hampir siap...', progress: 90, delay: 1200 },
            ];

            steps.forEach((step, index) => {
              setTimeout(() => {
                this.updateProgressLabel(step.label);
                this.targetProgress = step.progress;
              }, step.delay);
            });

            // Complete loading
            setTimeout(() => this.completeLoading(), 1600);
          },

          updateProgressBar() {
            if (this.elements.progressBar) {
              this.elements.progressBar.style.width = `${this.targetProgress}%`;
            }
            if (this.elements.progressText) {
              this.elements.progressText.textContent = `${Math.round(this.targetProgress)}%`;
            }
          },

          updateProgressLabel(label) {
            if (this.elements.progressLabel) {
              this.elements.progressLabel.textContent = label;
              this.elements.progressLabel.style.animation = 'none';
              this.elements.progressLabel.offsetHeight; // Force reflow
              this.elements.progressLabel.style.animation = 'fade-in 300ms ease-out';
            }
          },

          startProgressAnimation() {
            const animate = () => {
              const diff = this.targetProgress - this.progress;
              if (Math.abs(diff) > 0.5) {
                this.progress += diff * 0.15; // Easing
                this.updateProgressBar();
              } else if (this.targetProgress >= 100 && this.progress >= 99.5) {
                this.progress = 100;
                this.updateProgressBar();
              }
              this.animationFrameId = requestAnimationFrame(() => this.startProgressAnimation());
            };
            this.animationFrameId = requestAnimationFrame(() => this.startProgressAnimation());
          },

          completeLoading() {
            // Ensure minimum display time
            const elapsed = performance.now() - this.startTime;
            const remainingTime = Math.max(0, this.minDisplayTime - elapsed);

            setTimeout(() => {
              this.targetProgress = 100;
              this.updateProgressLabel('Siap!');

              // Wait for progress bar to complete
              const checkComplete = () => {
                if (this.progress >= 99.5) {
                  this.transitionToApp();
                } else {
                  setTimeout(checkComplete, 50);
                }
              };
              checkComplete();
            }, remainingTime);
          },

          transitionToApp() {
            // Cancel animation frame
            cancelAnimationFrame(this.animationFrameId);
            clearInterval(this.featureCarouselInterval);

            // Animate out
            const screen = this.elements.screen;
            const appContainer = this.elements.appContainer;

            if (!screen || !appContainer) return;

            // Add exit animation classes
            screen.style.animation = 'fade-out 400ms cubic-bezier(0.7, 0, 0.84, 0) forwards, slide-up 400ms cubic-bezier(0.7, 0, 0.84, 0) forwards';
            screen.style.transformOrigin = 'center bottom';

            // Show app container
            appContainer.classList.remove('hidden');
            appContainer.setAttribute('aria-hidden', 'false');
            screen.setAttribute('aria-hidden', 'true');

            // Animate in app
            const appContent = appContainer.querySelector('[data-page-content]') || appContainer.firstElementChild;
            if (appContent) {
              appContent.style.opacity = '0';
              appContent.style.transform = 'translateY(20px)';
              appContent.style.transition = 'opacity 400ms cubic-bezier(0.16, 1, 0.3, 1), transform 400ms cubic-bezier(0.16, 1, 0.3, 1)';

              // Trigger reflow
              appContent.offsetHeight;

              requestAnimationFrame(() => {
                appContent.style.opacity = '1';
                appContent.style.transform = 'translateY(0)';
              });
            }

            // Remove splash after animation
            setTimeout(() => {
              screen.remove();
              document.body.style.overflow = '';

              // Dispatch custom event untuk app initialization
              window.dispatchEvent(new CustomEvent('splash:complete', {
                detail: { loadTime: performance.now() - this.startTime }
              }));

              // Initialize app components
              this.initializeAppComponents();
            }, 400);
          },

          pauseAnimations() {
            document.querySelectorAll('.splash-container *').forEach(el => {
              el.style.animationPlayState = 'paused';
            });
            cancelAnimationFrame(this.animationFrameId);
            clearInterval(this.featureCarouselInterval);
          },

          resumeAnimations() {
            document.querySelectorAll('.splash-container *').forEach(el => {
              el.style.animationPlayState = 'running';
            });
            this.startProgressAnimation();
            this.startFeatureCarousel();
          },

          setupIntersectionObserver() {
            if ('IntersectionObserver' in window) {
              const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                  if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                  }
                });
              }, { rootMargin: '100px' });

              document.querySelectorAll('[data-preload]').forEach(el => observer.observe(el));
            }
          },

          preloadPage(page) {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = page;
            document.head.appendChild(link);
          },

          initializeAppComponents() {
            if (window.Alpine) {
              Alpine.start();
            }

            if (window.Livewire) {
              window.Livewire.start();
            }

            this.initCustomComponents();

            if (window.gtag) {
              gtag('event', 'splash_complete', {
                load_time: performance.now() - this.startTime,
                asset_count: this.totalAssets
              });
            }
          },

          initCustomComponents() {
            this.initTooltips();
            this.initDropdowns();
            this.initModals();
            this.initToasts();
            this.initTabs();
            this.initAccordions();
            this.initScrollReveal();
            this.initFormValidation();
            this.initImageLazyLoad();
            this.initParallax();
            this.initCounters();
          },

          // ==========================================
          // COMPONENT INITIALIZERS
          // ==========================================

          initTooltips() {
            document.querySelectorAll('[data-tooltip]').forEach(el => {
              new Tooltip(el);
            });
          },

          initDropdowns() {
            document.querySelectorAll('[data-dropdown]').forEach(el => {
              new Dropdown(el);
            });

            document.addEventListener('click', (e) => {
              document.querySelectorAll('.dropdown.open').forEach(dd => {
                if (!dd.contains(e.target)) {
                  dd.classList.remove('open');
                }
              });
            });
          },

          initModals() {
            document.querySelectorAll('[data-modal]').forEach(trigger => {
              trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.open();
              });
            });

            document.querySelectorAll('[data-modal-close]').forEach(btn => {
              btn.addEventListener('click', () => this.close());
            });
          },

          initToasts() {
            window.showToast = function(options = {}) {
              return showToast(options);
            };
          },

          initTabs() {
            document.querySelectorAll('.tabs').forEach(tabs => {
              tabs.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', () => {
                  const tabsContainer = tab.closest('.tabs');
                  const indicator = tabsContainer.querySelector('.tab-indicator');
                  const targetTabEl = tabsContainer.querySelector(`[data-tab="${tab.dataset.tab}"]`);

                  if (!indicator || !targetTabEl) return;

                  indicator.style.width = `${targetTabEl.offsetWidth}px`;
                  indicator.style.transform = `translateX(${targetTabEl.offsetLeft}px)`;

                  const container = document.querySelector('[data-tabs-container]');
                  if (container) {
                    container.querySelectorAll('.tab-content').forEach(panel => {
                      panel.classList.toggle('active', panel.dataset.tab === targetTab.dataset.tab);
                    });
                  }
                });
              });
            });
          },

          initAccordions() {
            document.querySelectorAll('.accordion-header').forEach(header => {
              header.addEventListener('click', () => {
                const item = header.closest('.accordion-item');
                const isOpen = item.classList.contains('open');

                document.querySelectorAll('.accordion .accordion-item').forEach(item => {
                  if (item !== item) item.classList.remove('open');
                });

                item.classList.toggle('open');
              });
            });
          },

          initScrollReveal() {
            if (!('IntersectionObserver' in window)) return;

            const observer = new IntersectionObserver((entries) => {
              entries.forEach(entry => {
                if (entry.isIntersecting) {
                  entry.target.classList.add('visible');
                  observer.unobserve(entry.target);
                }
              });
            }, { rootMargin: '0px 0px -50px 0px', threshold: 0.1 });

            document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
          },

          initFormValidation() {
            document.querySelectorAll('form[data-validate]').forEach(form => {
              form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const isValid = await validateForm(form);
                if (isValid) {
                  form.dispatchEvent(new CustomEvent('form:valid', { detail: { formData: new FormData(form) } }));
                }
              });

              form.querySelectorAll('input, textarea, select').forEach(input => {
                input.addEventListener('blur', () => validateField(input));
                input.addEventListener('input', () => {
                  if (input.classList.contains('error')) validateField(input);
                });
              });
            });
          },

          initImageLazyLoad() {
            if (!('IntersectionObserver' in window)) return;

            const observer = new IntersectionObserver((entries) => {
              entries.forEach(entry => {
                if (entry.isIntersecting) {
                  const img = entry.target;
                  if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.onload = () => img.classList.add('loaded');
                  };
                  observer.unobserve(img);
                }
              });
            }, { rootMargin: '100px' });

            document.querySelectorAll('img[data-src]').forEach(img => observer.observe(img));
          },

          initParallax() {
            const parallaxElements = document.querySelectorAll('[data-parallax]');
            if (!parallaxElements.length) return;

            let ticking = false;
            const update = () => {
              const scrollY = window.scrollY;
              parallaxElements.forEach(el => {
                const speed = parseFloat(el.dataset.parallax) || 0.5;
                const offset = window.scrollY * speed;
                el.style.transform = `translateY(${offset}px)`;
              });
            };

            window.addEventListener('scroll', () => {
              if (!ticking) {
                requestAnimationFrame(update);
                ticking = false;
              }
            }, { passive: true });
          },

          initCounters() {
            const counters = document.querySelectorAll('[data-counter]');
            if (!counters.length) return;

            const observer = new IntersectionObserver((entries) => {
              entries.forEach(entry => {
                if (entry.isIntersecting) {
                  const el = entry.target;
                  const target = parseInt(el.dataset.counter) || 0;
                  MicroInteractions.counter.animate(el, target, 2000, { decimals: 0 });
                  observer.unobserve(el);
                }
              });
            }, { threshold: 0.5 });

            document.querySelectorAll('[data-counter]').forEach(el => observer.observe(el));
          },
        };

        // Initialize on DOM ready
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', () => SplashController.init());
        } else {
          SplashController.init();
        }

        // Expose for debugging
        window.SplashController = SplashController;

      })();
    </script>

    @push('scripts')