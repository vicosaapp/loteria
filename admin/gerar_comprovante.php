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
 * - formato: Formato de saída (opcional)
 *   'html' (padrão) ou 'pdf'
 * 
 * Exemplo de uso:
 * gerar_comprovante.php?usuario_id=123&jogo=Dia+de+Sorte&aposta_id=456&formato=pdf
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

// Verifica formato solicitado (html ou pdf)
$formato = isset($_GET['formato']) ? strtolower($_GET['formato']) : 'html';

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
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'revendedor')) {
    header("Location: ../login.php");
    exit();
}

// Verificar parâmetros
if (!isset($_GET['usuario_id']) || empty($_GET['usuario_id'])) {
    die("Usuário não especificado");
}

$usuario_id = $_GET['usuario_id'];
$jogo_nome = isset($_GET['jogo']) ? $_GET['jogo'] : null;
$aposta_id = isset($_GET['aposta_id']) ? $_GET['aposta_id'] : null;

// Verificar token para acesso público
$public_token = isset($_GET['public_token']) ? $_GET['public_token'] : '';
$token_esperado = md5($usuario_id . $aposta_id . 'loteria_seguranca');
$token_valido = ($public_token === $token_esperado);

// Verificar autenticação (permitir acesso público com token válido)
if (!isset($_SESSION['usuario_id']) && !$token_valido) {
    header("Content-Type: text/html");
    echo "<h1>Acesso não autorizado</h1>";
    echo "<p>Você não tem permissão para acessar este recurso.</p>";
    exit();
}

// Aviso para administradores sobre a nova página pública
if (isset($_SESSION['usuario_id']) && $_SESSION['tipo'] === 'admin' && $formato === 'html') {
    echo '<div style="background-color: #fff3cd; color: #856404; padding: 15px; margin-bottom: 20px; border: 1px solid #ffeeba; border-radius: 5px;">';
    echo '<strong>Aviso ao Administrador:</strong> Este link de comprovante na área administrativa está sendo substituído por uma nova página pública mais segura para os apostadores.';
    echo '<br>A nova URL é: <code>' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/comprovante.php</code>";
    echo '<br>Verifique se o arquivo está configurado corretamente.';
    echo '</div>';
}

