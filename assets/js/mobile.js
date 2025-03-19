// Mobile navigation script - LotoMinas

document.addEventListener('DOMContentLoaded', function() {
    // Adicionar o botão toggle se não existir
    if (!document.querySelector('.toggle-sidebar')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'toggle-sidebar';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(toggleBtn);
    }
    
    // Adicionar overlay se não existir
    if (!document.querySelector('.sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    // Adicionar evento ao botão toggle
    document.querySelector('.toggle-sidebar').addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        sidebar.classList.toggle('show');
        overlay.classList.toggle('active');
    });
    
    // Adicionar evento ao overlay para fechar a barra lateral
    document.querySelector('.sidebar-overlay').addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        sidebar.classList.remove('show');
        overlay.classList.remove('active');
    });
    
    // Adicionar barra de navegação móvel se não existir
    if (!document.querySelector('.mobile-nav')) {
        const mobileNav = document.createElement('div');
        mobileNav.className = 'mobile-nav';
        
        // Determinar a página atual
        const currentPath = window.location.pathname;
        
        // Adicionar itens de menu
        mobileNav.innerHTML = `
            <a href="/revendedor/index.php" class="mobile-nav-item ${currentPath.includes('/index.php') || currentPath.endsWith('/revendedor/') ? 'active' : ''}">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="/revendedor/apostas.php" class="mobile-nav-item ${currentPath.includes('/apostas.php') ? 'active' : ''}">
                <i class="fas fa-ticket-alt"></i>
                <span>Apostas</span>
            </a>
            <a href="/revendedor/clientes.php" class="mobile-nav-item ${currentPath.includes('/clientes.php') ? 'active' : ''}">
                <i class="fas fa-users"></i>
                <span>Clientes</span>
            </a>
            <a href="/revendedor/importar_apostas.php" class="mobile-nav-item ${currentPath.includes('/importar_apostas.php') ? 'active' : ''}">
                <i class="fas fa-file-import"></i>
                <span>Importar</span>
            </a>
            <a href="/revendedor/perfil.php" class="mobile-nav-item ${currentPath.includes('/perfil.php') ? 'active' : ''}">
                <i class="fas fa-user"></i>
                <span>Perfil</span>
            </a>
        `;
        
        document.body.appendChild(mobileNav);
    }
    
    // Fechar a barra lateral quando clicar em um link
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('active');
            }
        });
    });
    
    // Detectar mudanças na largura da janela
    window.addEventListener('resize', function() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
            overlay.classList.remove('active');
        }
    });
    
    // Adicionar meta viewport se não existir
    if (!document.querySelector('meta[name="viewport"]')) {
        const viewport = document.createElement('meta');
        viewport.name = 'viewport';
        viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
        document.head.appendChild(viewport);
    }
}); 