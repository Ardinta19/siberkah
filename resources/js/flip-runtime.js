// One version and registration, shared by the lazy-loaded Vite motion chunks.
import { gsap } from 'gsap';
import { Flip } from 'gsap/Flip';

gsap.registerPlugin(Flip);
export { gsap, Flip };
