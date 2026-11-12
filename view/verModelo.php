<?php
require_once '../processamento/sessao.php';
exigirLogin();

$busca = trim($_GET['busca'] ?? '');
$modelos = listarModelos($busca);

$titulo = 'Modelos';
$pagina = 'cadastros';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Cadastros</span>
        <h1>Catálogo de modelos</h1>
        <p>Catálogo de peças da oficina, com peso e tempo de impressão.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form class="f-busca d-flex gap-2" method="GET">
            <input class="form-control" name="busca" value="<?= h($busca) ?>" placeholder="Buscar nome ou categoria...">
            <button class="btn btn-aco btn-icone" type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
        <a href="cadastroModelo.php" class="btn btn-forja"><i class="bi bi-plus-lg me-1"></i>Novo modelo</a>
    </div>
</div>

<?php if (!$modelos): ?>
    <div class="f-card"><div class="f-vazio"><i class="bi bi-box"></i>Nenhum modelo encontrado.</div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($modelos as $md): ?>
    <div class="col-sm-6 col-lg-4 col-xl-3">
        <div class="f-card f-card-quente h-100 d-flex flex-column p-3">
            <div class="f-modelo-img mb-3">
                <img src="../img/modelos/<?= h($md['imagem'] ?: 'sem-imagem.svg') ?>" alt="<?= h($md['nome']) ?>" loading="lazy">
            </div>
            <span class="f-mini mb-1"><?= h($md['categoria'] ?: 'Sem categoria') ?></span>
            <h2 class="h6 mb-2"><?= h($md['nome']) ?></h2>
            <p class="small f-muted mb-3"><?= h(mb_strimwidth($md['descricao'] ?? '', 0, 90, '…')) ?></p>
            <div class="d-flex flex-wrap gap-1 mb-3">
                <span class="f-chip"><i class="bi bi-speedometer"></i><?= number_format($md['peso_g'], 0, ',', '.') ?> g</span>
                <span class="f-chip"><i class="bi bi-clock"></i><?= number_format($md['tempo_h'], 1, ',', '.') ?> h</span>
                <?php if ($md['material_tipo']): ?><span class="f-chip"><i class="bi bi-circle-half"></i><?= h($md['material_tipo']) ?></span><?php endif; ?>
            </div>
            <div class="mt-auto d-flex gap-2">
                <a class="btn btn-aco btn-sm flex-fill" href="cadastroModelo.php?id=<?= $md['id'] ?>"><i class="bi bi-pencil me-1"></i>Editar</a>
                <form action="../processamento/processamento.php" method="POST" data-confirmar="Excluir o modelo <?= h($md['nome']) ?>?">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="acao" value="excluirModelo">
                    <input type="hidden" name="id" value="<?= $md['id'] ?>">
                    <button class="btn btn-perigo btn-sm btn-icone" type="submit" title="Excluir"><i class="bi bi-trash3"></i></button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require 'includes/rodape.php'; ?>
