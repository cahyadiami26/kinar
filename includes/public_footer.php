<footer class="pub-footer" id="kontak">
  <div class="cols">
    <div><h4><?= e($site['name']) ?></h4>
      <p><?= e($site['tagline']) ?><br>Menghidangkan keagungan warisan kuliner Indonesia.</p></div>
    <div><h4>Jam Operasional</h4><p><?= e($site['hours']) ?></p></div>
    <div><h4>Lokasi &amp; Navigasi</h4><p>📍 <?= e($site['address']) ?><br>📞 <?= e($site['phone']) ?></p></div>
    <div><h4>Warta &amp; Reservasi</h4><p><a href="https://wa.me/<?= e($site['wa']) ?>" style="color:#fff">💬 WhatsApp Concierge</a></p></div>
  </div>
  <div class="copy">© <?= date('Y') ?> <?= e($site['name']) ?>. Seluruh hak cipta dilindungi.</div>
</footer>
<script src="<?= BASE_URL ?>/assets/js/public.js"></script>
</body>
</html>
