<?php
require_once '../processamento/sessao.php';
exigirLogin();

$total = contarCadastros();
$titulo = 'Painel';
$pagina = 'home';
require 'includes/topo.php';

$saudacao = (int) date('H') < 12 ? 'Bom dia' : ((int) date('H') < 18 ? 'Boa tarde' : 'Boa noite');
$cards = [
    ['bi-people', $total['clientes'], 'Clientes', 'verCliente.php'],
    ['bi-circle-half', $total['materiais'], 'Materiais', 'verMaterial.php'],
];
?>
<div class="f-cabecalho">
    <div>
        <span class="f-mini">Painel da forja</span>
        <h1><?= $saudacao ?>, <span class="f-texto-brasa"><?= h(explode(' ', usuarioLogado()['nome'])[0]) ?></span></h1>
        <p>Cadastros da oficina de impressão 3D.</p>
    </div>
    <a href="cadastroCliente.php" class="btn btn-forja"><i class="bi bi-plus-lg me-1"></i>Novo cliente</a>
</div>

<div class="row g-3 mb-3">
    <?php foreach ($cards as [$icone, $valor, $rotulo, $link]): ?>
    <div class="col-sm-6">
        <a href="<?= $link ?>" class="text-decoration-none text-reset">
            <div class="f-card f-card-quente f-indicador">
                <div class="f-indicador-icone"><i class="bi <?= $icone ?>"></i></div>
                <div>
                    <div class="f-indicador-valor"><?= (int) $valor ?></div>
                    <div class="f-indicador-rotulo"><?= h($rotulo) ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="f-card">
    <div class="f-card-titulo"><i class="bi bi-lightning-charge"></i>Atalhos</div>
    <div class="row g-2">
        <div class="col-sm-6 col-lg-3 d-grid"><a href="cadastroCliente.php" class="btn btn-aco text-start"><i class="bi bi-people me-2"></i>Novo cliente</a></div>
        <div class="col-sm-6 col-lg-3 d-grid"><a href="cadastroMaterial.php" class="btn btn-aco text-start"><i class="bi bi-circle-half me-2"></i>Novo material</a></div>
        <div class="col-sm-6 col-lg-3 d-grid"><a href="verCliente.php" class="btn btn-aco text-start"><i class="bi bi-list-ul me-2"></i>Ver clientes</a></div>
        <div class="col-sm-6 col-lg-3 d-grid"><a href="verMaterial.php" class="btn btn-aco text-start"><i class="bi bi-list-ul me-2"></i>Ver materiais</a></div>
    </div>
</div>
<?php require 'includes/rodape.php'; ?>
