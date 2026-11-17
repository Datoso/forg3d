<?php
require_once '../processamento/sessao.php';
exigirLogin();

$cfg = buscarConfig();
$clientes = listarClientes();
$materiais = listarMateriais();
$impressoras = array_values(array_filter(listarImpressoras(), fn($p) => $p['status'] !== 'manutencao'));
$modelos = listarModelos();
$modeloInicial = (int) ($_GET['modelo'] ?? 0);

$titulo = 'Novo orçamento';
$pagina = 'orcamentos';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Orçamentos</span>
        <h1>Forjar novo <span class="f-texto-brasa">orçamento</span></h1>
        <p>Material + energia + desgaste da máquina + margem de falha + lucro, calculados automaticamente.</p>
    </div>
    <a href="verOrcamento.php" class="btn btn-aco"><i class="bi bi-list-ul me-1"></i>Ver orçamentos</a>
</div>

<?php if (!$clientes || !$materiais || !$impressoras): ?>
    <div class="alert alert-warning">Para orçar você precisa ter pelo menos um <a href="cadastroCliente.php">cliente</a>, um <a href="cadastroMaterial.php">material</a> e uma <a href="cadastroImpressora.php">impressora</a> disponível.</div>
<?php endif; ?>

<form id="formOrcamento" action="../processamento/processamento.php" method="POST">
    <?= campoCSRF() ?>
    <input type="hidden" name="acao" value="salvarOrcamento">

    <div class="row g-3">
        <div class="col-lg-8">

            <div class="f-card mb-3">
                <div class="f-card-titulo"><i class="bi bi-person-vcard"></i>Cliente e condições</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="cliente_id">Cliente *</label>
                        <select class="form-select" id="cliente_id" name="cliente_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= h($c['nome']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="prazo_entrega">Prazo de entrega</label>
                        <input type="date" class="form-control" id="prazo_entrega" name="prazo_entrega" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="margem">Margem de lucro</label>
                        <div class="input-group"><input type="number" step="0.1" min="0" class="form-control" id="margem" name="margem" value="<?= (float) $cfg['margem_padrao'] ?>"><span class="input-group-text">%</span></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="taxa_falha">Margem de falha</label>
                        <div class="input-group"><input type="number" step="0.1" min="0" class="form-control" id="taxa_falha" name="taxa_falha" value="<?= (float) $cfg['taxa_falha_padrao'] ?>"><span class="input-group-text">%</span></div>
                        <div class="form-text">Cobre impressões que dão errado e precisam ser refeitas.</div>
                    </div>
                </div>
            </div>

            <div class="f-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="f-card-titulo mb-0"><i class="bi bi-boxes"></i>Peças</div>
                    <button type="button" class="btn btn-aco btn-sm" id="btnAddItem"><i class="bi bi-plus-lg me-1"></i>Adicionar peça</button>
                </div>
                <div id="listaItens"></div>
            </div>

            <div class="f-card mt-3">
                <label class="form-label" for="observacoes">Observações para o cliente</label>
                <textarea class="form-control" id="observacoes" name="observacoes" rows="2" placeholder="Cor, acabamento, forma de entrega..."></textarea>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="f-card f-rebites f-resumo">
                <div class="f-card-titulo"><i class="bi bi-receipt-cutoff"></i>Resumo do orçamento</div>
                <div class="f-resumo-linha"><span>Material</span><span id="rMaterial">R$ 0,00</span></div>
                <div class="f-resumo-linha"><span>Energia</span><span id="rEnergia">R$ 0,00</span></div>
                <div class="f-resumo-linha"><span>Desgaste da máquina</span><span id="rMaquina">R$ 0,00</span></div>
                <div class="f-resumo-linha"><span>Margem de falha</span><span id="rFalha">R$ 0,00</span></div>
                <div class="f-resumo-linha"><span class="text-light">Custo de produção</span><strong id="rCusto">R$ 0,00</strong></div>
                <div class="f-resumo-linha"><span>Lucro</span><span id="rLucro" style="color:var(--f-verde)">R$ 0,00</span></div>
                <div class="f-resumo-total">
                    <div class="f-mini">Preço final</div>
                    <div class="valor f-texto-brasa" id="rTotal">R$ 0,00</div>
                    <div class="small f-muted" id="rInfo">0 peça(s) · 0 g · 0 h</div>
                </div>
                <div id="rAvisoEstoque" class="small mt-3"></div>
                <button class="btn btn-forja w-100 mt-3 py-2" type="submit"><i class="bi bi-fire me-1"></i>Salvar orçamento</button>
                <p class="small f-muted mt-2 mb-0 text-center">Tarifa de energia: <?= moeda($cfg['tarifa_kwh']) ?>/kWh</p>
            </div>
        </div>
    </div>
