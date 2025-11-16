<?php
// Ensure session is started before using session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="header">
    <div class="container">
        <div class="header-content">
            <!-- Updated logo link to use absolute path for consistency -->
            <a href="<?php echo isset($_SESSION["usuario_id"]) ? "../index.php" : "../index.php"; ?>" class="logo">
                <div class="heart-icon">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                </div>
                <span class="logo-text">Planner de Sonhos</span>
            </a>

            <!-- Added mobile menu button and restructured navigation for responsiveness -->
            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>

            <nav class="nav" id="mainNav">
                <?php if (isset($_SESSION["usuario_id"])): ?>
                    <a href="../index.php" class="nav-link">Início</a>
                    <div class="dropdown">
                        <a href="funcionalidades.php" class="nav-link dropdown-toggle">Funcionalidades ▾</a>
                        <div class="dropdown-menu">
                            <a href="calendario.php">Calendário</a>
                            <a href="orcamento.php">Orçamento</a>
                            <a href="itens.php">Serviços</a>
                            <a href="tarefas.php">Lista de Tarefas</a>
                            <a href="mensagens.php">Mensagens</a>
                            <a href="notificacoes.php">Notificações</a>
                            <a href="historico.php">Histórico</a>
                        </div>
                    </div>
                    <a href="contato.php" class="nav-link">Contato</a>
                <?php else: ?>
                    <a href="index.php" class="nav-link">Início</a>
                <?php endif; ?>

                <?php if (isset($_SESSION["usuario_id"])): ?>
                    <div class="profile-dropdown-wrapper">
                        <?php
                        $foto_path = '../user/fotos/' . htmlspecialchars($_SESSION['foto_perfil'] ?? 'default.png');
                        ?>
                        <img src="<?php echo $foto_path; ?>" alt="Foto de perfil" class="profile-avatar"
                            onclick="toggleProfileDropdown()">
                        <div class="profile-dropdown" id="profileDropdown">
                            <div class="profile-dropdown-header">
                                <div class="profile-dropdown-user">
                                    <img src="<?php echo $foto_path; ?>" alt="Avatar" class="profile-dropdown-avatar">
                                    <div class="profile-dropdown-info">
                                        <div class="profile-dropdown-name">
                                            <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?>
                                        </div>
                                        <div class="profile-dropdown-email">
                                            <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="profile-dropdown-menu">
                                <a href="user/perfil.php" class="profile-dropdown-item">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    Meu Perfil
                                </a>
                                <a href="funcionalidades.php" class="profile-dropdown-item">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    Funcionalidades
                                </a>
                                <form method="post" style="margin:0;">
                                    <button type="submit" name="logout" class="profile-dropdown-item logout"
                                        style="width: 100%; text-align: left; background: none; border: none; font-family: inherit; font-size: inherit; cursor: pointer; display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem;">
                                        <svg fill="hsl(var(--foreground))" width="800px" height="800px" viewBox="0 0 36 36"
                                            version="1.1" preserveAspectRatio="xMidYMid meet"
                                            xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                            <title>logout-line</title>
                                            <path
                                                d="M7,6H23v9.8h2V6a2,2,0,0,0-2-2H7A2,2,0,0,0,5,6V30a2,2,0,0,0,2,2H23a2,2,0,0,0,2-2H7Z"
                                                class="clr-i-outline clr-i-outline-path-1"></path>
                                            <path
                                                d="M28.16,17.28a1,1,0,0,0-1.41,1.41L30.13,22H15.63a1,1,0,0,0-1,1,1,1,0,0,0,1,1h14.5l-3.38,3.46a1,1,0,1,0,1.41,1.41L34,23.07Z"
                                                class="clr-i-outline clr-i-outline-path-2"></path>
                                            <rect x="0" y="0" width="36" height="36" fill-opacity="0" />
                                        </svg>
                                        Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="user/login-unified.php" class="btn-primary" style="align-items: center">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>

<script>
    function toggleMobileMenu() {
        const btn = document.getElementById('mobileMenuBtn');
        const nav = document.getElementById('mainNav');
        
        if (btn && nav) {
            btn.classList.toggle('hamburger-active');
            nav.classList.toggle('active');
        }
    }

    function toggleProfileDropdown() {
        const dropdown = document.getElementById('profileDropdown');
        if (dropdown) {
            dropdown.classList.toggle('active');
        }
    }

    // Mobile menu button click
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (event) {
        const dropdown = document.getElementById('profileDropdown');
        const avatar = event.target.closest('.profile-avatar');
        const menuBtn = event.target.closest('.mobile-menu-btn');
        const nav = event.target.closest('.nav');
        
        if (dropdown && !avatar && !event.target.closest('.profile-dropdown')) {
            dropdown.classList.remove('active');
        }
        
        // Close mobile menu when clicking outside
        if (menuBtn || (nav && window.innerWidth <= 768)) {
            return;
        }
        
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        if (mobileMenuBtn && mobileMenuBtn.classList.contains('hamburger-active')) {
            if (!event.target.closest('.mobile-menu-btn') && !event.target.closest('.nav')) {
                mobileMenuBtn.classList.remove('hamburger-active');
                const mainNav = document.getElementById('mainNav');
                if (mainNav) mainNav.classList.remove('active');
            }
        }
    });

    // Close mobile menu on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mainNav = document.getElementById('mainNav');
            if (mobileMenuBtn) mobileMenuBtn.classList.remove('hamburger-active');
            if (mainNav) mainNav.classList.remove('active');
        }
    });
</script>
