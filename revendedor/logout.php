<?php
// Iniciar a sessão para poder destruí-la
session_start();

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie da sessão se existir
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login do revendedor
header("Location: /revendedor/index.php");
exit();
?> 