<?php
session_start();
require_once "../config/conexao.php";

$userType = $_GET['type'] ?? 'cliente';
$step = $_POST['step'] ?? 1;
$mensagem = "";
$userData = [];

// Validar tipo de usuário
if (!in_array($userType, ['cliente', 'fornecedor', 'cerimonialista'])) {
    $userType = 'cliente';
}

// Guardar dados da sessão
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($step == 1) {
        if ($userType === 'cliente') {
            $nome = $_POST["nome"] ?? '';
            $nome_conj = $_POST["nome_conj"] ?? '';
            $genero = $_POST["genero"] ?? '';
            $idade = $_POST["idade"] ?? '';
            $telefone = $_POST["telefone"] ?? '';
            $email = $_POST["email"] ?? '';

            try {
                $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    throw new Exception("Este email já está cadastrado. Tente outro.");
                }
            } catch (Exception $e) {
                $_SESSION['signup_error'] = $e->getMessage();
                header("Location: signup-unified.php?type=cliente");
                exit;
            }

            $_SESSION['signup_data'] = compact('nome', 'nome_conj', 'genero', 'idade', 'telefone', 'email');
            $_SESSION['signup_step'] = 2;
        } elseif ($userType === 'fornecedor') {
            $nome = $_POST["nome"] ?? '';
            $telefone = $_POST["telefone"] ?? '';
            $email = $_POST["email"] ?? '';
            $cnpj = $_POST["cnpj"] ?? '';
            $endereco = $_POST["endereco"] ?? '';
            $categoria = $_POST["categoria"] ?? 'geral';

            try {
                $stmt = $pdo->prepare("SELECT id_fornecedor FROM fornecedores WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    throw new Exception("Este email já está cadastrado. Tente outro.");
                }
            } catch (Exception $e) {
                $_SESSION['signup_error'] = $e->getMessage();
                header("Location: signup-unified.php?type=fornecedor");
                exit;
            }

            $_SESSION['signup_data'] = compact('nome', 'telefone', 'email', 'cnpj', 'endereco', 'categoria');
            $_SESSION['signup_step'] = 2;
        } else {
            $nome = $_POST["nome"] ?? '';
            $telefone = $_POST["telefone"] ?? '';
            $email = $_POST["email"] ?? '';
            $especializacao = $_POST["especializacao"] ?? '';
            $experiencia_anos = $_POST["experiencia_anos"] ?? 0;

            try {
                $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    throw new Exception("Este email já está cadastrado. Tente outro.");
                }
            } catch (Exception $e) {
                $_SESSION['signup_error'] = $e->getMessage();
                header("Location: signup-unified.php?type=cerimonialista");
                exit;
            }

            $_SESSION['signup_data'] = compact('nome', 'telefone', 'email', 'especializacao', 'experiencia_anos');
            $_SESSION['signup_step'] = 2;
        }
    } elseif ($step == 2) {
        // Step 2: Senha
        $senha = $_POST["senha"] ?? '';
        $confirma_senha = $_POST["confirma_senha"] ?? '';

        if ($senha !== $confirma_senha) {
            $mensagem = "<div class='mensagem-erro'>As senhas não coincidem!</div>";
        } elseif (strlen($senha) < 6) {
            $mensagem = "<div class='mensagem-erro'>A senha deve ter pelo menos 6 caracteres!</div>";
        } else {
            $_SESSION['signup_data']['senha'] = password_hash($senha, PASSWORD_DEFAULT);
            $_SESSION['signup_step'] = 3;
        }
    } elseif ($step == 3 && $userType === 'cliente') {
        $data_casamento = $_POST["data_casamento"] ?? '';
        $orcamento_total = (float) str_replace(',', '.', $_POST["orcamento_total"] ?? 0);
        $local_casamento = $_POST["local_casamento"] ?? '';
        $tipo_cerimonia = $_POST["tipo_cerimonia"] ?? '';
        $quantidade_convidados = (int) $_POST["quantidade_convidados"] ?? 0;
        
        $_SESSION['signup_data']['data_casamento'] = $data_casamento;
        $_SESSION['signup_data']['orcamento_total'] = $orcamento_total;
        $_SESSION['signup_data']['local_casamento'] = $local_casamento;
        $_SESSION['signup_data']['tipo_cerimonia'] = $tipo_cerimonia;
        $_SESSION['signup_data']['quantidade_convidados'] = $quantidade_convidados;
        
        finalizarCadastroCliente();
        exit;
    } elseif ($step == 3 && in_array($userType, ['fornecedor', 'cerimonialista'])) {
        if ($userType === 'fornecedor') {
            $preco_minimo = (float) str_replace(',', '.', $_POST["preco_minimo"] ?? 0);
            $horario_funcionamento = $_POST["horario_funcionamento"] ?? '';
            $_SESSION['signup_data']['preco_minimo'] = $preco_minimo;
            $_SESSION['signup_data']['horario_funcionamento'] = $horario_funcionamento;
        } else {
            $valor_minimo = (float) str_replace(',', '.', $_POST["valor_minimo"] ?? 0);
            $tipos_cerimonia = $_POST["tipos_cerimonia"] ?? [];
            $_SESSION['signup_data']['valor_minimo'] = $valor_minimo;
            $_SESSION['signup_data']['tipos_cerimonia'] = json_encode($tipos_cerimonia);
        }
        
        finalizarCadastroProfissional($userType);
        exit;
    }
}

