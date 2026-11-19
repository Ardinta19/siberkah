const MicroInteractions = {
  // === BUTTON INTERACTIONS ===
  button: {
    ripple: (button, event) => {
      const ripple = document.createElement('span');
      const rect = button.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = event.clientX - rect.left - size / 2;
      const y = event.clientY - rect.top - size / 2;

      ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: currentColor;
        opacity: 0.15;
        border-radius: 50%;
        transform: scale(0);
        animation: ripple-effect 600ms ease-out forwards;
        pointer-events: none;
      `;

      button.style.position = 'relative';
      button.style.overflow = 'hidden';
      button.appendChild(ripple);

      ripple.addEventListener('animationend', () => ripple.remove());
    },

    press: (button) => {
      button.style.transform = 'scale(0.98)';
      button.style.transition = 'transform 50ms ease-out';
    },

    release: (button) => {
      button.style.transform = '';
      button.style.transition = 'transform 150ms cubic-bezier(0.34, 1.56, 0.64, 1)';
    },

    loading: (button, isLoading) => {
      if (isLoading) {
        button.dataset.originalContent = button.innerHTML;
        button.innerHTML = `
          <span class="btn-spinner flex items-center justify-center">
            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"/>
              <path class="opacity-75" fill="none" stroke="currentColor" stroke-width="3" d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
            </svg>
          </span>
        `;
        button.disabled = true;
      } else {
        button.innerHTML = button.dataset.originalContent || '';
        button.disabled = false;
      }
    },
  },

  // === INPUT INTERACTIONS ===
  input: {
    floatLabel: (input) => {
      const wrapper = input.closest('.input-group');
      const label = wrapper?.querySelector('.input-label');
      if (!label) return;

      const update = () => {
        const hasValue = input.value.length > 0;
        const isFocused = document.activeElement === input;
        label.classList.toggle('floating', hasValue || isFocused);
      };

      input.addEventListener('focus', update);
      input.addEventListener('blur', update);
      input.addEventListener('input', update);
      update();
    },

    charCounter: (input, maxLength) => {
      const wrapper = input.closest('.input-group');
      let counter = wrapper?.querySelector('.char-counter');

      if (!counter) {
        counter = document.createElement('div');
        counter.className = 'char-counter text-right font-mono text-caption text-on-surface-variant/60 mt-1.5';
        wrapper?.appendChild(counter);
      }

      const update = () => {
        const length = input.value.length;
        counter.textContent = `${length}/${maxLength}`;
        counter.classList.toggle('text-warning', length > maxLength * 0.8);
        counter.classList.toggle('text-error', length >= maxLength);
        input.classList.toggle('error', length >= maxLength);
      };

      input.addEventListener('input', update);
      update();
    },

    autoResize: (textarea) => {
      const resize = () => {
        textarea.style.height = 'auto';
        textarea.style.height = `${Math.min(textarea.scrollHeight, 300)}px`;
      };

      textarea.addEventListener('input', resize);
      resize();
    },

    formatOnBlur: (input, formatter) => {
      input.addEventListener('blur', () => {
        if (input.value) {
          input.value = formatter(input.value);
        }
      });

      input.addEventListener('focus', () => {
        input.dataset.rawValue = input.value;
        input.value = input.value.replace(/[^\d]/g, '');
      });
    },
  },

  // === CARD INTERACTIONS ===
  card: {
    tilt: (card, intensity = 10) => {
      let rafId = null;

      const handleMove = (e) => {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
          const rect = card.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          const centerX = rect.width / 2;
          const centerY = rect.height / 2;
          const rotateX = ((y - centerY) / centerY) * intensity;
          const rotateY = ((centerX - x) / centerX) * intensity;

          card.style.transform = `
            perspective(1000px)
            rotateX(${rotateX}deg)
            rotateY(${rotateY}deg)
            scale3d(1.02, 1.02, 1.02)
          `;
        });
      };

      const handleLeave = () => {
        card.style.transition = 'transform 400ms cubic-bezier(0.34, 1.56, 0.64, 1)';
        card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
      };

      card.addEventListener('mousemove', handleMove);
      card.addEventListener('mouseleave', handleLeave);

      return () => {
        card.removeEventListener('mousemove', handleMove);
        card.removeEventListener('mouseleave', handleLeave);
      };
    },

    magnetic: (card, strength = 0.3) => {
      let rafId = null;

      const handleMove = (e) => {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
          const rect = card.getBoundingClientRect();
          const centerX = rect.left + rect.width / 2;
          const centerY = rect.top + rect.height / 2;
          const deltaX = (e.clientX - centerX) * strength;
          const deltaY = (e.clientY - centerY) * strength;

          card.style.transform = `translate(${deltaX}px, ${deltaY}px) scale(1.02)`;
        });
      };

      const handleLeave = () => {
        card.style.transition = 'transform 400ms cubic-bezier(0.34, 1.56, 0.64, 1)';
        card.style.transform = 'translate(0, 0) scale(1)';
      };

      card.addEventListener('mousemove', handleMove);
      card.addEventListener('mouseleave', handleLeave);

      return () => {
        card.removeEventListener('mousemove', handleMove);
        card.removeEventListener('mouseleave', handleLeave);
      };
    },

    lift: (card) => {
      card.style.transition = 'transform 150ms cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 150ms ease-out';
      card.style.transform = 'translateY(-4px) scale(1.02)';
      card.style.boxShadow = 'var(--shadow-ambient-medium)';

      return () => {
        card.style.transition = 'transform 200ms ease-out, box-shadow 200ms ease-out';
        card.style.transform = '';
        card.style.boxShadow = '';
      };
    },

    pulse: (card, delay = 0) => {
      card.style.transition = 'transform 2s ease-in-out, box-shadow 2s ease-in-out';
      card.style.animation = `pulse-soft 2s ease-in-out infinite${delay ? ` ${delay}ms` : ''}`;

      return () => {
        card.style.animation = '';
        card.style.transition = '';
      };
    },

    float: (card, height = 10) => {
      let rafId = null;
      let yOffset = 0;
      const direction = 1;

      const handleMove = (e) => {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
          const rect = card.getBoundingClientRect();
          const centerX = rect.left + rect.width / 2;
          const centerY = rect.top + rect.height / 2;
          const deltaX = (e.clientX - centerX) * 0.1;
          yOffset += (e.clientY - centerY) * 0.05;

          card.style.transform = `translateY(${yOffset}px) translateX(${deltaX}px) scale(1.01)`;
        });
      };

      const handleLeave = () => {
        card.style.transition = 'transform 400ms cubic-bezier(0.34, 1.56, 0.64, 1)';
        card.style.transform = '';
      };

      card.addEventListener('mousemove', handleMove);
      card.addEventListener('mouseleave', handleLeave);
    },
  },

  // === TOGGLE / SWITCH ===
  toggle: {
    animate: (toggle) => {
      const input = toggle.querySelector('input');
      const thumb = toggle.querySelector('.switch-thumb');
      const track = toggle.querySelector('.switch-track');

      if (!input || !thumb || !track) return;

      const update = () => {
        const isChecked = input.checked;
        thumb.style.transform = isChecked ? 'translateX(100%)' : 'translateX(0)';
        track.style.backgroundColor = isChecked ? 'var(--color-primary)' : '';
      };

      input.addEventListener('change', update);
      update();
    },

    haptic: (toggle) => {
      const input = toggle.querySelector('input');
      input.addEventListener('change', () => {
        toggle.style.animation = 'none';
        toggle.offsetHeight;
        toggle.style.animation = 'pulse-scale 150ms ease-out';

        if (navigator.vibrate) {
          navigator.vibrate(10);
        }
      });
    },
  },

  // === MODAL / DRAWER ===
  modal: {
    enter: (modal) => {
      const content = modal.querySelector('.modal-content') || modal;
      const backdrop = modal.previousElementSibling;

      content.style.opacity = '0';
      content.style.transform = 'scale(0.95) translateY(20px)';
      backdrop.style.opacity = '0';

      content.offsetHeight;

      requestAnimationFrame(() => {
        content.style.transition = 'opacity 250ms cubic-bezier(0.16, 1, 0.3, 1), transform 250ms cubic-bezier(0.16, 1, 0.3, 1)';
        backdrop.style.transition = 'opacity 200ms ease-out';

        content.style.opacity = '1';
        content.style.transform = 'translateY(0) scale(1)';
        backdrop.style.opacity = '1';
      });
    },

    exit: (modal) => {
      return new Promise((resolve) => {
        const content = modal.querySelector('.modal-content') || modal;
        const backdrop = modal.previousElementSibling;

        content.style.transition = 'opacity 200ms ease-in, transform 200ms ease-in';
        backdrop.style.transition = 'opacity 150ms ease-in';

        content.style.opacity = '0';
        content.style.transform = 'scale(0.95) translateY(20px)';
        backdrop.style.opacity = '0';

        content.addEventListener('transitionend', () => resolve(), { once: true });
      });
    },

    trapFocus: (modal) => {
      const focusable = modal.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      const handleTab = (e) => {
        if (e.key !== 'Tab') return;

        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      };

      modal.addEventListener('keydown', this.handleTab = (e) => {
        if (e.key === 'Tab') {
          if (e.shiftKey && document.activeElement === focusable[0]) {
            e.preventDefault();
            focusable[focusable.length - 1].focus();
          } else if (!e.shiftKey && document.activeElement === focusable[focusable.length - 1]) {
            e.preventDefault();
            focusable[0].focus();
          }
        });

      focusable[0]?.focus();
    },
  },

  // === DROPDOWN ===
  dropdown: {
    open: (dropdown) => {
      const content = dropdown.querySelector('.dropdown-content') || dropdown;
      const trigger = dropdown.querySelector('[data-dropdown-trigger]');

      content.style.opacity = '0';
      content.style.transform = 'translateY(-8px) scale(0.98)';

      requestAnimationFrame(() => {
        content.style.transition = 'opacity 150ms ease-out, transform 150ms ease-out';
        content.style.opacity = '1';
        content.style.transform = 'translateY(0) scale(1)';
      });
    },

    close: (dropdown) => {
      return new Promise((resolve) => {
        const content = dropdown.querySelector('.dropdown-content') || dropdown;

        content.style.transition = 'opacity 100ms ease-in, transform 100ms ease-in';
        content.style.opacity = '0';
        content.style.transform = 'translateY(-4px) scale(0.98)';

        content.addEventListener('transitionend', () => resolve(), { once: true });
      });
    },

    position: (dropdown, trigger) => {
      const content = dropdown.querySelector('.dropdown-content') || dropdown;
      const triggerRect = trigger.getBoundingClientRect();
      const contentRect = content.getBoundingClientRect();
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      const gap = 8;

      let top = triggerRect.bottom + gap;
      let left = triggerRect.left;

      if (left + contentRect.width > viewportWidth - 16) {
        left = viewportWidth - contentRect.width - 16;
      }
      if (left < 16) left = 16;

      if (top + contentRect.height > viewportHeight - 16) {
        top = triggerRect.top - contentRect.height - gap;
      }

      content.style.top = `${top}px`;
      content.style.left = `${left}px`;
    },
  },

  // === TOAST ===
  toast: {
    show: (toast) => {
      toast.style.transform = 'translateX(120%)';
      toast.style.opacity = '0';

      requestAnimationFrame(() => {
        toast.style.transition = 'transform 300ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms ease-out';
        toast.style.transform = 'translateX(0)';
        toast.style.opacity = '1';
      });
    },

    dismiss: (toast) => {
      return new Promise((resolve) => {
        toast.style.transition = 'transform 200ms ease-in, opacity 150ms ease-in';
        toast.style.transform = 'translateX(120%)';
        toast.style.opacity = '0';

        toast.addEventListener('transitionend', () => resolve(), { once: true });
      });
    },
  },

  // === TABS ===
  tabs: {
    switch: (tabs, targetTab) => {
      const tabsContainer = tabs.closest('.tabs');
      const indicator = tabsContainer.querySelector('.tab-indicator');
      const targetTabEl = tabsContainer.querySelector(`[data-tab="${targetTab}"]`);

      if (!indicator || !targetTabEl) return;

      indicator.style.width = `${targetTabEl.offsetWidth}px`;
      indicator.style.transform = `translateX(${targetTabEl.offsetLeft}px)`;

      const container = document.querySelector('[data-tabs-container]');
      if (container) {
        container.querySelectorAll('.tab-content').forEach(panel => {
          panel.classList.toggle('active', panel.dataset.tab === targetTab);
        });
      }
    },
  },

  // === ACCORDION ===
  accordion: {
    toggle: (item) => {
      const isOpen = item.classList.contains('open');
      const content = item.querySelector('.accordion-content');
      const icon = item.querySelector('.accordion-icon');

      if (isOpen) {
        content.style.height = `${content.scrollHeight}px`;
        requestAnimationFrame(() => {
          content.style.transition = 'height 300ms ease-in-out';
          content.style.height = '0px';
        });

        if (icon) icon.style.transform = 'rotate(0deg)';
        item.classList.remove('open');
      } else {
        item.classList.add('open');
        content.style.height = 'auto';
        const height = content.scrollHeight;
        content.style.height = '0px';

        requestAnimationFrame(() => {
          content.style.transition = 'height 300ms ease-in-out';
          content.style.height = `${content.scrollHeight}px`;
        });

        if (icon) icon.style.transform = 'rotate(180deg)';

        content.addEventListener('transitionend', () => {
          if (item.classList.contains('open')) {
            content.style.height = 'auto';
            content.style.transition = '';
          }
        }, { once: true });
      },
    },

  // === FORM VALIDATION ===
  validation: {
    showError: (input, message) => {
      const wrapper = input.closest('.input-group');
      let errorEl = wrapper?.querySelector('.input-error');

      if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.className = 'input-error animate-slide-down';
        wrapper?.appendChild(errorEl);
      }

      errorEl.innerHTML = `
        <span class="material-symbols-outlined text-[14px]">error</span>
        <span>${message}</span>
      `;

      input.classList.add('error');
      input.setAttribute('aria-invalid', 'true');
      input.setAttribute('aria-describedby', errorEl.id || 'error-message');
    },

    clearError: (input) => {
      const wrapper = input.closest('.input-group');
      const errorEl = wrapper?.querySelector('.input-error');

      if (errorEl) {
        errorEl.style.animation = 'slide-up 200ms ease-in forwards';
        setTimeout(() => errorEl.remove(), 200);
      }

      input.classList.remove('error');
      input.removeAttribute('aria-invalid');
      input.removeAttribute('aria-describedby');
    },

    shake: (input) => {
      input.style.animation = 'none';
      input.offsetHeight;
      input.style.animation = 'wiggle 400ms ease-in-out';
    },
  },

  // === LOADING STATES ===
  loading: {
    show: (element) => {
      element.style.position = 'relative';
      element.style.overflow = 'hidden';

      const skeleton = document.createElement('div');
      skeleton.className = 'skeleton-loader absolute inset-0';
      skeleton.innerHTML = `
        <div class="skeleton-shimmer absolute inset-0"></div>
      `;
      element.style.position = 'relative';
      element.appendChild(skeleton);
    },

    hide: (element) => {
      const loader = element.querySelector('.skeleton-loader');
      if (loader) {
        loader.style.transition = 'opacity 200ms ease-out';
        loader.style.opacity = '0';
        setTimeout(() => loader.remove(), 200);
      }
    },

    button: (button, isLoading) => {
      if (isLoading) {
        button.dataset.originalContent = button.innerHTML;
        button.innerHTML = `
          <span class="btn-spinner flex items-center justify-center">
            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"/>
              <path class="opacity-75" fill="none" stroke="currentColor" stroke-width="3" d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
            </svg>
          </span>
        `;
        button.disabled = true;
      } else {
        button.innerHTML = button.dataset.originalContent || '';
        button.disabled = false;
      }
    },
  },

  // === IMAGE LOADING ===
  image: {
    lazyLoad: (img) => {
      if (!('IntersectionObserver' in window)) {
        img.src = img.dataset.src;
        return;
      }

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.onload = () => img.classList.add('loaded');
          };
          observer.unobserve(img);
        });
      }, { rootMargin: '100px' });

      document.querySelectorAll('img[data-src]').forEach(img => observer.observe(img));
    },

    blurUp: (img, lowQualitySrc) => {
      img.src = lowQualitySrc;
      img.style.filter = 'blur(20px)';
      img.style.transition = 'filter 400ms ease-out';

      const highResImg = new Image();
      highResImg.src = img.dataset.src;
      highResImg.onload = () => {
        img.src = img.dataset.src;
        requestAnimationFrame(() => {
          img.style.filter = 'blur(0)';
        });
      };
    },
  },

  // === SCROLL INTERACTIONS ===
  scroll: {
    reveal: (elements, options = {}) => {
      if (!('IntersectionObserver' in window)) {
        elements.forEach(el => el.classList.add('visible'));
        return;
      }

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      }, { rootMargin: options.rootMargin || '0px 0px -50px 0px', threshold: options.threshold || 0.1 });

      elements.forEach(el => observer.observe(el));
    },

    parallax: (elements, options = {}) => {
      const speed = options.speed || 0.5;
      let ticking = false;

      const update = () => {
        const scrollY = window.scrollY;
        elements.forEach(el => {
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

    progress: (indicator) => {
      const update = () => {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = scrollTop / docHeight;
        indicator.style.width = `${progress * 100}%`;
      };

      window.addEventListener('scroll', update, { passive: true });
      update();
    },
  },

  // === COUNTERS ===
  counter: {
    animate: (el, target, duration = 2000, options = {}) => {
      const { prefix = '', suffix = '', decimals = 0, easing = 'easeOutCubic' } = options;
      const start = 0;
      const startTime = performance.now();
      const durationMs = duration;

      const easingFunctions = {
        easeOutCubic: t => 1 - Math.pow(1 - t, 3),
        easeOutQuart: t => 1 - Math.pow(1 - t, 4),
        easeOutExpo: t => t === 1 ? 1 : 1 - Math.pow(2, -10 * t),
        spring: t => 1 - Math.pow(1 - t, 3) * Math.cos(t * Math.PI * 4),
      };

      const easingFn = easingFunctions[options.easing] || easingFunctions.easeOutCubic;

      const animate = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / durationMs, 1);
        const eased = easingFunctions[options.easing]?.(progress) || easingFunctions.easeOutCubic(progress);
        const current = target * eased;

        el.textContent = prefix + current.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + suffix;

        if (progress < 1) {
          requestAnimationFrame(animate);
        } else {
          el.textContent = prefix + target.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
        }
      };

      const startTime = performance.now();
      requestAnimationFrame(animate);
    },
  },

  // === DRAG & DROP ===
  dragDrop: {
    init: (container, options = {}) => {
      const { onDrop, onDragStart, onDragOver, dragClass = 'dragging', overClass = 'drag-over' } = options;

      let draggedItem = null;

      container.querySelectorAll('[draggable="true"]').forEach(item => {
        item.addEventListener('dragstart', (e) => {
          draggedItem = item;
          item.classList.add(dragClass);
          e.dataTransfer.effectAllowed = 'move';
          onDragStart?.(item, e);
        });

        item.addEventListener('dragend', () => {
          item.classList.remove(dragClass);
          container.querySelectorAll(`.${overClass}`).forEach(el => el.classList.remove(overClass));
          onDragStart?.(item, e);
        });

        item.addEventListener('dragover', (e) => {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          const target = e.target.closest('[draggable="true"]');
          if (target && target !== draggedItem) {
            container.querySelectorAll(`.${overClass}`).forEach(el => el.classList.remove(overClass));
            target.classList.add(overClass);
            onDragOver?.(target, e);
          }
        });

        item.addEventListener('drop', (e) => {
          e.preventDefault();
          const target = e.target.closest('[draggable="true"]');
          if (target && target !== draggedItem) {
            onDrop?.(draggedItem, target, e);
          }
          container.querySelectorAll(`.${overClass}`).forEach(el => el.classList.remove(overClass));
        });
      });
    },
  },

  // === INITIALIZER ===
  init: (root = document) => {
    // Initialize all interactions
    root.querySelectorAll('.btn').forEach(btn => {
      btn.addEventListener('mousedown', () => MicroInteractions.button.press(btn));
      btn.addEventListener('mouseup', () => MicroInteractions.button.release(btn));
      btn.addEventListener('mouseleave', () => MicroInteractions.button.release(btn));
      btn.addEventListener('click', (e) => MicroInteractions.button.ripple(btn, e));
    });

    root.querySelectorAll('.input-group input, .input-group textarea').forEach(input => {
      MicroInteractions.input.floatLabel(input);
    });

    root.querySelectorAll('[data-char-counter]').forEach(input => {
      MicroInteractions.input.charCounter(input, input.dataset.charCounter);
    });

    root.querySelectorAll('[data-auto-resize]').forEach(textarea => {
      MicroInteractions.input.autoResize(textarea);
    });

    root.querySelectorAll('.card[data-tilt]').forEach(card => {
      MicroInteractions.card.tilt(card, card.dataset.tilt);
    });

    root.querySelectorAll('[data-magnetic]').forEach(card => {
      MicroInteractions.card.magnetic(card, card.dataset.magnetic);
    });

    root.querySelectorAll('.switch').forEach(toggle => {
      MicroInteractions.toggle.animate(toggle);
      MicroInteractions.toggle.haptic(toggle);
    });

    root.querySelectorAll('[data-tooltip]').forEach(el => new MicroInteractions.Tooltip(el));
    root.querySelectorAll('[data-dropdown]').forEach(el => new MicroInteractions.Dropdown(el));
    root.querySelectorAll('[data-modal]').forEach(el => new MicroInteractions.Modal(el));
    root.querySelectorAll('.accordion-header').forEach(el => new MicroInteractions.Accordion(el));

    MicroInteractions.scroll.reveal(root.querySelectorAll('.reveal'));

    root.querySelectorAll('[data-counter]').forEach(el => {
      const target = parseInt(el.dataset.counter);
      MicroInteractions.counter.animate(el, target);
    });

    root.querySelectorAll('[draggable="true"]').forEach(el => {
      MicroInteractions.dragDrop.init(el);
    });
  },
};

// Initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => MicroInteractions.init());
} else {
  MicroInteractions.init();
}