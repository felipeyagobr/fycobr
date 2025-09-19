<?php
@session_start();
require_once '../conexao.php';
header('Content-Type: application/json');

$userId = $_SESSION['usuario_id'] ?? 0;
$raspadinhaId = (int)($_POST['raspadinha_id'] ?? 0);

if (!$userId || !$raspadinhaId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Requisição inválida']));
}

$stmt = $pdo->prepare("SELECT valor, chance_de_vitoria FROM raspadinhas WHERE id = ?");
$stmt->execute([$raspadinhaId]);
$raspadinha = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$raspadinha) {
    http_response_code(404);
    exit(json_encode(['error' => 'Raspadinha não encontrada']));
}

// --- INÍCIO DA LÓGICA DE SALDO (REAL PRIMEIRO) E ROLLOVER ---

// 1. Busca todos os saldos do usuário, não apenas o real
$stmt = $pdo->prepare("SELECT saldo, saldo_bonus, rollover_pending, influencer FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    http_response_code(404);
    exit(json_encode(['error' => 'Usuário não encontrado']));
}

// 2. Verifica o saldo TOTAL (real + bônus)
$saldo_total_disponivel = $usuario['saldo'] + $usuario['saldo_bonus'];
$valor_raspadinha = $raspadinha['valor'];

if ($saldo_total_disponivel < $valor_raspadinha) {
    http_response_code(403);
    exit(json_encode(['error' => 'Saldo insuficiente']));
}

$isInfluencer = (int)$usuario['influencer'] === 1;

// 3. LÓGICA DE DÉBITO: GASTA O SALDO REAL PRIMEIRO
if ($usuario['saldo'] >= $valor_raspadinha) {
    // Se o saldo real é suficiente, gasta só dele
    $pdo->prepare("UPDATE usuarios SET saldo = saldo - ? WHERE id = ?")
        ->execute([$valor_raspadinha, $userId]);
} else {
    // Se não for, usa todo o saldo real e o que faltar, tira do bônus
    $restante_do_bonus = $valor_raspadinha - $usuario['saldo'];
    $pdo->prepare("UPDATE usuarios SET saldo = 0, saldo_bonus = saldo_bonus - ? WHERE id = ?")
        ->execute([$restante_do_bonus, $userId]);
}

// 4. ABATE DO ROLLOVER: Qualquer aposta (com real ou bônus) deve contar
if ($usuario['rollover_pending'] > 0) {
    $novo_rollover = $usuario['rollover_pending'] - $valor_raspadinha;

    if ($novo_rollover <= 0) {
        // Rollover completo! Pega o que SOBROU de bônus e transfere para o saldo real
        // Busca o saldo de bônus atualizado ANTES de transferir, pois ele pode ter sido usado agora
        $stmt = $pdo->prepare("SELECT saldo_bonus FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $saldo_bonus_restante = $stmt->fetchColumn();

        $pdo->prepare("UPDATE usuarios SET saldo = saldo + ?, saldo_bonus = 0, rollover_pending = 0 WHERE id = ?")
            ->execute([$saldo_bonus_restante, $userId]);
    } else {
        // Apenas atualiza o valor que falta para cumprir o rollover
        $pdo->prepare("UPDATE usuarios SET rollover_pending = ? WHERE id = ?")
            ->execute([$novo_rollover, $userId]);
    }
}

// --- FIM DA LÓGICA DE SALDO E ROLLOVER ---

// --- INÍCIO DA LÓGICA DE CHANCE DE VITÓRIA ---
$chanceDeVitoria = (int)$raspadinha['chance_de_vitoria'];
$numeroSorteado = mt_rand(1, 100);
$venceuChanceInicial = ($numeroSorteado <= $chanceDeVitoria);
// --- FIM DA LÓGICA DE CHANCE DE VITÓRIA ---


$stmt = $pdo->prepare("SELECT id, probabilidade, valor FROM raspadinha_premios WHERE raspadinha_id = ?");
$stmt->execute([$raspadinhaId]);
$premiosBrutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($premiosBrutos) === 0 && $venceuChanceInicial) {
    http_response_code(500);
    exit(json_encode(['error' => 'Nenhum prêmio configurado para uma rodada vencedora']));
}

if ($venceuChanceInicial) {
    // Se venceu, aplica bônus para influencers se for o caso
    if ($isInfluencer) {
        $premiosBrutos = aplicarBonusInfluencer($premiosBrutos, $raspadinha['valor'], $userId);
    }
}


/**
 * Aplica bonus GENEROSO de probabilidade para influencers
 * Sistema inteligente que aumenta drasticamente as chances de ganho
 * @param array $premios Array de prêmios
 * @param float $custoRaspadinha Valor da raspadinha
 * @param int $userId ID do usuário para verificar histórico
 * @return array Prêmios com probabilidades MUITO melhoradas para influencers
 */
