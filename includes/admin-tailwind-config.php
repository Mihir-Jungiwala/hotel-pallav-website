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
<style>
[x-cloak]{display:none!important}
*{ -webkit-tap-highlight-color:transparent; }
html,body{ -webkit-tap-highlight-color:transparent; }

/* Custom select arrow — the native browser one has no reserved padding, so it
   sits flush against the rounded corner instead of sitting inside the field
   like every other control here. */
select{
  appearance:none; -webkit-appearance:none; -moz-appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237C3AED' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat:no-repeat;
  background-position:right 14px center;
  background-size:15px;
  padding-right:40px !important;
  cursor:pointer;
}
select::-ms-expand{ display:none; }
</style>
