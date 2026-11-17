<?php
require_once '../processamento/sessao.php';
exigirAdmin();

$id = (int) ($_GET['id'] ?? 0);
$us = $id ? buscarUsuario($id) : null;
if ($id && !$us) {
    avisar('danger', 'Usuário não encontrado.');
    header('Location: verUsuario.php');
    exit;
}
$v = fn($campo, $padrao = '') => valorAntigo($campo, $us[$campo] ?? $padrao);
$eu = $id === usuarioLogado()['id'];

$titulo = $us ? 'Editar usuário' : 'Novo usuário';
$pagina = 'cadastros';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Cadastros · Usuários</span>
        <h1><?= $us ? 'Editar usuário' : 'Novo usuário' ?></h1>
    </div>
    <a href="verUsuario.php" class="btn btn-aco"><i class="bi bi-arrow-left me-1"></i>Voltar à lista</a>
</div>

<form class="f-card f-rebites" action="../processamento/processamento.php" method="POST">
    <?= campoCSRF() ?>
    <input type="hidden" name="acao" value="salvarUsuario">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="nome">Nome completo *</label>
            <input class="form-control" id="nome" name="nome" required maxlength="100" value="<?= h($v('nome')) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">E-mail (login) *</label>
            <input type="email" class="form-control" id="email" name="email" required maxlength="120" value="<?= h($v('email')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="nivel">Nível de acesso</label>
            <select class="form-select" id="nivel" name="nivel" <?= $eu ? 'disabled' : '' ?>>
                <option value="operador" <?= $v('nivel', 'operador') === 'operador' ? 'selected' : '' ?>>Operador — orçamentos, cadastros e estoque</option>
                <option value="admin" <?= $v('nivel') === 'admin' ? 'selected' : '' ?>>Administrador — tudo + usuários e configurações</option>
            </select>
            <?php if ($eu): ?><input type="hidden" name="nivel" value="admin"><?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="senha"><?= $us ? 'Nova senha (deixe vazio para manter)' : 'Senha *' ?></label>
            <div class="input-group">
                <input type="password" class="form-control" id="senha" name="senha" minlength="6" <?= $us ? '' : 'required' ?> autocomplete="new-password">
                <button class="btn btn-aco" type="button" data-mostrar-senha="#senha" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" <?= (int) $v('ativo', 1) ? 'checked' : '' ?> <?= $eu ? 'disabled' : '' ?>>
                <label class="form-check-label" for="ativo">Usuário ativo (pode fazer login)</label>
                <?php if ($eu): ?><input type="hidden" name="ativo" value="1"><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="verUsuario.php" class="btn btn-aco">Cancelar</a>
        <button class="btn btn-forja" type="submit"><i class="bi bi-check2 me-1"></i>Salvar usuário</button>
    </div>
</form>
<?php limparFormulario(); require 'includes/rodape.php'; ?>
