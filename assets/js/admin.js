// Kinar Resto - Admin JS
// Auto-refresh ringan untuk halaman dapur & POS (antrian berjalan)
if (location.pathname.includes('kitchen.php') || location.pathname.includes('pos.php')) {
  setTimeout(() => location.reload(), 30000);
}
// Preview tag toggle visual
document.querySelectorAll('.tag input[type=checkbox]').forEach(cb => {
  cb.addEventListener('change', () => {
    cb.closest('.tag').classList.toggle('t-wine', cb.checked);
    cb.closest('.tag').classList.toggle('t-gray', !cb.checked);
  });
});
