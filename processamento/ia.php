<?php
require_once __DIR__ . '/sessao.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !usuarioLogado()) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}
if (!hash_equals(tokenCSRF(), $_POST['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sessão expirada. Recarregue a página.']);
    exit;
}

$descricao = mb_substr(trim((string) ($_POST['descricao'] ?? '')), 0, 300);
if (mb_strlen($descricao) < 3) {
    echo json_encode(['erro' => 'Diga qual peça o cliente quer, por exemplo: suporte de celular.']);
    exit;
}

$materiais = consultar('SELECT id, tipo, cor, estoque_g FROM materiais ORDER BY tipo');
$listaMateriais = implode("\n", array_map(
    fn($m) => "- id {$m['id']}: {$m['tipo']} {$m['cor']} ({$m['estoque_g']} g em estoque)", $materiais));

$instrucoes = <<<TXT
Você é o "Assistente da Forja" da FORG3D, especialista em impressão 3D FDM.
O usuário escreve só o nome da peça, às vezes com 2 ou 3 palavras. Deduza o uso
mais provável e sugira de 2 a 3 opções de filamento, da mais indicada para a menos.
Cada opção deve atender a um cenário diferente (ex.: uso em casa, uso externo, peça flexível).
Escolha SOMENTE materiais do estoque abaixo (use o id). Prefira materiais com estoque suficiente.

Estoque atual:
$listaMateriais

Responda APENAS com um objeto JSON válido, sem texto antes ou depois, neste formato:
{"peca": "nome curto da peça em português",
 "uso": "uso provável em uma frase",
 "termos_busca": "2 a 4 palavras em inglês para buscar modelos prontos",
 "peso_estimado_g": 0, "tempo_estimado_h": 0,
 "opcoes": [{"material_id": 0, "material": "tipo e cor", "indicado_para": "cenário em poucas palavras",
   "preenchimento": "ex.: 20% giroide", "camada": "ex.: 0,20 mm", "paredes": "ex.: 3",
   "suportes": "Sim/Não", "temperatura": "bico/mesa, ex.: 240 °C / 80 °C", "motivo": "1 frase"}],
 "observacoes": "dica prática curta"}
Peso e tempo são estimativas para UMA unidade de tamanho comum (números).
TXT;

$resposta = null;
$modo = 'demo';
$aviso = null;

if (GEMINI_API_KEY !== '') {
    $corpo = [
        'systemInstruction' => ['parts' => [['text' => $instrucoes]]],
        'contents'          => [['role' => 'user', 'parts' => [['text' => 'Peça: ' . $descricao]]]],
        'generationConfig'  => ['responseMimeType' => 'application/json', 'temperature' => 0.4],
    ];
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODELO . ':generateContent';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 40,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . GEMINI_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => json_encode($corpo),
    ]);
    if (GEMINI_CA_BUNDLE !== '') {
        curl_setopt($ch, CURLOPT_CAINFO, GEMINI_CA_BUNDLE);
    }
    $bruto = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($bruto === false) {
        $aviso = 'Não consegui falar com a API do Gemini (' . $erroCurl . '). Mostrando modo demonstração.';
    } elseif ($status !== 200) {
        $msg = json_decode($bruto, true)['error']['message'] ?? ('HTTP ' . $status);
        $aviso = 'A API do Gemini recusou a chamada: ' . $msg . ' — mostrando modo demonstração.';
    } else {
        $json = json_decode($bruto, true);
        $partes = $json['candidates'][0]['content']['parts'] ?? [];
        $textoIA = implode('', array_column($partes, 'text'));
        $resposta = extrairJson($textoIA);
        if ($resposta && !empty($resposta['opcoes']) && is_array($resposta['opcoes'])) {
            $modo = 'gemini';
        } else {
            $resposta = null;
            $aviso = 'O Gemini respondeu fora do formato esperado. Mostrando modo demonstração.';
        }
    }
}

if (!$resposta) {
    $resposta = respostaDemo($descricao, $materiais);
}

$ids = array_map('intval', array_column($materiais, 'id'));
$opcoes = [];
foreach (array_slice($resposta['opcoes'], 0, 3) as $op) {
    if (!is_array($op)) {
        continue;
    }
    if (!in_array((int) ($op['material_id'] ?? 0), $ids, true)) {
        $op['material_id'] = 0;
    }
    $opcoes[] = $op;
}
$resposta['opcoes'] = $opcoes;

$termos = trim((string) ($resposta['termos_busca'] ?? '')) ?: $descricao;
$resposta['termos_busca'] = $termos;
$q = rawurlencode($termos);
$resposta['modelos'] = [
    ['site' => 'Printables', 'url' => "https://www.printables.com/search/models?q=$q"],
    ['site' => 'Thingiverse', 'url' => "https://www.thingiverse.com/search?q=$q&type=things"],
    ['site' => 'MakerWorld', 'url' => "https://makerworld.com/en/search/models?keyword=$q"],
];

