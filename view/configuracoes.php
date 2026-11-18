<?php
require_once '../processamento/sessao.php';
exigirAdmin();

$cfg = buscarConfig();
$titulo = 'Configurações';
$pagina = '';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Somente admin</span>
        <h1>Configurações da forja</h1>
        <p>Valores padrão usados nos novos orçamentos.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <form class="f-card f-rebites" action="../processamento/processamento.php" method="POST">
            <?= campoCSRF() ?>
            <input type="hidden" name="acao" value="salvarConfig">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="nome_empresa">Nome da empresa (aparece no orçamento)</label>
                    <input class="form-control" id="nome_empresa" name="nome_empresa" maxlength="80" value="<?= h($cfg['nome_empresa']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tarifa_kwh">Tarifa de energia</label>
                    <div class="input-group"><span class="input-group-text">R$</span><input type="number" step="0.001" min="0" class="form-control" id="tarifa_kwh" name="tarifa_kwh" value="<?= (float) $cfg['tarifa_kwh'] ?>"><span class="input-group-text">/kWh</span></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="margem_padrao">Margem de lucro padrão</label>
                    <div class="input-group"><input type="number" step="0.1" min="0" class="form-control" id="margem_padrao" name="margem_padrao" value="<?= (float) $cfg['margem_padrao'] ?>"><span class="input-group-text">%</span></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="taxa_falha_padrao">Margem de falha padrão</label>
                    <div class="input-group"><input type="number" step="0.1" min="0" class="form-control" id="taxa_falha_padrao" name="taxa_falha_padrao" value="<?= (float) $cfg['taxa_falha_padrao'] ?>"><span class="input-group-text">%</span></div>
                </div>
            </div>
            <div class="text-end mt-4"><button class="btn btn-forja" type="submit"><i class="bi bi-check2 me-1"></i>Salvar</button></div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="f-card f-ia h-100">
            <div class="f-card-titulo"><i class="bi bi-stars"></i>IA — Gemini</div>
            <?php if (GEMINI_API_KEY !== ''): ?>
                <p class="mb-2"><span class="f-ia-selo conectado">Conectado</span> modelo <code><?= h(GEMINI_MODELO) ?></code></p>
                <p class="small f-muted mb-0">A chave está no arquivo <code>processamento/config.php</code> (fora do GitHub).</p>
            <?php else: ?>
                <p class="mb-2"><span class="f-ia-selo demo">Modo demonstração</span></p>
                <ol class="small f-muted mb-0 ps-3">
                    <li>Crie uma chave grátis em <strong>aistudio.google.com</strong> → Get API key.</li>
                    <li>Abra <code>processamento/config.php</code> no VS Code.</li>
                    <li>Cole a chave em <code>GEMINI_API_KEY</code> e salve.</li>
                    <li>Recarregue o orçamento: o selo vira "Gemini conectado".</li>
                </ol>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require 'includes/rodape.php'; ?>
