(function () {
  function initArticleEditor() {
    const shell = document.querySelector('[data-article-editor]');
    if (!shell || shell.dataset.editorReady === '1') return;

    const canvas = shell.querySelector('#articleBlockCanvas');
    const input = shell.querySelector('#articleContentInput');
    const addButton = shell.querySelector('#articleAddButton');
    const menu = shell.querySelector('#articleAddMenu');
    const addWrap = shell.querySelector('.article-add-wrap');
    const form = shell.closest('form');
    const modal = document.querySelector('#articleRelatedModal');
    const search = document.querySelector('#articleRelatedSearch');
    const list = document.querySelector('#articleRelatedList');
    const empty = document.querySelector('#articleRelatedEmpty');
    const availableArticles = Array.isArray(window.GlowSkinAvailableArticles) ? window.GlowSkinAvailableArticles : [];

    if (!canvas || !input || !addButton || !menu || !addWrap) return;
    shell.dataset.editorReady = '1';

    let selectedBlock = null;

    function escapeHtml(value) {
      const node = document.createElement('div');
      node.textContent = String(value ?? '');
      return node.innerHTML;
    }

    const initial = String(window.GlowSkinArticleEditorInitialContent || '').trim();
    if (initial) {
      const looksLikeHtml = /<\/?[a-z][\s\S]*>/i.test(initial);
      canvas.innerHTML = looksLikeHtml
        ? initial
        : initial.split(/\n\s*\n/).filter(Boolean).map((text) => `<p>${escapeHtml(text).replace(/\n/g, '<br>')}</p>`).join('');
    } else if (!canvas.innerHTML.trim()) {
      canvas.innerHTML = '<p><br></p>';
    }

    function closestBlock(node) {
      if (!node) return null;
      const element = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
      return element ? element.closest('p, h2, .article-read-also') : null;
    }

    function rememberSelection() {
      const selection = window.getSelection();
      selectedBlock = closestBlock(selection && selection.anchorNode) || selectedBlock;
    }

    function focusAtEnd(element) {
      element.focus();
      const range = document.createRange();
      range.selectNodeContents(element);
      range.collapse(false);
      const selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
      selectedBlock = element;
    }

    function setMenu(open) {
      menu.hidden = !open;
      addButton.setAttribute('aria-expanded', String(open));
    }

    function appendBlock(block) {
      canvas.appendChild(block);
      setMenu(false);
      focusAtEnd(block);
    }

    function makeRelatedBlock(article) {
      const block = document.createElement('div');
      block.className = 'article-read-also';
      block.setAttribute('contenteditable', 'false');

      const label = document.createElement('strong');
      label.textContent = 'Baca Juga';
      const link = document.createElement('a');
      link.href = article.url;
      link.textContent = article.title;
      link.setAttribute('contenteditable', 'false');
      block.append(label, link);
      return block;
    }

    function renderArticlePicker(keyword) {
      if (!list || !empty) return;
      const term = String(keyword || '').trim().toLocaleLowerCase('id-ID');
      const filtered = availableArticles.filter((article) => {
        return !term || `${article.title || ''} ${article.category || ''}`.toLocaleLowerCase('id-ID').includes(term);
      });

      list.innerHTML = '';
      empty.hidden = filtered.length > 0;

      filtered.forEach((article) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'article-related-item';
        button.innerHTML = `
          <img class="article-related-thumb" src="${escapeHtml(article.image || '')}" alt="">
          <span class="article-related-copy">
            <strong>${escapeHtml(article.title || 'Artikel tanpa judul')}</strong>
            <small>${escapeHtml(article.category || 'Tanpa kategori')}</small>
          </span>
          <span class="article-related-pick">Pilih Artikel</span>`;
        button.addEventListener('click', function () {
          appendBlock(makeRelatedBlock(article));
          closeRelatedModal();
        });
        list.appendChild(button);
      });
    }

    function openRelatedModal() {
      if (!modal) return;
      renderArticlePicker('');
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      window.setTimeout(() => search && search.focus(), 40);
    }

    function closeRelatedModal() {
      if (!modal) return;
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (search) search.value = '';
    }

    ['keyup', 'mouseup', 'focus'].forEach((eventName) => canvas.addEventListener(eventName, rememberSelection));

    shell.querySelectorAll('[data-command]').forEach((button) => {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        canvas.focus();
        document.execCommand(button.dataset.command, false, null);
        rememberSelection();
      });
    });

    shell.querySelectorAll('[data-align]').forEach((button) => {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        rememberSelection();
        const block = selectedBlock || canvas.querySelector('p, h2, .article-read-also');
        if (block) block.style.textAlign = button.dataset.align;
      });
    });

    addButton.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      setMenu(menu.hidden);
    });

    menu.addEventListener('click', function (event) {
      const button = event.target.closest('[data-insert]');
      if (!button) return;
      event.preventDefault();
      event.stopPropagation();

      const type = button.dataset.insert;
      if (type === 'readmore') {
        setMenu(false);
        openRelatedModal();
        return;
      }

      let block;
      if (type === 'heading') {
        block = document.createElement('h2');
        block.textContent = 'Tulis subjudul di sini';
      } else {
        block = document.createElement('p');
        block.textContent = 'Tulis paragraf baru di sini';
      }
      appendBlock(block);
    });

    document.addEventListener('click', function (event) {
      if (!addWrap.contains(event.target)) setMenu(false);
    });

    if (search) search.addEventListener('input', () => renderArticlePicker(search.value));
    document.querySelectorAll('[data-related-close]').forEach((button) => button.addEventListener('click', closeRelatedModal));
    if (modal) modal.addEventListener('click', (event) => { if (event.target === modal) closeRelatedModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal && !modal.hidden) closeRelatedModal(); });

    form && form.addEventListener('submit', function (event) {
      input.value = canvas.innerHTML.trim();
      if (!canvas.textContent.trim()) {
        event.preventDefault();
        canvas.focus();
        window.alert('Isi artikel wajib diisi.');
      }
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initArticleEditor);
  else initArticleEditor();
})();

