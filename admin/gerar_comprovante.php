<?php
/**
 * Comprovante de Aposta
 * 
 * Este script gera um comprovante para uma aposta específica.
 * 
 * Parâmetros:
 * - usuario_id: ID do usuário que fez a aposta (obrigatório)
 * - jogo: Nome do jogo apostado (obrigatório)
 * - aposta_id: ID específico da aposta (opcional)
 *   Se fornecido, exibirá apenas essa aposta específica.
 *   Se não fornecido, exibirá a aposta mais recente.
 * 
 * Exemplo de uso:
 * gerar_comprovante.php?usuario_id=123&jogo=Dia+de+Sorte&aposta_id=456
 */

// Limpar qualquer saída anterior
ob_clean();
if (ob_get_length()) ob_end_clean();

// Prevenir qualquer saída antes do PDF
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Garante que nenhum conteúdo foi enviado antes
if (headers_sent()) {
    die('Já foram enviados headers');
}

// Inicia o buffer de saída
ob_start();

session_start();
require_once '../config/database.php';

// Função para transformar o nome do jogo em um nome de classe CSS válido
function sanitizeClassName($nome) {
    // Converter para minúsculas
    $nome = strtolower($nome);
    // Remover acentos
    $nome = preg_replace('/[áàãâä]/u', 'a', $nome);
    $nome = preg_replace('/[éèêë]/u', 'e', $nome);
    $nome = preg_replace('/[íìîï]/u', 'i', $nome);
    $nome = preg_replace('/[óòõôö]/u', 'o', $nome);
    $nome = preg_replace('/[úùûü]/u', 'u', $nome);
    $nome = preg_replace('/[ç]/u', 'c', $nome);
    // Substituir espaços e outros caracteres por traços
    $nome = preg_replace('/[^a-z0-9]/', '-', $nome);
    // Remover traços consecutivos
    $nome = preg_replace('/-+/', '-', $nome);
    // Remover traços no início e no fim
    $nome = trim($nome, '-');
    
    return $nome;
}

// Verifica se é admin ou revendedor
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'revendedor'])) {
    header("Location: ../login.php");
    exit();
}

// Validar parâmetros
if (!isset($_GET['usuario_id']) || !isset($_GET['jogo'])) {
    die('Parâmetros inválidos');
}

$usuario_id = (int)$_GET['usuario_id'];
$jogo = $_GET['jogo'];

// Modificar a consulta para buscar uma aposta específica se o parâmetro aposta_id for fornecido
$aposta_id = isset($_GET['aposta_id']) ? (int)$_GET['aposta_id'] : null;

// Debug
error_log("Buscando apostas para usuário_id: " . $usuario_id . " e jogo: " . $jogo);

// Adicionar condição de ID da aposta, se fornecido
$sql = "SELECT 
            ai.*, 
            u.nome as apostador, 
            r.nome as revendedor, 
            COALESCE(j.nome, ai.jogo_nome) as jogo_nome
        FROM apostas_importadas ai
        LEFT JOIN usuarios u ON ai.usuario_id = u.id
        LEFT JOIN usuarios r ON ai.revendedor_id = r.id
        LEFT JOIN jogos j ON j.titulo_importacao = ai.jogo_nome
        WHERE ai.usuario_id = :usuario_id 
        AND (
            ai.jogo_nome = :jogo 
            OR ai.jogo_nome LIKE :jogo_mobile
            OR j.nome = :jogo_nome
        )";

// Se for fornecido um ID específico de aposta, adiciona a condição
if ($aposta_id) {
    $sql .= " AND ai.id = :aposta_id";
}

$sql .= " ORDER BY ai.created_at DESC";

$stmt = $pdo->prepare($sql);
$params = [
    ':usuario_id' => $usuario_id,
    ':jogo' => $jogo,
    ':jogo_mobile' => "Loterias Mobile: %" . $jogo,
    ':jogo_nome' => $jogo
];

// Adiciona o parâmetro de aposta_id se estiver definido
if ($aposta_id) {
    $params[':aposta_id'] = $aposta_id;
}

$stmt->execute($params);
$apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug
error_log("SQL: " . $sql);
error_log("Parâmetros: " . json_encode($params));
error_log("Apostas encontradas: " . count($apostas));

