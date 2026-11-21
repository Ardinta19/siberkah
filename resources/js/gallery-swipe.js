// Commit on release, never during a scroll, pinch or cancelled touch.
export function enableGallerySwipe(surface, step) {
  let start=null;
  const cancel=()=>{start=null;};
  surface.addEventListener('touchstart',event=>{
    if(event.touches.length!==1 || (window.visualViewport?.scale ?? 1)>1.01){cancel();return;}
    const point=event.touches[0];start={id:point.identifier,x:point.clientX,y:point.clientY};
  },{passive:true});
  surface.addEventListener('touchmove',event=>{
    if(!start)return;
    const p=event.touches[0];
    if(event.touches.length!==1 || Math.abs(p.clientY-start.y)>Math.max(14,Math.abs(p.clientX-start.x)))cancel();
  },{passive:true});
  surface.addEventListener('touchcancel',cancel,{passive:true});
  surface.addEventListener('touchend',event=>{
    const origin=start;cancel();
    if(!origin || event.touches.length || !event.cancelable)return;
    const p=Array.from(event.changedTouches).find(t=>t.identifier===origin.id);
    if(!p)return;
    const dx=p.clientX-origin.x,dy=p.clientY-origin.y;
    if(Math.abs(dx)<44 || Math.abs(dx)<1.5*Math.abs(dy))return;
    event.preventDefault(); // The completed swipe must not also activate the photo link.
    step(dx<0?1:-1);
  },{passive:false});
}
