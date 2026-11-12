<?php
require_once '../processamento/sessao.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$md = $id ? buscarModelo($id) : null;
if ($id && !$md) {
    avisar('danger', 'Modelo não encontrado.');
    header('Location: verModelo.php');
    exit;
}
$v = fn($campo, $padrao = '') => valorAntigo($campo, $md[$campo] ?? $padrao);
$materiais = listarMateriais();
$imagem = !empty($md['imagem']) ? '../img/modelos/' . $md['imagem'] : '../img/modelos/sem-imagem.svg';

$titulo = $md ? 'Editar modelo' : 'Novo modelo';
$pagina = 'cadastros';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Cadastros · Modelos</span>
        <h1><?= $md ? 'Editar modelo' : 'Novo modelo' ?></h1>
    </div>
    <a href="verModelo.php" class="btn btn-aco"><i class="bi bi-arrow-left me-1"></i>Voltar ao catálogo</a>
</div>

<form class="f-card f-rebites" action="../processamento/processamento.php" method="POST" enctype="multipart/form-data">
    <?= campoCSRF() ?>
    <input type="hidden" name="acao" value="salvarModelo">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="row g-4">
        <div class="col-md-4">
            <label class="form-label" for="imagem">Imagem da peça</label>
            <div class="f-modelo-img mb-2"><img id="previewImagem" src="<?= h($imagem) ?>" alt="Pré-visualização"></div>
            <input type="file" class="form-control" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp" data-preview="#previewImagem">
            <div class="form-text">JPG, PNG ou WEBP até 2 MB.</div>
        </div>
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="nome">Nome da peça *</label>
                    <input class="form-control" id="nome" name="nome" required maxlength="120" value="<?= h($v('nome')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="categoria">Categoria</label>
                    <input class="form-control" id="categoria" name="categoria" list="listaCategorias" maxlength="60" value="<?= h($v('categoria')) ?>">
                    <datalist id="listaCategorias">
                        <?php foreach (['Utilidades', 'Decoração', 'Peças técnicas', 'Brindes', 'Escritório', 'Proteção', 'Miniaturas', 'Protótipos'] as $cat): ?><option value="<?= $cat ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="peso_g">Peso por unidade *</label>
                    <div class="input-group"><input type="number" step="0.1" min="0.1" class="form-control" id="peso_g" name="peso_g" required value="<?= h($v('peso_g')) ?>"><span class="input-group-text">g</span></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tempo_h">Tempo de impressão *</label>
                    <div class="input-group"><input type="number" step="0.1" min="0.1" class="form-control" id="tempo_h" name="tempo_h" required value="<?= h($v('tempo_h')) ?>"><span class="input-group-text">h</span></div>
                    <div class="form-text">Veja no fatiador (Cura, Orca, Bambu Studio).</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="material_id">Material sugerido</label>
                    <select class="form-select" id="material_id" name="material_id">
                        <option value="">— nenhum —</option>
                        <?php foreach ($materiais as $mt): ?>
                            <option value="<?= $mt['id'] ?>" <?= (int) $v('material_id') === (int) $mt['id'] ? 'selected' : '' ?>><?= h($mt['tipo'] . ' ' . $mt['cor']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="descricao">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="4" placeholder="Medidas, configurações de impressão, observações..."><?= h($v('descricao')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="verModelo.php" class="btn btn-aco">Cancelar</a>
        <button class="btn btn-forja" type="submit"><i class="bi bi-check2 me-1"></i>Salvar modelo</button>
    </div>
</form>
<?php limparFormulario(); require 'includes/rodape.php'; ?>
