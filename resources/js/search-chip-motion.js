import { gsap, Flip } from './flip-runtime.js';

// Read the old layout before React commits; animate only surviving/new chips after it.
export function before(nodes) {
  Flip.killFlipsOf(nodes,true);
  return nodes.length ? Flip.getState(nodes) : null;
}
export function after(previous,nodes,added) {
  const ctx=gsap.context(()=>{
    if(previous && nodes.length)Flip.from(previous,{targets:nodes,duration:.28,ease:'power2.out',prune:true});
    if(added.length)gsap.from(added,{opacity:.45,y:4,duration:.2,ease:'power2.out',clearProps:'opacity,transform'});
  });
  return ()=>ctx.revert();
}
