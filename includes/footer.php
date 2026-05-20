        <footer class="text-muted small mt-5">
            Radar Imoveis build: <strong>2026-05-19-imoveis</strong>
        </footer>
    </main>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="appToast" class="toast text-bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-body"></div>
  </div>
</div>
<script>window.IMOVEIS_BASE_URL = <?= json_encode(rtrim(BASE_URL, '/') . '/') ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>?v=20260519-imoveis"></script>
</body>
</html>
