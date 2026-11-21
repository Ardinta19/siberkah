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

// Reveal checkout surfaces once. No tween touches control values, prices or submit state.
const checkout=document.querySelector('.bk-checkout-page #checkout');
if(checkout){
  gsap.matchMedia().add('(prefers-reduced-motion: no-preference)',()=>{
    gsap.from(checkout.querySelectorAll('.bk-checkout-card,.bk-checkout-review'),{
      y:10,opacity:.8,duration:.46,stagger:.06,ease:'power2.out',clearProps:'transform,opacity'
    });
    // Native fragment navigation already scrolls and focuses the destination.
    // Animate only its decorative marker; repeated jumps must not accumulate tweens.
    let accent;
    function markDestination(event){
      const petal=event.target.closest('h2[tabindex]')?.querySelector('.bk-section-petal');
      if(!petal)return;
      accent?.revert();
      accent=gsap.timeline({defaults:{ease:'power2.out'}})
        .fromTo(petal,{rotation:-28,scale:.82},{rotation:12,scale:1.18,duration:.22})
        .to(petal,{rotation:0,scale:1,duration:.28,clearProps:'transform'});
    }
    checkout.addEventListener('focusin',markDestination);
    return ()=>{checkout.removeEventListener('focusin',markDestination);accent?.revert();};
  });
}