registrarConsultaIA(usuarioLogado()['id'], $descricao, json_encode($resposta, JSON_UNESCAPED_UNICODE), $modo);

echo json_encode(['modo' => $modo, 'aviso' => $aviso, 'sugestao' => $resposta], JSON_UNESCAPED_UNICODE);

function extrairJson(string $texto): ?array
{
    if (preg_match('/\{.*\}/s', $texto, $m)) {
        $dados = json_decode($m[0], true);
        return is_array($dados) ? $dados : null;
    }
    return null;
}

function respostaDemo(string $descricao, array $materiais): array
{
    $t = mb_strtolower($descricao);
    $tem = fn(array $palavras) => (bool) array_filter($palavras, fn($p) => str_contains($t, $p));

    $perfis = [
        'PLA'  => ['em casa, decoração e uso leve', '15% grade', '0,16 mm', '3', 'Não', '210 °C / 60 °C', 'Ótimo acabamento, barato e fácil de imprimir.'],
        'PETG' => ['peça funcional, que leva esforço', '30% giroide', '0,20 mm', '4', 'Não', '240 °C / 80 °C', 'Mais resistente a impacto e a calor que o PLA.'],
        'ASA'  => ['uso externo, sol e carro', '40% giroide', '0,20 mm', '4', 'Não', '250 °C / 100 °C', 'Resiste a raios UV e calor sem amarelar nem deformar.'],
        'ABS'  => ['perto de calor ou motor', '35% cúbico', '0,20 mm', '4', 'Não', '245 °C / 100 °C', 'Aguenta temperatura alta sem amolecer.'],
        'TPU'  => ['peça flexível ou que amortece', '15% giroide', '0,20 mm', '3', 'Não', '225 °C / 50 °C', 'Flexível e resistente ao desgaste.'],
    ];

    if ($tem(['capa', 'borracha', 'flex', 'amortec', 'pé de', 'vedação', 'vedacao', 'pulseira'])) {
        $ordem = ['TPU', 'PETG', 'PLA'];
    } elseif ($tem(['carro', 'sol', 'extern', 'jardim', 'chuva', 'moto'])) {
        $ordem = ['ASA', 'PETG', 'ABS'];
    } elseif ($tem(['motor', 'quente', 'calor', 'forno'])) {
        $ordem = ['ABS', 'ASA', 'PETG'];
    } elseif ($tem(['engrenagem', 'suporte', 'gancho', 'trava', 'encaixe', 'dobradiça', 'peça de', 'organizador'])) {
        $ordem = ['PETG', 'PLA', 'ASA'];
    } else {
        $ordem = ['PLA', 'PETG', 'TPU'];
    }

    $opcoes = [];
    foreach ($ordem as $tipo) {
        $candidatos = array_filter($materiais, fn($m) => stripos($m['tipo'], $tipo) === 0);
        usort($candidatos, fn($a, $b) => $b['estoque_g'] <=> $a['estoque_g']);
        $m = $candidatos[0] ?? null;
        if (!$m) {
            continue;
        }
        [$indicado, $infill, $camada, $paredes, $suportes, $temp, $motivo] = $perfis[$tipo];
        $opcoes[] = [
            'material_id'   => $m['id'],
            'material'      => $m['tipo'] . ' ' . $m['cor'],
            'indicado_para' => $indicado,
            'preenchimento' => $infill,
            'camada'        => $camada,
            'paredes'       => $paredes,
            'suportes'      => $suportes,
            'temperatura'   => $temp,
            'motivo'        => $motivo,
        ];
    }

    $dicionario = [
        'suporte de celular' => 'phone stand', 'suporte' => 'holder stand', 'celular' => 'phone', 'capa' => 'case cover',
        'controle' => 'remote', 'vaso' => 'vase', 'chaveiro' => 'keychain', 'engrenagem' => 'gear', 'organizador' => 'organizer',
        'gancho' => 'hook', 'caixa' => 'box', 'porta' => 'holder', 'fone' => 'headphone', 'carro' => 'car', 'parede' => 'wall',
        'mesa' => 'desk', 'cabo' => 'cable', 'luminária' => 'lamp', 'luminaria' => 'lamp', 'brinquedo' => 'toy', 'miniatura' => 'miniature',
    ];
    $termos = $t;
    foreach ($dicionario as $pt => $en) {
        $termos = str_replace($pt, $en, $termos);
    }
    $termos = trim(preg_replace('/\b(de|da|do|para|pra|com|um|uma|o|a)\b/u', ' ', $termos));
    $termos = preg_replace('/\s+/', ' ', $termos);

    return [
        'peca'             => mb_strtoupper(mb_substr($descricao, 0, 1)) . mb_substr($descricao, 1),
        'uso'              => 'Sugestões por regras locais: descreva o uso para refinar.',
        'termos_busca'     => $termos,
        'peso_estimado_g'  => 50,
        'tempo_estimado_h' => 3,
        'opcoes'           => $opcoes,
        'observacoes'      => 'Baixe um modelo pronto nos links acima e confira peso e tempo no fatiador.',
    ];
}
