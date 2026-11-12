<?php
require_once '../processamento/sessao.php';
$titulo = 'Nova senha';
$semMenu = true;
$token = (string) ($_GET['token'] ?? '');
$dono = $token !== '' ? validarTokenSenha($token) : null;
require 'includes/topo.php';
?>
    <div class="f-login-marca">
        <img src="../img/logo.svg" alt="Logo FORG3D">
        <h1 class="h2"><span class="f-texto-aco">FORG</span><span class="f-texto-brasa">3D</span></h1>
    </div>

    <div class="f-card f-rebites">
        <?php if (!$dono): ?>
            <div class="f-vazio">
                <i class="bi bi-hourglass-bottom"></i>
                <p class="mb-3">Este link é inválido, já foi usado ou expirou.</p>
                <a href="recuperarSenha.php" class="btn btn-forja">Pedir um novo link</a>
            </div>
        <?php else: ?>
            <h2 class="h5 mb-1 text-center">Criar nova senha</h2>
            <p class="f-muted small text-center mb-4">Conta: <?= h($dono['email']) ?></p>
            <form action="../processamento/processamento.php" method="POST">
                <?= campoCSRF() ?>
                <input type="hidden" name="acao" value="redefinirSenha">
                <input type="hidden" name="token" value="<?= h($token) ?>">
                <div class="mb-3">
                    <label for="nova_senha" class="form-label">Nova senha</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" minlength="6" required>
                        <button class="btn btn-aco" type="button" data-mostrar-senha="#nova_senha" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="form-text">Mínimo de 6 caracteres.</div>
                </div>
                <div class="mb-4">
                    <label for="confirmar_senha" class="form-label">Confirmar nova senha</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" minlength="6" required data-igual-a="#nova_senha">
                </div>
                <button class="btn btn-forja w-100" type="submit"><i class="bi bi-shield-check me-1"></i> Salvar nova senha</button>
            </form>
        <?php endif; ?>
    </div>
<?php require 'includes/rodape.php'; ?>
