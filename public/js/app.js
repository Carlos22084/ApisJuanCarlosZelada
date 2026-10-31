document.querySelector('[data-menu]')?.addEventListener('click',()=>document.querySelector('#sidebar').classList.toggle('open'));
document.querySelector('[data-public-menu]')?.addEventListener('click',()=>document.querySelector('[data-public-nav]').classList.toggle('open'));
const assistant=document.querySelector('[data-assistant]');
document.querySelector('[data-assistant-toggle]')?.addEventListener('click',()=>assistant.hidden=false);
document.querySelector('[data-assistant-close]')?.addEventListener('click',()=>assistant.hidden=true);
document.querySelector('[data-assistant-form]')?.addEventListener('submit',async e=>{
  e.preventDefault(); const input=e.target.message, msg=input.value.trim(); if(!msg)return;
  const box=document.querySelector('[data-assistant-messages]'); box.insertAdjacentHTML('beforeend',`<p class="you"></p>`); box.lastElementChild.textContent=msg; input.value='';
  const waiting=document.createElement('p'); waiting.className='bot'; waiting.textContent='Escribiendo…'; box.appendChild(waiting); box.scrollTop=box.scrollHeight;
  try{const res=await fetch('/asistente',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({message:msg})}); const data=await res.json(); waiting.textContent=data.answer||'No pude responder en este momento.';}catch{waiting.textContent='No fue posible conectar con el asistente.'} box.scrollTop=box.scrollHeight;
});
document.querySelectorAll('[data-modal-open]').forEach(b=>b.addEventListener('click',()=>document.querySelector(b.dataset.modalOpen).showModal()));
document.querySelectorAll('[data-modal-close]').forEach(b=>b.addEventListener('click',()=>b.closest('dialog').close()));
document.querySelectorAll('.color-choice[data-color]').forEach(el=>el.addEventListener('click',()=>{document.querySelectorAll('.color-choice[data-color]').forEach(x=>x.classList.remove('selected'));el.classList.add('selected');document.querySelector('#primary_color').value=el.dataset.color;document.documentElement.style.setProperty('--preview-color',el.dataset.hex)}));

