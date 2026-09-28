import { createServer } from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import { extname, join, normalize } from 'node:path';
const root=process.cwd();
const types={'.html':'text/html; charset=utf-8','.htm':'text/html; charset=utf-8','.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.png':'image/png','.jpg':'image/jpeg','.jpeg':'image/jpeg','.webp':'image/webp','.svg':'image/svg+xml','.sql':'text/plain; charset=utf-8'};

const organization={
  '@context':'https://schema.org',
  '@graph':[
    {'@type':'Organization','@id':'https://spectechnology.pl/#organization','name':'SPECTECHNOLOGY S.A.','url':'https://spectechnology.pl/','email':'office@spectechnology.pl','address':{'@type':'PostalAddress','streetAddress':'ul. Fort Wola 22','postalCode':'01-258','addressLocality':'Warszawa','addressCountry':'PL'}},
    {'@type':'WebSite','@id':'https://spectechnology.pl/#website','url':'https://spectechnology.pl/','name':'SPECTECHNOLOGY','publisher':{'@id':'https://spectechnology.pl/#organization'},'inLanguage':['en','pl']}
  ]
};

function renderIndex(source,language){
  const pl=language==='pl';
  const title=pl?'SPECTECHNOLOGY — Projekt w Irlandii. Produkcja w Polsce.':'SPECTECHNOLOGY — Designed in Ireland. Made in Poland.';
  const description=pl?'Łączymy irlandzkich klientów i architektów z polskimi producentami indywidualnych zestawów domów szkieletowych.':'SPECTECHNOLOGY connects Irish projects and architects with Polish manufacturers of bespoke timber-frame house kits.';
  const canonical=`https://spectechnology.pl/${language}/`;
  const values=new Map([
    ['$lang',language],
    ["htmlspecialchars($title,ENT_QUOTES,'UTF-8')",title],
    ["htmlspecialchars($description,ENT_QUOTES,'UTF-8')",description],
    ['$canonical',canonical],
    ["$lang==='pl'?'pl_PL':'en_IE'",pl?'pl_PL':'en_IE'],
    ['json_encode($organization,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)',JSON.stringify(organization)],
    ["$lang==='pl'?'Zapytaj nas':'Ask us'",pl?'Zapytaj nas':'Ask us'],
    ["$lang==='pl'?'Asystent projektu':'Project assistant'",pl?'Asystent projektu':'Project assistant'],
    ["$lang==='pl'?'Imię':'Name'",pl?'Imię':'Name'],
    ["$lang==='pl'?'Telefon (opcjonalnie)':'Phone (optional)'",pl?'Telefon (opcjonalnie)':'Phone (optional)'],
    ["$lang==='pl'?'Zgadzam się na kontakt w sprawie tej rozmowy.':'I agree to be contacted about this conversation.'",pl?'Zgadzam się na kontakt w sprawie tej rozmowy.':'I agree to be contacted about this conversation.'],
    ["$lang==='pl'?'Wyślij do zespołu':'Send to the team'",pl?'Wyślij do zespołu':'Send to the team']
  ]);
  return source
    .replace(/^<\?php[\s\S]*?\?>\s*/,'')
    .replace(/<\?=([\s\S]*?)\?>/g,(match,expression)=>values.get(expression.trim())??'');
}

function portalPreview(path){
  const shell=content=>`<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Portal preview — SPECTECHNOLOGY</title><link rel="stylesheet" href="/assets/site-prod.css"><link rel="stylesheet" href="/assets/portal.css"></head><body class="portal"><main class="auth-card"><a href="/" class="portal-logo"><img src="/noBgColor-dark.png" alt="SPECTECHNOLOGY"></a>${content}<a href="/">← Main page / Strona główna</a></main></body></html>`;
  if(path==='/login.php')return shell(`<p class="eyebrow">CLIENT &amp; PARTNER PORTAL</p><h1>Sign in / Zaloguj się</h1><form onsubmit="event.preventDefault()"><label>Username / Login<input name="username" autocomplete="username" required></label><label>Password / Hasło<input type="password" name="password" autocomplete="current-password" required></label><button class="primary" type="submit">Sign in / Zaloguj się</button></form><p><a href="/forgot-password.php">Forgot password? / Nie pamiętasz hasła?</a></p>`);
  if(path==='/forgot-password.php')return shell(`<p class="eyebrow">ACCOUNT RECOVERY</p><h1>Forgot password / Nie pamiętam hasła</h1><p>Enter the email address assigned to your account.<br>Podaj adres e-mail przypisany do konta.</p><form onsubmit="event.preventDefault()"><label>Email<input type="email" name="email" required autocomplete="email"></label><button class="primary" type="submit">Send verification code / Wyślij kod</button></form><p><a href="/login.php">← Sign in / Zaloguj się</a></p>`);
  if(path==='/activate.php')return shell(`<p class="eyebrow">ACCOUNT ACTIVATION</p><h1>Invitation link required</h1><p>This page is activated by the one-time link sent by email.<br>Ta strona działa z jednorazowym linkiem wysłanym e-mailem.</p>`);
  if(path==='/dashboard.php'||path==='/conversations.php')return shell(`<p class="eyebrow">PROTECTED PORTAL</p><h1>Sign in required / Wymagane logowanie</h1><p>The dashboard uses the live PHP and MySQL environment and is not populated in the static local preview.</p><p><a class="primary" href="/login.php">Sign in / Zaloguj się</a></p>`);
  return null;
}

createServer(async(req,res)=>{
  try{
    const requestUrl=new URL(req.url||'/','http://127.0.0.1');
    let path=decodeURIComponent(requestUrl.pathname);
    const language=requestUrl.searchParams.get('lang')==='pl'||path.startsWith('/pl')?'pl':'en';
    if(path==='/'||path==='/en'||path==='/en/'||path==='/pl'||path==='/pl/')path='/index.php';
    if(path==='/logout.php'){
      res.statusCode=302;
      res.setHeader('Location','/login.php');
      return res.end();
    }
    const portal=portalPreview(path);
    if(portal){
      res.setHeader('Content-Type','text/html; charset=utf-8');
      return res.end(portal);
    }
    const file=normalize(join(root,path));
    if(!file.startsWith(root))throw new Error('bad path');
    await stat(file);
    res.setHeader('Content-Type',path.endsWith('.php')?'text/html; charset=utf-8':(types[extname(path)]||'application/octet-stream'));
    const content=await readFile(file);
    res.end(path==='/index.php'?renderIndex(content.toString('utf8'),language):content);
  }catch{
    res.statusCode=404;
    res.setHeader('Content-Type','text/html; charset=utf-8');
    res.end(await readFile(join(root,'404.html')));
  }
}).listen(4173,'127.0.0.1',()=>console.log('Local: http://127.0.0.1:4173/'));
