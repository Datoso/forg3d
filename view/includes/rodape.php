</main>

<?php if (!($semMenu ?? false)): ?>
<footer class="f-rodape">
    <div class="container-xl d-flex flex-wrap justify-content-between gap-2">
        <span><strong class="f-texto-aco">FORG</strong><strong class="f-texto-brasa">3D</strong> · Sistema de Gerenciamento de Impressões 3D</span>
        <span>Fatec Presidente Prudente · ADS · <?= date('Y') ?></span>
    </div>
</footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/forg3d.js"></script>
</body>
</html>