const aiStudio=document.querySelector('.ai-studio');
if(aiStudio){
  const tabs=[...document.querySelectorAll('[data-preview-tab]')],panels=[...document.querySelectorAll('[data-preview-panel]')];
  const showPanel=name=>{tabs.forEach(t=>t.classList.toggle('active',t.dataset.previewTab===name));panels.forEach(p=>p.hidden=p.dataset.previewPanel!==name)};
  tabs.forEach(tab=>tab.addEventListener('click',()=>{if(!tab.disabled)showPanel(tab.dataset.previewTab)}));
  const material=document.querySelector('#material_id'),color=document.querySelector('#primary_color'),photo=document.querySelector('#furniture_photo');
  const service=document.querySelector('#service_id'),quantity=document.querySelector('#furniture_quantity');
  const money=new Intl.NumberFormat('es-BO',{minimumFractionDigits:2,maximumFractionDigits:2});
  const updateQuoteEstimate=()=>{
    const serviceOption=service?.options[service.selectedIndex],materialOption=material?.options[material.selectedIndex];
    const unitPrice=Number(serviceOption?.dataset.price||0),amount=Math.max(1,Number.parseInt(quantity?.value||'1',10)||1);
    document.querySelector('#quoteService').textContent=serviceOption?.value?(serviceOption.dataset.name||serviceOption.textContent.trim()):'Por seleccionar';
    document.querySelector('#quoteMaterial').textContent=materialOption?.value?(materialOption.dataset.name||materialOption.textContent.trim()):'Por seleccionar';
    document.querySelector('#quoteUnitPrice').textContent=`Bs ${money.format(unitPrice)}`;
    document.querySelector('#quoteQuantity').textContent=String(amount);
    document.querySelector('#quoteTotal').textContent=`Bs ${money.format(unitPrice*amount)}`;
  };
  const prepareAiPhoto=file=>new Promise((resolve,reject)=>{
    const image=new Image(),url=URL.createObjectURL(file);
    image.onload=()=>{
      URL.revokeObjectURL(url);
      if(image.width<=512&&image.height<=512){resolve(file);return}
      const scale=Math.min(512/image.width,512/image.height),canvas=document.createElement('canvas');
      canvas.width=Math.max(1,Math.floor(image.width*scale));canvas.height=Math.max(1,Math.floor(image.height*scale));
      const context=canvas.getContext('2d');context.fillStyle='#fff';context.fillRect(0,0,canvas.width,canvas.height);context.drawImage(image,0,0,canvas.width,canvas.height);
      canvas.toBlob(blob=>blob?resolve(new File([blob],'furniture.jpg',{type:'image/jpeg'})):reject(new Error('No se pudo preparar la fotografía.')),'image/jpeg',.88);
    };
    image.onerror=()=>{URL.revokeObjectURL(url);reject(new Error('La fotografía seleccionada no es válida.'))};image.src=url;
  });
  const applySelectedMaterial=()=>{
      const option=material.options[material.selectedIndex],preview=document.querySelector('#materialPreview'),previewPhoto=document.querySelector('#materialPreviewPhoto');
      const colorButtons=[...document.querySelectorAll('.color-choice[data-material-id]')];
      if(!option?.value){preview.hidden=true;colorButtons.forEach(button=>button.hidden=true);document.querySelector('#materialHelp').textContent='Selecciona una opción para visualizar el material y aplicarlo al modelo.';return}
      const selectedColor=option.dataset.color||'',selectedHex=option.dataset.hex||'#9a6644',textureType=option.dataset.texture||'leather';
      color.value=selectedColor;
      document.documentElement.style.setProperty('--preview-color',selectedHex);
      colorButtons.forEach(button=>{button.hidden=button.dataset.materialName!==option.dataset.name;button.classList.toggle('selected',button.dataset.materialId===option.value)});
      let texture='repeating-linear-gradient(90deg,rgba(255,255,255,.08) 0 1px,transparent 1px 4px)';
      if(textureType==='velvet')texture='linear-gradient(115deg,rgba(255,255,255,.22),transparent 38%,rgba(0,0,0,.12) 72%,rgba(255,255,255,.08))';
      if(textureType==='linen')texture='repeating-linear-gradient(45deg,rgba(255,255,255,.11) 0 1px,transparent 1px 4px),repeating-linear-gradient(-45deg,rgba(0,0,0,.05) 0 1px,transparent 1px 5px)';
      if(textureType==='leather')texture='radial-gradient(circle at 25% 35%,rgba(255,255,255,.18) 0 1px,transparent 2px),linear-gradient(120deg,rgba(255,255,255,.12),transparent 40%,rgba(0,0,0,.10))';
      if(textureType==='boucle')texture='radial-gradient(circle,rgba(255,255,255,.22) 0 2px,rgba(0,0,0,.10) 3px,transparent 4px) 0 0/9px 9px';
      if(textureType==='microfiber')texture='linear-gradient(120deg,rgba(255,255,255,.20),transparent 42%,rgba(0,0,0,.08))';
      if(textureType==='jacquard')texture='repeating-linear-gradient(45deg,rgba(255,255,255,.18) 0 3px,rgba(0,0,0,.08) 3px 6px)';
      if(textureType==='polyester')texture='repeating-linear-gradient(90deg,rgba(255,255,255,.12) 0 1px,transparent 1px 3px),repeating-linear-gradient(0deg,rgba(0,0,0,.05) 0 1px,transparent 1px 3px)';
      document.documentElement.style.setProperty('--fabric-pattern',texture);
      previewPhoto.style.setProperty('--sample-color',selectedHex);
      preview.className=`material-preview ${textureType}`;
      document.querySelector('#materialPreviewName').textContent=option.dataset.name;
      document.querySelector('#materialPreviewDetails').textContent=`${option.dataset.category} · ${selectedColor}`;
      document.querySelector('#materialHelp').textContent='Material seleccionado para la propuesta de tapizado.';
      preview.hidden=false;
  };
  material.addEventListener('change',()=>{applySelectedMaterial();updateQuoteEstimate()});
  document.querySelectorAll('.color-choice[data-material-id]').forEach(button=>button.addEventListener('click',()=>{material.value=button.dataset.materialId;material.dispatchEvent(new Event('change'))}));
  service?.addEventListener('change',updateQuoteEstimate);
  quantity?.addEventListener('input',updateQuoteEstimate);
  applySelectedMaterial();
  updateQuoteEstimate();
  let uploadedPhotoUrl;
  photo?.addEventListener('change',()=>{
    const file=photo.files[0],preview=document.querySelector('#originalPreview'),empty=document.querySelector('#emptyPhoto');
    if(!file)return;
    if(uploadedPhotoUrl)URL.revokeObjectURL(uploadedPhotoUrl);
    uploadedPhotoUrl=URL.createObjectURL(file);
    preview.src=uploadedPhotoUrl;preview.hidden=false;empty.hidden=true;
    document.querySelector('.upload-zone strong').textContent=file.name;
    applySelectedMaterial();showPanel('photo');
  });
  document.querySelector('#generateAi')?.addEventListener('click',async()=>{
    const button=document.querySelector('#generateAi'),status=document.querySelector('#aiStatus'),processing=document.querySelector('#aiProcessing'),result=document.querySelector('#aiResult');
    const file=photo.files[0],furniture=document.querySelector('#furniture_type').value.trim();
    if(!file||!material.value||!color.value||!furniture){status.textContent='Completa tipo de mueble, material, color y fotografía.';status.className='ai-status error';return}
    button.disabled=true;button.innerHTML='<span class="mini-spinner"></span> Generando propuesta…';status.textContent='Analizando la forma, iluminación y superficie del mueble…';status.className='ai-status working';result.removeAttribute('src');processing.hidden=false;tabs.find(t=>t.dataset.previewTab==='result').disabled=false;showPanel('result');
    try{
      const generationStartedAt=Date.now(),aiPhoto=await prepareAiPhoto(file),data=new FormData();
      data.append('furniture_photo',aiPhoto);data.append('material_id',material.value);data.append('color',color.value);data.append('style',document.querySelector('#design_style').value);data.append('furniture_type',furniture);data.append('design_notes',document.querySelector('#design_notes').value);
      const response=await fetch(aiStudio.dataset.aiEndpoint,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},body:data});
      const responseText=await response.text();let payload;
      try{payload=JSON.parse(responseText)}catch{
        const recovery=await fetch(aiStudio.dataset.aiLatestEndpoint,{headers:{'Accept':'application/json'}});
        const recovered=await recovery.json().catch(()=>null);
        const isRecent=recovered?.created_at&&Date.parse(recovered.created_at)>=generationStartedAt-15000;
        if(!recovery.ok||!isRecent)throw new Error(response.redirected?'Tu sesión terminó. Inicia sesión nuevamente.':`El servidor no pudo completar la respuesta (HTTP ${response.status}). Inténtalo nuevamente.`);
        payload=recovered;
        status.textContent='✓ Diseño generado y recuperado correctamente.';
      }
      if(!response.ok)throw new Error(payload.message||Object.values(payload.errors||{})[0]?.[0]||'No se pudo generar la imagen.');
      await new Promise((resolve,reject)=>{result.onload=resolve;result.onerror=()=>reject(new Error('La propuesta fue generada, pero el navegador no pudo mostrar el archivo.'));result.src=payload.image_url+'?v='+Date.now()});
      window.dispatchEvent(new CustomEvent('ai-design-ready',{detail:{image:result}}));
      processing.hidden=true;document.querySelector('#ai_design_id').value=payload.design_id;status.textContent=status.textContent.includes('recuperado')?status.textContent:'✓ Diseño generado y aplicado a la vista 3D.';status.className='ai-status success';
    }
    catch(error){processing.hidden=true;showPanel('photo');status.textContent=error.message;status.className='ai-status error'}finally{button.disabled=false;button.innerHTML='✦ Generar otra propuesta con IA'}
  });
}
