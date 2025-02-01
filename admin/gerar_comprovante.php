<?php
require_once '../config/database.php';
require_once __DIR__ . '/../lib/fpdf/fpdf.php';

class ComprovantePDF extends FPDF {
    function Header() {
        // Usando helvetica (que é a fonte padrão)
        $this->SetFont('Helvetica', 'B', 16);
        
        // Cabeçalho com fundo azul
        $this->SetFillColor(0, 51, 153);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 15, utf8_decode('COMPROVANTE DE APOSTAS'), 0, 1, 'C', true);
        
        // Linha decorativa
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(255, 215, 0);
        $this->Line(10, 25, 200, 25);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }
}

// Recebe os parâmetros
$usuario_id = $_GET['usuario_id'] ?? null;
$jogo_nome = $_GET['jogo'] ?? null;

if (!$usuario_id || !$jogo_nome) {
    die('Parâmetros inválidos');
}

// Busca as informações incluindo o valor_premio
$stmt = $pdo->prepare("
    SELECT 
        ai.numeros,
        ai.valor_aposta,
        ai.valor_premio,
        ai.created_at,
        u.nome as apostador_nome,
        u.whatsapp,
        r.nome as revendedor_nome
    FROM apostas_importadas ai
    LEFT JOIN usuarios u ON ai.usuario_id = u.id
    LEFT JOIN usuarios r ON ai.revendedor_id = r.id
    WHERE ai.usuario_id = ? AND ai.jogo_nome = ?
    ORDER BY ai.created_at DESC
");

$stmt->execute([$usuario_id, $jogo_nome]);
$apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug para ver os valores
error_log('Dados das apostas: ' . print_r($apostas, true));

if (empty($apostas)) {
    die('Nenhuma aposta encontrada');
}

// Cria PDF
$pdf = new ComprovantePDF();
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);

// Informações do apostador
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetTextColor(0);
$pdf->Cell(0, 10, '', 0, 1);
$pdf->Cell(0, 8, utf8_decode('DADOS DO APOSTADOR'), 1, 1, 'C', true);
$pdf->SetFont('Helvetica', '', 11);
$pdf->Cell(40, 8, 'Nome:', 0, 0);
$pdf->Cell(0, 8, utf8_decode($apostas[0]['apostador_nome']), 0, 1);
$pdf->Cell(40, 8, 'WhatsApp:', 0, 0);
$pdf->Cell(0, 8, $apostas[0]['whatsapp'], 0, 1);
$pdf->Cell(40, 8, 'Revendedor:', 0, 0);
$pdf->Cell(0, 8, utf8_decode($apostas[0]['revendedor_nome'] ?: 'Admin'), 0, 1);
$pdf->Cell(40, 8, 'Data/Hora:', 0, 0);
$pdf->Cell(0, 8, date('d/m/Y H:i', strtotime($apostas[0]['created_at'])), 0, 1);

// Nome do Jogo
$pdf->Ln(5);
$pdf->SetFont('Helvetica', 'B', 14);
$pdf->Cell(0, 10, utf8_decode($jogo_nome), 0, 1, 'C');

// Apostas
$pdf->Ln(5);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('NÚMEROS APOSTADOS'), 1, 1, 'C', true);

foreach ($apostas as $index => $aposta) {
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Aposta ' . ($index + 1), 0, 1);
    
    // Números em grid
    $numeros = explode(' ', $aposta['numeros']);
    $pdf->SetFont('Courier', '', 11);
    
    foreach ($numeros as $i => $numero) {
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(8, 8, $numero, 1, ($i + 1) % 20 == 0 ? 1 : 0, 'C', true);
        if (($i + 1) % 20 == 0) {
            $pdf->Ln();
        }
    }
    if (count($numeros) % 20 != 0) {
        $pdf->Ln();
    }
    $pdf->Ln(4);
}

// Valor e Premiação
$pdf->SetFont('Helvetica', 'B', 12);
$total_apostas = count($apostas);
$valor_total = array_sum(array_column($apostas, 'valor_aposta'));
$valor_premio = floatval($apostas[0]['valor_premio'] ?? 0); // Convertendo para float

error_log('Valor da premiação: ' . $valor_premio); // Debug do valor

$pdf->Cell(0, 8, 'RESUMO', 1, 1, 'C', true);
$pdf->SetFont('Helvetica', '', 11);

// Quantidade de apostas
$pdf->Cell(60, 8, utf8_decode('Quantidade de Apostas:'), 0, 0);
$pdf->Cell(0, 8, $total_apostas, 0, 1);

// Valor total apostado
$pdf->Cell(60, 8, 'Valor Total Apostado:', 0, 0);
$pdf->Cell(0, 8, 'R$ ' . number_format($valor_total, 2, ',', '.'), 0, 1);

// Valor da premiação
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->Cell(60, 8, utf8_decode('Valor da Premiação:'), 0, 0);
$pdf->SetTextColor(0, 102, 0); // Verde escuro
$pdf->Cell(0, 8, 'R$ ' . number_format($valor_premio, 2, ',', '.'), 0, 1);
$pdf->SetTextColor(0); // Volta para preto

// Adiciona uma nota sobre a premiação
$pdf->SetFont('Helvetica', 'I', 9);
$pdf->Ln(2);
$pdf->MultiCell(0, 5, utf8_decode('* O valor da premiação será pago integralmente ao apostador que acertar todos os números.'), 0, 'L');

// Código de validação
$pdf->Ln(5);
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->Cell(0, 8, utf8_decode('Código de Validação'), 0, 1, 'C');
$pdf->Cell(0, 8, date('YmdHis') . str_pad($usuario_id, 6, '0', STR_PAD_LEFT), 0, 1, 'C');

// Data e hora da impressão
$pdf->SetFont('Helvetica', 'I', 8);
$pdf->Ln(5);
$pdf->Cell(0, 5, utf8_decode('Comprovante gerado em: ') . date('d/m/Y H:i:s'), 0, 1, 'C');

// Gera o PDF
$pdf->Output('Comprovante_' . date('YmdHis') . '.pdf', 'I'); 