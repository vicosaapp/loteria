<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// A solicitação de aprovação não é mais necessária, pois todas as apostas são aprovadas automaticamente
echo json_encode([
    'success' => true, 
    'message' => 'Esta funcionalidade foi desativada. Todas as apostas são aprovadas automaticamente agora.'
]);
exit; 