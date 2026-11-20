import { gsap } from 'gsap';
import { bloom } from './floral-feedback.js';

const hero = document.querySelector('.kc-hero-reveal');
if (hero) {
  gsap.matchMedia().add('(prefers-reduced-motion: no-preference)', () => {
    const replay = hero.querySelector('[data-replay]');
    const timeline = gsap.timeline({defaults:{ease:'power2.out'}})
      .from(hero.querySelector('.kc-still img'), {scale:1.13, duration:1.6})
      .from(hero.querySelectorAll('.kc-intro > :is(h1,p)'), {
        y:16, opacity:.35, duration:.65, stagger:.10
      }, 0);
    function play() {
      timeline.restart();
      bloom(replay);
    }
    replay.hidden = false;
    replay.addEventListener('click', play);
    return () => {
      replay.hidden = true;
      replay.removeEventListener('click', play);
    };
  });
}
