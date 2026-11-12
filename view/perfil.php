<?php
require_once '../processamento/sessao.php';
exigirLogin();

$u = usuarioLogado();
$titulo = 'Meu perfil';
$pagina = '';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Conta</span>
        <h1>Meu perfil</h1>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="f-card h-100">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="f-avatar" style="width:56px;height:56px;font-size:1.4rem"><?= h(mb_strtoupper(mb_substr($u['nome'], 0, 1))) ?></span>
                <div>
                    <h2 class="h5 mb-0"><?= h($u['nome']) ?></h2>
                    <div class="f-muted small"><?= h($u['email']) ?></div>
                </div>
            </div>
            <div class="f-resumo-linha"><span>Nível</span><span><?= $u['nivel'] === 'admin' ? 'Administrador' : 'Operador' ?></span></div>
            <div class="f-resumo-linha"><span>Sessão iniciada</span><span><?= h($_SESSION['login_em'] ?? '—') ?></span></div>
            <div class="f-resumo-linha"><span>ID da sessão</span><span class="small font-monospace"><?= h(substr(session_id(), 0, 10)) ?>…</span></div>
            <p class="small f-muted mt-3 mb-0">Esses dados vêm da variável <code>$_SESSION['usuario']</code>, criada no login.</p>
        </div>
    </div>
    <div class="col-lg-7">
        <form class="f-card f-rebites" action="../processamento/processamento.php" method="POST">
            <?= campoCSRF() ?>
            <input type="hidden" name="acao" value="trocarSenha">
            <div class="f-card-titulo"><i class="bi bi-key"></i>Trocar senha</div>
            <div class="mb-3">
                <label class="form-label" for="senha_atual">Senha atual</label>
                <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="nova_senha">Nova senha</label>
                    <input type="password" class="form-control" id="nova_senha" name="nova_senha" minlength="6" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="confirmar_senha">Confirmar</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" minlength="6" required data-igual-a="#nova_senha">
                </div>
            </div>
            <div class="text-end mt-4"><button class="btn btn-forja" type="submit"><i class="bi bi-shield-check me-1"></i>Atualizar senha</button></div>
        </form>
    </div>
</div>
<?php require 'includes/rodape.php'; ?>
