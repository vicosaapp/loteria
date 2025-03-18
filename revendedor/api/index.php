<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'Acesso direto não permitido',
    'data' => null
]); 