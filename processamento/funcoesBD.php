<?php
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    require_once __DIR__ . '/config.exemplo.php';
}

date_default_timezone_set('America/Sao_Paulo');

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

function buscarConfig(): array
{
    return consultarUm('SELECT * FROM configuracoes WHERE id = 1') ?? [
        'nome_empresa' => 'FORG3D', 'tarifa_kwh' => 0.95,
        'margem_padrao' => 60, 'taxa_falha_padrao' => 10,
    ];
}

function salvarConfig(array $d): void
{
    executar('UPDATE configuracoes SET nome_empresa = ?, tarifa_kwh = ?, margem_padrao = ?, taxa_falha_padrao = ? WHERE id = 1',
        [$d['nome_empresa'], $d['tarifa_kwh'], $d['margem_padrao'], $d['taxa_falha_padrao']]);
}

function autenticar(string $email, string $senha): ?array
{
    $u = consultarUm('SELECT * FROM usuarios WHERE email = ? AND ativo = 1', [$email]);
    if ($u && password_verify($senha, $u['senha'])) {
        return $u;
    }
    return null;
}

function listarUsuarios(string $busca = ''): array
{
    return consultar('SELECT id, nome, email, nivel, ativo, criado_em FROM usuarios
                      WHERE nome LIKE ? OR email LIKE ? ORDER BY nome', ["%$busca%", "%$busca%"]);
}

function buscarUsuario(int $id): ?array
{
    return consultarUm('SELECT id, nome, email, nivel, ativo FROM usuarios WHERE id = ?', [$id]);
}

function salvarUsuario(array $d): int
{
    if (!empty($d['id'])) {
        executar('UPDATE usuarios SET nome = ?, email = ?, nivel = ?, ativo = ? WHERE id = ?',
            [$d['nome'], $d['email'], $d['nivel'], $d['ativo'], $d['id']]);
        if (!empty($d['senha'])) {
            alterarSenha((int) $d['id'], $d['senha']);
        }
        return (int) $d['id'];
    }
    executar('INSERT INTO usuarios (nome, email, senha, nivel, ativo) VALUES (?, ?, ?, ?, ?)',
        [$d['nome'], $d['email'], password_hash($d['senha'], PASSWORD_DEFAULT), $d['nivel'], $d['ativo']]);
    return ultimoId();
}

function alterarSenha(int $usuarioId, string $novaSenha): void
{
    executar('UPDATE usuarios SET senha = ? WHERE id = ?', [password_hash($novaSenha, PASSWORD_DEFAULT), $usuarioId]);
}

function excluirUsuario(int $id): void
{
    executar('DELETE FROM usuarios WHERE id = ?', [$id]);
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
    return consultar('SELECT c.*, (SELECT COUNT(*) FROM orcamentos o WHERE o.cliente_id = c.id) AS total_orcamentos
                      FROM clientes c
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

function orcamentosQueUsam(string $coluna, int $id): array
{
    $linhas = consultar("SELECT DISTINCT orcamento_id FROM orcamento_itens WHERE $coluna = ? ORDER BY orcamento_id", [$id]);
    return array_column($linhas, 'orcamento_id');
}

function avisoEmUso(string $oQue, string $usado, array $orcamentos): string
{
    $lista = implode(', ', array_map(fn($n) => '#' . $n, array_slice($orcamentos, 0, 6)));
    $resto = count($orcamentos) > 6 ? ' e outros' : '';
    $qtd = count($orcamentos) === 1 ? 'no orçamento' : 'nos orçamentos';
    return "Não dá para excluir: $oQue foi $usado $qtd $lista$resto. Excluir apagaria o histórico desses pedidos.";
}

function excluirMaterial(int $id): ?string
{
    $usos = orcamentosQueUsam('material_id', $id);
    if ($usos) {
        return avisoEmUso('este material', 'usado', $usos);
    }
    $pdo = conectar();
    $pdo->beginTransaction();
    try {
        executar('DELETE FROM movimentacoes_estoque WHERE material_id = ?', [$id]);
        executar('DELETE FROM materiais WHERE id = ?', [$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return null;
}

function listarImpressoras(string $busca = ''): array
{
    return consultar('SELECT * FROM impressoras WHERE nome LIKE ? OR marca LIKE ? OR modelo LIKE ? ORDER BY nome',
        ["%$busca%", "%$busca%", "%$busca%"]);
}

function buscarImpressora(int $id): ?array
{
    return consultarUm('SELECT * FROM impressoras WHERE id = ?', [$id]);
}

function salvarImpressora(array $d): int
{
    $campos = [$d['nome'], $d['marca'], $d['modelo'], $d['tecnologia'], $d['potencia_w'], $d['valor_compra'], $d['vida_util_h'], $d['horas_uso'], $d['status']];
    if (!empty($d['id'])) {
        executar('UPDATE impressoras SET nome = ?, marca = ?, modelo = ?, tecnologia = ?, potencia_w = ?, valor_compra = ?, vida_util_h = ?, horas_uso = ?, status = ? WHERE id = ?',
            [...$campos, $d['id']]);
        return (int) $d['id'];
    }
    executar('INSERT INTO impressoras (nome, marca, modelo, tecnologia, potencia_w, valor_compra, vida_util_h, horas_uso, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $campos);
    return ultimoId();
}

function excluirImpressora(int $id): ?string
{
    $usos = orcamentosQueUsam('impressora_id', $id);
    if ($usos) {
        return avisoEmUso('esta impressora', 'usada', $usos);
    }
    executar('DELETE FROM impressoras WHERE id = ?', [$id]);
    return null;
}

function listarModelos(string $busca = ''): array
{
    return consultar('SELECT m.*, mt.tipo AS material_tipo, mt.cor AS material_cor
                      FROM modelos m LEFT JOIN materiais mt ON mt.id = m.material_id
                      WHERE m.nome LIKE ? OR m.categoria LIKE ? ORDER BY m.nome', ["%$busca%", "%$busca%"]);
}

function buscarModelo(int $id): ?array
{
    return consultarUm('SELECT * FROM modelos WHERE id = ?', [$id]);
}

function salvarModelo(array $d): int
{
    $material = !empty($d['material_id']) ? $d['material_id'] : null;
    if (!empty($d['id'])) {
        $sql = 'UPDATE modelos SET nome = ?, categoria = ?, descricao = ?, peso_g = ?, tempo_h = ?, material_id = ?';
        $p = [$d['nome'], $d['categoria'], $d['descricao'], $d['peso_g'], $d['tempo_h'], $material];
        if (!empty($d['imagem'])) {
            $sql .= ', imagem = ?';
            $p[] = $d['imagem'];
        }
        $p[] = $d['id'];
        executar($sql . ' WHERE id = ?', $p);
        return (int) $d['id'];
    }
    executar('INSERT INTO modelos (nome, categoria, descricao, peso_g, tempo_h, material_id, imagem) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$d['nome'], $d['categoria'], $d['descricao'], $d['peso_g'], $d['tempo_h'], $material, $d['imagem'] ?? null]);
    return ultimoId();
}

function excluirModelo(int $id): void
{
    executar('DELETE FROM modelos WHERE id = ?', [$id]);
}

function calcularItem(array $item, array $material, array $impressora, float $tarifa, float $falha, float $margem): array
{
    $peso  = (float) $item['peso_g'];
    $horas = (float) $item['tempo_h'];
    $qtd   = max(1, (int) $item['quantidade']);

    $custoMaterial = $peso / 1000 * (float) $material['preco_kg'];
    $custoEnergia  = $horas * (float) $impressora['potencia_w'] / 1000 * $tarifa;
    $custoMaquina  = $horas * ((float) $impressora['valor_compra'] / max(1, (int) $impressora['vida_util_h']));

    $custoUnit = ($custoMaterial + $custoEnergia + $custoMaquina) * (1 + $falha / 100);
    $precoUnit = $custoUnit * (1 + $margem / 100);

    return [
        'custo_material' => round($custoMaterial, 2),
        'custo_energia'  => round($custoEnergia, 2),
        'custo_maquina'  => round($custoMaquina, 2),
        'custo_unit'     => round($custoUnit, 2),
        'preco_unit'     => round($precoUnit, 2),
        'quantidade'     => $qtd,
    ];
}

function salvarOrcamento(array $d, array $itens, int $usuarioId): int
{
    $cfg    = buscarConfig();
    $margem = (float) $d['margem'];
    $falha  = (float) $d['taxa_falha'];
    $tarifa = (float) $cfg['tarifa_kwh'];

    $pdo = conectar();
    $pdo->beginTransaction();
    try {
        executar('INSERT INTO orcamentos (cliente_id, usuario_id, status, margem, taxa_falha, tarifa_kwh, observacoes, sugestao_ia, prazo_entrega)
                  VALUES (?, ?, "pendente", ?, ?, ?, ?, ?, ?)',
            [$d['cliente_id'], $usuarioId, $margem, $falha, $tarifa, $d['observacoes'], $d['sugestao_ia'] ?: null, $d['prazo_entrega'] ?: null]);
        $orcamentoId = ultimoId();

        $custoTotal = 0;
        $precoTotal = 0;
        foreach ($itens as $it) {
            $mat = buscarMaterial((int) $it['material_id']);
            $imp = buscarImpressora((int) $it['impressora_id']);
            if (!$mat || !$imp) {
                throw new RuntimeException('Material ou impressora inválidos em um dos itens.');
            }
            $c = calcularItem($it, $mat, $imp, $tarifa, $falha, $margem);
            executar('INSERT INTO orcamento_itens (orcamento_id, modelo_id, descricao, material_id, impressora_id, quantidade, peso_g, tempo_h,
                                                   custo_material, custo_energia, custo_maquina, custo_unit, preco_unit)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$orcamentoId, !empty($it['modelo_id']) ? $it['modelo_id'] : null, $it['descricao'], $mat['id'], $imp['id'], $c['quantidade'],
                 $it['peso_g'], $it['tempo_h'], $c['custo_material'], $c['custo_energia'], $c['custo_maquina'], $c['custo_unit'], $c['preco_unit']]);
            $custoTotal += $c['custo_unit'] * $c['quantidade'];
            $precoTotal += $c['preco_unit'] * $c['quantidade'];
        }

        executar('UPDATE orcamentos SET custo_total = ?, preco_final = ? WHERE id = ?', [round($custoTotal, 2), round($precoTotal, 2), $orcamentoId]);
        $pdo->commit();
        return $orcamentoId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function listarOrcamentos(string $status = '', string $busca = ''): array
{
    $sql = 'SELECT o.*, c.nome AS cliente_nome, u.nome AS usuario_nome,
                   (SELECT SUM(quantidade) FROM orcamento_itens i WHERE i.orcamento_id = o.id) AS total_pecas
            FROM orcamentos o
            JOIN clientes c ON c.id = o.cliente_id
            JOIN usuarios u ON u.id = o.usuario_id
            WHERE c.nome LIKE ?';
    $p = ["%$busca%"];
    if ($status !== '') {
        $sql .= ' AND o.status = ?';
        $p[] = $status;
    }
    return consultar($sql . ' ORDER BY o.criado_em DESC', $p);
}

function buscarOrcamento(int $id): ?array
{
    return consultarUm('SELECT o.*, c.nome AS cliente_nome, c.email AS cliente_email, c.telefone AS cliente_telefone,
                               c.documento AS cliente_documento, c.cidade AS cliente_cidade, u.nome AS usuario_nome
                        FROM orcamentos o
                        JOIN clientes c ON c.id = o.cliente_id
                        JOIN usuarios u ON u.id = o.usuario_id
                        WHERE o.id = ?', [$id]);
}

function listarItensOrcamento(int $orcamentoId): array
{
    return consultar('SELECT i.*, m.tipo AS material_tipo, m.cor AS material_cor, m.cor_hex, m.marca AS material_marca,
                             p.nome AS impressora_nome
                      FROM orcamento_itens i
                      JOIN materiais m ON m.id = i.material_id
                      JOIN impressoras p ON p.id = i.impressora_id
                      WHERE i.orcamento_id = ?', [$orcamentoId]);
}

function excluirOrcamento(int $id): void
{
    executar('DELETE FROM orcamentos WHERE id = ? AND status IN ("pendente", "cancelado")', [$id]);
}

function registrarMovimentacao(int $materialId, string $tipo, float $gramas, string $motivo, int $usuarioId, ?int $orcamentoId = null): void
{
    $delta = $tipo === 'saida' ? -abs($gramas) : ($tipo === 'entrada' ? abs($gramas) : $gramas);
    executar('UPDATE materiais SET estoque_g = GREATEST(0, estoque_g + ?) WHERE id = ?', [$delta, $materialId]);
    executar('INSERT INTO movimentacoes_estoque (material_id, tipo, quantidade_g, motivo, orcamento_id, usuario_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$materialId, $tipo, $delta, $motivo, $orcamentoId, $usuarioId]);
}

function listarMovimentacoes(int $limite = 40): array
{
    return consultar('SELECT mv.*, m.tipo AS material_tipo, m.cor, m.cor_hex, m.marca, u.nome AS usuario_nome
                      FROM movimentacoes_estoque mv
                      JOIN materiais m ON m.id = mv.material_id
                      LEFT JOIN usuarios u ON u.id = mv.usuario_id
                      ORDER BY mv.criado_em DESC, mv.id DESC LIMIT ' . (int) $limite);
}

function materiaisEmAlerta(): array
{
    return consultar('SELECT * FROM materiais WHERE estoque_g <= estoque_minimo_g ORDER BY (estoque_g / NULLIF(estoque_minimo_g,0))');
}

function consumoDoOrcamento(int $orcamentoId): array
{
    return consultar('SELECT i.material_id, m.tipo, m.cor, m.estoque_g, SUM(i.peso_g * i.quantidade) AS necessario_g
                      FROM orcamento_itens i JOIN materiais m ON m.id = i.material_id
                      WHERE i.orcamento_id = ? GROUP BY i.material_id, m.tipo, m.cor, m.estoque_g', [$orcamentoId]);
}

function alterarStatusOrcamento(int $id, string $novo, int $usuarioId): ?string
{
    $fluxo = [
        'pendente'    => ['aprovado', 'cancelado'],
        'aprovado'    => ['em_producao', 'cancelado'],
        'em_producao' => ['concluido', 'cancelado'],
    ];
    $orc = buscarOrcamento($id);
    if (!$orc) {
        return 'Orçamento não encontrado.';
    }
    if (!in_array($novo, $fluxo[$orc['status']] ?? [], true)) {
        return 'Não é possível mudar de "' . rotuloStatus($orc['status']) . '" para "' . rotuloStatus($novo) . '".';
    }

    $pdo = conectar();
    $pdo->beginTransaction();
    try {
        if ($novo === 'concluido') {
            $faltando = [];
            foreach (consumoDoOrcamento($id) as $c) {
                if ((float) $c['estoque_g'] < (float) $c['necessario_g']) {
                    $faltando[] = $c['tipo'] . ' ' . $c['cor'] . ' (precisa ' . gramas($c['necessario_g']) . ', tem ' . gramas($c['estoque_g']) . ')';
                }
            }
            if ($faltando) {
                $pdo->rollBack();
                return 'Estoque insuficiente: ' . implode('; ', $faltando) . '. Registre uma entrada no Estoque antes de concluir.';
            }
            foreach (consumoDoOrcamento($id) as $c) {
                registrarMovimentacao((int) $c['material_id'], 'saida', (float) $c['necessario_g'], 'Baixa automática — orçamento #' . $id, $usuarioId, $id);
            }
            executar('UPDATE impressoras p JOIN (SELECT impressora_id, SUM(tempo_h * quantidade) h FROM orcamento_itens WHERE orcamento_id = ? GROUP BY impressora_id) x
                      ON x.impressora_id = p.id SET p.horas_uso = p.horas_uso + x.h', [$id]);
            executar('UPDATE orcamentos SET status = ?, concluido_em = NOW() WHERE id = ?', [$novo, $id]);
        } else {
            executar('UPDATE orcamentos SET status = ? WHERE id = ?', [$novo, $id]);
        }
        $pdo->commit();
        return null;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function dadosDashboard(): array
{
    $d = [];
    $d['faturamento_mes'] = (float) (consultarUm('SELECT COALESCE(SUM(preco_final),0) v FROM orcamentos
        WHERE status = "concluido" AND DATE_FORMAT(concluido_em, "%Y-%m") = DATE_FORMAT(NOW(), "%Y-%m")')['v']);
    $d['lucro_mes'] = (float) (consultarUm('SELECT COALESCE(SUM(preco_final - custo_total),0) v FROM orcamentos
        WHERE status = "concluido" AND DATE_FORMAT(concluido_em, "%Y-%m") = DATE_FORMAT(NOW(), "%Y-%m")')['v']);
    $d['em_aberto'] = (int) (consultarUm('SELECT COUNT(*) v FROM orcamentos WHERE status IN ("pendente","aprovado","em_producao")')['v']);
    $d['filamento_mes'] = abs((float) (consultarUm('SELECT COALESCE(SUM(quantidade_g),0) v FROM movimentacoes_estoque
        WHERE tipo = "saida" AND DATE_FORMAT(criado_em, "%Y-%m") = DATE_FORMAT(NOW(), "%Y-%m")')['v']));
    $d['horas_maquina'] = (float) (consultarUm('SELECT COALESCE(SUM(i.tempo_h * i.quantidade),0) v FROM orcamento_itens i
        JOIN orcamentos o ON o.id = i.orcamento_id WHERE o.status = "concluido"')['v']);

    $linhas = consultar('SELECT DATE_FORMAT(concluido_em, "%Y-%m") mes, SUM(preco_final) total, SUM(custo_total) custo
        FROM orcamentos WHERE status = "concluido" AND concluido_em >= DATE_SUB(DATE_FORMAT(NOW(), "%Y-%m-01"), INTERVAL 5 MONTH)
        GROUP BY mes');
    $porMes = array_column($linhas, null, 'mes');
    $nomes = ['01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun',
              '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'];
    $d['meses'] = $d['faturamento'] = $d['custos'] = [];
    for ($i = 5; $i >= 0; $i--) {
        $chave = date('Y-m', strtotime("first day of -$i month"));
        $d['meses'][] = $nomes[substr($chave, 5, 2)];
        $d['faturamento'][] = round((float) ($porMes[$chave]['total'] ?? 0), 2);
        $d['custos'][] = round((float) ($porMes[$chave]['custo'] ?? 0), 2);
    }

    $d['consumo_material'] = consultar('SELECT CONCAT(m.tipo, " ", m.cor) rotulo, m.cor_hex, ABS(SUM(mv.quantidade_g)) gramas
        FROM movimentacoes_estoque mv JOIN materiais m ON m.id = mv.material_id
        WHERE mv.tipo = "saida" GROUP BY m.id, m.tipo, m.cor, m.cor_hex ORDER BY gramas DESC LIMIT 6');
    $d['por_status'] = consultar('SELECT status, COUNT(*) total FROM orcamentos GROUP BY status');
    $d['alertas'] = materiaisEmAlerta();
    $d['impressoras'] = consultar('SELECT * FROM impressoras ORDER BY status, nome');
    $d['ultimos'] = consultar('SELECT o.id, o.status, o.preco_final, o.criado_em, c.nome cliente FROM orcamentos o
        JOIN clientes c ON c.id = o.cliente_id ORDER BY o.criado_em DESC LIMIT 5');
    return $d;
}

function registrarConsultaIA(int $usuarioId, string $pergunta, string $resposta, string $modo): void
{
    executar('INSERT INTO ia_consultas (usuario_id, pergunta, resposta, modo) VALUES (?, ?, ?, ?)', [$usuarioId, $pergunta, $resposta, $modo]);
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

function rotuloStatus(string $s): string
{
    return [
        'pendente' => 'Pendente', 'aprovado' => 'Aprovado', 'em_producao' => 'Em produção',
        'concluido' => 'Concluído', 'cancelado' => 'Cancelado',
        'disponivel' => 'Disponível', 'em_uso' => 'Em uso', 'manutencao' => 'Manutenção',
    ][$s] ?? $s;
}

function badgeStatus(string $s): string
{
    return '<span class="f-status f-status-' . h($s) . '">' . h(rotuloStatus($s)) . '</span>';
}
