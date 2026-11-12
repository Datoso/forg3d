<?php
$titulo  = $titulo ?? 'FORG3D';
$pagina  = $pagina ?? '';
$semMenu = $semMenu ?? false;
$u = usuarioLogado();
$aviso = pegarAviso();

function ativo(string $item, string $pagina): string
{
    return $item === $pagina ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($titulo) ?> · FORG3D</title>
    <link rel="icon" href="../img/logo.svg" type="image/svg+xml">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="../css/forg3d.css">
</head>
<body<?= $semMenu ? ' class="f-login-pagina"' : '' ?>>

<?php if (!$semMenu && $u): ?>
<nav class="navbar navbar-expand-lg f-navbar sticky-top">
    <div class="container-xl">
        <a class="f-logo navbar-brand" href="home.php">
            <img src="../img/logo.svg" alt="">
            <span><span class="f-texto-aco">FORG</span><span class="f-texto-brasa">3D</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto ms-lg-4 gap-lg-1">
                <li class="nav-item"><a class="nav-link<?= ativo('home', $pagina) ?>" href="home.php"><i class="bi bi-speedometer2 me-1"></i>Painel</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle<?= ativo('cadastros', $pagina) ?>" href="#" data-bs-toggle="dropdown"><i class="bi bi-database me-1"></i>Cadastros</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="verCliente.php"><i class="bi bi-people me-2"></i>Clientes</a></li>
                        <li><a class="dropdown-item" href="verMaterial.php"><i class="bi bi-circle-half me-2"></i>Materiais</a></li>
                        <li><a class="dropdown-item" href="verImpressora.php"><i class="bi bi-printer me-2"></i>Impressoras</a></li>
                        <li><a class="dropdown-item" href="verModelo.php"><i class="bi bi-box me-2"></i>Modelos / peças</a></li>
                    </ul>
                </li>
            </ul>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle text-light" data-bs-toggle="dropdown">
                    <span class="f-avatar"><?= h(mb_strtoupper(mb_substr($u['nome'], 0, 1))) ?></span>
                    <span class="d-none d-md-inline"><?= h(explode(' ', $u['nome'])[0]) ?></span>
                    <span class="f-chip d-none d-md-inline-flex"><?= $u['nivel'] === 'admin' ? 'Admin' : 'Operador' ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text f-mini">Logado desde <?= h($_SESSION['login_em'] ?? '') ?></span></li>
                    <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person-gear me-2"></i>Meu perfil / senha</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="../processamento/processamento.php" method="POST">
                            <?= campoCSRF() ?>
                            <input type="hidden" name="acao" value="logout">
                            <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Sair</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

<?php if ($semMenu): ?>
<div class="f-faiscas" aria-hidden="true"></div>
<?php endif; ?>

<main class="<?= $semMenu ? 'f-login-caixa' : 'container-xl pb-4' ?>">
<?php if ($aviso): ?>
    <div class="alert alert-<?= h($aviso['tipo']) ?> alert-dismissible f-flash <?= $semMenu ? 'mb-3' : 'mt-4 mb-0' ?>" role="alert">
        <?= h($aviso['texto']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>
