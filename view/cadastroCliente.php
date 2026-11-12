<?php
require_once '../processamento/sessao.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$c = $id ? buscarCliente($id) : null;
if ($id && !$c) {
    avisar('danger', 'Cliente não encontrado.');
    header('Location: verCliente.php');
    exit;
}
$v = fn($campo, $padrao = '') => valorAntigo($campo, $c[$campo] ?? $padrao);

$titulo = $c ? 'Editar cliente' : 'Novo cliente';
$pagina = 'cadastros';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Cadastros · Clientes</span>
        <h1><?= $c ? 'Editar cliente' : 'Novo cliente' ?></h1>
    </div>
    <a href="verCliente.php" class="btn btn-aco"><i class="bi bi-arrow-left me-1"></i>Voltar à lista</a>
</div>

<form class="f-card f-rebites" action="../processamento/processamento.php" method="POST">
    <?= campoCSRF() ?>
    <input type="hidden" name="acao" value="salvarCliente">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="nome">Nome / Razão social *</label>
            <input class="form-control" id="nome" name="nome" required maxlength="120" value="<?= h($v('nome')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="tipo">Tipo de pessoa</label>
            <select class="form-select" id="tipo" name="tipo" data-documento="#documento">
                <option value="PF" <?= $v('tipo', 'PF') === 'PF' ? 'selected' : '' ?>>Pessoa física (CPF)</option>
                <option value="PJ" <?= $v('tipo') === 'PJ' ? 'selected' : '' ?>>Pessoa jurídica (CNPJ)</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="documento">CPF / CNPJ</label>
            <input class="form-control" id="documento" name="documento" maxlength="20" data-mascara="documento" value="<?= h($v('documento')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="email">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" maxlength="120" value="<?= h($v('email')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="telefone">Telefone / WhatsApp</label>
            <input class="form-control" id="telefone" name="telefone" maxlength="20" data-mascara="telefone" placeholder="(18) 99999-9999" value="<?= h($v('telefone')) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="cidade">Cidade</label>
            <input class="form-control" id="cidade" name="cidade" maxlength="80" value="<?= h($v('cidade', 'Presidente Prudente')) ?>">
        </div>
        <div class="col-12">
            <label class="form-label" for="observacoes">Observações</label>
            <textarea class="form-control" id="observacoes" name="observacoes" rows="3" placeholder="Preferências de cor, acabamento, prazos..."><?= h($v('observacoes')) ?></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="verCliente.php" class="btn btn-aco">Cancelar</a>
        <button class="btn btn-forja" type="submit"><i class="bi bi-check2 me-1"></i>Salvar cliente</button>
    </div>
</form>
<?php limparFormulario(); require 'includes/rodape.php'; ?>
