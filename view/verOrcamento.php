<?php
require_once '../processamento/sessao.php';
exigirLogin();

$status = $_GET['status'] ?? '';
$busca = trim($_GET['busca'] ?? '');
$orcamentos = listarOrcamentos($status, $busca);
$filtros = ['' => 'Todos', 'pendente' => 'Pendentes', 'aprovado' => 'Aprovados', 'em_producao' => 'Em produção', 'concluido' => 'Concluídos', 'cancelado' => 'Cancelados'];
$proximo = ['pendente' => 'aprovado', 'aprovado' => 'em_producao', 'em_producao' => 'concluido'];
$rotuloAcao = ['aprovado' => 'Aprovar', 'em_producao' => 'Iniciar produção', 'concluido' => 'Concluir'];

$titulo = 'Orçamentos';
$pagina = 'orcamentos';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Orçamentos</span>
        <h1>Todos os orçamentos</h1>
        <p><?= count($orcamentos) ?> registro(s) · total <strong class="text-light"><?= moeda(array_sum(array_column($orcamentos, 'preco_final'))) ?></strong></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form class="f-busca d-flex gap-2" method="GET">
            <input type="hidden" name="status" value="<?= h($status) ?>">
            <input class="form-control" name="busca" value="<?= h($busca) ?>" placeholder="Buscar por cliente...">
            <button class="btn btn-aco btn-icone" type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
        <a href="orcamento.php" class="btn btn-forja"><i class="bi bi-plus-lg me-1"></i>Novo orçamento</a>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($filtros as $chave => $nome): ?>
        <a href="?status=<?= $chave ?>&busca=<?= urlencode($busca) ?>" class="btn btn-sm <?= $status === $chave ? 'btn-forja' : 'btn-aco' ?>"><?= $nome ?></a>
    <?php endforeach; ?>
</div>

<div class="f-card p-0 overflow-hidden">
    <?php if (!$orcamentos): ?>
        <div class="f-vazio"><i class="bi bi-receipt"></i>Nenhum orçamento aqui.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table f-tabela table-hover">
            <thead><tr><th class="ps-4">#</th><th>Cliente</th><th>Criado</th><th>Peças</th><th>Custo</th><th>Preço</th><th>Status</th><th class="text-end pe-4">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($orcamentos as $o): ?>
                <tr>
                    <td class="ps-4 fw-semibold"><a href="detalheOrcamento.php?id=<?= $o['id'] ?>">#<?= $o['id'] ?></a></td>
                    <td>
                        <div class="fw-semibold"><?= h($o['cliente_nome']) ?></div>
                        <div class="small f-muted">por <?= h(explode(' ', $o['usuario_nome'])[0]) ?></div>
                    </td>
                    <td class="f-muted"><?= dataBR($o['criado_em']) ?></td>
                    <td><?= (int) $o['total_pecas'] ?></td>
                    <td class="f-muted"><?= moeda($o['custo_total']) ?></td>
                    <td class="fw-semibold"><?= moeda($o['preco_final']) ?></td>
                    <td><?= badgeStatus($o['status']) ?></td>
                    <td class="text-end pe-4 text-nowrap">
                        <?php if (isset($proximo[$o['status']])): $prox = $proximo[$o['status']]; ?>
                        <form class="d-inline" action="../processamento/processamento.php" method="POST"
                              <?= $prox === 'concluido' ? 'data-confirmar="Concluir o orçamento #' . $o['id'] . '? O filamento será baixado do estoque."' : '' ?>>
                            <?= campoCSRF() ?>
                            <input type="hidden" name="acao" value="alterarStatus">
                            <input type="hidden" name="id" value="<?= $o['id'] ?>">
                            <input type="hidden" name="status" value="<?= $prox ?>">
                            <input type="hidden" name="voltar" value="lista">
                            <button class="btn btn-aco btn-sm" type="submit"><?= $rotuloAcao[$prox] ?></button>
                        </form>
                        <?php endif; ?>
                        <a class="btn btn-aco btn-icone btn-sm" href="detalheOrcamento.php?id=<?= $o['id'] ?>" title="Ver detalhes"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php require 'includes/rodape.php'; ?>
