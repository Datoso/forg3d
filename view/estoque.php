<?php
require_once '../processamento/sessao.php';
exigirLogin();

$materiais = listarMateriais();
$alertas = materiaisEmAlerta();
$movs = listarMovimentacoes(40);
$totalG = array_sum(array_column($materiais, 'estoque_g'));

$titulo = 'Estoque';
$pagina = 'estoque';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Estoque inteligente</span>
        <h1>Estoque de filamento</h1>
        <p><?= gramas($totalG) ?> no total · <?= count($alertas) ?> material(is) abaixo do mínimo · baixa automática ao concluir pedidos</p>
    </div>
    <a href="cadastroMaterial.php" class="btn btn-aco"><i class="bi bi-plus-lg me-1"></i>Novo material</a>
</div>

<?php if ($alertas): ?>
<div class="alert alert-warning d-flex gap-2 align-items-start">
    <i class="bi bi-exclamation-triangle-fill mt-1"></i>
    <div><strong>Hora de comprar:</strong>
        <?= implode(', ', array_map(fn($m) => h($m['tipo'] . ' ' . $m['cor']) . ' (' . gramas($m['estoque_g']) . ')', $alertas)) ?>.
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <form class="f-card f-rebites" action="../processamento/processamento.php" method="POST">
            <?= campoCSRF() ?>
            <input type="hidden" name="acao" value="movimentarEstoque">
            <div class="f-card-titulo"><i class="bi bi-box-arrow-in-down"></i>Registrar movimentação</div>
            <div class="mb-3">
                <label class="form-label" for="material_id">Material</label>
                <select class="form-select" id="material_id" name="material_id" required>
                    <?php foreach ($materiais as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= h($m['tipo'] . ' ' . $m['cor']) ?> — <?= gramas($m['estoque_g']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label d-block">Tipo</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="tipo" id="tipoEntrada" value="entrada" checked>
                    <label class="btn btn-aco" for="tipoEntrada"><i class="bi bi-plus-circle me-1"></i>Entrada</label>
                    <input type="radio" class="btn-check" name="tipo" id="tipoAjuste" value="ajuste">
                    <label class="btn btn-aco" for="tipoAjuste"><i class="bi bi-arrow-left-right me-1"></i>Ajuste</label>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label" for="quantidade_g">Quantidade</label>
                <div class="input-group">
                    <input type="number" step="1" class="form-control" id="quantidade_g" name="quantidade_g" required value="1000">
                    <span class="input-group-text">g</span>
                </div>
                <div class="form-text">Entrada: bobina de 1 kg = 1000 g. Ajuste: use negativo para perda (ex.: −150).</div>
            </div>
            <div class="d-flex flex-wrap gap-1 mb-3">
                <?php foreach ([250, 500, 1000, 3000] as $g): ?>
                    <button type="button" class="f-chip border-0" data-preencher="#quantidade_g" data-valor="<?= $g ?>"><?= gramas($g) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="motivo">Motivo</label>
                <input class="form-control" id="motivo" name="motivo" maxlength="150" placeholder="Compra de bobina, perda por umidade...">
            </div>
            <button class="btn btn-forja w-100" type="submit"><i class="bi bi-check2 me-1"></i>Registrar</button>
        </form>
    </div>

    <div class="col-lg-8">
        <div class="f-card mb-3">
            <div class="f-card-titulo"><i class="bi bi-bar-chart-steps"></i>Nível por material</div>
            <div class="row g-3">
                <?php foreach ($materiais as $m):
                    $pct = min(100, $m['estoque_g'] / max(1, $m['estoque_minimo_g'] * 4) * 100);
                    $classe = $m['estoque_g'] <= $m['estoque_minimo_g'] ? ($m['estoque_g'] < $m['estoque_minimo_g'] / 2 ? 'f-critico' : 'f-baixo') : ''; ?>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><span class="f-cor me-1" style="background:<?= h($m['cor_hex']) ?>"></span><?= h($m['tipo'] . ' ' . $m['cor']) ?></span>
                            <span class="<?= $classe ? '' : 'f-muted' ?>" <?= $classe ? 'style="color:var(--f-brasa-2)"' : '' ?>><?= gramas($m['estoque_g']) ?></span>
                        </div>
                        <div class="f-camadas <?= $classe ?>"><span style="width:<?= round($pct) ?>%"></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="f-card p-0 overflow-hidden">
            <div class="f-card-titulo px-4 pt-4"><i class="bi bi-clock-history"></i>Histórico de movimentações</div>
            <div class="table-responsive">
                <table class="table f-tabela table-hover">
                    <thead><tr><th class="ps-4">Data</th><th>Material</th><th>Tipo</th><th class="text-end">Qtd</th><th>Motivo</th><th class="pe-4">Por</th></tr></thead>
                    <tbody>
                    <?php foreach ($movs as $mv): ?>
                        <tr>
                            <td class="ps-4 small f-muted text-nowrap"><?= dataBR($mv['criado_em'], true) ?></td>
                            <td class="text-nowrap"><span class="f-cor me-1" style="background:<?= h($mv['cor_hex']) ?>"></span><?= h($mv['material_tipo'] . ' ' . $mv['cor']) ?></td>
                            <td><?= ['entrada' => '<span class="f-status f-status-concluido">Entrada</span>', 'saida' => '<span class="f-status f-status-em_producao">Saída</span>', 'ajuste' => '<span class="f-status f-status-aprovado">Ajuste</span>'][$mv['tipo']] ?></td>
                            <td class="text-end fw-semibold text-nowrap" style="color:<?= $mv['quantidade_g'] >= 0 ? 'var(--f-verde)' : 'var(--f-brasa-2)' ?>">
                                <?= ($mv['quantidade_g'] >= 0 ? '+' : '−') . gramas(abs($mv['quantidade_g'])) ?>
                            </td>
                            <td class="small">
                                <?php if ($mv['orcamento_id']): ?>
                                    <a href="detalheOrcamento.php?id=<?= $mv['orcamento_id'] ?>"><?= h($mv['motivo']) ?></a>
                                <?php else: ?><?= h($mv['motivo']) ?><?php endif; ?>
                            </td>
                            <td class="pe-4 small f-muted"><?= h(explode(' ', $mv['usuario_nome'] ?? '—')[0]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require 'includes/rodape.php'; ?>
