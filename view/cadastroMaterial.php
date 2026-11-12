<?php
require_once '../processamento/sessao.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$m = $id ? buscarMaterial($id) : null;
if ($id && !$m) {
    avisar('danger', 'Material não encontrado.');
    header('Location: verMaterial.php');
    exit;
}
$v = fn($campo, $padrao = '') => valorAntigo($campo, $m[$campo] ?? $padrao);
$tipos = ['PLA', 'PLA Silk', 'PETG', 'ABS', 'ASA', 'TPU', 'Nylon', 'PC', 'Resina'];

$titulo = $m ? 'Editar material' : 'Novo material';
$pagina = 'cadastros';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Cadastros · Materiais</span>
        <h1><?= $m ? 'Editar material' : 'Novo material' ?></h1>
    </div>
    <a href="verMaterial.php" class="btn btn-aco"><i class="bi bi-arrow-left me-1"></i>Voltar à lista</a>
</div>

<form class="f-card f-rebites" action="../processamento/processamento.php" method="POST">
    <?= campoCSRF() ?>
    <input type="hidden" name="acao" value="salvarMaterial">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="tipo">Tipo *</label>
            <input class="form-control" id="tipo" name="tipo" list="listaTipos" required maxlength="30" value="<?= h($v('tipo', 'PLA')) ?>">
            <datalist id="listaTipos"><?php foreach ($tipos as $t): ?><option value="<?= $t ?>"><?php endforeach; ?></datalist>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="marca">Marca *</label>
            <input class="form-control" id="marca" name="marca" required maxlength="60" value="<?= h($v('marca')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="cor">Cor *</label>
            <input class="form-control" id="cor" name="cor" required maxlength="40" value="<?= h($v('cor')) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="cor_hex">Amostra</label>
            <input type="color" class="form-control form-control-color w-100" id="cor_hex" name="cor_hex" value="<?= h($v('cor_hex', '#ff6a1a')) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="diametro">Diâmetro (mm)</label>
            <select class="form-select" id="diametro" name="diametro">
                <?php foreach (['1.75', '2.85'] as $dm): ?>
                    <option value="<?= $dm ?>" <?= (string) (float) $v('diametro', 1.75) === (string) (float) $dm ? 'selected' : '' ?>><?= str_replace('.', ',', $dm) ?> mm</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="preco_kg">Preço por kg *</label>
            <div class="input-group"><span class="input-group-text">R$</span>
                <input type="number" step="0.01" min="0.01" class="form-control" id="preco_kg" name="preco_kg" required value="<?= h($v('preco_kg')) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="estoque_g">Estoque atual</label>
            <div class="input-group">
                <input type="number" step="1" min="0" class="form-control" id="estoque_g" name="estoque_g" value="<?= h($v('estoque_g', 1000)) ?>" <?= $m ? 'disabled' : '' ?>>
                <span class="input-group-text">g</span>
            </div>
            <?php if ($m): ?><div class="form-text">O estoque não é alterado na edição do material.</div><?php endif; ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="estoque_minimo_g">Estoque mínimo (alerta)</label>
            <div class="input-group">
                <input type="number" step="1" min="0" class="form-control" id="estoque_minimo_g" name="estoque_minimo_g" value="<?= h($v('estoque_minimo_g', 300)) ?>">
                <span class="input-group-text">g</span>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="verMaterial.php" class="btn btn-aco">Cancelar</a>
        <button class="btn btn-forja" type="submit"><i class="bi bi-check2 me-1"></i>Salvar material</button>
    </div>
</form>
<?php limparFormulario(); require 'includes/rodape.php'; ?>
