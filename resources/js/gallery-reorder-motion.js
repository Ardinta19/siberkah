import { Flip } from './flip-runtime.js';

// Capture only geometry. DOM order, submitted values and focus belong to media-order.
export function capture(rows) {
  Flip.killFlipsOf(rows, true);
  return Flip.getState(rows);
}
export function play(state) {
  return Flip.from(state, {duration:.32, ease:'power2.out', simple:true, prune:true});
}
