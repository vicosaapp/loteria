<?php
/**
 * Script para atualizar as apostas sem revendedor
 * Atribui todas as apostas sem revendedor para o revendedor ID 9 (Adriano Cunha)
 */

require_once 'config/database.php';

try {
    // ID do revendedor (Adriano Cunha)
    $revendedor_id = 9;
    
    // Buscar o revendedor para verificar se existe
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE id = ? AND tipo = 'revendedor'");
    $stmt->execute([$revendedor_id]);
    $revendedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$revendedor) {
        die("Revendedor não encontrado com ID {$revendedor_id}\n");
    }
    
    echo "Atualizando apostas para o revendedor: {$revendedor['nome']} (ID: {$revendedor['id']})\n";
    
    // Contar apostas sem revendedor
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM apostas WHERE revendedor_id IS NULL");
    $stmt->execute();
    $total_apostas_sem_revendedor = $stmt->fetchColumn();
    
    echo "Total de apostas sem revendedor: {$total_apostas_sem_revendedor}\n";
    
    if ($total_apostas_sem_revendedor == 0) {
        die("Não há apostas sem revendedor para atualizar.\n");
    }
    
    // Atualizar apostas sem revendedor
    $stmt = $pdo->prepare("UPDATE apostas SET revendedor_id = ? WHERE revendedor_id IS NULL");
    $stmt->execute([$revendedor_id]);
    $apostas_atualizadas = $stmt->rowCount();
    
    echo "Apostas atualizadas com sucesso: {$apostas_atualizadas}\n";
    
    // Verificar apostas associadas ao revendedor após atualização
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM apostas WHERE revendedor_id = ?");
    $stmt->execute([$revendedor_id]);
    $total_apostas_revendedor = $stmt->fetchColumn();
    
    echo "Total de apostas associadas ao revendedor após atualização: {$total_apostas_revendedor}\n";
    
    echo "Processo concluído com sucesso!\n";
    
} catch (PDOException $e) {
    die("Erro ao processar apostas: " . $e->getMessage() . "\n");
} 