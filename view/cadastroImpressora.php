<?php
require_once '../processamento/sessao.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$p = $id ? buscarImpressora($id) : null;
if ($id && !$p) {
    avisar('danger', 'Impressora não encontrada.');
    header('Location: verImpressora.php');
    exit;
}
$v = fn($campo, $padrao = '') => valorAntigo($campo, $p[$campo] ?? $padrao);

$titulo = $p ? 'Editar impressora' : 'Nova impressora';
$pagina = 'cadastros';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Cadastros · Impressoras</span>
        <h1><?= $p ? 'Editar impressora' : 'Nova impressora' ?></h1>
    </div>
    <a href="verImpressora.php" class="btn btn-aco"><i class="bi bi-arrow-left me-1"></i>Voltar à lista</a>
</div>

<form class="f-card f-rebites" action="../processamento/processamento.php" method="POST">
    <?= campoCSRF() ?>
    <input type="hidden" name="acao" value="salvarImpressora">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="nome">Apelido na oficina *</label>
            <input class="form-control" id="nome" name="nome" required maxlength="60" placeholder="ex.: Bigorna 01" value="<?= h($v('nome')) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="marca">Marca *</label>
            <input class="form-control" id="marca" name="marca" required maxlength="60" value="<?= h($v('marca')) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="modelo">Modelo *</label>
            <input class="form-control" id="modelo" name="modelo" required maxlength="60" value="<?= h($v('modelo')) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="tecnologia">Tecnologia</label>
            <select class="form-select" id="tecnologia" name="tecnologia">
                <?php foreach (['FDM', 'SLA'] as $t): ?><option <?= $v('tecnologia', 'FDM') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="potencia_w">Potência média</label>
            <div class="input-group"><input type="number" min="1" class="form-control" id="potencia_w" name="potencia_w" required value="<?= h($v('potencia_w', 300)) ?>"><span class="input-group-text">W</span></div>
            <div class="form-text">Entra no custo de energia.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="valor_compra">Valor de compra</label>
            <div class="input-group"><span class="input-group-text">R$</span><input type="number" step="0.01" min="0" class="form-control" id="valor_compra" name="valor_compra" required value="<?= h($v('valor_compra')) ?>"></div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="vida_util_h">Vida útil estimada</label>
            <div class="input-group"><input type="number" min="1" class="form-control" id="vida_util_h" name="vida_util_h" required value="<?= h($v('vida_util_h', 8000)) ?>"><span class="input-group-text">h</span></div>
            <div class="form-text">Valor ÷ vida útil = desgaste por hora.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="horas_uso">Horas já usadas</label>
            <div class="input-group"><input type="number" step="0.1" min="0" class="form-control" id="horas_uso" name="horas_uso" value="<?= h($v('horas_uso', 0)) ?>"><span class="input-group-text">h</span></div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="status">Situação</label>
            <select class="form-select" id="status" name="status">
                <?php foreach (['disponivel', 'em_uso', 'manutencao'] as $s): ?>
                    <option value="<?= $s ?>" <?= $v('status', 'disponivel') === $s ? 'selected' : '' ?>><?= rotuloStatus($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="verImpressora.php" class="btn btn-aco">Cancelar</a>
        <button class="btn btn-forja" type="submit"><i class="bi bi-check2 me-1"></i>Salvar impressora</button>
    </div>
</form>
<?php limparFormulario(); require 'includes/rodape.php'; ?>