function finalizarCadastroCliente() {
    global $pdo;
    
    $data = $_SESSION['signup_data'];
    
    try {
        $sql = "INSERT INTO usuarios (nome, nome_conjuge, genero, idade, telefone, email, senha, cargo, foto_perfil, orcamento_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['nome'], 
            $data['nome_conj'], 
            $data['genero'],
            $data['idade'], 
            $data['telefone'], 
            $data['email'], 
            $data['senha'],
            'cliente',
            'default.png',
            $data['orcamento_total'] ?? 0
        ]);

        $id_usuario = $pdo->lastInsertId();
        
        session_regenerate_id(true);
        $_SESSION["usuario_id"] = (int) $id_usuario;
        $_SESSION["nome"] = $data['nome'];
        $_SESSION["tipo_usuario"] = 'cliente';
        $_SESSION["foto_perfil"] = 'default.png';
        
        unset($_SESSION['signup_data']);
        unset($_SESSION['signup_step']);

        header("Location: ../pages/escolher-cerimonialista.php");
        exit;
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar cliente: " . $e->getMessage());
        $_SESSION['signup_error'] = "Erro ao cadastrar. Tente novamente mais tarde.";
        header("Location: signup-unified.php?type=cliente");
        exit;
    }
}

function finalizarCadastroProfissional($userType) {
    global $pdo;
    
    $data = $_SESSION['signup_data'];
    
    try {
        if ($userType === 'fornecedor') {
            $sql = "INSERT INTO fornecedores (nome_fornecedor, categoria, email, telefone, descricao, senha, apenas_pacotes) 
                    VALUES (?, ?, ?, ?, ?, ?, 0)";
            $stmt = $pdo->prepare($sql);
            
            $descricao = "Empresa cadastrada através do sistema.";
            if (isset($data['cnpj']) && !empty($data['cnpj'])) {
                $descricao .= " CNPJ: " . $data['cnpj'] . ".";
            }
            if (isset($data['endereco']) && !empty($data['endereco'])) {
                $descricao .= " Endereço: " . $data['endereco'] . ".";
            }
            if (isset($data['preco_minimo']) && $data['preco_minimo'] > 0) {
                $descricao .= " Preço mínimo: R$ " . number_format($data['preco_minimo'], 2, ',', '.') . ".";
            }
            if (isset($data['horario_funcionamento']) && !empty($data['horario_funcionamento'])) {
                $descricao .= " Horário: " . $data['horario_funcionamento'] . ".";
            }
            
            $stmt->execute([
                $data['nome'], 
                $data['categoria'], 
                $data['email'], 
                $data['telefone'] ?? '', 
                $descricao,
                $data['senha']
            ]);

            $id_fornecedor = $pdo->lastInsertId();
            
            session_regenerate_id(true);
            $_SESSION["fornecedor_id"] = (int) $id_fornecedor;
            $_SESSION["fornecedor_nome"] = $data['nome'];
            $_SESSION["fornecedor_email"] = $data['email'];
            
            unset($_SESSION['signup_data']);
            unset($_SESSION['signup_step']);

            header("Location: ../supplier/dashboard.php");
        } else {
            $sql = "INSERT INTO usuarios (nome, email, senha, cargo, foto_perfil, telefone, genero, orcamento_total) 
                    VALUES (?, ?, ?, 'cerimonialista', 'default.png', ?, 'Outro', 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nome'], 
                $data['email'], 
                $data['senha'], 
                $data['telefone'] ?? ''
            ]);

            $id_usuario = $pdo->lastInsertId();
            
            session_regenerate_id(true);
            $_SESSION["usuario_id"] = (int) $id_usuario;
            $_SESSION["nome"] = $data['nome'];
            $_SESSION["tipo_usuario"] = 'cerimonialista';
            $_SESSION["foto_perfil"] = 'default.png';
            
            unset($_SESSION['signup_data']);
            unset($_SESSION['signup_step']);

            header("Location: ../pages/cerimonialista-home.php");
        }
        exit;
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar profissional: " . $e->getMessage());
        if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'email_unique') !== false) {
            $_SESSION['signup_error'] = "Este email já está cadastrado. Tente outro.";
        } else {
            $_SESSION['signup_error'] = "Erro ao cadastrar. Tente novamente mais tarde.";
        }
        $redirectType = $userType === 'fornecedor' ? 'fornecedor' : 'cerimonialista';
        header("Location: signup-unified.php?type=$redirectType");
        exit;
    }
}

