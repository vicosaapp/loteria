<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Principal</div>
                <a class="nav-link <?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>
                
                <div class="sb-sidenav-menu-heading">Gerenciamento</div>
                
                <a class="nav-link <?php echo ($currentPage == 'usuarios') ? 'active' : ''; ?>" href="gerenciar_usuarios.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                    Usuários
                </a>
                
                <a class="nav-link <?php echo ($currentPage == 'revendedores') ? 'active' : ''; ?>" href="adicionar_revendedor.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-tie"></i></div>
                    Revendedores
                </a>
                
                <a class="nav-link <?php echo ($currentPage == 'jogos') ? 'active' : ''; ?>" href="gerenciar_jogos.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-gamepad"></i></div>
                    Jogos
                </a>
                
                <a class="nav-link <?php echo ($currentPage == 'resultados') ? 'active' : ''; ?>" href="gerenciar_resultados.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-trophy"></i></div>
                    Resultados
                </a>
                
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseApostas" aria-expanded="false" aria-controls="collapseApostas">
                    <div class="sb-nav-link-icon"><i class="fas fa-ticket-alt"></i></div>
                    Apostas
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseApostas" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link <?php echo ($currentPage == 'apostas') ? 'active' : ''; ?>" href="gerenciar_apostas.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-list"></i></div>
                            Todas as Apostas
                        </a>
                        <a class="nav-link <?php echo ($currentPage == 'apostas_pendentes') ? 'active' : ''; ?>" href="apostas_pendentes.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-clock"></i></div>
                            Apostas Pendentes
                        </a>
                        <a class="nav-link <?php echo ($currentPage == 'logs_comprovantes') ? 'active' : ''; ?>" href="logs_comprovantes.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-file-alt"></i></div>
                            Logs de Comprovantes
                        </a>
                    </nav>
                </div>
                
                <a class="nav-link <?php echo ($currentPage == 'valores') ? 'active' : ''; ?>" href="valores_jogos.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-dollar-sign"></i></div>
                    Valores e Prêmios
                </a>
                
                <div class="sb-sidenav-menu-heading">Configurações</div>
                
                <a class="nav-link <?php echo ($currentPage == 'configuracoes') ? 'active' : ''; ?>" href="configuracoes.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>
                    Configurações
                </a>
                
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUtilidades" aria-expanded="false" aria-controls="collapseUtilidades">
                    <div class="sb-nav-link-icon"><i class="fas fa-tools"></i></div>
                    Utilitários
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseUtilidades" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="scripts/atualizar_db.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>
                            Atualizar Banco de Dados
                        </a>
                        <a class="nav-link" href="instalar_fila_comprovantes.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-envelope"></i></div>
                            Instalar Fila de Comprovantes
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Logado como:</div>
            <?php echo isset($_SESSION['nome']) ? htmlspecialchars($_SESSION['nome']) : 'Administrador'; ?>
        </div>
    </nav>
</div> 