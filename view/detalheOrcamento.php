<?php
require_once '../processamento/sessao.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
$o = buscarOrcamento($id);
if (!$o) {
    avisar('danger', 'Orçamento não encontrado.');
    header('Location: verOrcamento.php');
    exit;
}
$itens = listarItensOrcamento($id);
$consumo = consumoDoOrcamento($id);
$cfg = buscarConfig();

$tot = ['material' => 0, 'energia' => 0, 'maquina' => 0];
foreach ($itens as $i) {
    $tot['material'] += $i['custo_material'] * $i['quantidade'];
    $tot['energia'] += $i['custo_energia'] * $i['quantidade'];
    $tot['maquina'] += $i['custo_maquina'] * $i['quantidade'];
}
$base = array_sum($tot);
$falha = $o['custo_total'] - $base;

$acoes = [
    'pendente'    => ['aprovado' => ['Aprovar', 'bi-check2-circle'], 'cancelado' => ['Cancelar', 'bi-x-circle']],
    'aprovado'    => ['em_producao' => ['Iniciar produção', 'bi-play-circle'], 'cancelado' => ['Cancelar', 'bi-x-circle']],
    'em_producao' => ['concluido' => ['Concluir e baixar estoque', 'bi-check2-all'], 'cancelado' => ['Cancelar', 'bi-x-circle']],
];
$etapas = ['pendente', 'aprovado', 'em_producao', 'concluido'];
$etapaAtual = array_search($o['status'], $etapas, true);

$titulo = 'Orçamento #' . $id;
$pagina = 'orcamentos';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Orçamento</span>
        <h1>#<?= $id ?> <span class="ms-2 align-middle fs-6"><?= badgeStatus($o['status']) ?></span></h1>
        <p>Criado em <?= dataBR($o['criado_em'], true) ?> por <?= h($o['usuario_nome']) ?><?= $o['concluido_em'] ? ' · concluído em ' . dataBR($o['concluido_em'], true) : '' ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2 no-print">
        <a href="verOrcamento.php" class="btn btn-aco"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
        <button class="btn btn-aco" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir / PDF</button>
        <?php foreach ($acoes[$o['status']] ?? [] as $novo => [$rotulo, $icone]): ?>
            <form action="../processamento/processamento.php" method="POST"
                  data-confirmar="<?= $novo === 'concluido' ? 'Concluir? O filamento será baixado do estoque automaticamente.' : ($novo === 'cancelado' ? 'Cancelar este orçamento?' : '') ?>">
                <?= campoCSRF() ?>
                <input type="hidden" name="acao" value="alterarStatus">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="status" value="<?= $novo ?>">
                <button class="btn <?= $novo === 'cancelado' ? 'btn-perigo' : 'btn-forja' ?>" type="submit"><i class="bi <?= $icone ?> me-1"></i><?= $rotulo ?></button>
            </form>
        <?php endforeach; ?>
        <?php if (in_array($o['status'], ['pendente', 'cancelado'], true)): ?>
            <form action="../processamento/processamento.php" method="POST" data-confirmar="Excluir definitivamente o orçamento #<?= $id ?>?">
                <?= campoCSRF() ?>
                <input type="hidden" name="acao" value="excluirOrcamento">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button class="btn btn-perigo btn-icone" type="submit" title="Excluir"><i class="bi bi-trash3"></i></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($o['status'] !== 'cancelado'): ?>
