<?php
require_once '../processamento/sessao.php';
exigirLogin();

$busca = trim($_GET['busca'] ?? '');
$impressoras = listarImpressoras($busca);
$tarifa = (float) buscarConfig()['tarifa_kwh'];

$titulo = 'Impressoras';
$pagina = 'cadastros';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Cadastros</span>
        <h1>Impressoras</h1>
        <p>Custo/hora = energia (tarifa <?= moeda($tarifa) ?>/kWh) + desgaste da máquina</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form class="f-busca d-flex gap-2" method="GET">
            <input class="form-control" name="busca" value="<?= h($busca) ?>" placeholder="Buscar nome, marca...">
            <button class="btn btn-aco btn-icone" type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
        <a href="cadastroImpressora.php" class="btn btn-forja"><i class="bi bi-plus-lg me-1"></i>Nova impressora</a>
    </div>
</div>

<?php if (!$impressoras): ?>
    <div class="f-card"><div class="f-vazio"><i class="bi bi-printer"></i>Nenhuma impressora encontrada.</div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($impressoras as $p):
        $energiaH = $p['potencia_w'] / 1000 * $tarifa;
        $desgasteH = $p['valor_compra'] / max(1, $p['vida_util_h']);
        $vida = min(100, $p['horas_uso'] / max(1, $p['vida_util_h']) * 100); ?>
    <div class="col-md-6 col-xl-4">
        <div class="f-card f-card-quente h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2 class="h5 mb-0"><?= h($p['nome']) ?></h2>
                    <div class="small f-muted"><?= h($p['marca'] . ' ' . $p['modelo']) ?> · <?= $p['tecnologia'] ?></div>
                </div>
                <?= badgeStatus($p['status']) ?>
            </div>
            <div class="f-grade-info mb-3">
                <div><small>Potência</small><strong><?= (int) $p['potencia_w'] ?> W</strong></div>
                <div><small>Custo/hora</small><strong><?= moeda($energiaH + $desgasteH) ?></strong></div>
                <div><small>Energia/h</small><strong><?= moeda($energiaH) ?></strong></div>
                <div><small>Desgaste/h</small><strong><?= moeda($desgasteH) ?></strong></div>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span class="f-muted">Vida útil usada</span>
                <span><?= number_format($p['horas_uso'], 0, ',', '.') ?> / <?= number_format($p['vida_util_h'], 0, ',', '.') ?> h</span>
            </div>
            <div class="f-camadas <?= $vida > 85 ? 'f-critico' : ($vida > 60 ? 'f-baixo' : '') ?> mb-3"><span style="width:<?= round($vida) ?>%"></span></div>
            <div class="mt-auto d-flex justify-content-end gap-2">
                <a class="btn btn-aco btn-sm" href="cadastroImpressora.php?id=<?= $p['id'] ?>"><i class="bi bi-pencil me-1"></i>Editar</a>
                <form action="../processamento/processamento.php" method="POST" data-confirmar="Excluir a impressora <?= h($p['nome']) ?>?">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="acao" value="excluirImpressora">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button class="btn btn-perigo btn-sm" type="submit"><i class="bi bi-trash3"></i></button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require 'includes/rodape.php'; ?>