$signupData = $_SESSION['signup_data'] ?? [];
$signupStep = $_SESSION['signup_step'] ?? 1;
$signup_error = $_SESSION['signup_error'] ?? '';
if (isset($_SESSION['signup_error'])) {
    unset($_SESSION['signup_error']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cadastro - Planner de Sonhos</title>
  <link rel="stylesheet" href="../Style/styles.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap"
    rel="stylesheet" />
  <link rel="shortcut icon" href="../Style/assets/icon.png" type="image/x-icon">
  <style>
    .signup-container {
      max-width: 600px;
      margin: auto;
      padding: 2rem 0;
    }

    .signup-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .signup-header h1 {
      font-size: 2rem;
      color: hsl(var(--foreground));
      margin-bottom: 0.5rem;
    }

    .signup-header p {
      color: hsl(var(--muted-foreground));
    }

    .progress-bar {
      width: 100%;
      height: 4px;
      background: hsl(var(--border));
      border-radius: 2px;
      margin-bottom: 2rem;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background: hsl(var(--primary));
      transition: width 0.3s ease;
    }

    .form-card {
      background: hsl(var(--card));
      border: 1px solid hsl(var(--border));
      border-radius: 1rem;
      padding: 2rem;
    }

    .input-group {
      margin-bottom: 1.5rem;
    }

    .input-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: hsl(var(--foreground));
    }

    .input-group input,
    .input-group select,
    .input-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid hsl(var(--border));
      border-radius: 0.5rem;
      font-family: inherit;
      color: hsl(var(--foreground));
      background: hsl(var(--background));
      box-sizing: border-box;
    }

    .input-group input:focus,
    .input-group select:focus,
    .input-group textarea:focus {
      outline: none;
      border-color: hsl(var(--primary));
      box-shadow: 0 0 0 3px hsl(var(--primary) / 0.1);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .plan-selector {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .plan-card {
      border: 2px solid hsl(var(--border));
      border-radius: 0.75rem;
      padding: 1.5rem;
      cursor: pointer;
      transition: all 0.2s;
      text-align: center;
      position: relative;
    }

    .plan-card:hover {
      border-color: hsl(var(--primary));
    }

    .plan-card input[type="radio"] {
      position: absolute;
      opacity: 0;
    }

    .plan-card input[type="radio"]:checked + label {
      color: hsl(var(--primary));
    }

    .plan-card.selected {
      border-color: hsl(var(--primary));
      background: hsl(var(--primary) / 0.05);
    }

    .plan-badge {
      display: inline-block;
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .plan-name {
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: hsl(var(--foreground));
    }

    .plan-price {
      font-size: 1.5rem;
      font-weight: 700;
      color: hsl(var(--primary));
      margin-bottom: 0.5rem;
    }

    .plan-description {
      font-size: 0.875rem;
      color: hsl(var(--muted-foreground));
    }

    .mensagem-erro {
      color: #F44336;
      background-color: #FFEBEE;
      border: 1px solid #F44336;
      padding: 1rem;
      border-radius: 0.75rem;
      margin-bottom: 1rem;
    }

    .form-actions {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
    }

    .form-actions button {
      flex: 1;
      padding: 0.75rem;
      border: none;
      border-radius: 0.5rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-submit {
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-back {
      background: hsl(var(--muted));
      color: hsl(var(--foreground));
    }

    .type-selector {
      display: flex;
      gap: 0.75rem;
      margin-bottom: 2rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .type-button {
      padding: 0.75rem 1.5rem;
      border: 2px solid hsl(var(--border));
      border-radius: 0.75rem;
      background: transparent;
      color: hsl(var(--foreground));
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s;
      text-decoration: none;
      font-size: 0.9rem;
    }

    .type-button.active {
      background: hsl(var(--primary));
      color: hsl(var(--primary-foreground));
      border-color: hsl(var(--primary));
    }

    .checkbox-group {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.5rem;
      margin-top: 0.5rem;
    }

    .checkbox-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .checkbox-item input[type="checkbox"] {
      width: auto;
    }

    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }

      .plan-selector {
        grid-template-columns: 1fr;
      }

      .form-actions {
        flex-direction: column;
      }

      .checkbox-group {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <a href="../index.php" class="logo">
          <div class="heart-icon">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
          <span class="logo-text">Planner de Sonhos</span>
        </a>
        <nav class="nav">
          <a href="../index.php" class="nav-link">Início</a>
          <a href="login-unified.php" class="btn-primary">Login</a>
        </nav>
      </div>
    </div>
  </header>

  <main>
    <section class="page-content" style="padding-top: 4rem; min-height: 80vh">
      <div class="container">
        <div class="signup-container">
          <div class="signup-header">
            <h1>Cadastro</h1>
            <p>Crie sua conta em <?php echo ucfirst($userType); ?></p>
          </div>

          <div class="type-selector">
            <a href="?type=cliente" class="type-button <?php echo $userType === 'cliente' ? 'active' : ''; ?>">Cliente</a>
            <a href="?type=fornecedor" class="type-button <?php echo $userType === 'fornecedor' ? 'active' : ''; ?>">Fornecedor</a>
            <a href="?type=cerimonialista" class="type-button <?php echo $userType === 'cerimonialista' ? 'active' : ''; ?>">Cerimonialista</a>
          </div>

          <div class="progress-bar">
            <?php
            $totalSteps = 3;
            $progressPercent = ($signupStep / $totalSteps) * 100;
            ?>
            <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%"></div>
          </div>

          <?php 
          if ($signup_error) {
              echo "<div class='mensagem-erro'>" . htmlspecialchars($signup_error) . "</div>";
          }
          ?>

          <div class="form-card">
            <?php if ($signupStep == 1): ?>
              <h2>Informações Básicas</h2>
              <form method="POST">
                <input type="hidden" name="step" value="1">

                <?php if ($userType === 'cliente'): ?>
                  <div class="input-group">
                    <label>Nome</label>
                    <input type="text" name="nome" placeholder="Seu nome completo" required />
                  </div>

                  <div class="input-group">
                    <label>Nome do Cônjuge</label>
                    <input type="text" name="nome_conj" placeholder="Nome do cônjuge" required />
                  </div>

                  <div class="form-row">
                    <div class="input-group">
                      <label>Gênero</label>
                      <select name="genero" required>
                        <option value="">Selecione</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Feminino">Feminino</option>
                        <option value="Outro">Outro</option>
                      </select>
                    </div>
                    <div class="input-group">
                      <label>Idade</label>
                      <input type="number" name="idade" placeholder="Sua idade" required />
                    </div>
                  </div>

                  <div class="input-group">
                    <label>Telefone</label>
                    <input type="tel" name="telefone" placeholder="Seu telefone" required />
                  </div>

                  <div class="input-group">
                    <label>E-mail</label>
                    <input type="email" name="email" placeholder="Seu e-mail" required />
                  </div>

                <?php elseif ($userType === 'fornecedor'): ?>
                  <div class="input-group">
                    <label>Nome da Empresa</label>
                    <input type="text" name="nome" placeholder="Nome da empresa" required />
                  </div>

                  <div class="input-group">
                    <label>CNPJ</label>
                    <input type="text" name="cnpj" placeholder="XX.XXX.XXX/0001-XX" />
                  </div>

                  <div class="input-group">
                    <label>Endereço</label>
                    <input type="text" name="endereco" placeholder="Rua, número, cidade" />
                  </div>

                  <div class="input-group">
                    <label>Categoria</label>
                    <select name="categoria" required>
                      <option value="geral">Geral</option>
                      <option value="decoracao">Decoração</option>
                      <option value="catering">Catering</option>
                      <option value="fotografia">Fotografia</option>
                      <option value="musica">Música</option>
                      <option value="convites">Convites</option>
                      <option value="transporte">Transporte</option>
                    </select>
                  </div>

                  <div class="form-row">
                    <div class="input-group">
                      <label>E-mail</label>
                      <input type="email" name="email" placeholder="E-mail empresarial" required />
                    </div>
                    <div class="input-group">
                      <label>Telefone</label>
                      <input type="tel" name="telefone" placeholder="Telefone" required />
                    </div>
                  </div>

                <?php else: ?>
                  <div class="input-group">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" placeholder="Seu nome completo" required />
                  </div>

                  <div class="input-group">
                    <label>Especialização</label>
                    <select name="especializacao" required>
                      <option value="">Selecione</option>
                      <option value="civil">Casamento Civil</option>
                      <option value="religioso">Casamento Religioso</option>
                      <option value="simbolico">Casamento Simbólico</option>
                      <option value="completo">Múltiplas Especialidades</option>
                    </select>
                  </div>

                  <div class="form-row">
                    <div class="input-group">
                      <label>Anos de Experiência</label>
                      <input type="number" name="experiencia_anos" min="0" placeholder="Ex: 5" required />
                    </div>
                    <div class="input-group">
                      <label>E-mail</label>
                      <input type="email" name="email" placeholder="E-mail" required />
                    </div>
                  </div>

                  <div class="input-group">
                    <label>Telefone</label>
                    <input type="tel" name="telefone" placeholder="Telefone" required />
                  </div>
                <?php endif; ?>

                <div class="form-actions">
                  <button type="submit" class="btn-submit">Próximo</button>
                </div>

                <p style="text-align: center; margin-top: 1rem; font-size: 0.875rem; color: hsl(var(--muted-foreground));">
                  Já tem conta? <a href="login-unified.php?type=<?php echo $userType; ?>" style="color: hsl(var(--primary)); text-decoration: underline;">Faça login</a>
                </p>
              </form>

            <?php elseif ($signupStep == 2): ?>
              <h2>Criar Senha</h2>
              <form method="POST">
                <input type="hidden" name="step" value="2">

                <div class="input-group">
                  <label>Senha</label>
                  <input type="password" name="senha" placeholder="Escolha uma senha forte" required />
                </div>

                <div class="input-group">
                  <label>Confirmar Senha</label>
                  <input type="password" name="confirma_senha" placeholder="Confirme sua senha" required />
                </div>

                <div class="form-actions">
                  <button type="button" class="btn-back" onclick="history.back()">Voltar</button>
                  <button type="submit" class="btn-submit">Próximo</button>
                </div>
              </form>

            <?php elseif ($signupStep == 3): ?>
              <?php if ($userType === 'cliente'): ?>
                <h2>Informações do Casamento</h2>
                <form method="POST">
                  <input type="hidden" name="step" value="3">

                  <div class="input-group">
                    <label>Data do Casamento</label>
                    <input type="date" name="data_casamento" required />
                  </div>

                  <div class="input-group">
                    <label>Local do Casamento</label>
                    <input type="text" name="local_casamento" placeholder="Cidade/Estado ou Local específico" />
                  </div>

                  <div class="form-row">
                    <div class="input-group">
                      <label>Tipo de Cerimônia</label>
                      <select name="tipo_cerimonia">
                        <option value="">Selecione</option>
                        <option value="civil">Civil</option>
                        <option value="religioso">Religioso</option>
                        <option value="simbolico">Simbólico</option>
                      </select>
                    </div>
                    <div class="input-group">
                      <label>Quantidade de Convidados</label>
                      <input type="number" name="quantidade_convidados" min="1" placeholder="Ex: 100" />
                    </div>
                  </div>

                  <div class="input-group">
                    <label>Orçamento Total (R$)</label>
                    <input type="text" name="orcamento_total" placeholder="Ex: 50.000,00" required />
                  </div>

                  <div class="form-actions">
                    <button type="button" class="btn-back" onclick="history.back()">Voltar</button>
                    <button type="submit" class="btn-submit">Concluir Cadastro</button>
                  </div>
                </form>

              <?php elseif ($userType === 'fornecedor'): ?>
                <h2>Configurações da Empresa</h2>
                <form method="POST">
                  <input type="hidden" name="step" value="3">

                  <div class="input-group">
                    <label>Preço Mínimo (R$)</label>
                    <input type="text" name="preco_minimo" placeholder="Ex: 1.000,00" />
                  </div>

                  <div class="input-group">
                    <label>Horário de Funcionamento</label>
                    <input type="text" name="horario_funcionamento" placeholder="Ex: Seg-Dom 08h-22h" />
                  </div>

                  <div class="form-actions">
                    <button type="button" class="btn-back" onclick="history.back()">Voltar</button>
                    <button type="submit" class="btn-submit">Concluir Cadastro</button>
                  </div>
                </form>

              <?php else: ?>
                <h2>Configurações Profissionais</h2>
                <form method="POST">
                  <input type="hidden" name="step" value="3">

                  <div class="input-group">
                    <label>Valor Mínimo (R$)</label>
                    <input type="text" name="valor_minimo" placeholder="Ex: 500,00" />
                  </div>

                  <div class="input-group">
                    <label>Tipos de Cerimônia que Realiza</label>
                    <div class="checkbox-group">
                      <div class="checkbox-item">
                        <input type="checkbox" name="tipos_cerimonia[]" value="civil" />
                        <label style="margin: 0;">Civil</label>
                      </div>
                      <div class="checkbox-item">
                        <input type="checkbox" name="tipos_cerimonia[]" value="religioso" />
                        <label style="margin: 0;">Religioso</label>
                      </div>
                      <div class="checkbox-item">
                        <input type="checkbox" name="tipos_cerimonia[]" value="simbolico" />
                        <label style="margin: 0;">Simbólico</label>
                      </div>
                      <div class="checkbox-item">
                        <input type="checkbox" name="tipos_cerimonia[]" value="laico" />
                        <label style="margin: 0;">Laico</label>
                      </div>
                    </div>
                  </div>

                  <div class="form-actions">
                    <button type="button" class="btn-back" onclick="history.back()">Voltar</button>
                    <button type="submit" class="btn-submit">Concluir Cadastro</button>
                  </div>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </main>
</body>

</html>
