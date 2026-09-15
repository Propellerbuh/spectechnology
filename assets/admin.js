(()=>{
  const main=document.querySelector('.portal-main');
  if(!main)return;
  const buttons=[...document.querySelectorAll('[data-admin-view]')];
  const setView=view=>{
    const next=view==='table'?'table':'cards';
    main.dataset.adminView=next;
    buttons.forEach(button=>{
      const active=button.dataset.adminView===next;
      button.classList.toggle('active',active);
      button.setAttribute('aria-pressed',String(active));
    });
    try{localStorage.setItem('spectech-admin-view',next)}catch(error){}
  };
  buttons.forEach(button=>button.addEventListener('click',()=>setView(button.dataset.adminView)));
  document.querySelectorAll('.table-expand').forEach(button=>button.addEventListener('click',()=>{
    const article=button.closest('.application');
    const open=article.classList.toggle('expanded');
    button.setAttribute('aria-expanded',String(open));
    button.textContent=open?'Close / Zamknij':'Open / Otwórz';
  }));
  let saved='cards';
  try{saved=localStorage.getItem('spectech-admin-view')==='table'?'table':'cards'}catch(error){}
  setView(saved);
})();