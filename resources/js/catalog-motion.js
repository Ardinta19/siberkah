import { gsap } from 'gsap';


const hero = document.querySelector('.kc-hero-reveal');
if (hero) {
  gsap.matchMedia().add('(prefers-reduced-motion: no-preference)', () => {
    gsap.timeline({defaults:{ease:'power2.out'}})
      .from(hero.querySelector('.kc-still img'), {scale:1.13, duration:1.6})
      .from(hero.querySelectorAll('.kc-intro > :is(h1,p)'), {
        y:16, opacity:.35, duration:.65, stagger:.10
      }, 0);
  });
}
