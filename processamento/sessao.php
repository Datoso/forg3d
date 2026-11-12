<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/funcoesBD.php';

function usuarioLogado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function ehAdmin(): bool
{
    return (usuarioLogado()['nivel'] ?? '') === 'admin';
}

function exigirLogin(): void
{
    if (!usuarioLogado()) {
        avisar('warning', 'Faça login para acessar o sistema.');
        header('Location: ' . caminhoView('login.php'));
        exit;
    }
}

function exigirAdmin(): void
{
    exigirLogin();
    if (!ehAdmin()) {
        avisar('danger', 'Apenas administradores podem acessar essa área.');
        header('Location: ' . caminhoView('home.php'));
        exit;
    }
}

function avisar(string $tipo, string $texto): void
{
    $_SESSION['aviso'] = ['tipo' => $tipo, 'texto' => $texto];
}

function pegarAviso(): ?array
{
    $a = $_SESSION['aviso'] ?? null;
    unset($_SESSION['aviso']);
    return $a;
}

function lembrarFormulario(array $dados): void
{
    $_SESSION['form'] = $dados;
}

function valorAntigo(string $campo, $padrao = '')
{
    return $_SESSION['form'][$campo] ?? $padrao;
}

function limparFormulario(): void
{
    unset($_SESSION['form']);
}

function tokenCSRF(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function campoCSRF(): string
{
    return '<input type="hidden" name="csrf" value="' . tokenCSRF() . '">';
}

function caminhoView(string $pagina): string
{
    $pastaAtual = basename(dirname($_SERVER['SCRIPT_FILENAME']));
    return ($pastaAtual === 'view' ? '' : '../view/') . $pagina;
}
