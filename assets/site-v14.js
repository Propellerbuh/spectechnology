(()=>{
  if(typeof t!=='undefined'){
    t.en.legal={privacy:'Privacy',cookies:'Cookies',terms:'Terms & liability'};t.pl.legal={privacy:'Prywatność',cookies:'Cookies',terms:'Warunki i odpowiedzialność'};
    t.en.cookie={text:'We use essential storage for language and theme preferences. With your choice, we may also use functional services such as country suggestion.',policy:'Cookie policy',reject:'Essential only',accept:'Accept'};
    t.pl.cookie={text:'Używamy niezbędnej pamięci dla języka i motywu. Za zgodą możemy także korzystać z usług funkcjonalnych, takich jak podpowiedź kraju.',policy:'Polityka cookies',reject:'Tylko niezbędne',accept:'Akceptuję'};
    t.en.form.requiredNote='* Required fields';t.pl.form.requiredNote='* Pola wymagane';setLang(localStorage.getItem('spectechnology-language')||'en');
  }
  const header=document.querySelector('.site-header');
  const topButton=document.querySelector('.back-to-top');
  const onScroll=()=>{header?.classList.toggle('scrolled',scrollY>24);topButton?.classList.toggle('visible',scrollY>500)};
  addEventListener('scroll',onScroll,{passive:true});onScroll();topButton?.addEventListener('click',()=>scrollTo({top:0,behavior:'smooth'}));

  const banner=document.querySelector('.cookie-banner');
  const consent=localStorage.getItem('spectechnology-cookie-choice');
  if(!consent)banner?.removeAttribute('hidden');
  document.querySelector('.cookie-accept')?.addEventListener('click',()=>{localStorage.setItem('spectechnology-cookie-choice','accepted');banner.hidden=true;dispatchEvent(new Event('spectechnology-functional-consent'))});
  document.querySelector('.cookie-reject')?.addEventListener('click',()=>{localStorage.setItem('spectechnology-cookie-choice','essential-only');banner.hidden=true});

  const form=document.querySelector('#registerForm');const country=form?.querySelector('[name=country]');const role=form?.querySelector('[name=role]');const clearOptionalRequired=()=>{form?.querySelectorAll('[name=name],[name=company],[name=country],[name=role],[name=other_country],[name=other_role]').forEach(el=>el.required=false)};clearOptionalRequired();country?.addEventListener('change',clearOptionalRequired);role?.addEventListener('change',clearOptionalRequired);
  const suggestCountry=()=>{if(!country||country.value)return;fetch('https://api.country.is/').then(r=>r.ok?r.json():null).then(data=>{if(country.value)return;country.value=data?.country==='IE'?'Ireland':data?.country==='PL'?'Poland':'Other';country.dispatchEvent(new Event('change',{bubbles:true}))}).catch(()=>{})};if(consent==='accepted')suggestCountry();addEventListener('spectechnology-functional-consent',suggestCountry,{once:true});

  const submit=form?.querySelector('.submit'),email=form?.querySelector('[name=email]'),details=form?.querySelector('[name=details]'),agreement=form?.querySelector('[name=consent]');
  const updateSubmit=()=>{if(!submit)return;submit.disabled=!(email?.validity.valid&&details?.value.trim()&&agreement?.checked)};
  form?.addEventListener('input',updateSubmit);form?.addEventListener('change',updateSubmit);updateSubmit();

  const observer='IntersectionObserver' in window?new IntersectionObserver(items=>items.forEach(item=>{if(item.isIntersecting){item.target.classList.add('in-view');observer.unobserve(item.target)}}),{threshold:.08}):null;
  document.querySelectorAll('main>section .layout>div,main>section>div,.cards article,.event,.news-grid>a').forEach(el=>{el.classList.add('reveal');if(observer)observer.observe(el);else el.classList.add('in-view')});
})();








