<?php
// Chave secreta para assinar os tokens
define('JWT_SECRET', 'sua_chave_secreta_aqui');

/**
 * Gera um token JWT
 * @param array $payload Dados a serem incluídos no token
 * @return string Token JWT
 */
function gerarToken($payload) {
    // Header
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];
    
    // Adicionar timestamps
    $payload['iat'] = time(); // Issued At
    $payload['exp'] = time() + (24 * 60 * 60); // Expira em 24 horas
    
    // Codificar header e payload
    $header_encoded = base64url_encode(json_encode($header));
    $payload_encoded = base64url_encode(json_encode($payload));
    
    // Gerar assinatura
    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true);
    $signature_encoded = base64url_encode($signature);
    
    // Retornar token completo
    return "$header_encoded.$payload_encoded.$signature_encoded";
}

/**
 * Valida um token JWT
 * @param string $token Token JWT
 * @return array|false Payload decodificado ou false se inválido
 */
function validarToken($token) {
    // Separar partes do token
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    // Decodificar header e payload
    $header = json_decode(base64url_decode($parts[0]), true);
    $payload = json_decode(base64url_decode($parts[1]), true);
    
    // Verificar algoritmo
    if ($header['alg'] !== 'HS256') {
        return false;
    }
    
    // Verificar assinatura
    $signature = base64url_decode($parts[2]);
    $expected_signature = hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", JWT_SECRET, true);
    if (!hash_equals($signature, $expected_signature)) {
        return false;
    }
    
    // Verificar expiração
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

/**
 * Codifica uma string em base64url
 * @param string $data String para codificar
 * @return string String codificada
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Decodifica uma string em base64url
 * @param string $data String para decodificar
 * @return string String decodificada
 */
function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
} 