(function(){
  function initLivePreview(){
    const form=document.querySelector('form.admin-form-wide');
    const preview=document.getElementById('articleLivePreview');
    if(!form||!preview||preview.dataset.ready==='1') return;
    preview.dataset.ready='1';
    const q=(s)=>document.querySelector(s);
    const title=form.querySelector('[name="title"]'), category=form.querySelector('[name="category"]'), caption=form.querySelector('[name="hero_caption"]'), excerpt=form.querySelector('[name="excerpt"]'), imageUrl=form.querySelector('[name="image_url"]'), imageFile=form.querySelector('[name="image"]');
    const canvas=q('#articleBlockCanvas'), toggle=q('#articlePreviewToggle'), body=q('#articlePreviewBody');
    const text=(el,fallback)=>String(el?.value||'').trim()||fallback;
    function update(){
      const t=text(title,'Judul Artikel Akan Tampil di Sini');
      q('#articlePreviewTitle').textContent=t;q('#articlePreviewBreadcrumb').textContent=t;q('#articlePreviewTocTitle').textContent=t;
      q('#articlePreviewCategory').textContent=text(category,'BEAUTY HIGHLIGHTS').toUpperCase();
      q('#articlePreviewCaption').textContent=text(caption,'Caption hero gambar akan tampil di sini.');
      q('#articlePreviewExcerpt').textContent=text(excerpt,'Deskripsi singkat artikel akan tampil di bagian pembuka.');
      const html=canvas?.innerHTML.trim();q('#articlePreviewContent').innerHTML=html||'<p>Isi artikel akan tampil di sini saat admin mulai menulis.</p>';
    }
    [title,category,caption,excerpt,imageUrl].forEach(el=>el&&el.addEventListener('input',update));
    if(canvas){['input','keyup','mouseup'].forEach(ev=>canvas.addEventListener(ev,update));new MutationObserver(update).observe(canvas,{subtree:true,childList:true,characterData:true,attributes:true});}
    if(imageUrl) imageUrl.addEventListener('input',()=>{const v=imageUrl.value.trim();if(v)q('#articlePreviewImage').src=v;});
    if(imageFile) imageFile.addEventListener('change',()=>{const file=imageFile.files&&imageFile.files[0];if(file){const r=new FileReader();r.onload=e=>q('#articlePreviewImage').src=e.target.result;r.readAsDataURL(file);}});
    toggle&&toggle.addEventListener('click',()=>{preview.classList.toggle('is-collapsed');const open=!preview.classList.contains('is-collapsed');toggle.textContent=open?'Tutup Preview':'Tampilkan Preview';toggle.setAttribute('aria-expanded',String(open));});
    update();
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initLivePreview);else initLivePreview();
})();
