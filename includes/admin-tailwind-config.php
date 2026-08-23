<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { display: ['Playfair Display', 'ui-serif', 'serif'], sans: ['Inter', 'ui-sans-serif', 'sans-serif'] },
        colors: {
          /* Aligned to assets/css/site.css's --p50..--p900 / --gold / --cream tokens
             so the admin panel and public site resolve to the same brand palette.
             Keys/class names (bg-pallav-700 etc.) are unchanged — only the hex values. */
          pallav: { 50:'#F7F4FF',100:'#EFE9FE',200:'#DFD3FD',300:'#C6B0FB',400:'#A886F7',500:'#8B5CF6',600:'#7C3AED',700:'#6D28D9',800:'#5B21B6',900:'#4A1A8F' },
          gold: { 50:'#FDF8EC',100:'#FBF0D3',200:'#F6E3A8',300:'#F6D67C',400:'#F0C465',500:'#C9A227',600:'#B38B1E',700:'#8F6D17',800:'#6B5210' },
          cream: '#FBF9FF'
        }
      }
    }
  }
</script>
<style>[x-cloak]{display:none!important}</style>