<div class="f-card mb-3 no-print py-3">
    <div class="d-flex justify-content-between position-relative" style="gap:.5rem">
        <?php foreach ($etapas as $n => $e): $feito = $n <= $etapaAtual; ?>
            <div class="text-center flex-fill">
                <div class="mx-auto mb-1 d-grid" style="place-items:center;width:34px;height:34px;border-radius:50%;
                     <?= $feito ? 'background:var(--f-grad-brasa);color:#1a0c03;box-shadow:0 0 14px var(--f-brasa-glow)' : 'background:var(--f-aco-2);color:var(--f-texto-3);border:1px solid var(--f-borda-forte)' ?>">
                    <i class="bi <?= ['bi-hourglass', 'bi-hand-thumbs-up', 'bi-printer', 'bi-check2-all'][$n] ?>"></i>
                </div>
                <div class="small <?= $feito ? 'text-light' : 'f-muted' ?>"><?= rotuloStatus($e) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="f-card mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="f-logo mb-2"><img src="../img/logo.svg" alt=""><span><span class="f-texto-aco">FORG</span><span class="f-texto-brasa">3D</span></span></div>
                    <div class="small f-muted"><?= h($cfg['nome_empresa']) ?> · Impressão 3D sob medida</div>
                </div>
                <div class="text-md-end">
                    <div class="f-mini">Cliente</div>
                    <div class="fw-semibold"><?= h($o['cliente_nome']) ?></div>
                    <div class="small f-muted"><?= h($o['cliente_documento'] ?: '') ?> <?= h($o['cliente_cidade'] ? '· ' . $o['cliente_cidade'] : '') ?></div>
                    <div class="small f-muted"><?= h($o['cliente_email'] ?: '') ?> <?= h($o['cliente_telefone'] ? '· ' . $o['cliente_telefone'] : '') ?></div>
                </div>
            </div>
        </div>

        <div class="f-card p-0 overflow-hidden mb-3">
            <div class="table-responsive">
                <table class="table f-tabela f-tab-itens">
                    <thead><tr><th class="ps-4">Peça</th><th>Material</th><th class="text-center">Qtd</th><th>Por unidade</th><th class="text-end pe-4">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($itens as $i): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold"><?= h($i['descricao']) ?></div>
                                <div class="small f-muted"><?= number_format($i['peso_g'], 0, ',', '.') ?> g · <?= number_format($i['tempo_h'], 1, ',', '.') ?> h · <?= h($i['impressora_nome']) ?></div>
                            </td>
                            <td><span class="f-cor me-1" style="background:<?= h($i['cor_hex']) ?>"></span><?= h($i['material_tipo'] . ' ' . $i['material_cor']) ?></td>
                            <td class="text-center"><?= (int) $i['quantidade'] ?></td>
                            <td><?= moeda($i['preco_unit']) ?><div class="small f-muted">custo <?= moeda($i['custo_unit']) ?></div></td>
                            <td class="text-end pe-4 fw-semibold"><?= moeda($i['preco_unit'] * $i['quantidade']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($o['sugestao_ia']): ?>
        <div class="f-card f-ia mb-3">
            <div class="f-card-titulo"><i class="bi bi-stars"></i>Recomendação do Assistente da Forja</div>
            <p class="mb-0 small" style="white-space:pre-line"><?= h($o['sugestao_ia']) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($o['observacoes']): ?>
        <div class="f-card mb-3">
            <div class="f-card-titulo"><i class="bi bi-chat-left-text"></i>Observações</div>
            <p class="mb-0" style="white-space:pre-line"><?= h($o['observacoes']) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="f-card f-rebites mb-3">
            <div class="f-card-titulo"><i class="bi bi-calculator"></i>Composição do preço</div>
            <div class="f-resumo-linha"><span>Material</span><span><?= moeda($tot['material']) ?></span></div>
            <div class="f-resumo-linha"><span>Energia (<?= moeda($o['tarifa_kwh']) ?>/kWh)</span><span><?= moeda($tot['energia']) ?></span></div>
            <div class="f-resumo-linha"><span>Desgaste da máquina</span><span><?= moeda($tot['maquina']) ?></span></div>
            <div class="f-resumo-linha"><span>Margem de falha (<?= (float) $o['taxa_falha'] ?>%)</span><span><?= moeda($falha) ?></span></div>
            <div class="f-resumo-linha"><span class="text-light">Custo de produção</span><strong><?= moeda($o['custo_total']) ?></strong></div>
            <div class="f-resumo-linha"><span>Lucro (<?= (float) $o['margem'] ?>%)</span><span style="color:var(--f-verde)"><?= moeda($o['preco_final'] - $o['custo_total']) ?></span></div>
            <div class="f-resumo-total">
                <div class="f-mini">Valor total</div>
                <div class="valor f-texto-brasa"><?= moeda($o['preco_final']) ?></div>
                <div class="small f-muted">Entrega prevista: <?= dataBR($o['prazo_entrega']) ?></div>
            </div>
        </div>

        <div class="f-card no-print">
            <div class="f-card-titulo"><i class="bi bi-boxes"></i>Filamento necessário</div>
            <?php foreach ($consumo as $c):
                $ok = $o['status'] === 'concluido' || $c['estoque_g'] >= $c['necessario_g']; ?>
                <div class="d-flex justify-content-between small py-1">
                    <span><?= h($c['tipo'] . ' ' . $c['cor']) ?></span>
                    <span style="color:<?= $ok ? 'var(--f-texto)' : 'var(--f-vermelho)' ?>">
                        <?= gramas($c['necessario_g']) ?>
                        <?php if ($o['status'] !== 'concluido'): ?><span class="f-muted">/ <?= gramas($c['estoque_g']) ?> em estoque</span><?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
            <p class="small f-muted mt-2 mb-0">
                <?= $o['status'] === 'concluido' ? '<i class="bi bi-check2-circle" style="color:var(--f-verde)"></i> Já baixado do estoque.' : 'Será baixado automaticamente ao concluir.' ?>
            </p>
        </div>
    </div>
</div>
<?php require 'includes/rodape.php'; ?>
