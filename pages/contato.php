<?php
// contato.php
session_start(); // Adicionando session_start no início

$host = 'localhost';
$dbname = 'casamento';          // seu banco
$username = 'root';              // ajuste se tiver senha
$password = 'root';                  // ajuste se tiver senha

$mensagem_sucesso = '';
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $nome = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['phone'] ?? '');
        $assunto = trim($_POST['subject'] ?? '');
        $mensagem = trim($_POST['message'] ?? '');

        if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
            throw new Exception('Por favor, preencha todos os campos obrigatórios.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Por favor, insira um e-mail válido.');
        }

        $sql = "INSERT INTO mensagens_contato 
                (nome, email, telefone, assunto, mensagem, data_envio) 
                VALUES (:nome, :email, :telefone, :assunto, :mensagem, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':telefone' => $telefone,
            ':assunto' => $assunto,
            ':mensagem' => $mensagem
        ]);

        $mensagem_sucesso = 'Mensagem enviada com sucesso! Entraremos em contato em breve.';
        $nome = $email = $telefone = $assunto = $mensagem = '';

    } catch (PDOException $e) {
        $mensagem_erro = 'Erro na conexão com o banco de dados. Tente novamente mais tarde.';
        error_log("Erro BD: " . $e->getMessage());
    } catch (Exception $e) {
        $mensagem_erro = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contato - WeddingEasy</title>
    <link rel="stylesheet" href="../Style/styles.css" />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
      rel="stylesheet"
    />
    <style>
      .user-profile {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
      }

      .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid hsl(var(--primary));
        cursor: pointer;
      }

      .user-avatar-default {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary)) 80%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        border: 2px solid hsl(var(--primary));
      }

      .profile-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: hsl(var(--card));
        border: 1px solid hsl(var(--border));
        border-radius: 0.75rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        min-width: 200px;
        display: none;
        z-index: 1000;
        margin-top: 0.5rem;
      }

      .profile-dropdown.active {
        display: block;
      }

      .profile-dropdown-header {
        padding: 1rem;
        border-bottom: 1px solid hsl(var(--border));
      }

      .profile-dropdown-name {
        font-weight: 600;
        color: hsl(var(--foreground));
        margin: 0;
        font-size: 0.9rem;
      }

      .profile-dropdown-email {
        color: hsl(var(--muted-foreground));
        margin: 0;
        font-size: 0.8rem;
        margin-top: 0.25rem;
      }

      .profile-dropdown-menu {
        padding: 0.5rem 0;
      }

      .profile-dropdown-item {
        display: block;
        padding: 0.75rem 1rem;
        color: hsl(var(--foreground));
        text-decoration: none;
        transition: background-color 0.2s;
        font-size: 0.9rem;
      }

      .profile-dropdown-item:hover {
        background-color: hsl(var(--accent));
      }

      .profile-dropdown-item.logout {
        color: #ef4444;
        border-top: 1px solid hsl(var(--border));
        margin-top: 0.5rem;
      }

      .profile-dropdown-item.logout:hover {
        background-color: #fef2f2;
      }

      @media (max-width: 768px) {
        .user-profile {
          order: -1;
        }
        
        .profile-dropdown {
          right: -1rem;
        }
      }
    </style>
  </head>
  <body>
    <!-- Header -->
    <header class="header">
      <div class="container">
        <div class="header-content">
          <!-- Logo -->
          <a href="../index.php" class="logo">
            <div class="heart-icon">
              <svg
                width="16"
                height="16"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
                />
              </svg>
            </div>
            <span class="logo-text">WeddingEasy</span>
          </a>

          <!-- Navigation Desktop -->
          <nav class="nav">
            <a href="../index.php" class="nav-link">Início</a>

            <!-- Dropdown Funcionalidades -->
            <div class="dropdown">
              <a href="funcionalidades.php" class="nav-link dropdown-toggle"
                >Funcionalidades ▾</a
              >
              <div class="dropdown-menu">
                <a href="calendario.php">Calendário</a>
                <a href="orcamento.php">Orçamento</a>
                <a href="gestao-contratos.php">Gestão de Contratos</a>
                <a href="tarefas.php">Lista de Tarefas</a>
              </div>
            </div>

            <a href="contato.php" class="nav-link">Contato</a>
            
            <?php if (isset($_SESSION['usuario_logado'])): ?>
              <!-- Usuário logado -->
              <div class="user-profile">
                <?php if (!empty($_SESSION['usuario_logado']['foto_perfil']) && file_exists('../uploads/perfil/' . $_SESSION['usuario_logado']['foto_perfil'])): ?>
                  <img 
                    src="../uploads/perfil/<?php echo htmlspecialchars($_SESSION['usuario_logado']['foto_perfil']); ?>" 
                    alt="Foto do perfil" 
                    class="user-avatar"
                    onclick="toggleProfileDropdown()"
                  />
                <?php else: ?>
                  <div class="user-avatar-default" onclick="toggleProfileDropdown()">
                    <?php echo strtoupper(substr($_SESSION['usuario_logado']['nome'], 0, 1)); ?>
                  </div>
                <?php endif; ?>
                
                <div class="profile-dropdown" id="profileDropdown">
                  <div class="profile-dropdown-header">
                    <p class="profile-dropdown-name"><?php echo htmlspecialchars($_SESSION['usuario_logado']['nome']); ?></p>
                    <p class="profile-dropdown-email"><?php echo htmlspecialchars($_SESSION['usuario_logado']['email']); ?></p>
                  </div>
                  <div class="profile-dropdown-menu">
                    <a href="../user/perfil.php" class="profile-dropdown-item">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; vertical-align: middle;">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                      </svg>
                      Meu Perfil
                    </a>
                    <a href="dashboard.php" class="profile-dropdown-item">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; vertical-align: middle;">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="9" y1="9" x2="15" y2="9"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                      </svg>
                      Dashboard
                    </a>
                    <a href="casamento.php" class="profile-dropdown-item">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; vertical-align: middle;">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                      </svg>
                      Meu Casamento
                    </a>
                    <a href="../user/logout.php" class="profile-dropdown-item logout">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem; vertical-align: middle;">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16,17 21,12 16,7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                      </svg>
                      Sair
                    </a>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <!-- Usuário não logado -->
              <a href="../user/login.php" class="btn-primary" style="align-items: center">Login</a>
            <?php endif; ?>
          </nav>

          <!-- Mobile menu button -->
          <button
            id="hamburgerBtn"
            class="mobile-menu-btn"
            onclick="toggleMobileMenu()"
          >
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
          </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="mobile-menu">
          <nav
            style="
              display: flex;
              flex-direction: column;
              gap: 1rem;
              padding: 1rem 0;
              border-top: 1px solid hsl(var(--border));
              margin-top: 0.5rem;
            "
          >
            <a href="../index.php" class="nav-link" style="padding: 0.5rem 0"
              >Início</a
            >
            <a
              href="funcionalidades.php"
              class="nav-link"
              style="padding: 0.5rem 0"
              >Funcionalidades</a
            >
            <a href="contato.php" class="nav-link" style="padding: 0.5rem 0"
              >Contato</a
            >
            
            <?php if (isset($_SESSION['usuario_logado'])): ?>
              <div style="border-top: 1px solid hsl(var(--border)); margin-top: 1rem; padding-top: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                  <?php if (!empty($_SESSION['usuario_logado']['foto_perfil']) && file_exists('../uploads/perfil/' . $_SESSION['usuario_logado']['foto_perfil'])): ?>
                    <img 
                      src="../uploads/perfil/<?php echo htmlspecialchars($_SESSION['usuario_logado']['foto_perfil']); ?>" 
                      alt="Foto do perfil" 
                      class="user-avatar"
                      style="width: 32px; height: 32px;"
                    />
                  <?php else: ?>
                    <div class="user-avatar-default" style="width: 32px; height: 32px; font-size: 0.8rem;">
                      <?php echo strtoupper(substr($_SESSION['usuario_logado']['nome'], 0, 1)); ?>
                    </div>
                  <?php endif; ?>
                  <div>
                    <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['usuario_logado']['nome']); ?></div>
                    <div style="font-size: 0.8rem; color: hsl(var(--muted-foreground));"><?php echo htmlspecialchars($_SESSION['usuario_logado']['email']); ?></div>
                  </div>
                </div>
                <a href="../user/perfil.php" class="nav-link" style="padding: 0.5rem 0">Meu Perfil</a>
                <a href="dashboard.php" class="nav-link" style="padding: 0.5rem 0">Dashboard</a>
                <a href="casamento.php" class="nav-link" style="padding: 0.5rem 0">Meu Casamento</a>
                <a href="../user/logout.php" class="nav-link" style="padding: 0.5rem 0; color: #ef4444;">Sair</a>
              </div>
            <?php else: ?>
              <a href="../user/login.php" class="btn-primary" style="align-items: center">Login</a>
            <?php endif; ?>
          </nav>
        </div>
      </div>
    </header>

    <main>
      <section class="page-content">
        <div class="container">
          <div class="page-header">
            <h1 class="page-title">
              Entre em
              <span class="gradient-text">Contato</span>
            </h1>
            <p class="page-description">
              Estamos aqui para ajudar você a planejar o casamento dos seus
              sonhos. Entre em contato conosco e tire todas as suas dúvidas.
            </p>
          </div>

          <div class="contact-grid">
            <div class="contact-form-card">
              <div class="contact-form-header">
                <svg
                  class="message-icon"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                >
                  <path
                    d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"
                  />
                </svg>
                <h2>Envie uma Mensagem</h2>
              </div>

              <?php if ($mensagem_sucesso): ?>
              <div class="alert alert-success">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <?php echo htmlspecialchars($mensagem_sucesso); ?>
              </div>
              <?php endif; ?>

              <?php if ($mensagem_erro): ?>
              <div class="alert alert-error">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <?php echo htmlspecialchars($mensagem_erro); ?>
              </div>
              <?php endif; ?>

              <form method="POST" class="contact-form">
                <div class="form-row">
                  <div class="form-group">
                    <label for="name">Nome Completo</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($nome ?? ''); ?>" required />
                  </div>
                  <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="phone">Telefone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($telefone ?? ''); ?>" />
                  </div>
                  <div class="form-group">
                    <label for="subject">Assunto</label>
                    <select id="subject" name="subject" required>
                      <option value="">Selecione um assunto</option>
                      <option value="duvidas" <?php echo ($assunto ?? '') === 'duvidas' ? 'selected' : ''; ?>>Dúvidas Gerais</option>
                      <option value="suporte" <?php echo ($assunto ?? '') === 'suporte' ? 'selected' : ''; ?>>Suporte Técnico</option>
                      <option value="vendas" <?php echo ($assunto ?? '') === 'vendas' ? 'selected' : ''; ?>>Informações de Vendas</option>
                      <option value="feedback" <?php echo ($assunto ?? '') === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                      <option value="outros" <?php echo ($assunto ?? '') === 'outros' ? 'selected' : ''; ?>>Outros</option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label for="message">Mensagem</label>
                  <textarea
                    id="message"
                    name="message"
                    rows="6"
                    placeholder="Conte-nos como podemos ajudar você..."
                    required
                  ><?php echo htmlspecialchars($mensagem ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                  <svg
                    class="send-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                  >
                    <line x1="22" y1="2" x2="11" y2="13" />
                    <polygon points="22,2 15,22 11,13 2,9 22,2" />
                  </svg>
                  Enviar Mensagem
                </button>
              </form>
            </div>

            <div class="contact-info">
              <div class="contact-info-card">
                <div class="contact-info-header">
                  <svg
                    class="heart-icon"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                  >
                    <path
                      d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                    />
                  </svg>
                  <h2>Informações de Contato</h2>
                </div>

                <div class="contact-details">
                  <div class="contact-detail">
                    <svg
                      class="mail-icon"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                    >
                      <path
                        d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"
                      />
                      <polyline points="22,6 12,13 2,6" />
                    </svg>
                    <div>
                      <h3>E-mail</h3>
                      <p>contato@weddingeasy.com</p>
                      <p>suporte@weddingeasy.com</p>
                    </div>
                  </div>

                  <div class="contact-detail">
                    <svg
                      class="phone-icon"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                    >
                      <path
                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"
                      />
                    </svg>
                    <div>
                      <h3>Telefone</h3>
                      <p>(11) 9999-9999</p>
                      <p>WhatsApp: (11) 8888-8888</p>
                    </div>
                  </div>

                  <div class="contact-detail">
                    <svg
                      class="map-icon"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                    >
                      <path
                        d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"
                      />
                      <circle cx="12" cy="10" r="3" />
                    </svg>
                    <div>
                      <h3>Endereço</h3>
                      <p>
                        Rua das Flores, 123<br />
                        Jardim Primavera<br />
                        São Paulo - SP, 01234-567
                      </p>
                    </div>
                  </div>

                  <div class="contact-detail">
                    <svg
                      class="clock-icon"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                    >
                      <circle cx="12" cy="12" r="10" />
                      <polyline points="12,6 12,12 16,14" />
                    </svg>
                    <div>
                      <h3>Horário de Atendimento</h3>
                      <p>
                        Segunda a Sexta: 9h às 18h<br />
                        Sábado: 9h às 14h<br />
                        Domingo: Fechado
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="faq-card">
            <h2>Perguntas Frequentes</h2>
            <div class="faq-items">
              <div class="faq-item">
                <h3>Como funciona o WeddingEasy?</h3>
                <p>
                  O WeddingEasy é uma plataforma completa para planejamento de
                  casamentos com módulos especializados para cada aspecto da
                  organização.
                </p>
              </div>

              <div class="faq-item">
                <h3>Existe um período de teste gratuito?</h3>
                <p>
                  Sim! Oferecemos 14 dias de teste gratuito para que você possa
                  explorar todas as funcionalidades.
                </p>
              </div>

              <div class="faq-item">
                <h3>Posso cancelar a qualquer momento?</h3>
                <p>
                  Claro! Você pode cancelar sua assinatura a qualquer momento
                  sem taxas adicionais.
                </p>
              </div>
            </div>
          </div>

          <div class="features-cta">
            <h2 class="cta-title">Pronto para começar?</h2>
            <p class="cta-description">
              Nossa equipe está pronta para ajudar você a planejar o casamento
              dos seus sonhos. Entre em contato conosco hoje mesmo!
            </p>
            <div class="cta-buttons">
              <a href="mailto:contato@weddingeasy.com" class="btn btn-primary">
                <svg
                  class="mail-icon"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                >
                  <path
                    d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"
                  />
                  <polyline points="22,6 12,13 2,6" />
                </svg>
                Enviar E-mail
              </a>
              <a
                href="https://wa.me/5511888888888"
                target="_blank"
                class="btn btn-primary"
              >
                <svg
                  class="phone-icon"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                >
                  <path
                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"
                  />
                </svg>
                WhatsApp
              </a>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-brand">
            <a href="../index.php" class="logo">
              <div class="heart-icon">
                <svg
                  width="16"
                  height="16"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
                  />
                </svg>
              </div>
              <span class="logo-text">WeddingEasy</span>
            </a>
            <p class="footer-description">
              A plataforma mais completa para cerimonialistas organizarem
              casamentos perfeitos. Simplifique sua gestão e encante seus
              clientes.
            </p>
            <div class="footer-contact">
              <svg
                style="width: 1rem; height: 1rem"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"
                />
                <polyline points="22,6 12,13 2,6" />
              </svg>
              <span>contato@weddingeasy.com</span>
            </div>
          </div>

          <div class="footer-modules">
            <h3>Legal</h3>
            <ul>
              <li><a href="../legal-pages/about.html">Sobre</a></li>
              <li>
                <a href="../legal-pages/privacity-politics.html"
                  >Política de Privacidade</a
                >
              </li>
              <li>
                <a href="../legal-pages/uses-terms.html">Termos de Uso</a>
              </li>
            </ul>
          </div>
        </div>

        <div class="footer-bottom">
          <p>&copy; 2024 WeddingEasy. Todos os direitos reservados.</p>
        </div>
      </div>
    </footer>
    <script>
      // Mobile menu toggle
      function toggleMobileMenu() {
        const mobileMenu = document.getElementById("mobileMenu");
        const hamburgerBtn = document.getElementById("hamburgerBtn");

        mobileMenu.classList.toggle("active");
        hamburgerBtn.classList.toggle("hamburger-active");
      }

      // Profile dropdown toggle
      function toggleProfileDropdown() {
        const dropdown = document.getElementById("profileDropdown");
        dropdown.classList.toggle("active");
      }

      // Fechar dropdown quando clicar fora
      document.addEventListener('click', function(event) {
        const profile = document.querySelector('.user-profile');
        const dropdown = document.getElementById("profileDropdown");
        
        if (profile && !profile.contains(event.target)) {
          dropdown?.classList.remove("active");
        }
      });

      // Smooth scroll for anchor links
      document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", function (e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute("href"));
          if (target) {
            target.scrollIntoView({
              behavior: "smooth",
              block: "start",
            });

            // Close mobile menu if open
            const mobileMenu = document.getElementById("mobileMenu");
            const hamburgerBtn = document.getElementById("hamburgerBtn");
            mobileMenu.classList.remove("active");
            hamburgerBtn.classList.remove("hamburger-active");
          }
        });
      });

      // Auto-hide success message after 5 seconds
      <?php if ($mensagem_sucesso): ?>
      setTimeout(function() {
        const alert = document.querySelector('.alert-success');
        if (alert) {
          alert.style.opacity = '0';
          alert.style.transform = 'translateY(-10px)';
          setTimeout(() => alert.remove(), 300);
        }
      }, 5000);
      <?php endif; ?>
    </script>
  </body>
</html>