if (empty($apostas)) {
    // Tentar buscar em apostas normais
    $sql = "SELECT 
                a.*, 
                u.nome as apostador,
                r.nome as revendedor,
                j.nome as jogo_nome,
                a.valor_aposta,
                a.numeros
            FROM apostas a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN usuarios r ON u.revendedor_id = r.id
            LEFT JOIN jogos j ON a.tipo_jogo_id = j.id
            WHERE a.usuario_id = :usuario_id 
            AND (j.nome = :jogo OR j.titulo_importacao = :jogo)";
    
    // Se for fornecido um ID específico de aposta, adiciona a condição
    if ($aposta_id) {
        $sql .= " AND a.id = :aposta_id";
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $params = [
        ':usuario_id' => $usuario_id,
        ':jogo' => $jogo
    ];
    
    // Adiciona o parâmetro de aposta_id se estiver definido
    if ($aposta_id) {
        $params[':aposta_id'] = $aposta_id;
    }
    
    $stmt->execute($params);
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug
    error_log("Buscando em apostas normais");
    error_log("Apostas normais encontradas: " . count($apostas));
}

if (empty($apostas)) {
    die('Apostas não encontradas para o usuário ' . $usuario_id . ' e jogo ' . $jogo);
}

// Usar a primeira aposta para informações gerais
$aposta = $apostas[0];

// Antes de buscar o concurso
error_log("Tentando buscar concurso para o jogo: " . $jogo);

// Inicializar a variável para o número do concurso
$numero_concurso = 'Próximo'; // Valor padrão

try {
    // 1. Verificar se há um concurso explicitamente indicado nas apostas
    if (isset($aposta['concurso']) && !empty($aposta['concurso'])) {
        $numero_concurso = $aposta['concurso'];
        error_log("Concurso encontrado diretamente na aposta: " . $numero_concurso);
    } 
    // 2. Busca específica pelo nome exato do jogo
    else {
        $sql_direto = "SELECT c.numero_concurso 
                      FROM concursos c 
                      JOIN jogos j ON c.jogo_id = j.id 
                      WHERE j.nome = :nome_exato 
                      ORDER BY c.created_at DESC 
                      LIMIT 1";
        $stmt_direto = $pdo->prepare($sql_direto);
        $stmt_direto->execute([':nome_exato' => $jogo]);
        $resultado_direto = $stmt_direto->fetch(PDO::FETCH_ASSOC);
        
        error_log("Resultado da busca direta: " . json_encode($resultado_direto));
        
        if ($resultado_direto && !empty($resultado_direto['numero_concurso'])) {
            $numero_concurso = $resultado_direto['numero_concurso'];
            error_log("Concurso encontrado (busca direta): " . $numero_concurso);
        } 
        // 3. Tentar com LIKE para correspondência parcial
        else {
            $sql_like = "SELECT c.numero_concurso, j.nome as nome_jogo
                        FROM concursos c 
                        JOIN jogos j ON c.jogo_id = j.id 
                        WHERE j.nome LIKE :nome_parcial 
                        ORDER BY c.created_at DESC 
                        LIMIT 1";
            $stmt_like = $pdo->prepare($sql_like);
            $stmt_like->execute([':nome_parcial' => '%' . $jogo . '%']);
            $resultado_like = $stmt_like->fetch(PDO::FETCH_ASSOC);
            
            error_log("Resultado da busca com LIKE: " . json_encode($resultado_like));
            
            if ($resultado_like && !empty($resultado_like['numero_concurso'])) {
                $numero_concurso = $resultado_like['numero_concurso'];
                error_log("Concurso encontrado (busca LIKE): " . $numero_concurso . " para o jogo " . $resultado_like['nome_jogo']);
            } 
            // 4. Usar algoritmo de similaridade de texto
            else {
                // Debug: mostrar todos os jogos na tabela
                $todos_jogos = $pdo->query("SELECT id, nome FROM jogos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
                error_log("Todos os jogos disponíveis: " . json_encode($todos_jogos));
                
                // Tentar buscar o concurso pelo nome mais similar
                $melhor_match = null;
                $maior_similaridade = 0;
                
                foreach ($todos_jogos as $jogo_db) {
                    $similaridade = similar_text($jogo, $jogo_db['nome'], $porcentagem);
                    if ($porcentagem > $maior_similaridade) {
                        $maior_similaridade = $porcentagem;
                        $melhor_match = $jogo_db;
                    }
                }
                
                if ($melhor_match && $maior_similaridade > 70) {
                    error_log("Melhor correspondência: {$melhor_match['nome']} (similaridade: {$maior_similaridade}%)");
                    
                    // Buscar concurso para o jogo com melhor correspondência
                    $sql_match = "SELECT c.numero_concurso 
                                FROM concursos c 
                                WHERE c.jogo_id = :jogo_id 
                                ORDER BY c.created_at DESC 
                                LIMIT 1";
                    $stmt_match = $pdo->prepare($sql_match);
                    $stmt_match->execute([':jogo_id' => $melhor_match['id']]);
                    $resultado_match = $stmt_match->fetch(PDO::FETCH_ASSOC);
                    
                    if ($resultado_match && !empty($resultado_match['numero_concurso'])) {
                        $numero_concurso = $resultado_match['numero_concurso'];
                        error_log("Concurso encontrado para jogo similar: " . $numero_concurso);
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Erro ao buscar concurso: " . $e->getMessage());
    $numero_concurso = 'Próximo';
}

// Adicionar o número do concurso aos dados da aposta
if ($numero_concurso !== 'Próximo') {
    // Converter para inteiro para remover zeros à esquerda e garantir formato numérico
    $numero_concurso = intval($numero_concurso);
}
$aposta['numero_concurso'] = $numero_concurso;

// Buscar informações do jogo e valores específicos para o número de dezenas
$sql_jogo = "SELECT 
                j.id,
                j.nome, 
                j.titulo_importacao,
                COALESCE(vj.valor_premio, 1600.00) as valor_premio_padrao,
                COALESCE(vj.dezenas, 15) as dezenas_padrao,
                COALESCE(vj.valor_aposta, 2.00) as valor_aposta_padrao
            FROM jogos j
            LEFT JOIN valores_jogos vj ON j.id = vj.jogo_id
            WHERE j.nome = :jogo 
            OR j.titulo_importacao = :jogo
            LIMIT 1";

$stmt_jogo = $pdo->prepare($sql_jogo);
$stmt_jogo->execute([':jogo' => $jogo]);
$info_jogo = $stmt_jogo->fetch(PDO::FETCH_ASSOC);

if (!$info_jogo) {
    // Valores padrão caso não encontre na tabela
    $info_jogo = [
        'nome' => $jogo,
        'valor_premio_padrao' => 1600.00,
        'dezenas_padrao' => 15,
        'valor_aposta_padrao' => 2.00
    ];
}

// Debug
error_log("Informações do jogo: " . json_encode($info_jogo));

// Verificar se temos uma aposta criada através da página criar_aposta.php
$e_aposta_criada = false;
if (isset($aposta['origem']) && $aposta['origem'] == 'criar_aposta') {
    $e_aposta_criada = true;
    error_log("Esta é uma aposta criada através da página criar_aposta.php");
} else if (isset($aposta['numeros']) && strpos($aposta['numeros'], ',') !== false) {
    // Verificar o formato típico das apostas criadas na interface
    $e_aposta_criada = true;
    error_log("Esta parece ser uma aposta criada através da interface (formato de números com vírgulas)");
}

// Calcular valor total das apostas corretamente
$valor_total_apostas = 0;
$valor_premio_total = 0;

foreach ($apostas as $aposta_item) {
    // Usar o valor da aposta do banco de dados se disponível
    $valor_desta_aposta = isset($aposta_item['valor_aposta']) && !empty($aposta_item['valor_aposta']) 
        ? floatval($aposta_item['valor_aposta']) 
        : $info_jogo['valor_aposta_padrao'];
    
    // Usar o valor premio do banco de dados se disponível
    $valor_premio_desta_aposta = isset($aposta_item['valor_premio']) && !empty($aposta_item['valor_premio']) 
        ? floatval($aposta_item['valor_premio']) 
        : $info_jogo['valor_premio_padrao'];
    
    $valor_total_apostas += $valor_desta_aposta;
    $valor_premio_total += $valor_premio_desta_aposta;
    
    // Log para diagnóstico
    error_log("Aposta ID: " . $aposta_item['id'] . " - Valor: " . $valor_desta_aposta . " - Prêmio: " . $valor_premio_desta_aposta);
}

// Para apostas criadas através da página criar_aposta.php, podemos confiar nos valores fornecidos
$valor_por_aposta = $e_aposta_criada && isset($apostas[0]['valor_aposta']) && !empty($apostas[0]['valor_aposta']) 
    ? floatval($apostas[0]['valor_aposta']) 
    : $info_jogo['valor_aposta_padrao'];

$valor_premio_unitario = $e_aposta_criada && isset($apostas[0]['valor_premio']) && !empty($apostas[0]['valor_premio']) 
    ? floatval($apostas[0]['valor_premio']) 
    : $info_jogo['valor_premio_padrao'];

// Melhorar a função de extração de números
function extrairNumeros($texto_numeros) {
    $numeros_aposta = [];
    
    // Se a entrada for vazia ou inválida
    if (empty($texto_numeros)) {
        error_log("Aviso: texto de números vazio");
        return $numeros_aposta;
    }
    
    // Verificar se é um array serializado (possível formato usado em criar_aposta.php)
    if (preg_match('/^a:\d+:{/', $texto_numeros)) {
        try {
            $dados = unserialize($texto_numeros);
            if (is_array($dados)) {
                error_log("Números extraídos de array serializado: " . implode(',', $dados));
                return $dados; // Já é um array de números
            }
        } catch (Exception $e) {
            error_log("Erro ao unserialize: " . $e->getMessage());
        }
    }
    
    // Verificar se temos o formato de criar_aposta.php (números em formato CSV)
    if (strpos($texto_numeros, ',') !== false) {
        // Estamos lidando com uma aposta normal no formato CSV
        $temp = array_map('trim', explode(',', $texto_numeros));
        foreach ($temp as $num) {
            if (is_numeric($num)) {
                $numeros_aposta[] = intval($num);
            }
        }
        error_log("Números extraídos de CSV: " . implode(',', $numeros_aposta));
        if (!empty($numeros_aposta)) {
            return $numeros_aposta;
        }
    }
    
    // Verificar se temos formato JSON de criar_aposta.php
    if (substr($texto_numeros, 0, 1) === '[' && substr($texto_numeros, -1) === ']') {
        try {
            $dados = json_decode($texto_numeros, true);
            if (is_array($dados)) {
                $numeros_aposta = array_map('intval', $dados);
                error_log("Números extraídos de JSON: " . implode(',', $numeros_aposta));
                return $numeros_aposta;
            }
        } catch (Exception $e) {
            error_log("Erro ao decodificar JSON: " . $e->getMessage());
        }
    }
    
    // Verificar se temos espaços (formato alternativo usado na página de criar apostas)
    if (preg_match_all('/\b\d+\b/', $texto_numeros, $matches)) {
        $numeros_aposta = array_map('intval', $matches[0]);
        error_log("Números extraídos de regex pattern: " . implode(',', $numeros_aposta));
        if (!empty($numeros_aposta)) {
            return $numeros_aposta;
        }
    }
    
    // Se for um formato de importação (com linhas múltiplas)
    if (strpos($texto_numeros, "\n") !== false) {
        $linhas = explode("\n", $texto_numeros);
        if (count($linhas) > 1) {
            // Ignorar a primeira linha (título)
            $texto_processado = implode(" ", array_slice($linhas, 1));
            // Extrair todos os números
            if (preg_match_all('/\d+/', $texto_processado, $matches)) {
                $numeros_aposta = array_map('intval', $matches[0]);
                error_log("Números extraídos de linhas múltiplas: " . implode(',', $numeros_aposta));
                return $numeros_aposta;
            }
        }
    }
    
    // Tenta extrair qualquer número como última opção
    if (preg_match_all('/\d+/', $texto_numeros, $matches)) {
        $numeros_aposta = array_map('intval', $matches[0]);
        error_log("Números extraídos como último recurso: " . implode(',', $numeros_aposta));
    }
    
    // Ordenar numericamente
    sort($numeros_aposta, SORT_NUMERIC);
    
    return $numeros_aposta;
}

// Calcular corretamente o valor do prêmio
function calcularValorPremio($jogo_nome, $numeros_aposta, $pdo) {
    // Buscar informações específicas deste jogo
    $stmt = $pdo->prepare("SELECT * FROM jogos WHERE nome = ? LIMIT 1");
    $stmt->execute([$jogo_nome]);
    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$jogo) {
        error_log("Jogo não encontrado para cálculo de prêmio: " . $jogo_nome);
        return 0;
    }
    
    // Verificar se temos valor de premiação definido para a quantidade de números
    $qtd_numeros = count($numeros_aposta);
    $stmt = $pdo->prepare("SELECT valor_premio FROM valores_jogos WHERE jogo_id = ? AND dezenas = ? LIMIT 1");
    $stmt->execute([$jogo['id'], $qtd_numeros]);
    $valor_especifico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($valor_especifico) {
        return floatval($valor_especifico['valor_premio']);
    }
    
    // Se não encontrou, usa o valor padrão do jogo
    return floatval($jogo['premio'] ?? 0);
}

// Extrair os números da aposta para usar no cálculo do prêmio
$numeros_aposta = extrairNumeros($aposta['numeros']);

// Se temos um valor explícito de prêmio na aposta, usamos ele
if (isset($aposta['valor_premio']) && !empty($aposta['valor_premio'])) {
    $valor_premio = floatval($aposta['valor_premio']);
} 
// Se extraímos números com sucesso, calculamos o prêmio baseado nestes números
else if (!empty($numeros_aposta)) {
    $valor_premio = calcularValorPremio($aposta['jogo_nome'], $numeros_aposta, $pdo);
}
// Caso contrário, usa o valor padrão do jogo
else {
    $valor_premio = floatval($info_jogo['valor_premio_padrao'] ?? 0);
}

// Log para depuração
error_log("Valor do prêmio calculado: " . $valor_premio);

// Log para depuração
error_log("Valor por aposta: " . $valor_por_aposta);
error_log("Valor prêmio unitário: " . $valor_premio_unitario);
error_log("Valor total apostas: " . $valor_total_apostas);
error_log("Valor prêmio total: " . $valor_premio_total);

// Calcular o ganho máximo (soma de todas as apostas vezes premiação)
$ganho_maximo = count($apostas) * $info_jogo['valor_premio_padrao'];

// Debug para verificar o que está sendo extraído
error_log("Extração de números - dados da primeira aposta:");
error_log("Numeros brutos: " . $apostas[0]['numeros']);
error_log("Número do concurso: " . $aposta['numero_concurso']);
error_log("Valor total calculado: " . $valor_total_apostas);

// Debug final
error_log("Número do concurso definido: " . $aposta['numero_concurso']);

// Definir as cores padrão para cada tipo de jogo
$cores_jogos = [
    'dia-de-sorte' => '#fd7e14',  // Laranja
    'lotofacil' => '#9c27b0',     // Roxo
    'lotomania' => '#fd7e14',     // Laranja
    'mais-milionaria' => '#9c27b0', // Roxo
    'mega-sena' => '#209869',     // Verde
    'quina' => '#260085',         // Azul escuro
    'timemania' => '#209869',     // Verde
    'dupla-sena' => '#dc3545'     // Vermelho
];

// Função para obter a cor do jogo
function getCorJogo($nome_jogo) {
    global $cores_jogos;
    $classe = sanitizeClassName($nome_jogo);
    return $cores_jogos[$classe] ?? '#007bff'; // Azul como cor padrão
}

// CSS dinâmico para os jogos
$css_dinamico = '';
foreach ($cores_jogos as $jogo_classe => $cor) {
    $css_dinamico .= "
    .{$jogo_classe} .number {
        background-color: {$cor};
    }";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprovante de Aposta - <?php echo $jogo; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            position: relative;
            color: #333;
            overflow: hidden;
        }
        .container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                45deg,
                rgba(0, 123, 255, 0.03),
                rgba(0, 123, 255, 0.03) 10px,
                rgba(0, 123, 255, 0.06) 10px,
                rgba(0, 123, 255, 0.06) 20px
            );
            z-index: -1;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(to right, #0056b3, #007bff);
            border-radius: 10px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .header::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/></svg>');
            opacity: 0.3;
            z-index: 0;
        }
        .logo {
            max-width: 200px;
            width: 100%;
            height: auto;
            margin: 0 auto;
            display: block;
            padding: 10px;
            position: relative;
            z-index: 1;
        }
        .header h2 {
            color: white;
            margin: 10px 0 0;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
        }
        .concurso-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 5px 15px;
            color: white;
            font-weight: bold;
            z-index: 2;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .info-block {
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            border: 2px dashed #007bff;
            box-shadow: 0 0 5px rgba(0,0,0,0.05);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-row strong {
            color: #0056b3;
        }
        .aposta-grupo {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
        }
        .aposta-grupo h4 {
            color: #0056b3;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        .numbers {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin: 15px 0;
        }
        .number {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #007bff;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            margin: 5px;
            font-size: 16px;
        }
        .number.selected {
            background: #007bff; /* Cor azul como na página criar_aposta */
        }
        /* Estilo específico para apostas de Dia de Sorte (em laranja) */
        .dia-de-sorte .number {
            background: #fd7e14;
        }
        /* Estilo específico para apostas de Lotofácil (em rosa/roxo) */
        .lotofacil .number {
            background: #9c27b0;
        }
        /* Estilo específico para apostas de Lotomania (em laranja) */
        .lotomania .number {
            background: #fd7e14;
        }
        /* Estilo específico para apostas de Mais Milionária (em roxo) */
        .mais-milionaria .number {
            background: #9c27b0;
        }
        .game-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            position: relative;
        }
        .game-info::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><circle cx="20" cy="20" r="15" fill="none" stroke="rgba(0,123,255,0.1)" stroke-width="2"/></svg>');
            opacity: 0.4;
            z-index: -1;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #666;
            position: relative;
        }
        .footer::before {
            content: "";
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 1px;
            background: linear-gradient(to right, transparent, #007bff, transparent);
        }
        .serial-number {
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            margin-top: 10px;
            font-size: 0.9em;
            color: #495057;
        }
        .lottery-edge {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 15px;
            background: repeating-linear-gradient(
                to bottom,
                #007bff,
                #007bff 10px,
                #fff 10px,
                #fff 20px
            );
        }
        .lottery-edge-left {
            left: 0;
            border-top-left-radius: 15px;
            border-bottom-left-radius: 15px;
        }
        .lottery-edge-right {
            right: 0;
            border-top-right-radius: 15px;
            border-bottom-right-radius: 15px;
        }
        .validation-code {
            display: none;
        }
        .warning {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 10px;
            color: #856404;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .warning::before {
            content: "⚠";
            position: absolute;
            font-size: 80px;
            opacity: 0.05;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .total-info {
            margin-top: 20px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 10px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        .total-info h3 {
            color: #0056b3;
            margin-bottom: 10px;
            text-shadow: 1px 1px 0 rgba(255,255,255,0.8);
        }
        .total-info::before {
            content: "$";
            position: absolute;
            font-size: 120px;
            opacity: 0.03;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .text-danger {
            color: #dc3545;
            text-align: center;
            font-weight: bold;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        <?php echo $css_dinamico; ?>
    </style>
</head>
<body>
    <div class="container">
        <!-- Bordas laterais estilo loteria -->
        <div class="lottery-edge lottery-edge-left"></div>
        <div class="lottery-edge lottery-edge-right"></div>
        
        <div class="header">
            <div class="concurso-badge">Concurso Nº <?php echo isset($aposta['numero_concurso']) && $aposta['numero_concurso'] !== 'Próximo' ? $aposta['numero_concurso'] : 'Próximo'; ?></div>
            <?php
            // Verificar qual caminho do logo funciona
            $logo_paths = ['../assets/img/logo.png', '../assets/images/logo.png', '../assets/logo.png'];
            $logo_path = '../assets/img/logo.png'; // Padrão
            
            foreach ($logo_paths as $path) {
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . parse_url($path, PHP_URL_PATH))) {
                    $logo_path = $path;
                    break;
                }
            }
            ?>
            <img src="<?php echo $logo_path; ?>" alt="Logo" class="logo">
            <h2 class="jogo-titulo"><?php echo $aposta['jogo_nome']; ?></h2>
        </div>
        
        <div class="info-block">
            <div class="info-row">
                <strong>Comprovante Nº:</strong>
                <span><?php echo str_pad($aposta['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-row">
                <strong>Concurso Nº:</strong>
                <span><?php echo isset($aposta['numero_concurso']) && $aposta['numero_concurso'] !== 'Próximo' ? $aposta['numero_concurso'] : 'Próximo concurso'; ?></span>
            </div>
            <div class="info-row">
                <strong>Data e hora:</strong>
                <span><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></span>
            </div>
            <div class="info-row">
                <strong>Apostador:</strong>
                <span><?php echo $aposta['apostador']; ?></span>
            </div>
            <div class="info-row">
                <strong>Revendedor:</strong>
                <span><?php echo $aposta['revendedor']; ?></span>
            </div>
            <div class="info-row">
                <strong>Valor da Aposta:</strong>
                <span>R$ <?php echo number_format($valor_por_aposta, 2, ',', '.'); ?></span>
            </div>
        </div>
        
        <div class="info-block">
            <h3>Aposta Realizada</h3>
            <div class="aposta-grupo <?php echo sanitizeClassName($aposta['jogo_nome']); ?>">
                <div class="numbers">
                    <?php 
                    // Extrair os números da aposta
                    $numeros_aposta = extrairNumeros($aposta['numeros']);
                    
                    // Log para debug
                    error_log("Processando aposta ID: " . $aposta['id']);
                    error_log("Texto original: " . $aposta['numeros']);
                    error_log("Números extraídos: " . implode(',', $numeros_aposta));
                    
                    // Verificar se extraímos números
                    if (empty($numeros_aposta)) {
                        echo '<p class="text-danger">Nenhum número encontrado</p>';
                    } else {
                        // Exibir os números
                        foreach ($numeros_aposta as $numero): 
                            // Garantir que o número seja exibido corretamente
                            $numero = intval($numero); // Remover zeros à esquerda
                        ?>
                            <div class="number" style="background-color: <?php echo getCorJogo($aposta['jogo_nome']); ?>">
                                <?php echo str_pad($numero, 2, '0', STR_PAD_LEFT); ?>
                            </div>
                        <?php endforeach; 
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="game-info">
            <h3>Informações do Jogo</h3>
            <div class="info-row">
                <strong>Nome do Jogo:</strong>
                <span><?php echo $info_jogo['nome']; ?></span>
            </div>
            <div class="info-row">
                <strong>Números Necessários:</strong>
                <span><?php echo $info_jogo['dezenas_padrao']; ?> números</span>
            </div>
            <div class="info-row">
                <strong>Prêmio por Acerto:</strong>
                <span>R$ <?php echo number_format($valor_premio_unitario, 2, ',', '.'); ?></span>
            </div>
            <div class="info-row">
                <strong>Ganho Máximo Possível:</strong>
                <span>R$ <?php echo number_format($valor_premio, 2, ',', '.'); ?></span>
            </div>
        </div>

        <div class="total-info">
            <h3>Resumo da Aposta</h3>
            <p><strong>Valor da Aposta:</strong> R$ <?php echo number_format($valor_por_aposta, 2, ',', '.'); ?></p>
            <p><strong>Ganho Máximo Possível:</strong> R$ <?php echo number_format($valor_premio, 2, ',', '.'); ?></p>
        </div>

        <div class="warning">
            <p><strong>ATENÇÃO:</strong> Este comprovante é sua garantia. Guarde-o em local seguro.</p>
            <p>Em caso de premiação, este documento será necessário para receber o prêmio.</p>
        </div>
        
        <div class="footer">
            <p><strong>ID da aposta:</strong> <?php echo str_pad($aposta['id'], 8, '0', STR_PAD_LEFT); ?></p>
            <p>Gerado em: <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>Este comprovante tem validade legal e não necessita de assinatura.</p>
            <div class="serial-number">
                <?php 
                    // Gerar um número de série baseado no ID da aposta e data
                    $serial = strtoupper(substr(md5($aposta['id'] . $aposta['created_at'] . time()), 0, 20));
                    echo chunk_split($serial, 4, ' ');
                ?>
            </div>
        </div>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimir
        </button>
        <button onclick="history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </button>
    </div>

    <script>
        window.onload = function() {
            // Adicionar botões de Bootstrap
            var style = document.createElement('style');
            style.textContent = `
                .btn {
                    display: inline-block;
                    padding: 0.375rem 0.75rem;
                    font-size: 1rem;
                    font-weight: 400;
                    line-height: 1.5;
                    text-align: center;
                    text-decoration: none;
                    vertical-align: middle;
                    cursor: pointer;
                    border: 1px solid transparent;
                    border-radius: 0.25rem;
                    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
                }
                .btn-primary {
                    color: #fff;
                    background-color: #007bff;
                    border-color: #007bff;
                }
                .btn-secondary {
                    color: #fff;
                    background-color: #6c757d;
                    border-color: #6c757d;
                }
                .btn:hover {
                    opacity: 0.9;
                }
            `;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>