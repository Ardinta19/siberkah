import { gsap } from 'gsap';
import { DrawSVGPlugin } from 'gsap/DrawSVGPlugin';

gsap.registerPlugin(DrawSVGPlugin);

// Trace a decorative edge, never redraw the brand artwork or resize its hit area.
export function traceSeal(pill){
  return gsap.fromTo(pill.querySelector('.bk-seal-trace path'),{drawSVG:'0%'},
    {drawSVG:'100%',duration:.48,ease:'power2.out',delay:.13});
}
