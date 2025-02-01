<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#007bff">
    <title><?php echo $pageTitle ?? 'Sistema de Loteria'; ?></title>
    
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" href="/images/icon-192x192.png">
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
    
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://unpkg.com/imask"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><?php echo $pageTitle ?? 'Sistema de Loteria'; ?></h1>
        </div>
    </header>


    <div class="install-prompt">
        <p>Instale nosso app para uma melhor experiência!</p>
        <button id="installButton" class="btn">Instalar</button>
    </div> 