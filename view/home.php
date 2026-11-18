<?php
require_once '../processamento/sessao.php';
exigirLogin();

$d = dadosDashboard();
$titulo = 'Painel';
$pagina = 'home';
$usarGraficos = true;
require 'includes/topo.php';

$saudacao = (int) date('H') < 12 ? 'Bom dia' : ((int) date('H') < 18 ? 'Boa tarde' : 'Boa noite');
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Painel da forja</span>
        <h1><?= $saudacao ?>, <span class="f-texto-brasa"><?= h(explode(' ', usuarioLogado()['nome'])[0]) ?></span></h1>
        <p>Resumo de <?= ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'][(int) date('n')] ?> e o que está na mesa agora.</p>
    </div>
    <a href="orcamento.php" class="btn btn-forja"><i class="bi bi-plus-lg me-1"></i>Novo orçamento</a>
</div>

<div class="row g-3 mb-3">
    <?php
    $cards = [
        ['bi-cash-coin', moeda($d['faturamento_mes']), 'Faturamento no mês', 'Lucro: ' . moeda($d['lucro_mes'])],
        ['bi-hourglass-split', $d['em_aberto'], 'Orçamentos em aberto', 'pendente · aprovado · produção'],
        ['bi-circle-half', gramas($d['filamento_mes']), 'Filamento usado no mês', 'baixado automaticamente'],
        ['bi-clock-history', number_format($d['horas_maquina'], 1, ',', '.') . ' h', 'Horas de máquina', 'em pedidos concluídos'],
    ];
    foreach ($cards as [$icone, $valor, $rotulo, $extra]): ?>
    <div class="col-6 col-xl-3">
        <div class="f-card f-card-quente f-indicador">
            <div class="f-indicador-icone"><i class="bi <?= $icone ?>"></i></div>
            <div>
                <div class="f-indicador-valor"><?= h($valor) ?></div>
                <div class="f-indicador-rotulo"><?= h($rotulo) ?></div>
                <div class="f-mini mt-1" style="letter-spacing:.04em;text-transform:none"><?= h($extra) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="f-card h-100">
            <div class="f-card-titulo"><i class="bi bi-bar-chart-line"></i>Faturamento × custo — últimos 6 meses</div>
            <div style="height:280px"><canvas id="graficoFaturamento"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="f-card h-100">
            <div class="f-card-titulo"><i class="bi bi-pie-chart"></i>Filamento consumido</div>
            <?php if ($d['consumo_material']): ?>
                <div style="height:280px"><canvas id="graficoMaterial"></canvas></div>
            <?php else: ?>
                <div class="f-vazio"><i class="bi bi-inbox"></i>Nenhuma baixa ainda.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="f-card h-100">
            <div class="f-card-titulo"><i class="bi bi-exclamation-triangle"></i>Alertas de estoque</div>
            <?php if (!$d['alertas']): ?>
                <div class="f-vazio py-4"><i class="bi bi-check2-circle" style="color:var(--f-verde)"></i>Todos os materiais acima do mínimo.</div>
            <?php else: foreach ($d['alertas'] as $m):
                $pct = $m['estoque_minimo_g'] > 0 ? min(100, $m['estoque_g'] / $m['estoque_minimo_g'] * 100) : 0; ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><span class="f-cor me-1" style="background:<?= h($m['cor_hex']) ?>"></span><?= h($m['tipo'] . ' ' . $m['cor']) ?></span>
                        <span class="f-muted"><?= gramas($m['estoque_g']) ?> / mín. <?= gramas($m['estoque_minimo_g']) ?></span>
                    </div>
                    <div class="f-camadas <?= $pct < 50 ? 'f-critico' : 'f-baixo' ?>"><span style="width:<?= round($pct) ?>%"></span></div>
                </div>
            <?php endforeach; ?>
                <a href="estoque.php" class="btn btn-aco btn-sm w-100 mt-1"><i class="bi bi-box-arrow-in-down me-1"></i>Registrar entrada</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="f-card h-100">
            <div class="f-card-titulo"><i class="bi bi-printer"></i>Impressoras</div>
            <?php foreach ($d['impressoras'] as $p):
                $vida = $p['vida_util_h'] > 0 ? min(100, $p['horas_uso'] / $p['vida_util_h'] * 100) : 0; ?>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="border-color:var(--f-borda)!important">
                    <div>
                        <div class="fw-semibold"><?= h($p['nome']) ?></div>
                        <div class="small f-muted"><?= h($p['marca'] . ' ' . $p['modelo']) ?> · <?= round($vida) ?>% da vida útil</div>
                    </div>
                    <?= badgeStatus($p['status']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="f-card h-100">
            <div class="f-card-titulo"><i class="bi bi-receipt"></i>Últimos orçamentos</div>
            <?php foreach ($d['ultimos'] as $o): ?>
                <a href="detalheOrcamento.php?id=<?= $o['id'] ?>" class="d-flex align-items-center justify-content-between py-2 border-bottom text-decoration-none text-reset" style="border-color:var(--f-borda)!important">
                    <div>
                        <div class="fw-semibold">#<?= $o['id'] ?> · <?= h($o['cliente']) ?></div>
                        <div class="small f-muted"><?= dataBR($o['criado_em']) ?> · <?= moeda($o['preco_final']) ?></div>
                    </div>
                    <?= badgeStatus($o['status']) ?>
                </a>
            <?php endforeach; ?>
            <a href="verOrcamento.php" class="btn btn-aco btn-sm w-100 mt-3">Ver todos</a>
        </div>
    </div>
</div>

<?php
$dadosJs = json_encode([
    'meses' => $d['meses'], 'faturamento' => $d['faturamento'], 'custos' => $d['custos'],
    'materiais' => array_column($d['consumo_material'], 'rotulo'),
    'gramas' => array_map('floatval', array_column($d['consumo_material'], 'gramas')),
    'cores' => array_column($d['consumo_material'], 'cor_hex'),
], JSON_UNESCAPED_UNICODE);
$scriptExtra = "<script>document.addEventListener('DOMContentLoaded', () => FORG3D.graficosDashboard($dadosJs));</script>";
require 'includes/rodape.php';
