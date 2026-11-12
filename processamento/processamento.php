<?php
require_once __DIR__ . '/sessao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../view/home.php');
    exit;
}

if (!hash_equals(tokenCSRF(), $_POST['csrf'] ?? '')) {
    avisar('danger', 'Sessão expirada. Tente novamente.');
    header('Location: ../view/login.php');
    exit;
}

$acao = $_POST['acao'] ?? '';

$publicas = ['login', 'recuperarSenha', 'redefinirSenha'];
if (!in_array($acao, $publicas, true)) {
    exigirLogin();
}
$usuario = usuarioLogado();

function texto(string $campo, int $max = 255): string
{
    return mb_substr(trim((string) ($_POST[$campo] ?? '')), 0, $max);
}

function numero(string $campo, float $min = 0): float
{
    $v = str_replace(',', '.', (string) ($_POST[$campo] ?? '0'));
    return max($min, is_numeric($v) ? (float) $v : 0);
}

function opcao(string $campo, array $permitidas, string $padrao): string
{
    $v = $_POST[$campo] ?? $padrao;
    return in_array($v, $permitidas, true) ? $v : $padrao;
}

function ir(string $pagina, string $tipo = '', string $mensagem = ''): void
{
    if ($mensagem !== '') {
        avisar($tipo, $mensagem);
    }
    header('Location: ../view/' . $pagina);
    exit;
}

function salvarUpload(string $campo): ?string
{
    if (empty($_FILES[$campo]['name']) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio da imagem.');
    }
    if ($_FILES[$campo]['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no máximo 2 MB.');
    }
    $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($_FILES[$campo]['tmp_name']);
    if (!isset($tipos[$mime])) {
        throw new RuntimeException('Envie uma imagem JPG, PNG ou WEBP.');
    }
    $nome = 'modelo-' . uniqid() . '.' . $tipos[$mime];
    $destino = __DIR__ . '/../img/modelos/' . $nome;
    if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }
    return $nome;
}

