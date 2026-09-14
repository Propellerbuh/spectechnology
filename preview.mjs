import { createServer } from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import { extname, join, normalize } from 'node:path';
const root=process.cwd();
const types={'.css':'text/css','.js':'text/javascript','.png':'image/png','.svg':'image/svg+xml','.sql':'text/plain'};
createServer(async(req,res)=>{try{let path=decodeURIComponent((req.url||'/').split('?')[0]);if(path==='/')path='/index.php';const file=normalize(join(root,path));if(!file.startsWith(root))throw new Error('bad path');await stat(file);res.setHeader('Content-Type',path.endsWith('.php')?'text/html; charset=utf-8':(types[extname(path)]||'application/octet-stream'));res.end(await readFile(file));}catch{res.statusCode=404;res.end('Not found');}}).listen(4173,'127.0.0.1',()=>console.log('Local: http://127.0.0.1:4173/'));
