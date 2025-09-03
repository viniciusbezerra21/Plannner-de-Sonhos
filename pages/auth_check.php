<?php
// auth_check.php - Sistema de proteção para páginas restritas
session_start();

function verificarLogin() {
    return isset($_SESSION['usuario_logado']) && $_SESSION['logado'] === true;
}

function redirecionarSeNaoLogado($pagina_atual = '') {
    if (!verificarLogin()) {
        $_SESSION['mensagem_erro'] = "Você precisa fazer login para acessar esta página.";
        $_SESSION['pagina_redirecionamento'] = $pagina_atual;
        header("Location: ../index.php");
        exit;
    }
}

function mostrarModalLogin($nomePagina = 'esta página') {
    if (!verificarLogin()) {
        return '
        <div id="modalLogin" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Acesso Restrito</h3>
                    <button class="modal-close" onclick="fecharModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="modal-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <circle cx="12" cy="16" r="1"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <p>Para acessar ' . $nomePagina . ', você precisa estar logado em sua conta.</p>
                    <div class="modal-buttons">
                        <a href="../user/login.php" class="btn-primary">Fazer Login</a>
                        <a href="../user/cadastro.php" class="btn-outline">Criar Conta</a>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(8px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.3s ease-out;
            }
            
            .modal-content {
                background: hsl(var(--card));
                border-radius: 1rem;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
                max-width: 400px;
                width: 90%;
                max-height: 90vh;
                overflow-y: auto;
                animation: slideUp 0.3s ease-out;
            }
            
            .modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1.5rem 1.5rem 1rem;
                border-bottom: 1px solid hsl(var(--border));
            }
            
            .modal-header h3 {
                margin: 0;
                color: hsl(var(--foreground));
                font-size: 1.25rem;
                font-weight: 600;
            }
            
            .modal-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                color: hsl(var(--muted-foreground));
                cursor: pointer;
                padding: 0.25rem;
                border-radius: 0.25rem;
                transition: all 0.2s;
            }
            
            .modal-close:hover {
                color: hsl(var(--foreground));
                background: hsl(var(--accent));
            }
            
            .modal-body {
                padding: 1.5rem;
                text-align: center;
            }
            
            .modal-icon {
                margin-bottom: 1rem;
                color: hsl(var(--primary));
            }
            
            .modal-body p {
                color: hsl(var(--muted-foreground));
                margin-bottom: 2rem;
                line-height: 1.6;
            }
            
            .modal-buttons {
                display: flex;
                gap: 0.75rem;
                flex-direction: column;
            }
            
            @media (min-width: 480px) {
                .modal-buttons {
                    flex-direction: row;
                    justify-content: center;
                }
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes slideUp {
                from { 
                    opacity: 0; 
                    transform: translateY(20px) scale(0.95); 
                }
                to { 
                    opacity: 1; 
                    transform: translateY(0) scale(1); 
                }
            }
        </style>
        <script>
            function fecharModal() {
                const modal = document.getElementById("modalLogin");
                modal.style.animation = "fadeOut 0.3s ease-in forwards";
                setTimeout(() => {
                    window.location.href = "../index.php";
                }, 300);
            }
            
            // Fechar modal ao clicar fora
            document.getElementById("modalLogin").addEventListener("click", function(e) {
                if (e.target === this) {
                    fecharModal();
                }
            });
            
            // Adicionar animação de saída
            const style = document.createElement("style");
            style.textContent = `
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        </script>';
    }
    return '';
}

// Função para verificar se uma página precisa de autenticação
function paginaRequerAutenticacao($pagina) {
    $paginasRestritas = [
        'calendario.php',
        'orcamento.php', 
        'gestao-contratos.php',
        'tarefas.php',
        'dashboard.php',
        'casamento.php',
        'perfil.php'
    ];
    
    return in_array($pagina, $paginasRestritas);
}
?>