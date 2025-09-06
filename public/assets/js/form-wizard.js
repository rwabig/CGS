// Simple form wizard / progress tracker
export function initWizard(selector) {
  const steps=document.querySelectorAll(selector+" .step");
  let current=0;
  function show(i){ steps.forEach((s,j)=>{s.style.display=j===i?'block':'none'}); }
  show(current);
  document.querySelector(selector+" .next")?.addEventListener('click',()=>{ if(current<steps.length-1){show(++current);} });
  document.querySelector(selector+" .prev")?.addEventListener('click',()=>{ if(current>0){show(--current);} });
}
