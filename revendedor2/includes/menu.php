<?php
$currentPage = $currentPage ?? '';
?>
<div id="sidebar-overlay"></div>
<nav id="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/logo.png" alt="Logo" class="logo">
    </div>
    <ul class="list-unstyled components">
        <li class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="<?php echo $currentPage === 'resultados' ? 'active' : ''; ?>">
            <a href="resultados.php">
                <i class="fas fa-check-circle"></i>
                <span>Resultados</span>
            </a>
        </li>
        <li class="<?php echo $currentPage === 'ganhadores' ? 'active' : ''; ?>">
            <a href="ganhadores.php">
                <i class="fas fa-trophy"></i>
                <span>Ganhadores</span>
            </a>
        </li>
        <li class="<?php echo $currentPage === 'apostas' ? 'active' : ''; ?>">
            <a href="apostas.php">
                <i class="fas fa-ticket-alt"></i>
                <span>Apostas</span>
            </a>
        </li>
        <li class="<?php echo $currentPage === 'comissoes' ? 'active' : ''; ?>">
            <a href="comissoes.php">
                <i class="fas fa-dollar-sign"></i>
                <span>Comissões</span>
            </a>
        </li>
        <li class="<?php echo $currentPage === 'clientes' ? 'active' : ''; ?>">
            <a href="clientes.php">
                <i class="fas fa-users"></i>
                <span>Clientes</span>
            </a>
        </li>
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </li>
    </ul>
</nav> 