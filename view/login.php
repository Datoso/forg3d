<?php
require_once '../processamento/sessao.php';
if (usuarioLogado()) {
    header('Location: home.php');
    exit;
}
$titulo = 'Entrar';
$semMenu = true;
require 'includes/topo.php';
?>
    <div class="f-login-marca">
        <img src="../img/logo.svg" alt="Logo FORG3D">
        <h1><span class="f-texto-aco">FORG</span><span class="f-texto-brasa">3D</span></h1>
        <p>Gerenciamento de impressões 3D — forjado camada por camada</p>
    </div>

    <div class="f-card f-rebites">
        <h2 class="h5 mb-4 text-center">Acessar a forja</h2>
        <form action="../processamento/processamento.php" method="POST" novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="acao" value="login">

            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" required autofocus
                           value="<?= h(valorAntigo('email')) ?>" placeholder="voce@forg3d.com">
                </div>
            </div>

            <div class="mb-2">
                <label for="senha" class="form-label">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="senha" name="senha" required placeholder="••••••••">
                    <button class="btn btn-aco" type="button" data-mostrar-senha="#senha" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
                </div>
            </div>

            <div class="text-end mb-4">
                <a href="recuperarSenha.php" class="small">Esqueci minha senha</a>
            </div>

            <button class="btn btn-forja w-100 py-2" type="submit"><i class="bi bi-fire me-1"></i> Entrar</button>
        </form>
    </div>

    <p class="text-center f-muted small mt-3 mb-0">
        Teste: <code>admin@forg3d.com</code> / <code>admin123</code>
    </p>
<?php limparFormulario(); require 'includes/rodape.php'; ?>
