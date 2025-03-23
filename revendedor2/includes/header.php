<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? $pageTitle : 'Painel Revendedor'; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<!-- Incluir suporte para dispositivos móveis -->
<?php include_once 'mobile_support.php'; ?>

<style>
    body {
        background-color: #f4f6f9;
    }
    .app-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
</style> 