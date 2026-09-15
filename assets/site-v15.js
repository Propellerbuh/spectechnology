(()=>{
  const form=document.querySelector('#registerForm');
  const input=form?.querySelector('input[type="file"][name="attachments[]"]');
  if(!input)return;
  const list=document.createElement('div'); list.className='selected-files'; list.setAttribute('aria-live','polite'); input.insertAdjacentElement('afterend',list);
  let selected=[];
  const key=file=>`${file.name}:${file.size}:${file.lastModified}`;
  const sync=()=>{const transfer=new DataTransfer();selected.forEach(file=>transfer.items.add(file));input.files=transfer.files};
  const formatSize=bytes=>bytes<1048576?`${Math.max(1,Math.round(bytes/1024))} KB`:`${(bytes/1048576).toFixed(1)} MB`;
  const render=()=>{list.replaceChildren();selected.forEach((file,index)=>{const row=document.createElement('div');row.className='selected-file';const info=document.createElement('span');const name=document.createElement('strong');name.textContent=file.name;const meta=document.createElement('small');meta.textContent=formatSize(file.size);info.append(name,meta);const remove=document.createElement('button');remove.type='button';remove.className='remove-file';remove.textContent='×';remove.setAttribute('aria-label',`Remove ${file.name}`);remove.addEventListener('click',()=>{selected.splice(index,1);sync();render();input.dispatchEvent(new Event('change',{bubbles:true}))});row.append(info,remove);list.append(row)})};
  input.addEventListener('change',()=>{const incoming=[...input.files];const known=new Set(selected.map(key));incoming.forEach(file=>{if(!known.has(key(file))&&selected.length<5){selected.push(file);known.add(key(file))}});sync();render()});
  form.addEventListener('reset',()=>{selected=[];setTimeout(()=>{sync();render()},0)});
})();