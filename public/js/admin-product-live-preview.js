(function(){
  const root=document.querySelector('[data-product-live-preview]');
  const form=document.querySelector('form.admin-form-wide');
  if(!root||!form)return;
  const q=(name)=>form.querySelector(`[name="${name}"]`);
  const out={
    image:root.querySelector('[data-preview-image]'),badge:root.querySelector('[data-preview-badge]'),category:root.querySelector('[data-preview-category]'),name:root.querySelector('[data-preview-name]'),subtitle:root.querySelector('[data-preview-subtitle]'),price:root.querySelector('[data-preview-price]'),original:root.querySelector('[data-preview-original]'),tones:root.querySelector('[data-preview-tones]')
  };
  const money=(v)=>'Rp '+(Number(v||0)).toLocaleString('id-ID');
  function firstImage(){
    const file=form.querySelector('input[name^="product_images["]');
    if(file&&file.files&&file.files[0])return URL.createObjectURL(file.files[0]);
    const url=form.querySelector('input[name^="product_image_urls["]');
    if(url&&url.value.trim())return url.value.trim();
    return form.querySelector('input[name="image_url"]')?.value||out.image.dataset.fallback;
  }
  function renderTones(){
    const hidden=form.querySelector('[data-color-values]');
    const values=(hidden?.value||'').split(',').map(x=>x.trim()).filter(Boolean);
    out.tones.innerHTML='';
    if(!values.length){out.tones.innerHTML='<span class="admin-product-preview-empty">Belum ada tone warna.</span>';return;}
    values.slice(0,12).forEach(c=>{const i=document.createElement('span');i.className='admin-product-preview-tone';i.style.background=c;i.title=c;out.tones.appendChild(i)});
  }
  function update(){
    out.image.src=firstImage();
    out.name.textContent=q('name')?.value.trim()||'Nama Produk';
    out.subtitle.textContent=q('subtitle')?.value.trim()||'Subtitle produk akan tampil di sini.';
    out.category.textContent=q('category')?.value||'Kategori Produk';
    out.price.textContent=money(q('price')?.value);
    const original=q('original_price')?.value;
    out.original.textContent=original?money(original):'';
    out.original.style.display=original?'inline':'none';
    const badge=q('badge')?.value;
    out.badge.textContent=badge||'';out.badge.style.display=badge?'inline-flex':'none';
    renderTones();
  }
  form.addEventListener('input',update);form.addEventListener('change',update);
  new MutationObserver(update).observe(form,{subtree:true,attributes:true,attributeFilter:['value']});
  update();
})();
