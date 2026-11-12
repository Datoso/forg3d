<?php
require_once '../processamento/sessao.php';
exigirLogin();

$busca = trim($_GET['busca'] ?? '');
$clientes = listarClientes($busca);

$titulo = 'Clientes';
$pagina = 'cadastros';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Cadastros</span>
        <h1>Clientes</h1>
        <p><?= count($clientes) ?> cliente(s) <?= $busca !== '' ? 'encontrado(s) para "' . h($busca) . '"' : 'cadastrado(s)' ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form class="f-busca d-flex gap-2" method="GET">
            <input class="form-control" name="busca" value="<?= h($busca) ?>" placeholder="Buscar nome, e-mail, cidade...">
            <button class="btn btn-aco btn-icone" type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
        <a href="cadastroCliente.php" class="btn btn-forja"><i class="bi bi-plus-lg me-1"></i>Novo cliente</a>
    </div>
</div>

<div class="f-card p-0 overflow-hidden">
    <?php if (!$clientes): ?>
        <div class="f-vazio"><i class="bi bi-people"></i>Nenhum cliente encontrado.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table f-tabela table-hover">
            <thead>
                <tr><th class="ps-4">Cliente</th><th>Contato</th><th>Cidade</th><th class="text-end pe-4">Ações</th></tr>
            </thead>
            <tbody>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-semibold"><?= h($c['nome']) ?></div>
                        <div class="small f-muted"><?= $c['tipo'] ?> · <?= h($c['documento'] ?: 'sem documento') ?></div>
                    </td>
                    <td>
                        <div class="small"><?= h($c['email'] ?: '—') ?></div>
                        <div class="small f-muted"><?= h($c['telefone'] ?: '') ?></div>
                    </td>
                    <td><?= h($c['cidade'] ?: '—') ?></td>
                    <td class="text-end pe-4 text-nowrap">
                        <a class="btn btn-aco btn-icone" href="cadastroCliente.php?id=<?= $c['id'] ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" action="../processamento/processamento.php" method="POST" data-confirmar="Excluir o cliente <?= h($c['nome']) ?>?">
                            <?= campoCSRF() ?>
                            <input type="hidden" name="acao" value="excluirCliente">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