// Buscar dados do cliente
$stmt = $pdo->prepare("SELECT nome, email, whatsapp, telefone FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    // Tentar buscar em apostas
    $stmt = $pdo->prepare("SELECT a.numeros, u.nome, u.email, u.whatsapp, u.telefone FROM apostas a JOIN usuarios u ON a.usuario_id = u.id WHERE a.usuario_id = ? LIMIT 1");
    $stmt->execute([$usuario_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$cliente) {
    // Não encontrou o cliente, usar um placeholder
    $cliente = [
        'nome' => 'Cliente não cadastrado',
        'email' => '',
        'whatsapp' => '',
        'telefone' => ''
    ];
}

// Buscar dados das apostas
try {
    if ($aposta_id) {
        // Se temos um ID específico, buscamos apenas essa aposta
        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.usuario_id,
                a.numeros,
                a.valor_aposta as valor,
                a.valor_premio as valor_premio,
                a.created_at,
                j.nome as jogo_nome
            FROM 
                apostas a
                JOIN jogos j ON a.tipo_jogo_id = j.id
            WHERE 
                a.id = ?
        ");
        $stmt->execute([$aposta_id]);
    } else if ($jogo_nome) {
        // Se temos um nome de jogo, buscamos todas as apostas desse jogo
        if ($jogo_nome == 'Normal') {
            // Apostas normais
            $stmt = $pdo->prepare("
                SELECT 
                    a.id,
                    a.usuario_id,
                    a.numeros,
                    a.valor_aposta as valor,
                    a.valor_premio as valor_premio,
                    a.created_at,
                    j.nome as jogo_nome
                FROM 
                    apostas a
                    JOIN jogos j ON a.tipo_jogo_id = j.id
                WHERE 
                    a.usuario_id = ?
                ORDER BY 
                    a.created_at DESC
            ");
            $stmt->execute([$usuario_id]);
        } else {
            // Apostas importadas
            $stmt = $pdo->prepare("
                SELECT 
                    ai.id,
                    ai.usuario_id,
                    ai.numeros,
                    ai.valor_aposta as valor,
                    ai.valor_premio as valor_premio,
                    ai.created_at,
                    ai.jogo_nome
                FROM 
                    apostas_importadas ai
                WHERE 
                    ai.usuario_id = ? AND
                    ai.jogo_nome LIKE ?
                ORDER BY 
                    ai.created_at DESC
            ");
            $stmt->execute([$usuario_id, "%$jogo_nome%"]);
        }
    } else {
        // Se não temos nenhum dos dois, buscamos todas as apostas do usuário
        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.usuario_id,
                a.numeros,
                a.valor_aposta as valor,
                a.valor_premio as valor_premio,
                a.created_at,
                j.nome as jogo_nome
            FROM 
                apostas a
                JOIN jogos j ON a.tipo_jogo_id = j.id
            WHERE 
                a.usuario_id = ?
            ORDER BY 
                a.created_at DESC
        ");
        $stmt->execute([$usuario_id]);
    }

    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar apostas: " . $e->getMessage());
}

if (empty($apostas)) {
    die("Nenhuma aposta encontrada para esse cliente");
}

// Agrupar apostas por jogo
$apostas_por_jogo = [];
foreach ($apostas as $aposta) {
    $nome_jogo = $aposta['jogo_nome'];
    if (!isset($apostas_por_jogo[$nome_jogo])) {
        $apostas_por_jogo[$nome_jogo] = [];
    }
    $apostas_por_jogo[$nome_jogo][] = $aposta;
}

// Para cada jogo, mostrar as apostas correspondentes
// Vamos exibir apenas a primeira aposta para simplificar
if (!empty($apostas_por_jogo)) {
    reset($apostas_por_jogo);
    $jogo_nome = key($apostas_por_jogo);
    $apostas_jogo = current($apostas_por_jogo);
    $aposta = $apostas_jogo[0]; // Pegar a primeira aposta
    
    // Processar os números da aposta
    $numeros_array = [];
    if (strpos($aposta['numeros'], ',') !== false) {
        $numeros_array = explode(',', $aposta['numeros']);
    } else {
        $numeros_array = preg_split('/\s+/', trim($aposta['numeros']));
    }
    $numeros_array = array_filter($numeros_array, 'is_numeric');
    
    // Gerar o ID único da aposta
    $aposta_id = $aposta['id'];
    
    // Obter data e hora da aposta
    $data_emissao = !empty($aposta['created_at']) 
        ? date('d/m/Y H:i:s', strtotime($aposta['created_at'])) 
        : date('d/m/Y H:i:s');
    
    // Buscar o número do concurso diretamente da aposta, se existir (lógica original)
    $concurso_numero = isset($aposta['concurso']) && !empty($aposta['concurso']) ? $aposta['concurso'] : null;
    $data_sorteio = null;
    $hora_sorteio = null;
    
    // Fallback se não encontrou informações do concurso diretamente na aposta (lógica original)
    if (empty($concurso_numero)) { // Alterado de !$concurso_numero para empty($concurso_numero) para consistência
        $nome_jogo_atual = $aposta['jogo_nome']; 
        $stmt_fallback = $pdo->prepare("
            SELECT c.codigo, c.data_sorteio
            FROM jogos j
            LEFT JOIN concursos c ON j.id = c.jogo_id AND c.status = 'pendente'
            WHERE j.nome = ?
            ORDER BY c.data_sorteio ASC
            LIMIT 1
        ");
        $stmt_fallback->execute([$nome_jogo_atual]);
        $concurso_info_fallback = $stmt_fallback->fetch(PDO::FETCH_ASSOC);
        
        $concurso_numero = $concurso_info_fallback ? $concurso_info_fallback['codigo'] : 'N/A';
        if (!empty($concurso_info_fallback['data_sorteio'])) {
            $data_sorteio = date('d/m/Y', strtotime($concurso_info_fallback['data_sorteio']));
            $hora_sorteio = date('H:i', strtotime($concurso_info_fallback['data_sorteio']));
        } else {
            $data_sorteio = date('d/m/Y'); 
            $hora_sorteio = '20:00'; 
        }
    } else {
        // Se $concurso_numero veio de $aposta['concurso'], precisamos buscar a data do sorteio para ele
        // Esta parte pode precisar de ajuste dependendo de como $aposta['concurso'] é populado
        // Por enquanto, vamos assumir que se $aposta['concurso'] existe, a data do sorteio precisa ser encontrada
        // Se $aposta['concurso'] é apenas o NÚMERO do concurso, precisamos de uma query para buscar sua data.
        // Se a data já vier junto com $aposta['concurso_data_sorteio'] por exemplo, seria mais fácil.
        // Vamos manter a lógica de fallback para data por enquanto, se não vier de $aposta diretamente.

        // Se temos $concurso_numero de $aposta['concurso'], tentamos buscar a data para esse concurso específico.
        $stmt_data_concurso_especifico = $pdo->prepare("
            SELECT data_sorteio 
            FROM concursos 
            WHERE codigo = ? AND jogo_id = (SELECT id FROM jogos WHERE nome = ? LIMIT 1)
            LIMIT 1
        ");
        $stmt_data_concurso_especifico->execute([$concurso_numero, $aposta['jogo_nome']]);
        $info_data_especifica = $stmt_data_concurso_especifico->fetch(PDO::FETCH_ASSOC);

        if ($info_data_especifica && !empty($info_data_especifica['data_sorteio'])) {
            $data_sorteio = date('d/m/Y', strtotime($info_data_especifica['data_sorteio']));
            $hora_sorteio = date('H:i', strtotime($info_data_especifica['data_sorteio']));
        } else {
            // Fallback se não encontrar data para o concurso específico de $aposta['concurso']
            $data_sorteio = date('d/m/Y'); 
            $hora_sorteio = '20:00';
        }
    }
    
    // Usar o valor_premio da aposta específica
    $premio_estimado = $aposta['valor_premio'];
    
    if ($formato === 'pdf') {
        // Gera PDF usando FPDF
        require_once '../lib/fpdf/fpdf.php';
        
        // Extende a classe FPDF para personalizar o comprovante
        class PDF extends FPDF {
            // Propriedade para armazenar estados de transparência
            protected $extgstates = array();
            
            function Header() {
                // Sem header padrão para controlar precisamente o posicionamento
            }
            
            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->SetTextColor(85, 85, 85);
                $this->Cell(0, 5, 'Aplicativo Loto Minas ( lotominas.site )', 0, 0, 'C');
            }
            
            // Função auxiliar para desenhar círculos no PDF
            function Circle($x, $y, $r, $style='D')
            {
                $this->SetFillColor(96, 48, 177); // Cor roxa #6030b1
                $this->Ellipse($x, $y, $r, $r, $style);
            }
            
            // Função para desenhar elipses no PDF
            function Ellipse($x, $y, $rx, $ry, $style='D')
            {
                if($style=='F')
                    $op='f';
                elseif($style=='FD' || $style=='DF')
                    $op='B';
                else
                    $op='S';

                $lx=4/3*(M_SQRT2-1)*$rx;
                $ly=4/3*(M_SQRT2-1)*$ry;
                
                $this->_out(sprintf('%.2F %.2F m',($x+$rx)*$this->k,($this->h-$y)*$this->k));
                $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
                    ($x+$rx)*$this->k,($this->h-$y-$ly)*$this->k,
                    ($x+$lx)*$this->k,($this->h-$y-$ry)*$this->k,
                    $x*$this->k,($this->h-$y-$ry)*$this->k));
                $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
                    ($x-$lx)*$this->k,($this->h-$y-$ry)*$this->k,
                    ($x-$rx)*$this->k,($this->h-$y-$ly)*$this->k,
                    ($x-$rx)*$this->k,($this->h-$y)*$this->k));
                $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
                    ($x-$rx)*$this->k,($this->h-$y+$ly)*$this->k,
                    ($x-$lx)*$this->k,($this->h-$y+$ry)*$this->k,
                    $x*$this->k,($this->h-$y+$ry)*$this->k));
                $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
                    ($x+$lx)*$this->k,($this->h-$y+$ry)*$this->k,
                    ($x+$rx)*$this->k,($this->h-$y+$ly)*$this->k,
                    ($x+$rx)*$this->k,($this->h-$y)*$this->k));
                $this->_out($op);
            }
            
            // Função para desenhar fundo amarelo simplificado
            function YellowBackground() {
                // Configura a cor de preenchimento como amarelo
                $this->SetFillColor(255, 228, 92); // #ffe45c
                
                // Desenha um retângulo amarelo 
                $margin = 10;
                $width = $this->GetPageWidth() - (2 * $margin);
                $height = $this->GetPageHeight() - (2 * $margin);
                
                // Retângulo preenchido
                $this->Rect($margin, $margin, $width, $height, 'F');
                
                // Removidos os círculos decorativos que estavam causando problemas
            }
            
            // Função para desenhar uma linha tracejada personalizada
            function DashedLine($x1, $y1, $x2, $y2, $dash_length=3, $space_length=3) {
                $this->SetLineWidth(0.5); // Linha mais grossa
                $this->SetDrawColor(170, 170, 170);
                
                $length = sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
                $angle = atan2($y2 - $y1, $x2 - $x1);
                
                $cos_angle = cos($angle);
                $sin_angle = sin($angle);
                
                $current_x = $x1;
                $current_y = $y1;
                
                $dash = true;
                $i = 0;
                
                while ($i < $length) {
                    $i_length = ($dash) ? $dash_length : $space_length;
                    
                    $x = $current_x + $cos_angle * $i_length;
                    $y = $current_y + $sin_angle * $i_length;
                    
                    if ($dash) {
                        $this->Line($current_x, $current_y, $x, $y);
                    }
                    
                    $current_x = $x;
                    $current_y = $y;
                    $dash = !$dash;
                    
                    $i += $i_length;
                }
            }
        }
        
        // Inicializa o PDF
        $pdf = new PDF();
        $pdf->SetTitle('Comprovante de Aposta #' . $aposta_id);
        $pdf->SetAuthor('Loto Minas');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15); // Reduzido espaço inferior
        
        // Variáveis de configuração de layout
        $margem_lateral = 15; // Reduzida margem lateral
        $largura_pagina = $pdf->GetPageWidth();
        $altura_pagina = $pdf->GetPageHeight();
        
        // Desenha o fundo amarelo simplificado
        $pdf->YellowBackground();
        
        // Configura margens e espaçamento
        $pdf->SetMargins($margem_lateral, 15, $margem_lateral); // Reduzida margem superior
        
        // Adiciona o logo centralizado no topo
        $logo_width = 60; // Logo menor
        $logo_x = ($largura_pagina - $logo_width) / 2;
        $pdf->Image('../img_app/logo.png', $logo_x, 15, $logo_width);
        
        // Posiciona após o logo
        $y_after_logo = 60; // Reduzido espaço após o logo
        $pdf->SetY($y_after_logo);
        
        // Estilo para os campos e valores
        $label_width = 55;
        $space_after_field = 6; // Reduzido espaço entre os campos
        $y = $pdf->GetY();
        
        // Definir a formatação
        $pdf->SetFont('Arial', 'B', 9); // Fonte menor
        $pdf->SetTextColor(0, 0, 0);
        
        // ID da Aposta
        $pdf->SetXY($margem_lateral, $y);
        $pdf->Cell($label_width, 6, 'ID APOSTA:', 0);
        $pdf->SetX($margem_lateral + $label_width);
        $pdf->Cell(0, 6, $aposta_id, 0, 1);
        $y += $space_after_field;
        
        // Data de Emissão
        $pdf->SetXY($margem_lateral, $y);
        $pdf->Cell($label_width, 6, 'EMITIDO EM:', 0);
        $pdf->SetX($margem_lateral + $label_width);
        $pdf->Cell(0, 6, $data_emissao, 0, 1);
        $y += $space_after_field;
        
        // Participante
        $pdf->SetXY($margem_lateral, $y);
        $pdf->Cell($label_width, 6, 'PARTICIPANTE:', 0);
        $pdf->SetX($margem_lateral + $label_width);
        $pdf->Cell(0, 6, mb_strtoupper($cliente['nome']), 0, 1);
        $y += $space_after_field;
        
        // Concurso
        $pdf->SetXY($margem_lateral, $y);
        $pdf->Cell($label_width, 6, 'CONCURSO:', 0);
        $pdf->SetX($margem_lateral + $label_width);
        $pdf->Cell(0, 6, $concurso_numero, 0, 1);
        $y += $space_after_field;
        
        // Data do Sorteio
        $pdf->SetXY($margem_lateral, $y);
        $pdf->Cell($label_width, 6, 'DATA DO SORTEIO:', 0);
        $pdf->SetX($margem_lateral + $label_width);
        $pdf->Cell(0, 6, $data_sorteio, 0, 1);
        $y += $space_after_field;
        
        // Hora do Sorteio
        $pdf->SetXY($margem_lateral, $y);
        $pdf->Cell($label_width, 6, 'HORA DO SORTEIO:', 0);
        $pdf->SetX($margem_lateral + $label_width);
        $pdf->Cell(0, 6, $hora_sorteio, 0, 1);
        $y += $space_after_field + 3; // Reduzido espaço antes da linha
        
        // Linha tracejada como separador (mais espessa e visível)
        $x_start = $margem_lateral;
        $x_end = $largura_pagina - $margem_lateral;
        $pdf->DashedLine($x_start, $y, $x_end, $y);
        $y += 8; // Reduzido espaço após a linha
        
        // Nome do Jogo (centralizado e destacado)
        $pdf->SetY($y);
        $pdf->SetFont('Arial', 'B', 14); // Fonte menor
        $pdf->Cell(0, 8, mb_strtoupper($jogo_nome), 0, 1, 'C');
        $y = $pdf->GetY() + 5; // Reduzido espaço após o título
        
        // Números - desenho melhorado e mais compacto
        // Ajusta o layout para centralizar melhor os números
        $numero_diametro = 11; // Diâmetro menor para os círculos
        $espaco_entre_circulos = $numero_diametro + 1; // Espaço menor entre círculos
        $area_largura = $largura_pagina - (2 * $margem_lateral);
        $max_circulos_por_linha = floor($area_largura / $espaco_entre_circulos);
        
        // Ajustando o máximo de círculos por linha para ter um layout mais equilibrado
        $max_circulos_por_linha = min($max_circulos_por_linha, 10); // No máximo 10 números por linha
        
        // Assegurar que os números estão ordenados para uma melhor apresentação
        sort($numeros_array, SORT_NUMERIC);
        
        // Calcular quantas linhas serão necessárias
        $total_numeros = count($numeros_array);
        $linhas_necessarias = ceil($total_numeros / $max_circulos_por_linha);
        
        // Posição inicial Y para os números
        $numeros_y = $y;
        
        // Para cada linha de números
        for ($linha = 0; $linha < $linhas_necessarias; $linha++) {
            // Calcular quantos números nesta linha
            $numeros_nesta_linha = min($max_circulos_por_linha, $total_numeros - ($linha * $max_circulos_por_linha));
            
            // Centralização na linha
            $largura_linha = $numeros_nesta_linha * $espaco_entre_circulos;
            $x_inicio = ($largura_pagina - $largura_linha) / 2 + ($espaco_entre_circulos / 2);
            
            // Desenhar os números desta linha
            for ($i = 0; $i < $numeros_nesta_linha; $i++) {
                $indice = ($linha * $max_circulos_por_linha) + $i;
                if ($indice < $total_numeros) { // Prevenir acesso fora dos limites
                    $numero = $numeros_array[$indice];
                    $numero_formatado = str_pad(trim($numero), 2, '0', STR_PAD_LEFT);
                    
                    // Posição X ajustada
                    $x = $x_inicio + ($i * $espaco_entre_circulos) - ($numero_diametro/2);
                    
                    // Desenha círculo roxo
                    $pdf->Circle($x + ($numero_diametro/2), $numeros_y + ($numero_diametro/2), $numero_diametro/2, 'F');
                    
                    // Posiciona o número no centro do círculo
                    $pdf->SetXY($x - 1, $numeros_y);
                    $pdf->SetFont('Arial', 'B', 8); // Fonte menor para os números
                    $pdf->SetTextColor(255, 255, 255); // Texto branco
                    $pdf->Cell($numero_diametro+2, $numero_diametro, $numero_formatado, 0, 0, 'C');
                    
                    // Restaura cor do texto
                    $pdf->SetTextColor(0, 0, 0);
                }
            }
            
            // Próxima linha de números com menos espaço
            $numeros_y += $numero_diametro + 2;
        }
        
        // Posição após os números
        $y = $numeros_y + 5; // Reduzido espaço após os números
        
        // Linha tracejada como separador final
        $pdf->DashedLine($x_start, $y, $x_end, $y);
        $y += 8; // Reduzido espaço após a linha
        
        // Informações finais
        $pdf->SetY($y);
        $pdf->SetFont('Arial', 'B', 9); // Fonte menor
        
        // QTD DEZENAS
        $pdf->SetXY($margem_lateral, $y);
        $pdf->Cell($label_width, 6, 'QTD DEZENAS:', 0);
        $pdf->SetX($margem_lateral + $label_width);
        $pdf->Cell(0, 6, count($numeros_array), 0, 1);
        $y += $space_after_field;
        
        // VALOR APOSTADO
        $pdf->SetXY($margem_lateral, $y);
        $pdf->Cell($label_width, 6, 'VALOR APOSTADO:', 0);
        $pdf->SetX($margem_lateral + $label_width);
        $pdf->Cell(0, 6, 'R$ ' . number_format($aposta['valor'], 2, ',', '.'), 0, 1);
        $y += $space_after_field;
        
        // VALOR DO PRÊMIO
        $pdf->SetXY($margem_lateral, $y);
        $pdf->Cell($label_width, 6, 'VALOR DO PRÊMIO:', 0);
        $pdf->SetX($margem_lateral + $label_width);
        $pdf->Cell(0, 6, 'R$ ' . number_format($premio_estimado, 2, ',', '.'), 0, 1);
        
        // Saída do PDF
        ob_end_clean(); // Limpa qualquer saída anterior
        $pdf->Output('Comprovante_' . $aposta_id . '.pdf', 'I');
        exit;
        
    } else {
        // Gerar HTML para o comprovante
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Comprovante de Apostas</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    margin: 0; 
                    padding: 20px;
                    background-color: #f5f5f5;
                }
                .comprovante { 
                    max-width: 400px; 
                    margin: 0 auto; 
                    background-color: #ffe45c;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    position: relative;
                    overflow: hidden;
                }
                .comprovante::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Ccircle cx=\'50\' cy=\'50\' r=\'40\' fill=\'%23fed930\' opacity=\'0.3\'/%3E%3C/svg%3E");
                    background-repeat: repeat;
                    background-size: 80px;
                    opacity: 0.5;
                    z-index: 0;
                }
                .logo-container {
                    text-align: center; 
                    margin-bottom: 15px; 
                    position: relative; 
                    z-index: 1;
                }
                .logo-img {
                    max-width: 150px; 
                    height: auto;
                }
                .info-item { 
                    margin-bottom: 8px; 
                    display: flex;
                    position: relative;
                    z-index: 1;
                }
                .info-label { 
                    font-weight: bold; 
                    width: 160px;
                    font-size: 14px;
                    flex-shrink: 0;
                }
                .info-value {
                    font-size: 14px;
                    flex-grow: 1;
                    font-weight: bold;
                }
                .divisor {
                    border-top: 1px dashed #aaa;
                    margin: 15px 0;
                    position: relative;
                    z-index: 1;
                }
                .jogo-nome {
                    text-align: center;
                    font-size: 22px;
                    font-weight: bold;
                    margin: 15px 0;
                    position: relative;
                    z-index: 1;
                }
                .numeros { 
                    display: flex; 
                    flex-wrap: wrap; 
                    gap: 4px; 
                    justify-content: center;
                    margin: 10px 0 15px 0;
                    position: relative;
                    z-index: 1;
                }
                .numero { 
                    width: 36px; 
                    height: 36px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center;
                    background: #6030b1; 
                    color: white; 
                    border-radius: 50%; 
                    font-weight: bold;
                    font-size: 15px;
                }
                .footer { 
                    margin-top: 20px; 
                    text-align: center; 
                    font-size: 14px;
                    color: #555;
                    position: relative;
                    z-index: 1;
                }
                .bottom-info {
                    margin-top: 20px;
                    position: relative;
                    z-index: 1;
                }
                .btn-container {
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                    margin-top: 15px;
                }
                .btn {
                    padding: 10px 20px;
                    background: #6030b1;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                }
                .btn:hover {
                    background: #4c2690;
                }
                @media print {
                    body {
                        margin: 0;
                        padding: 0;
                        background: none;
                    }
                    .comprovante {
                        box-shadow: none;
                        width: 100%;
                        max-width: none;
                        border-radius: 0;
                    }
                    .no-print {
                        display: none !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="comprovante">
                <div class="logo-container">
                    <img src="/img_app/logo.png" alt="Logo Loto Minas" class="logo-img">
                </div>
                <div class="info-item">
                    <span class="info-label">ID APOSTA:</span>
                    <span class="info-value">' . $aposta_id . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">EMITIDO EM:</span>
                    <span class="info-value">' . $data_emissao . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">PARTICIPANTE:</span>
                    <span class="info-value">' . htmlspecialchars(strtoupper($cliente['nome'])) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">CONCURSO:</span>
                    <span class="info-value">' . $concurso_numero . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">DATA DO SORTEIO:</span>
                    <span class="info-value">' . $data_sorteio . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">HORA DO SORTEIO:</span>
                    <span class="info-value">' . $hora_sorteio . '</span>
                </div>
                
                <div class="divisor"></div>
                
                <div class="jogo-nome">' . htmlspecialchars($jogo_nome) . '</div>
                
                <div class="numeros">';
        
        foreach ($numeros_array as $numero) {
            $numero_formatado = str_pad(trim($numero), 2, '0', STR_PAD_LEFT);
            $html .= '<div class="numero">' . $numero_formatado . '</div>';
        }
        
        $html .= '
                </div>
                
                <div class="divisor"></div>
                
                <div class="bottom-info">
                    <div class="info-item">
                        <span class="info-label">QTD DEZENAS:</span>
                        <span class="info-value">' . count($numeros_array) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">VALOR APOSTADO:</span>
                        <span class="info-value">R$ ' . number_format($aposta['valor'], 2, ',', '.') . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">VALOR DO PRÊMIO:</span>
                        <span class="info-value">R$ ' . number_format($premio_estimado, 2, ',', '.') . '</span>
                    </div>
                </div>
                
                <div class="footer">
                    <p>Aplicativo Loto Minas ( lotominas.site )</p>
                    <div class="btn-container no-print">
                        <button onclick="window.print();" class="btn">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <a href="' . $_SERVER['REQUEST_URI'] . '&formato=pdf" class="btn">
                            <i class="fas fa-file-pdf"></i> Baixar PDF
                        </a>
                    </div>
                </div>
            </div>
            
            <script>
                window.onload = function() {
                    // Comentado para não imprimir automaticamente
                    // window.print();
                }
            </script>
        </body>
        </html>';

        echo $html;
    }
}