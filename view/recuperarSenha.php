<?php
require_once '../processamento/sessao.php';
$titulo = 'Recuperar senha';
$semMenu = true;
$email = $_SESSION['email_simulado'] ?? null;
require 'includes/topo.php';
?>
    <div class="f-login-marca">
        <img src="../img/logo.svg" alt="Logo FORG3D">
        <h1 class="h2"><span class="f-texto-aco">FORG</span><span class="f-texto-brasa">3D</span></h1>
    </div>

    <div class="f-card f-rebites">
        <h2 class="h5 mb-1 text-center">Recuperar senha</h2>
        <p class="f-muted small text-center mb-4">Informe o e-mail cadastrado. Vamos gerar um link válido por <?= TOKEN_VALIDADE_MIN ?> minutos para criar uma nova senha.</p>

        <form action="../processamento/processamento.php" method="POST">
            <?= campoCSRF() ?>
            <input type="hidden" name="acao" value="recuperarSenha">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus placeholder="voce@forg3d.com">
            </div>
            <button class="btn btn-forja w-100" type="submit"><i class="bi bi-send me-1"></i> Enviar link</button>
        </form>

        <?php if ($email): ?>
        <div class="f-email-simulado mt-4">
            <div class="f-mini mb-2"><i class="bi bi-envelope-paper me-1"></i>E-mail simulado (ambiente local)</div>
            <p class="mb-1"><strong>Para:</strong> <?= h($email['para']) ?></p>
            <p class="mb-2">Olá, <?= h(explode(' ', $email['nome'])[0]) ?>! Clique no link para criar uma nova senha:</p>
            <a class="btn btn-aco btn-sm" href="<?= h($email['link']) ?>"><i class="bi bi-key me-1"></i>Redefinir minha senha</a>
            <p class="mt-2 mb-0 small f-muted">Em produção este link seria enviado por e-mail (PHPMailer + SMTP).</p>
        </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="login.php" class="small"><i class="bi bi-arrow-left"></i> Voltar ao login</a>
        </div>
    </div>
<?php require 'includes/rodape.php'; ?>
