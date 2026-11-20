// Keep semantics in sync with intent, including a second click mid-transition.
// Closing panels become inert immediately; only the visual hide waits.
export function disclosure(panel, fold = false) {
  let animation;
  return (open, motion = true) => {
    const wasHidden = panel.hidden;
    const height = panel.getBoundingClientRect().height;
    const opacity = wasHidden ? 0 : Number(getComputedStyle(panel).opacity);
    animation?.cancel();
    animation = null;
    panel.inert = !open;
    if (!motion || !panel.animate || matchMedia('(prefers-reduced-motion: reduce)').matches || (!open && wasHidden)) {
      panel.hidden = !open;
      return;
    }
    panel.hidden = false;
    const endHeight = panel.getBoundingClientRect().height;
    const frames = fold
      ? [{height:`${wasHidden ? 0 : height}px`, opacity,overflow:'clip'}, {height:`${open ? endHeight : 0}px`,opacity:Number(open),overflow:'clip'}]
      : [{opacity,transform:`translateY(${wasHidden ? -8 : 0}px)`},{opacity:Number(open),transform:`translateY(${open ? 0 : -8}px)`}];
    const current = panel.animate(frames,{duration:open ? 210 : 155,easing:'cubic-bezier(.22,.78,.26,1)'});
    animation = current;
    current.onfinish = () => { if(animation === current){ panel.hidden = !open; animation = null; } };
  };
}
