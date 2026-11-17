<?php
require_once '../processamento/sessao.php';
exigirAdmin();

$busca = trim($_GET['busca'] ?? '');
$usuarios = listarUsuarios($busca);
$meuId = usuarioLogado()['id'];

$titulo = 'Usuários';
$pagina = 'cadastros';
require 'includes/topo.php';
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Cadastros · somente admin</span>
        <h1>Usuários</h1>
        <p>Quem pode entrar na forja.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form class="f-busca d-flex gap-2" method="GET">
            <input class="form-control" name="busca" value="<?= h($busca) ?>" placeholder="Buscar nome ou e-mail...">
            <button class="btn btn-aco btn-icone" type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
        <a href="cadastroUsuario.php" class="btn btn-forja"><i class="bi bi-plus-lg me-1"></i>Novo usuário</a>
    </div>
</div>

<div class="f-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table f-tabela table-hover">
            <thead><tr><th class="ps-4">Usuário</th><th>Nível</th><th>Situação</th><th>Desde</th><th class="text-end pe-4">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $us): ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="f-avatar"><?= h(mb_strtoupper(mb_substr($us['nome'], 0, 1))) ?></span>
                            <div>
                                <div class="fw-semibold"><?= h($us['nome']) ?> <?= $us['id'] == $meuId ? '<span class="f-chip ms-1">você</span>' : '' ?></div>
                                <div class="small f-muted"><?= h($us['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= $us['nivel'] === 'admin' ? '<span class="f-status f-status-em_producao">Administrador</span>' : '<span class="f-status f-status-pendente">Operador</span>' ?></td>
                    <td><?= $us['ativo'] ? '<span class="f-status f-status-concluido">Ativo</span>' : '<span class="f-status f-status-cancelado">Inativo</span>' ?></td>
                    <td class="f-muted"><?= dataBR($us['criado_em']) ?></td>
                    <td class="text-end pe-4 text-nowrap">
                        <a class="btn btn-aco btn-icone" href="cadastroUsuario.php?id=<?= $us['id'] ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                        <?php if ($us['id'] != $meuId): ?>
                        <form class="d-inline" action="../processamento/processamento.php" method="POST" data-confirmar="Excluir o usuário <?= h($us['nome']) ?>?">
                            <?= campoCSRF() ?>
                            <input type="hidden" name="acao" value="excluirUsuario">
                            <input type="hidden" name="id" value="<?= $us['id'] ?>">
                            <button class="btn btn-perigo btn-icone" type="submit" title="Excluir"><i class="bi bi-trash3"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require 'includes/rodape.php'; ?>
