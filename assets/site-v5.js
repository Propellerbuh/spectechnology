(()=>{
  if(typeof t!=='undefined'){
    Object.assign(t.en.news||={}, {readMore:'Learn more →'}); Object.assign(t.pl.news||={}, {readMore:'Dowiedz się więcej →'});
    Object.assign(t.en.form,{files:'Photos or documents',fileHelp:'PDF, Word, XML, Excel/CSV or JPG, PNG, WebP. Up to 5 files, 10 MB each. Executables and scripts are not accepted.'});
    Object.assign(t.pl.form,{files:'Zdjęcia lub dokumenty',fileHelp:'PDF, Word, XML, Excel/CSV albo JPG, PNG, WebP. Maksymalnie 5 plików po 10 MB. Pliki wykonywalne i skrypty nie są akceptowane.'});
    if(t.en.insights)t.en.insights.i1='Latest available H1 2026 comparison: Irish one-off housing starts rose 36%.';
    if(t.pl.insights)t.pl.insights.i1='Najnowsze dostępne porównanie za I półrocze 2026: liczba rozpoczętych indywidualnych domów w Irlandii wzrosła o 36%.';
    setLang(localStorage.getItem('spectechnology-language')||'en');
  }
  const root=document.documentElement;
  const themeButton=document.querySelector('.theme-toggle');
  const saved=localStorage.getItem('spectechnology-theme');
  const initial=saved==='dark'?'dark':'light';
  function setTheme(theme){root.dataset.theme=theme;localStorage.setItem('spectechnology-theme',theme);if(themeButton){themeButton.textContent=theme==='dark'?'☀':'☾';themeButton.setAttribute('aria-label',theme==='dark'?'Use light theme':'Use dark theme');themeButton.title=themeButton.getAttribute('aria-label')}}
  setTheme(initial);themeButton?.addEventListener('click',()=>setTheme(root.dataset.theme==='dark'?'light':'dark'));

  const form=document.querySelector('#registerForm');
  const input=form?.querySelector('input[type="file"]');
  const status=document.querySelector('#formStatus');
  const allowed=['pdf','doc','docx','xml','xls','xlsx','csv','jpg','jpeg','png','webp'];
  form?.addEventListener('submit',e=>{const files=[...(input?.files||[])];let error='';if(files.length>5)error=document.documentElement.lang==='pl'?'Można dodać maksymalnie 5 plików.':'You can attach up to 5 files.';if(!error&&files.some(f=>f.size>10*1024*1024))error=document.documentElement.lang==='pl'?'Każdy plik może mieć maksymalnie 10 MB.':'Each file must be 10 MB or smaller.';if(!error&&files.some(f=>!allowed.includes((f.name.split('.').pop()||'').toLowerCase())))error=document.documentElement.lang==='pl'?'Niedozwolony format pliku.':'Unsupported file format.';if(error){e.preventDefault();e.stopImmediatePropagation();status.textContent=error}},true);

  function updateArticleLinks(){const lang=document.documentElement.lang==='pl'?'pl':'en';document.querySelectorAll('a[data-article]').forEach(a=>a.href=`article.html?slug=${encodeURIComponent(a.dataset.article)}&lang=${lang}`)}
  document.querySelectorAll('[data-lang]').forEach(b=>b.addEventListener('click',()=>setTimeout(updateArticleLinks,0)));updateArticleLinks();
})();


