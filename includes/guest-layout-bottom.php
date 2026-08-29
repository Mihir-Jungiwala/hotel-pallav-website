      </div>
      <p class="text-center text-pallav-300 text-xs mt-6">&copy; <?= date('Y') ?> Hotel Pallav. Admin access only.</p>
    </div>
  </div>
<!-- ============ PASSWORD SHOW/HIDE TOGGLE (any input wrapped in .pw-field) ============ -->
<script>
document.addEventListener('click', function(e){
  var btn = e.target.closest('.pw-toggle');
  if (!btn) return;
  var input = btn.parentElement.querySelector('input');
  if (!input) return;
  var show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.querySelector('.pw-icon-show').classList.toggle('hidden', show);
  btn.querySelector('.pw-icon-hide').classList.toggle('hidden', !show);
  btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
</script>
</body>
</html>
