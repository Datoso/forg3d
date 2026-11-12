<?php
require_once '../processamento/sessao.php';
exigirLogin();

$busca = trim($_GET['busca'] ?? '');
$materiais = listarMateriais($busca);
$valorEstoque = array_sum(array_map(fn($m) => $m['estoque_g'] / 1000 * $m['preco_kg'], $materiais));

$titulo = 'Materiais';
$pagina = 'cadastros';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Cadastros</span>
        <h1>Materiais</h1>
        <p><?= count($materiais) ?> filamento(s) · valor em estoque <strong class="text-light"><?= moeda($valorEstoque) ?></strong></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form class="f-busca d-flex gap-2" method="GET">
            <input class="form-control" name="busca" value="<?= h($busca) ?>" placeholder="Buscar tipo, marca, cor...">
            <button class="btn btn-aco btn-icone" type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
        <a href="cadastroMaterial.php" class="btn btn-forja"><i class="bi bi-plus-lg me-1"></i>Novo material</a>
    </div>
</div>

<div class="f-card p-0 overflow-hidden">
    <?php if (!$materiais): ?>
        <div class="f-vazio"><i class="bi bi-circle-half"></i>Nenhum material encontrado.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table f-tabela table-hover">
            <thead><tr><th class="ps-4">Material</th><th>Marca</th><th>Preço/kg</th><th style="min-width:200px">Estoque</th><th class="text-end pe-4">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($materiais as $m):
                $limite = max(1, $m['estoque_minimo_g'] * 4);
                $pct = min(100, $m['estoque_g'] / $limite * 100);
                $classe = $m['estoque_g'] <= $m['estoque_minimo_g'] ? ($m['estoque_g'] < $m['estoque_minimo_g'] / 2 ? 'f-critico' : 'f-baixo') : ''; ?>
                <tr>
                    <td class="ps-4">
                        <span class="f-cor me-2" style="background:<?= h($m['cor_hex']) ?>"></span>
                        <strong><?= h($m['tipo']) ?></strong> <?= h($m['cor']) ?>
                        <div class="small f-muted ms-4"><?= str_replace('.', ',', (float) $m['diametro']) ?> mm</div>
                    </td>
                    <td><?= h($m['marca']) ?></td>
                    <td><?= moeda($m['preco_kg']) ?></td>
                    <td>
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= gramas($m['estoque_g']) ?></span>
                            <?php if ($classe): ?><span style="color:var(--f-brasa-2)"><i class="bi bi-exclamation-triangle-fill"></i> abaixo do mínimo</span>
                            <?php else: ?><span class="f-muted">mín. <?= gramas($m['estoque_minimo_g']) ?></span><?php endif; ?>
                        </div>
                        <div class="f-camadas <?= $classe ?>"><span style="width:<?= round($pct) ?>%"></span></div>
                    </td>
                    <td class="text-end pe-4 text-nowrap">
                        <a class="btn btn-aco btn-icone" href="cadastroMaterial.php?id=<?= $m['id'] ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" action="../processamento/processamento.php" method="POST" data-confirmar="Excluir <?= h($m['tipo'] . ' ' . $m['cor']) ?>?">
                            <?= campoCSRF() ?>
                            <input type="hidden" name="acao" value="excluirMaterial">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button class="btn btn-perigo btn-icone" type="submit" title="Excluir"><i class="bi bi-trash3"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php require 'includes/rodape.php'; ?>