try {
    switch ($acao) {
        case 'login':
            $email = texto('email', 120);
            $senha = (string) ($_POST['senha'] ?? '');
            $u = autenticar($email, $senha);
            if (!$u) {
                lembrarFormulario(['email' => $email]);
                ir('login.php', 'danger', 'E-mail ou senha incorretos.');
            }
            session_regenerate_id(true);
            $_SESSION['usuario'] = ['id' => (int) $u['id'], 'nome' => $u['nome'], 'email' => $u['email'], 'nivel' => $u['nivel']];
            $_SESSION['login_em'] = date('d/m/Y H:i');
            limparFormulario();
            ir('home.php', 'success', 'Bem-vindo à forja, ' . explode(' ', $u['nome'])[0] . '!');

        case 'logout':
            $_SESSION = [];
            session_destroy();
            session_start();
            ir('login.php', 'info', 'Você saiu do sistema.');

        case 'recuperarSenha':
            $email = texto('email', 120);
            $r = gerarTokenSenha($email);
            if ($r) {
                $_SESSION['email_simulado'] = [
                    'para'  => $r['usuario']['email'],
                    'nome'  => $r['usuario']['nome'],
                    'link'  => 'redefinirSenha.php?token=' . $r['token'],
                ];
            } else {
                unset($_SESSION['email_simulado']);
            }
            ir('recuperarSenha.php', 'success', 'Se o e-mail estiver cadastrado, enviamos um link para redefinir a senha.');

        case 'redefinirSenha':
            $token = texto('token', 64);
            $nova = (string) ($_POST['nova_senha'] ?? '');
            $conf = (string) ($_POST['confirmar_senha'] ?? '');
            $t = validarTokenSenha($token);
            if (!$t) {
                ir('recuperarSenha.php', 'danger', 'Link inválido ou expirado. Peça um novo.');
            }
            if (strlen($nova) < 6 || $nova !== $conf) {
                ir('redefinirSenha.php?token=' . urlencode($token), 'danger', 'A senha precisa ter ao menos 6 caracteres e as duas precisam ser iguais.');
            }
            alterarSenha((int) $t['id'], $nova);
            usarTokenSenha((int) $t['token_id']);
            unset($_SESSION['email_simulado']);
            ir('login.php', 'success', 'Senha alterada! Entre com a nova senha.');

        case 'trocarSenha':
            $atual = (string) ($_POST['senha_atual'] ?? '');
            $nova = (string) ($_POST['nova_senha'] ?? '');
            if (!autenticar($usuario['email'], $atual)) {
                ir('perfil.php', 'danger', 'Senha atual incorreta.');
            }
            if (strlen($nova) < 6 || $nova !== ($_POST['confirmar_senha'] ?? '')) {
                ir('perfil.php', 'danger', 'A nova senha precisa ter ao menos 6 caracteres e ser confirmada.');
            }
            alterarSenha($usuario['id'], $nova);
            ir('perfil.php', 'success', 'Senha atualizada.');

        case 'salvarCliente':
            $d = [
                'id' => (int) ($_POST['id'] ?? 0), 'nome' => texto('nome', 120), 'tipo' => opcao('tipo', ['PF', 'PJ'], 'PF'),
                'documento' => texto('documento', 20), 'email' => texto('email', 120), 'telefone' => texto('telefone', 20),
                'cidade' => texto('cidade', 80), 'observacoes' => texto('observacoes', 2000),
            ];
            if ($d['nome'] === '' || ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL))) {
                lembrarFormulario($d);
                ir('cadastroCliente.php' . ($d['id'] ? '?id=' . $d['id'] : ''), 'danger', 'Informe o nome e um e-mail válido.');
            }
            salvarCliente($d);
            limparFormulario();
            ir('verCliente.php', 'success', 'Cliente "' . $d['nome'] . '" salvo.');

        case 'excluirCliente':
            excluirCliente((int) $_POST['id']);
            ir('verCliente.php', 'success', 'Cliente excluído.');

        case 'salvarMaterial':
            $hex = texto('cor_hex', 7);
            $d = [
                'id' => (int) ($_POST['id'] ?? 0), 'tipo' => texto('tipo', 30), 'marca' => texto('marca', 60), 'cor' => texto('cor', 40),
                'cor_hex' => preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? $hex : '#888888',
                'diametro' => numero('diametro', 0.5), 'preco_kg' => numero('preco_kg'), 'estoque_g' => numero('estoque_g'),
                'estoque_minimo_g' => numero('estoque_minimo_g'),
            ];
            if ($d['tipo'] === '' || $d['marca'] === '' || $d['cor'] === '' || $d['preco_kg'] <= 0) {
                lembrarFormulario($d);
                ir('cadastroMaterial.php' . ($d['id'] ? '?id=' . $d['id'] : ''), 'danger', 'Preencha tipo, marca, cor e um preço por kg maior que zero.');
            }
            salvarMaterial($d, $usuario['id']);
            limparFormulario();
            ir('verMaterial.php', 'success', 'Material ' . $d['tipo'] . ' ' . $d['cor'] . ' salvo.');

        case 'excluirMaterial':
            excluirMaterial((int) $_POST['id']);
            ir('verMaterial.php', 'success', 'Material excluído.');

        case 'salvarImpressora':
            $d = [
                'id' => (int) ($_POST['id'] ?? 0), 'nome' => texto('nome', 60), 'marca' => texto('marca', 60), 'modelo' => texto('modelo', 60),
                'tecnologia' => opcao('tecnologia', ['FDM', 'SLA'], 'FDM'), 'potencia_w' => (int) numero('potencia_w', 1),
                'valor_compra' => numero('valor_compra'), 'vida_util_h' => (int) numero('vida_util_h', 1), 'horas_uso' => numero('horas_uso'),
                'status' => opcao('status', ['disponivel', 'em_uso', 'manutencao'], 'disponivel'),
            ];
            if ($d['nome'] === '' || $d['marca'] === '' || $d['modelo'] === '') {
                lembrarFormulario($d);
                ir('cadastroImpressora.php' . ($d['id'] ? '?id=' . $d['id'] : ''), 'danger', 'Preencha nome, marca e modelo.');
            }
            salvarImpressora($d);
            limparFormulario();
            ir('verImpressora.php', 'success', 'Impressora "' . $d['nome'] . '" salva.');

        case 'excluirImpressora':
            excluirImpressora((int) $_POST['id']);
            ir('verImpressora.php', 'success', 'Impressora excluída.');

        case 'salvarModelo':
            $d = [
                'id' => (int) ($_POST['id'] ?? 0), 'nome' => texto('nome', 120), 'categoria' => texto('categoria', 60),
                'descricao' => texto('descricao', 2000), 'peso_g' => numero('peso_g'), 'tempo_h' => numero('tempo_h'),
                'material_id' => (int) ($_POST['material_id'] ?? 0),
            ];
            if ($d['nome'] === '' || $d['peso_g'] <= 0 || $d['tempo_h'] <= 0) {
                lembrarFormulario($d);
                ir('cadastroModelo.php' . ($d['id'] ? '?id=' . $d['id'] : ''), 'danger', 'Informe nome, peso e tempo de impressão.');
            }
            try {
                $d['imagem'] = salvarUpload('imagem');
            } catch (RuntimeException $e) {
                lembrarFormulario($d);
                ir('cadastroModelo.php' . ($d['id'] ? '?id=' . $d['id'] : ''), 'danger', $e->getMessage());
            }
            salvarModelo($d);
            limparFormulario();
            ir('verModelo.php', 'success', 'Modelo "' . $d['nome'] . '" salvo.');

        case 'excluirModelo':
            excluirModelo((int) $_POST['id']);
            ir('verModelo.php', 'success', 'Modelo excluído.');

        default:
            ir('home.php', 'warning', 'Ação desconhecida.');
    }
} catch (PDOException $e) {
    if (erroDeVinculo($e)) {
        $destinos = ['excluirCliente' => 'verCliente.php', 'excluirMaterial' => 'verMaterial.php', 'excluirImpressora' => 'verImpressora.php', 'excluirModelo' => 'verModelo.php'];
        ir($destinos[$acao] ?? 'home.php', 'danger', 'Não dá para excluir: esse registro está ligado a orçamentos ou ao histórico do estoque.');
    }
    error_log($e->getMessage());
    ir('home.php', 'danger', 'Erro no banco de dados: ' . $e->getMessage());
} catch (RuntimeException $e) {
    ir('home.php', 'danger', $e->getMessage());
}

