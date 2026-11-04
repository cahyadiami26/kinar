// Kinar Resto - JS halaman publik & QR order
// konfirmasi sebelum mengosongkan keranjang
window.addEventListener('load', () => {
  document.querySelectorAll('a[href*="clear=1"]').forEach(a =>
    a.addEventListener('click', e => { if (!confirm('Kosongkan keranjang?')) e.preventDefault(); }));
});
