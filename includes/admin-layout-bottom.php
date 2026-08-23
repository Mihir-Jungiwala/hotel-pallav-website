  </main>
  </div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
  function initRichText(){
    document.querySelectorAll('.rte').forEach(function(el){
      if (el.dataset.rteInit) return;
      el.dataset.rteInit = '1';
      var targetName = el.getAttribute('data-target');
      var hidden = document.querySelector('input[type=hidden][name="' + targetName + '"]');
      if (!hidden) return;
      var editorDiv = document.createElement('div');
      el.appendChild(editorDiv);
      var quill = new Quill(editorDiv, {
        theme: 'snow',
        modules: { toolbar: [['bold','italic'],[{list:'bullet'}],['link'],['clean']] }
      });
      quill.root.innerHTML = hidden.value || '';
      quill.on('text-change', function(){
        hidden.value = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML;
      });
    });
  }
  document.addEventListener('DOMContentLoaded', initRichText);
</script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