function aplicarBonusInfluencer(array $premios, float $custoRaspadinha, int $userId): array
{
    global $pdo;

    // Verificar últimas 5 jogadas do influencer para análise mais ampla
    $stmt = $pdo->prepare("
        SELECT o.resultado 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $ultimasJogadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Analisar padrão de vitórias/derrotas
    $derrotasConsecutivas = 0;
    $vitoriasUltimas5 = 0;

    foreach ($ultimasJogadas as $index => $resultado) {
        if ($resultado === 'gain') {
            $vitoriasUltimas5++;
            if ($index === 0) break; // Se a última foi vitória, para a contagem de derrotas
        } else {
            if ($index < 3) $derrotasConsecutivas++; // Só conta derrotas recentes
        }
    }

    // Configurações GENEROSAS para influencers
    $configuracao = [
        // Bonus base para influencers (sempre aplicado)
        'bonus_base_influencer' => 8,

        // Bonus por categoria de prêmio
        'bonus_premios_pequenos' => 15,  // 1x a 3x o valor da raspadinha
        'bonus_premios_medios'   => 12,  // 3x a 8x o valor da raspadinha
        'bonus_premios_grandes'  => 8,   // 8x a 15x o valor da raspadinha
        'bonus_premios_mega'     => 4,   // Acima de 15x (ainda tem bonus, mas menor)

        // Bonus por situação
        'bonus_derrotas_consecutivas' => $derrotasConsecutivas * 5, // +5 para cada derrota seguida
        'bonus_poucas_vitorias' => ($vitoriasUltimas5 <= 1) ? 10 : 0, // Se ganhou pouco recentemente

        // Multiplicador geral de sorte
        'multiplicador_sorte' => 1.5 // 50% de bonus geral
    ];

    foreach ($premios as &$premio) {
        $valorPremio = (float)$premio['valor'];
        $multiplicador = $valorPremio / $custoRaspadinha;
        $probabilidadeOriginal = $premio['probabilidade'];

        // Determina categoria do prêmio e aplica bonus correspondente
        $bonusCategoria = 0;
        if ($multiplicador <= 3) {
            $bonusCategoria = $configuracao['bonus_premios_pequenos'];
        } elseif ($multiplicador <= 8) {
            $bonusCategoria = $configuracao['bonus_premios_medios'];
        } elseif ($multiplicador <= 15) {
            $bonusCategoria = $configuracao['bonus_premios_grandes'];
        } else {
            $bonusCategoria = $configuracao['bonus_premios_mega'];
        }

        // Calcula bonus total
        $bonusTotal =
            $configuracao['bonus_base_influencer'] +
            $bonusCategoria +
            $configuracao['bonus_derrotas_consecutivas'] +
            $configuracao['bonus_poucas_vitorias'];

        // Aplica bonus e multiplicador de sorte
        $novaProb = ($probabilidadeOriginal + $bonusTotal) * $configuracao['multiplicador_sorte'];
        $premio['probabilidade'] = max(0.5, $novaProb);
    }

    // Log para acompanhar os ajustes (remover em produção se necessário)
    error_log("Influencer $userId - Derrotas consecutivas: $derrotasConsecutivas, Vitórias últimas 5: $vitoriasUltimas5");

    return $premios;
}

function sortearPremio(array $premios): int
{
    $total = 0;
    foreach ($premios as $p) {
        $total += $p['probabilidade'];
    }

    $rand = mt_rand(0, (int)($total * 100)) / 100;
    $acumulado = 0;

    foreach ($premios as $p) {
        $acumulado += $p['probabilidade'];
        if ($rand <= $acumulado) {
            return (int)$p['id'];
        }
    }

    return (int)$premios[array_key_last($premios)]['id']; // fallback
}

/**
 * Função melhorada para controlar repetições no grid
 * Para influencers: permite mais facilmente combinações vencedoras
 */
function gerarGridEquilibrado(array $premios, bool $isInfluencer, bool $vencedor): array
{
    $grid = [];
    $contagem = [];
    $maxTentativasItem = $isInfluencer ? 100 : 50; // Influencers têm mais tentativas

    // Buscar custo da raspadinha para calcular multiplicadores
    global $pdo, $raspadinhaId;
    $stmt = $pdo->prepare("SELECT valor FROM raspadinhas WHERE id = ?");
    $stmt->execute([$raspadinhaId]);
    $raspadinha = $stmt->fetch(PDO::FETCH_ASSOC);
    $custoRaspadinha = (float)$raspadinha['valor'];

    if (!$vencedor) {
        // Lógica para gerar um grid perdedor
        $idsPremios = array_map(function ($p) {
            return (int)$p['id'];
        }, $premios);

        // Se não houver prêmios (em caso de derrota), preenche com números aleatórios
        if (empty($idsPremios)) {
            for ($i = 0; $i < 9; $i++) {
                $grid[] = $i + 1;
            }
            shuffle($grid);
            return $grid;
        }

        $contagem = array_fill_keys($idsPremios, 0);

        // Preenche o grid aleatoriamente, mas garantindo que não haja 3 itens iguais
        for ($i = 0; $i < 9; $i++) {
            do {
                $itemId = $idsPremios[array_rand($idsPremios)];
            } while (($contagem[$itemId] ?? 0) >= 2);

            $grid[] = $itemId;
            $contagem[$itemId]++;
        }
        shuffle($grid);
        return $grid;
    }


    // Para influencers, só prêmios MUITO altos (acima de 20x) têm restrição
    $premiosRestritivos = [];
    if (!$isInfluencer) {
        // Usuários normais: prêmios acima de 10x são restritivos
        foreach ($premios as $premio) {
            $multiplicador = (float)$premio['valor'] / $custoRaspadinha;
            if ($multiplicador > 10) {
                $premiosRestritivos[] = (int)$premio['id'];
            }
        }
    } else {
        // Influencers: apenas prêmios EXTREMAMENTE altos são restritivos
        foreach ($premios as $premio) {
            $multiplicador = (float)$premio['valor'] / $custoRaspadinha;
            if ($multiplicador > 20) { // Muito mais permissivo para influencers
                $premiosRestritivos[] = (int)$premio['id'];
            }
        }
    }

    // Configurações muito mais permissivas para influencers
    $config = [
        'max_grupos_tres' => $isInfluencer ? 3 : 1, // Influencers podem ter até 3 grupos de 3
        'tentativas_extras' => $isInfluencer ? 50 : 0,
        'premios_restritivos' => $premiosRestritivos,
        'max_repeticoes_especiais' => $isInfluencer ? 3 : 2 // Influencers podem repetir mais
    ];

    for ($i = 0; $i < 9; $i++) {
        $tentativas = 0;
        $maxTentativas = $maxTentativasItem + $config['tentativas_extras'];

        do {
            $itemId = sortearPremio($premios);
            $tentativas++;

            $countItem = $contagem[$itemId] ?? 0;
            $gruposTres = 0;
            foreach ($contagem as $count) {
                if ($count >= 3) {
                    $gruposTres++;
                }
            }

            // Regras mais flexíveis para influencers
            $isPremioRestritivo = in_array($itemId, $config['premios_restritivos']);
            $maxRepeticoesItem = $isPremioRestritivo ? $config['max_repeticoes_especiais'] : 3;

            // Para influencers, permite mais grupos de 3
            $limiteRepeticoes = ($gruposTres >= $config['max_grupos_tres']) ? 2 : $maxRepeticoesItem;
            $ok = ($countItem < $limiteRepeticoes);

            if ($tentativas > $maxTentativas) {
                $itemId = encontrarItemSeguro($premios, $contagem, $limiteRepeticoes, $config['premios_restritivos']);
                break;
            }
        } while (!$ok);

        $grid[] = $itemId;
        $contagem[$itemId] = ($contagem[$itemId] ?? 0) + 1;
    }

    return $grid;
}

/**
 * Encontra um item que pode ser usado sem quebrar as regras
 * Mais permissivo para influencers
 */
function encontrarItemSeguro(array $premios, array $contagem, int $limiteRepeticoes, array $premiosRestritivos = []): int
{
    // Para influencers, tenta primeiro os prêmios de valor
    foreach ($premios as $premio) {
        $id = (int)$premio['id'];
        $count = $contagem[$id] ?? 0;
        $isPremioRestritivo = in_array($id, $premiosRestritivos);

        // Se é um prêmio bom e pode ser usado
        if (!$isPremioRestritivo && $count < $limiteRepeticoes && $premio['valor'] > 0) {
            return $id;
        }
    }

    // Depois tenta qualquer prêmio que não seja restritivo
    foreach ($premios as $premio) {
        $id = (int)$premio['id'];
        $count = $contagem[$id] ?? 0;
        $isPremioRestritivo = in_array($id, $premiosRestritivos);

        if (!$isPremioRestritivo && $count < $limiteRepeticoes) {
            return $id;
        }
    }

    // Se só restam restritivos, usa um com menos repetições
    foreach ($premios as $premio) {
        $id = (int)$premio['id'];
        $count = $contagem[$id] ?? 0;
        if ($count < 2) {
            return $id;
        }
    }

    // Última opção
    return (int)$premios[0]['id'];
}

// Gera o grid usando a função melhorada
$grid = gerarGridEquilibrado($premiosBrutos, $isInfluencer, $venceuChanceInicial);

$stmt = $pdo->prepare("INSERT INTO orders (user_id, raspadinha_id, premios_json) VALUES (?, ?, ?)");
$stmt->execute([$userId, $raspadinhaId, json_encode($grid)]);
$orderId = $pdo->lastInsertId();

// Log detalhado para influencers
if ($isInfluencer && $venceuChanceInicial) {
    $totalProbabilidade = array_sum(array_column($premiosBrutos, 'probabilidade'));
    error_log("Influencer ID $userId - Total probabilidade: $totalProbabilidade - Grid: " . json_encode($grid));
}

// Busca os saldos finais para retornar a informação correta
$stmt = $pdo->prepare("SELECT saldo, saldo_bonus FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$saldos_finais = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success'        => true,
    'order_id'       => $orderId,
    'grid'           => $grid,
    'saldo_novo'     => $saldos_finais['saldo'], // Retorna o saldo REAL atualizado
    'saldo_bonus'    => $saldos_finais['saldo_bonus'], // Retorna o saldo de BÔNUS atualizado
    'influencer'     => $isInfluencer
]);
