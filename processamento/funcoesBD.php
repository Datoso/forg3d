<?php
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    require_once __DIR__ . '/config.exemplo.php';
}

function conectar(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NOME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USUARIO, DB_SENHA, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('<h3 style="font-family:sans-serif">Não foi possível conectar ao banco "' . DB_NOME
                . '". Verifique se o MySQL do XAMPP está ligado e se o arquivo banco/forg3d.sql foi importado no phpMyAdmin.</h3>');
        }
    }
    return $pdo;
}

function consultar(string $sql, array $params = []): array
{
    $st = conectar()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function consultarUm(string $sql, array $params = []): ?array
{
    $st = conectar()->prepare($sql);
    $st->execute($params);
    $linha = $st->fetch();
    return $linha === false ? null : $linha;
}

function executar(string $sql, array $params = []): int
{
    $st = conectar()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

function ultimoId(): int
{
    return (int) conectar()->lastInsertId();
}

function erroDeVinculo(PDOException $e): bool
{
    return $e->getCode() == 23000 && str_contains($e->getMessage(), '1451');
}

function erroDuplicado(PDOException $e): bool
{
    return $e->getCode() == 23000 && str_contains($e->getMessage(), '1062');
}

function autenticar(string $email, string $senha): ?array
{
    $u = consultarUm('SELECT * FROM usuarios WHERE email = ? AND ativo = 1', [$email]);
    if ($u && password_verify($senha, $u['senha'])) {
        return $u;
    }
    return null;
}

function alterarSenha(int $usuarioId, string $novaSenha): void
{
    executar('UPDATE usuarios SET senha = ? WHERE id = ?', [password_hash($novaSenha, PASSWORD_DEFAULT), $usuarioId]);
}

function gerarTokenSenha(string $email): ?array
{
    $u = consultarUm('SELECT id, nome, email FROM usuarios WHERE email = ? AND ativo = 1', [$email]);
    if (!$u) {
        return null;
    }
    executar('UPDATE tokens_senha SET usado = 1 WHERE usuario_id = ? AND usado = 0', [$u['id']]);

    $token = bin2hex(random_bytes(32));
    executar('INSERT INTO tokens_senha (usuario_id, token, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))',
        [$u['id'], hash('sha256', $token), TOKEN_VALIDADE_MIN]);
    return ['usuario' => $u, 'token' => $token];
}

function validarTokenSenha(string $token): ?array
{
    return consultarUm('SELECT t.id AS token_id, u.id, u.nome, u.email FROM tokens_senha t
                        JOIN usuarios u ON u.id = t.usuario_id
                        WHERE t.token = ? AND t.usado = 0 AND t.expira_em > NOW()', [hash('sha256', $token)]);
}

function usarTokenSenha(int $tokenId): void
{
    executar('UPDATE tokens_senha SET usado = 1 WHERE id = ?', [$tokenId]);
}

function listarClientes(string $busca = ''): array
{
    return consultar('SELECT c.* FROM clientes c
                      WHERE c.nome LIKE ? OR c.email LIKE ? OR c.cidade LIKE ? OR c.documento LIKE ?
                      ORDER BY c.nome', ["%$busca%", "%$busca%", "%$busca%", "%$busca%"]);
}

function buscarCliente(int $id): ?array
{
    return consultarUm('SELECT * FROM clientes WHERE id = ?', [$id]);
}

function salvarCliente(array $d): int
{
    $campos = [$d['nome'], $d['tipo'], $d['documento'], $d['email'], $d['telefone'], $d['cidade'], $d['observacoes']];
    if (!empty($d['id'])) {
        executar('UPDATE clientes SET nome = ?, tipo = ?, documento = ?, email = ?, telefone = ?, cidade = ?, observacoes = ? WHERE id = ?',
            [...$campos, $d['id']]);
        return (int) $d['id'];
    }
    executar('INSERT INTO clientes (nome, tipo, documento, email, telefone, cidade, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)', $campos);
    return ultimoId();
}

function excluirCliente(int $id): void
{
    executar('DELETE FROM clientes WHERE id = ?', [$id]);
}

function listarMateriais(string $busca = ''): array
{
    return consultar('SELECT * FROM materiais WHERE tipo LIKE ? OR marca LIKE ? OR cor LIKE ?
                      ORDER BY tipo, marca, cor', ["%$busca%", "%$busca%", "%$busca%"]);
}

function buscarMaterial(int $id): ?array
{
    return consultarUm('SELECT * FROM materiais WHERE id = ?', [$id]);
}

function salvarMaterial(array $d, int $usuarioId): int
{
    $campos = [$d['tipo'], $d['marca'], $d['cor'], $d['cor_hex'], $d['diametro'], $d['preco_kg'], $d['estoque_minimo_g']];
    if (!empty($d['id'])) {
        executar('UPDATE materiais SET tipo = ?, marca = ?, cor = ?, cor_hex = ?, diametro = ?, preco_kg = ?, estoque_minimo_g = ? WHERE id = ?',
            [...$campos, $d['id']]);
        return (int) $d['id'];
    }
    executar('INSERT INTO materiais (tipo, marca, cor, cor_hex, diametro, preco_kg, estoque_minimo_g, estoque_g) VALUES (?, ?, ?, ?, ?, ?, ?, 0)', $campos);
    $id = ultimoId();
    if ((float) $d['estoque_g'] > 0) {
        registrarMovimentacao($id, 'entrada', (float) $d['estoque_g'], 'Estoque inicial do cadastro', $usuarioId);
    }
    return $id;
}

function excluirMaterial(int $id): void
{
    executar('DELETE FROM materiais WHERE id = ?', [$id]);
}

function registrarMovimentacao(int $materialId, string $tipo, float $gramas, string $motivo, int $usuarioId, ?int $orcamentoId = null): void
{
    $delta = $tipo === 'saida' ? -abs($gramas) : ($tipo === 'entrada' ? abs($gramas) : $gramas);
    executar('UPDATE materiais SET estoque_g = GREATEST(0, estoque_g + ?) WHERE id = ?', [$delta, $materialId]);
    executar('INSERT INTO movimentacoes_estoque (material_id, tipo, quantidade_g, motivo, orcamento_id, usuario_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$materialId, $tipo, $delta, $motivo, $orcamentoId, $usuarioId]);
}

function contarCadastros(): array
{
    return consultarUm('SELECT (SELECT COUNT(*) FROM clientes) AS clientes,
                               (SELECT COUNT(*) FROM materiais) AS materiais');
}

function h($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function moeda($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function gramas($valor): string
{
    $v = (float) $valor;
    return $v >= 1000 ? number_format($v / 1000, 2, ',', '.') . ' kg' : number_format($v, 0, ',', '.') . ' g';
}

function dataBR($data, bool $hora = false): string
{
    if (!$data) {
        return '—';
    }
    return date($hora ? 'd/m/Y H:i' : 'd/m/Y', strtotime($data));
}

