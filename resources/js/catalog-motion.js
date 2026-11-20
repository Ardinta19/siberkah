import { gsap } from 'gsap';

const hero = document.querySelector('.kc-hero-reveal');
if (hero) {
  gsap.matchMedia().add('(prefers-reduced-motion: no-preference)', () => {
    // The baseline is fully visible: no loader, forced scroll, or animation loop.
    gsap.timeline({defaults:{ease:'power2.out'}})
      .from(hero.querySelector('.kc-still img'), {scale:1.06, duration:1.2, clearProps:'transform'})
      .from(hero.querySelectorAll('.kc-intro > :is(h1,p)'), {
        y:8, opacity:.55, duration:.43, stagger:.075, clearProps:'transform,opacity'
      }, 0);
  });
}