</form>

<template id="tplItem">
    <div class="f-item" data-item>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="f-item-num">PEÇA <b data-numero>1</b></span>
            <div class="d-flex align-items-center gap-2">
                <span class="f-item-subtotal" data-subtotal>R$ 0,00</span>
                <button type="button" class="btn btn-perigo btn-icone btn-sm" data-remover title="Remover peça"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-5">
                <label class="form-label small mb-1">Do catálogo</label>
                <select class="form-select form-select-sm" data-campo="modelo_id">
                    <option value="">Peça personalizada</option>
                    <?php foreach ($modelos as $md): ?>
                        <option value="<?= $md['id'] ?>"><?= h($md['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label small mb-1">Descrição *</label>
                <input class="form-control form-control-sm" data-campo="descricao" maxlength="150" required placeholder="O que será impresso">
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Material</label>
                <select class="form-select form-select-sm" data-campo="material_id">
                    <?php foreach ($materiais as $mt): ?>
                        <option value="<?= $mt['id'] ?>"><?= h($mt['tipo'] . ' ' . $mt['cor']) ?> — <?= gramas($mt['estoque_g']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Impressora</label>
                <select class="form-select form-select-sm" data-campo="impressora_id">
                    <?php foreach ($impressoras as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= h($p['nome']) ?> (<?= h($p['modelo']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-1">
                <label class="form-label small mb-1">Qtd</label>
                <input type="number" min="1" value="1" class="form-control form-control-sm" data-campo="quantidade">
            </div>
            <div class="col-4 col-md-2">
                <label class="form-label small mb-1">Peso (g)</label>
                <input type="number" min="0.1" step="0.1" class="form-control form-control-sm" data-campo="peso_g" required>
            </div>
            <div class="col-4 col-md-2">
                <label class="form-label small mb-1">Tempo (h)</label>
                <input type="number" min="0.1" step="0.1" class="form-control form-control-sm" data-campo="tempo_h" required>
            </div>
        </div>
    </div>
</template>

<?php
$dadosJs = json_encode([
    'tarifa' => (float) $cfg['tarifa_kwh'],
    'materiais' => array_map(fn($m) => ['id' => (int) $m['id'], 'nome' => $m['tipo'] . ' ' . $m['cor'], 'preco_kg' => (float) $m['preco_kg'], 'estoque_g' => (float) $m['estoque_g']], $materiais),
    'impressoras' => array_map(fn($p) => ['id' => (int) $p['id'], 'potencia_w' => (int) $p['potencia_w'], 'valor' => (float) $p['valor_compra'], 'vida' => (int) $p['vida_util_h']], $impressoras),
    'modelos' => array_map(fn($m) => ['id' => (int) $m['id'], 'nome' => $m['nome'], 'peso_g' => (float) $m['peso_g'], 'tempo_h' => (float) $m['tempo_h'], 'material_id' => (int) $m['material_id']], $modelos),
    'modeloInicial' => $modeloInicial,
], JSON_UNESCAPED_UNICODE);
$scriptExtra = "<script>document.addEventListener('DOMContentLoaded', () => FORG3D.orcamento($dadosJs));</script>";
require 'includes/rodape.php';
