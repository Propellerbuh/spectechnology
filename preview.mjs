import { createServer } from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import { extname, join, normalize } from 'node:path';
const root=process.cwd();
const types={'.html':'text/html; charset=utf-8','.htm':'text/html; charset=utf-8','.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.png':'image/png','.jpg':'image/jpeg','.jpeg':'image/jpeg','.webp':'image/webp','.svg':'image/svg+xml','.sql':'text/plain; charset=utf-8'};
createServer(async(req,res)=>{try{let path=decodeURIComponent((req.url||'/').split('?')[0]);if(path==='/')path='/index.php';const file=normalize(join(root,path));if(!file.startsWith(root))throw new Error('bad path');await stat(file);res.setHeader('Content-Type',path.endsWith('.php')?'text/html; charset=utf-8':(types[extname(path)]||'application/octet-stream'));res.end(await readFile(file));}catch{res.statusCode=404;res.setHeader('Content-Type','text/html; charset=utf-8');res.end(await readFile(join(root,'404.html')));}}).listen(4173,'127.0.0.1',()=>console.log('Local: http://127.0.0.1:4173/'));
