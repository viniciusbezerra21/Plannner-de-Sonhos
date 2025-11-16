-- phpMyAdmin SQL Dump
-- version 4.5.4.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 16-Nov-2025 às 22:31
-- Versão do servidor: 5.7.11
-- PHP Version: 7.0.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `weddingeasy`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `atividades_usuario`
--

CREATE TABLE `atividades_usuario` (
  `id_atividade` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_atividade` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_atividade` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id_avaliacao` int(11) NOT NULL,
  `avaliador_id` int(11) NOT NULL,
  `avaliado_id` int(11) NOT NULL,
  `nota` int(11) NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci,
  `data_avaliacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `avaliacoes`
--

INSERT INTO `avaliacoes` (`id_avaliacao`, `avaliador_id`, `avaliado_id`, `nota`, `comentario`, `data_avaliacao`, `updated_at`) VALUES
(1, 3, 4, 5, 'Excelente profissionalismo', '2025-11-15 02:44:19', '2025-11-15 02:44:19'),
(2, 4, 3, 4, 'Muito bom, recomendo', '2025-11-15 02:44:19', '2025-11-15 02:44:19'),
(3, 5, 6, 5, 'Perfeito, voltaria a contratar', '2025-11-15 02:44:19', '2025-11-15 02:44:19'),
(4, 6, 5, 5, 'Excepcional, muito satisfeito', '2025-11-15 02:44:19', '2025-11-15 02:44:19'),
(5, 7, 8, 4, 'Bom atendimento', '2025-11-15 02:44:19', '2025-11-15 02:44:19'),
(6, 8, 7, 4, 'Preço justo e qualidade', '2025-11-15 02:44:19', '2025-11-15 02:44:19'),
(7, 9, 10, 5, 'Superou expectativas', '2025-11-15 02:44:19', '2025-11-15 02:44:19'),
(8, 10, 9, 5, 'Recomendo fortemente', '2025-11-15 02:44:19', '2025-11-15 02:44:19'),
(9, 11, 12, 3, 'Atendeu bem, mas com atrasos', '2025-11-15 02:44:19', '2025-11-15 02:44:19'),
(10, 12, 11, 4, 'Boa qualidade', '2025-11-15 02:44:19', '2025-11-15 02:44:19');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cerimonialista_datas_bloqueadas`
--

CREATE TABLE `cerimonialista_datas_bloqueadas` (
  `id_bloqueio` int(11) NOT NULL,
  `id_cerimonialista` int(11) NOT NULL,
  `data_bloqueada` date NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `cerimonialista_fornecedores`
--

CREATE TABLE `cerimonialista_fornecedores` (
  `id_relacao` int(11) NOT NULL,
  `id_cerimonialista` int(11) NOT NULL,
  `id_fornecedor` int(11) NOT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `cerimonialista_indisponibilidade`
--

CREATE TABLE `cerimonialista_indisponibilidade` (
  `id_indisponibilidade` int(11) NOT NULL,
  `id_cerimonialista` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criada_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `cliente_cerimonialista`
--

CREATE TABLE `cliente_cerimonialista` (
  `id_assoc` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_cerimonialista` int(11) NOT NULL,
  `data_casamento` date NOT NULL,
  `status` enum('ativo','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `contatos`
--

CREATE TABLE `contatos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assunto` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_envio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_resposta` enum('pendente','respondida') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `resposta` text COLLATE utf8mb4_unicode_ci,
  `data_resposta` timestamp NULL DEFAULT NULL,
  `respondido_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `contatos`
--

INSERT INTO `contatos` (`id`, `nome`, `email`, `telefone`, `assunto`, `mensagem`, `data_envio`, `status_resposta`, `resposta`, `data_resposta`, `respondido_por`) VALUES
(1, 'Lucas Alves', 'lucas.alves@mail.com', NULL, NULL, 'Olá, quero informações sobre pacotes de casamento.', '2025-01-10 12:12:00', 'pendente', NULL, NULL, NULL),
(2, 'Mariana Pinto', 'mariana.pinto@mail.com', NULL, NULL, 'Solicito orçamento para 100 pessoas.', '2025-01-15 13:22:00', 'pendente', NULL, NULL, NULL),
(3, 'Gustavo Moreira', 'gustavo.moreira@mail.com', NULL, NULL, 'Qual a disponibilidade para julho de 2025?', '2025-02-02 14:05:00', 'pendente', NULL, NULL, NULL),
(4, 'Beatriz Rocha', 'beatriz.rocha@mail.com', NULL, NULL, 'Preciso de serviço de fotografia e filmagem.', '2025-02-14 15:00:00', 'pendente', NULL, NULL, NULL),
(5, 'Ronaldo Silva', 'ronaldo.silva@mail.com', NULL, NULL, 'Vocês trabalham com espaços ao ar livre?', '2025-02-20 16:30:00', 'pendente', NULL, NULL, NULL),
(6, 'Patrícia Gomes', 'patricia.gomes@mail.com', NULL, NULL, 'Solicito contratação de cerimonialista.', '2025-03-01 17:10:00', 'pendente', NULL, NULL, NULL),
(7, 'André Sousa', 'andre.sousa@mail.com', NULL, NULL, 'Quero degustação do buffet.', '2025-03-05 18:00:00', 'pendente', NULL, NULL, NULL),
(8, 'Fernanda Costa', 'fernanda.costa@mail.com', NULL, NULL, 'Tem opção vegetariana no buffet?', '2025-03-10 19:20:00', 'pendente', NULL, NULL, NULL),
(9, 'Mauricio Ferreira', 'mauricio.ferreira@mail.com', NULL, NULL, 'Como funciona o pagamento parcelado?', '2025-03-18 12:40:00', 'pendente', NULL, NULL, NULL),
(10, 'Larissa Alves', 'larissa.alves@mail.com', NULL, NULL, 'Solicito orçamento para decoração rústica.', '2025-03-25 13:50:00', 'pendente', NULL, NULL, NULL),
(11, 'Renata Dias', 'renata.dias@mail.com', NULL, NULL, 'Tem lista de fornecedores recomendados?', '2025-04-02 14:00:00', 'pendente', NULL, NULL, NULL),
(12, 'Fábio Pires', 'fabio.pires@mail.com', NULL, NULL, 'Qual o prazo para confirmação de data?', '2025-04-09 15:30:00', 'pendente', NULL, NULL, NULL),
(13, 'Sabrina Melo', 'sabrina.melo@mail.com', NULL, NULL, 'Posso agendar uma visita ao espaço?', '2025-04-16 16:45:00', 'pendente', NULL, NULL, NULL),
(14, 'Vitor Campos', 'vitor.campos@mail.com', NULL, NULL, 'Vocês oferecem pacotes completos?', '2025-04-22 17:55:00', 'pendente', NULL, NULL, NULL),
(15, 'Carolina Prado', 'carolina.prado@mail.com', NULL, NULL, 'Solicito contato de fornecedor de som.', '2025-04-28 18:10:00', 'pendente', NULL, NULL, NULL),
(16, 'Henrique Lima', 'henrique.lima@mail.com', NULL, NULL, 'Quero contratar DJ para 6 horas.', '2025-05-03 19:25:00', 'pendente', NULL, NULL, NULL),
(17, 'Sílvia Torres', 'silvia.torres@mail.com', NULL, NULL, 'Tem desconto para contratos fechados com antecedência?', '2025-05-09 12:15:00', 'pendente', NULL, NULL, NULL),
(18, 'Rodrigo Bastos', 'rodrigo.bastos@mail.com', NULL, NULL, 'Preciso de orçamento para 50 pessoas.', '2025-05-14 13:35:00', 'pendente', NULL, NULL, NULL),
(19, 'Adriana Moraes', 'adriana.moraes@mail.com', NULL, NULL, 'Tem serviço de barista no evento?', '2025-05-20 14:45:00', 'pendente', NULL, NULL, NULL),
(20, 'Marcelo Teixeira', 'marcelo.teixeira@mail.com', NULL, NULL, 'Como funciona a política de cancelamento?', '2025-05-26 15:55:00', 'pendente', NULL, NULL, NULL),
(21, 'Helena Duarte', 'helena.duarte@mail.com', NULL, NULL, 'Necessito de iluminação especial para cerimônia.', '2025-06-01 16:05:00', 'pendente', NULL, NULL, NULL),
(22, 'Otávio Ramos', 'otavio.ramos@mail.com', NULL, NULL, 'Quero orçamento para filmagem com drone.', '2025-06-07 17:15:00', 'pendente', NULL, NULL, NULL),
(23, 'Simone Cardoso', 'simone.cardoso@mail.com', NULL, NULL, 'Tem pacote lua de mel?', '2025-06-12 18:25:00', 'pendente', NULL, NULL, NULL),
(24, 'Eduardo Gomes', 'eduardo.gomes@mail.com', NULL, NULL, 'Como agendar degustação?', '2025-06-18 19:35:00', 'pendente', NULL, NULL, NULL),
(25, 'Patrícia Nunes', 'patricia.nunes@mail.com', NULL, NULL, 'Vocês fazem convites personalizados?', '2025-06-24 12:45:00', 'pendente', NULL, NULL, NULL),
(26, 'Rogério Campos', 'rogerio.campos@mail.com', NULL, NULL, 'Quero informações sobre aluguel de trajes.', '2025-06-30 13:55:00', 'pendente', NULL, NULL, NULL),
(27, 'Aline Barbosa', 'aline.barbosa@mail.com', NULL, NULL, 'Tem fornecedores para cerimônias ao ar livre?', '2025-07-05 14:05:00', 'pendente', NULL, NULL, NULL),
(28, 'Júlio Cesar', 'julio.cesar@mail.com', NULL, NULL, 'Preciso de montagem de toldos para evento', '2025-07-11 15:15:00', 'pendente', NULL, NULL, NULL),
(29, 'Priscila Azevedo', 'priscila.azevedo@mail.com', NULL, NULL, 'Como funciona o pacote infantil?', '2025-07-16 16:25:00', 'pendente', NULL, NULL, NULL),
(30, 'Walério Santos', 'walerio.santos@mail.com', NULL, NULL, 'Solicito contato do fornecedor de bolos', '2025-07-22 17:35:00', 'pendente', NULL, NULL, NULL),
(31, 'Nathalia Souza', 'nathalia.souza@mail.com', NULL, NULL, 'Quero orçamento com opções veganas', '2025-07-28 18:45:00', 'pendente', NULL, NULL, NULL),
(32, 'Cássio Nascimento', 'cassio.nascimento@mail.com', NULL, NULL, 'Tem espaço com estacionamento?', '2025-08-03 12:05:00', 'pendente', NULL, NULL, NULL),
(33, 'Débora Almeida', 'debora.almeida@mail.com', NULL, NULL, 'Quero contratar fotógrafo para 8h', '2025-08-09 13:15:00', 'pendente', NULL, NULL, NULL),
(34, 'Leandro Rocha', 'leandro.rocha@mail.com', NULL, NULL, 'Solicito lista de valores para buffet', '2025-08-15 14:25:00', 'pendente', NULL, NULL, NULL),
(35, 'Irene Fernandes', 'irene.fernandes@mail.com', NULL, NULL, 'Como funciona a montagem do mobiliário?', '2025-08-21 15:35:00', 'pendente', NULL, NULL, NULL),
(36, 'Mauro Bento', 'mauro.bento@mail.com', NULL, NULL, 'Preciso de iluminação e som', '2025-08-27 16:45:00', 'pendente', NULL, NULL, NULL),
(37, 'Regina Pacheco', 'regina.pacheco@mail.com', NULL, NULL, 'Quais formas de pagamento aceitam?', '2025-09-02 17:55:00', 'pendente', NULL, NULL, NULL),
(38, 'Vânia Lopes', 'vania.lopes@mail.com', NULL, NULL, 'Tem pacotes promocionais para maio?', '2025-09-08 18:05:00', 'pendente', NULL, NULL, NULL),
(39, 'Fábio Oliveira', 'fabio.oliveira@mail.com', NULL, NULL, 'Solicito orçamento de filmagem', '2025-09-14 12:15:00', 'pendente', NULL, NULL, NULL),
(40, 'Isabel Castro', 'isabel.castro@mail.com', NULL, NULL, 'Gostaria de agendar reunião presencial', '2025-09-20 13:25:00', 'pendente', NULL, NULL, NULL),
(41, 'Nuno Pereira', 'nuno.pereira@mail.com', NULL, NULL, 'Como funciona o contrato de bar?', '2025-09-26 14:35:00', 'pendente', NULL, NULL, NULL),
(42, 'Paula Miranda', 'paula.miranda@mail.com', NULL, NULL, 'Tem atendimento online?', '2025-09-28 15:45:00', 'pendente', NULL, NULL, NULL),
(43, 'Rui Carvalho', 'rui.carvalho@mail.com', NULL, NULL, 'Gostaria de visitação ao espaço', '2025-09-29 16:00:00', 'pendente', NULL, NULL, NULL),
(44, 'Suelen Matos', 'suelen.matos@mail.com', NULL, NULL, 'Solicito orçamento urgente', '2025-09-29 16:05:00', 'pendente', NULL, NULL, NULL),
(45, 'Katapimbas', 'vinibizarro@email.com', NULL, NULL, 'Receba', '2025-10-01 11:20:12', 'pendente', NULL, NULL, NULL),
(46, 'Katapimbas', 'vinibizarro@email.com', NULL, NULL, 'Receba', '2025-10-01 11:20:50', 'pendente', NULL, NULL, NULL),
(47, 'Katapimbas', 'vinibizarro@email.com', NULL, NULL, '4245245', '2025-10-01 11:21:34', 'pendente', NULL, NULL, NULL),
(48, 'kaue', 'kauekfs@hotmail.com', NULL, NULL, 'receba casca de bala', '2025-10-08 12:18:55', 'pendente', NULL, NULL, NULL),
(49, 'Katapimbas', 'vinibizarro@email.com', '41769696969', 'vendas', 'uhjtherjhue', '2025-10-08 12:24:55', 'pendente', NULL, NULL, NULL),
(50, 'kaue', 'kauekfs@hotmail.com', '41769696969', 'vendas', 'receba casca de bala', '2025-10-08 12:39:16', 'pendente', NULL, NULL, NULL),
(51, 'Vinicius Gabriel Bizarro', 'vinibizarro@email.com', '47991149148', 'duvidas', 'a', '2025-11-16 21:31:04', 'respondida', NULL, NULL, NULL),
(52, 'Vinicius Gabriel Bizarro', 'vinibizarro@email.com', 'aaa', 'suporte', 'dada', '2025-11-16 21:55:10', 'respondida', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `contratos`
--

CREATE TABLE `contratos` (
  `id_contrato` int(11) NOT NULL,
  `nome_fornecedor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `arquivo_pdf` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_assinatura` date NOT NULL,
  `data_validade` date NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `status` enum('ativo','vencido','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `id_usuario` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `contratos`
--

INSERT INTO `contratos` (`id_contrato`, `nome_fornecedor`, `categoria`, `arquivo_pdf`, `data_assinatura`, `data_validade`, `valor`, `status`, `observacoes`, `id_usuario`, `created_at`, `updated_at`) VALUES
(1, 'Flor & Arte', 'Decoração', 'contrato-flor-arte.pdf', '2025-08-05', '2026-08-05', '5000.00', 'ativo', 'Contrato para decoração completa do evento', 1, '2025-09-29 13:21:24', '2025-09-29 13:21:24'),
(2, 'Sabor & Festa', 'Buffet', 'contrato-sabor-festa.pdf', '2025-07-10', '2026-07-10', '8000.00', 'ativo', 'Serviço de buffet para 150 pessoas', 1, '2025-09-29 13:21:24', '2025-09-29 13:21:24'),
(3, 'FotoLux', 'Fotografia', 'contrato-fotolux.pdf', '2025-06-20', '2026-06-20', '3500.00', 'ativo', 'Cobertura fotográfica completa do casamento', 1, '2025-09-29 13:21:24', '2025-09-29 13:21:24'),
(5, 'Fornecedor 1 - Flores & Arte', 'Decoração', 'contrato-5-flor-arte.pdf', '2024-09-10', '2025-09-10', '2500.00', 'ativo', 'Decoração básica', 3, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(6, 'Fornecedor 2 - Sabor & Festa', 'Buffet', 'contrato-6-buffet.pdf', '2025-01-15', '2026-01-15', '7200.00', 'ativo', 'Buffet completo', 4, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(7, 'Fornecedor 3 - FotoLux', 'Fotografia', 'contrato-7-fotolux.pdf', '2025-03-20', '2026-03-20', '3500.00', 'ativo', 'Cobertura + álbum', 5, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(8, 'Fornecedor 4 - Som & Luz', 'Som', 'contrato-8-somluz.pdf', '2024-11-05', '2025-11-05', '1800.00', 'ativo', 'Sistema + técnico', 6, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(9, 'Fornecedor 5 - Buffet Mix', 'Buffet', 'contrato-9-buffetmix.pdf', '2024-12-10', '2025-12-10', '6000.00', 'vencido', 'Valor pago em 2x', 7, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(10, 'Fornecedor 6 - Bolo & Cia', 'Confeitaria', 'contrato-10-bolo.pdf', '2025-02-14', '2026-02-14', '500.00', 'ativo', 'Bolo de 3 andares', 8, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(11, 'Fornecedor 7 - Vestidos & Cia', 'Trajes', 'contrato-11-vestidos.pdf', '2025-04-01', '2026-04-01', '1200.00', 'ativo', 'Aluguel vestido', 9, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(12, 'Fornecedor 8 - Decora Festa', 'Decoração', 'contrato-12-decorafesta.pdf', '2025-05-10', '2026-05-10', '1800.00', 'ativo', 'Decoração extra', 10, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(13, 'Fornecedor 9 - Cerimonial Express', 'Cerimonial', 'contrato-13-cerimonial.pdf', '2025-06-20', '2026-06-20', '900.00', 'ativo', 'Cerimonial 8h', 11, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(14, 'Fornecedor 10 - Locadora de Móveis', 'Locação', 'contrato-14-moveis.pdf', '2024-10-02', '2025-10-02', '400.00', 'cancelado', 'Cancelado por incompatibilidade de agenda', 12, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(15, 'Fornecedor 11 - Bar Itinerante', 'Bar', 'contrato-15-bar.pdf', '2025-07-11', '2026-07-11', '800.00', 'ativo', 'Bar itinerante 4h', 13, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(16, 'Fornecedor 12 - Foto360', 'Fotografia', 'contrato-16-foto360.pdf', '2025-08-01', '2026-08-01', '650.00', 'ativo', 'Cabine 360', 14, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(17, 'Fornecedor 13 - Open Bar Pro', 'Bar', 'contrato-17-openbar.pdf', '2024-09-15', '2025-09-15', '4200.00', 'vencido', 'Contrato antigo', 15, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(18, 'Fornecedor 14 - DJ Fest', 'DJ', 'contrato-18-dj.pdf', '2025-03-01', '2026-03-01', '800.00', 'ativo', 'DJ 6h', 16, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(19, 'Fornecedor 15 - Ilumina Eventos', 'Iluminação', 'contrato-19-ilumina.pdf', '2025-02-20', '2026-02-20', '400.00', 'ativo', 'Iluminação cênica', 17, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(20, 'Fornecedor 16 - SomMaster', 'Som', 'contrato-20-sommaster.pdf', '2025-06-10', '2026-06-10', '1200.00', 'ativo', 'Som + técnico', 18, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(21, 'Fornecedor 17 - Foto Glam', 'Fotografia', 'contrato-21-fotoglam.pdf', '2024-11-11', '2025-11-11', '900.00', 'vencido', 'Ensaio e cobertura', 19, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(22, 'Fornecedor 18 - Bolos & Doces', 'Confeitaria', 'contrato-22-bolos.pdf', '2025-07-01', '2026-07-01', '450.00', 'ativo', 'Sobremesas buffet', 20, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(23, 'Fornecedor 19 - Buffet Premium', 'Buffet', 'contrato-23-buffetpremium.pdf', '2025-01-05', '2026-01-05', '14000.00', 'ativo', 'Buffet premium 200p', 21, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(24, 'Fornecedor 20 - Flores do Campo', 'Floricultura', 'contrato-24-flores.pdf', '2024-12-30', '2025-12-30', '900.00', 'ativo', 'Flores para cerimônia', 22, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(25, 'Fornecedor 21 - FotoRápida', 'Fotografia', 'contrato-25-fotorapida.pdf', '2025-05-05', '2026-05-05', '700.00', 'ativo', 'Cobertura e fotos digitais', 23, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(26, 'Fornecedor 22 - Aluguel Trajes', 'Trajes', 'contrato-26-trajes.pdf', '2025-04-20', '2026-04-20', '350.00', 'ativo', 'Traje noivo e padrinhos', 24, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(27, 'Fornecedor 23 - Carro Cerimonial', 'Transporte', 'contrato-27-carro.pdf', '2025-02-02', '2026-02-02', '480.00', 'ativo', 'Carro clássico para chegada', 25, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(28, 'Fornecedor 24 - Convites Express', 'Convites', 'contrato-28-convites.pdf', '2024-10-20', '2025-10-20', '260.00', 'cancelado', 'Design não aprovado', 26, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(29, 'Fornecedor 25 - Lembrancinhas Ltda', 'Lembrancinhas', 'contrato-29-lembrancas.pdf', '2025-06-25', '2026-06-25', '200.00', 'ativo', '100 unidades personalizadas', 27, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(30, 'Fornecedor 26 - Móveis Rústicos', 'Locação', 'contrato-30-moveisrusticos.pdf', '2025-07-30', '2026-07-30', '1200.00', 'ativo', 'Móveis para recepção', 28, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(31, 'Fornecedor 27 - Buffet Vegetariano', 'Buffet', 'contrato-31-veg.pdf', '2024-09-01', '2025-09-01', '5000.00', 'vencido', 'Evento anterior', 29, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(32, 'Fornecedor 28 - Espaço Jardim', 'Espaço', 'contrato-32-espaco.pdf', '2025-03-12', '2026-03-12', '7000.00', 'ativo', 'Aluguel espaço dia inteiro', 30, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(33, 'Fornecedor 29 - Fotógrafo Drone', 'Filmagem', 'contrato-33-drone.pdf', '2025-08-08', '2026-08-08', '950.00', 'ativo', 'Drone + filmagem aérea', 31, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(34, 'Fornecedor 30 - Banda Viva', 'Banda', 'contrato-34-banda.pdf', '2025-01-20', '2026-01-20', '3000.00', 'ativo', 'Banda 5 integrantes', 32, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(35, 'Fornecedor 31 - Segurança Eventos', 'Segurança', 'contrato-35-seguranca.pdf', '2025-04-07', '2026-04-07', '600.00', 'ativo', 'Equipe 4 seguranças', 33, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(36, 'Fornecedor 32 - Transporte VIP', 'Transporte', 'contrato-36-transporte.pdf', '2025-05-22', '2026-05-22', '900.00', 'ativo', 'Traslados no dia', 34, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(37, 'Fornecedor 33 - Som Profissional', 'Som', 'contrato-37-somprof.pdf', '2025-06-02', '2026-06-02', '1300.00', 'ativo', 'Som + técnico', 35, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(38, 'Fornecedor 34 - Iluminação LED', 'Iluminação', 'contrato-38-led.pdf', '2025-07-09', '2026-07-09', '500.00', 'ativo', 'LED cênico', 36, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(39, 'Fornecedor 35 - Buffet Infantil', 'Buffet', 'contrato-39-infantil.pdf', '2025-08-18', '2026-08-18', '2200.00', 'ativo', 'Buffet kids', 37, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(40, 'Fornecedor 36 - Barista Eventos', 'Bar', 'contrato-40-barista.pdf', '2024-12-05', '2025-12-05', '450.00', 'vencido', 'Evento passado', 38, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(41, 'Fornecedor 37 - DJ Premium', 'DJ', 'contrato-41-djpremium.pdf', '2025-01-30', '2026-01-30', '1600.00', 'ativo', 'DJ 8h', 39, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(42, 'Fornecedor 38 - Foto Retro', 'Fotografia', 'contrato-42-retro.pdf', '2025-02-28', '2026-02-28', '600.00', 'ativo', 'Ensaio vintage', 40, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(43, 'Fornecedor 39 - Aluguel Toldos', 'Locação', 'contrato-43-toldos.pdf', '2025-03-05', '2026-03-05', '800.00', 'ativo', 'Toldos para área externa', 41, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(44, 'Fornecedor 40 - Fornecedora Cakes', 'Confeitaria', 'contrato-44-cakes.pdf', '2025-04-12', '2026-04-12', '700.00', 'ativo', 'Bolos e doces', 42, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(45, 'Fornecedor 41 - Filmagem Pro', 'Filmagem', 'contrato-45-filmagem.pdf', '2025-05-17', '2026-05-17', '2000.00', 'ativo', 'Filmagem completa', 43, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(46, 'Fornecedor 42 - Assessoria Top', 'Assessoria', 'contrato-46-assessoria.pdf', '2025-06-29', '2026-06-29', '1500.00', 'ativo', 'Assessoria completa', 44, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(47, 'Fornecedor 43 - Estética Noivas', 'Beleza', 'contrato-47-estetica.pdf', '2025-07-03', '2026-07-03', '900.00', 'ativo', 'Penteado e maquiagem', 45, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(48, 'Fornecedor 44 - Viagens Lua de Mel', 'Viagem', 'contrato-48-viagem.pdf', '2024-11-20', '2025-11-20', '8000.00', 'vencido', 'Pacote antigo', 46, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(49, 'Fornecedor 45 - Bar Móvel', 'Bar', 'contrato-49-barmovel.pdf', '2025-08-12', '2026-08-12', '600.00', 'ativo', 'Bar móvel básico', 47, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(50, 'Fornecedor 46 - Foto Estúdio', 'Fotografia', 'contrato-50-fotoestudio.pdf', '2025-06-06', '2026-06-06', '500.00', 'ativo', 'Ensaio e fotos', 48, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(51, 'Fornecedor 47 - Art Floral', 'Floricultura', 'contrato-51-artfloral.pdf', '2025-05-02', '2026-05-02', '700.00', 'ativo', 'Centros de mesa', 49, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(52, 'Fornecedor 48 - Arte Convites', 'Convites', 'contrato-52-arteconvites.pdf', '2025-04-15', '2026-04-15', '320.00', 'ativo', 'Convites personalizados', 50, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(53, 'Fornecedor 49 - Balões & Festa', 'Decoração', 'contrato-53-baloes.pdf', '2025-02-10', '2026-02-10', '250.00', 'ativo', 'Decoração com balões', 51, '2025-09-29 16:30:00', '2025-09-29 16:30:00'),
(57, 'Receba int.', 'moda', '', '2025-10-22', '2026-10-22', '0.00', 'ativo', NULL, 52, '2025-10-22 13:15:27', '2025-10-22 13:15:27'),
(58, 'Fornecedor 36 - Barista Eventos', 'buffet', '', '2025-10-22', '2026-10-22', '0.00', 'ativo', NULL, 52, '2025-10-22 13:20:09', '2025-10-22 13:20:09'),
(59, 'Fornecedor 47 - Art Floral', 'buffet', '', '2025-10-22', '2026-10-22', '0.00', 'ativo', NULL, 52, '2025-10-22 13:20:10', '2025-10-22 13:20:10'),
(60, 'Fornecedor 27 - Buffet Vegetariano', 'buffet', '', '2025-10-22', '2026-10-22', '0.00', 'ativo', NULL, 52, '2025-10-22 13:20:11', '2025-10-22 13:20:11'),
(61, 'Fornecedor 47 - Art Floral', 'buffet', '', '2025-10-22', '2026-10-22', '0.00', 'ativo', NULL, 52, '2025-10-22 13:20:12', '2025-10-22 13:20:12'),
(62, 'Fornecedor 18 - Bolos & Doces', 'buffet', '', '2025-10-22', '2026-10-22', '0.00', 'ativo', NULL, 52, '2025-10-22 13:20:14', '2025-10-22 13:20:14'),
(63, 'Fornecedor 28 - Espaço Jardim', 'buffet', '', '2025-10-22', '2026-10-22', '0.00', 'ativo', NULL, 52, '2025-10-22 13:20:16', '2025-10-22 13:20:16'),
(64, 'Fornecedor 18 - Bolos & Doces', 'buffet', '', '2025-10-22', '2026-10-22', '0.00', 'ativo', NULL, 52, '2025-10-22 13:20:18', '2025-10-22 13:20:18'),
(65, 'Fornecedor 28 - Espaço Jardim', 'buffet', '', '2025-10-22', '2026-10-22', '0.00', 'ativo', NULL, 52, '2025-10-22 13:20:19', '2025-10-22 13:20:19'),
(66, 'Fornecedor 18 - Bolos & Doces', 'buffet', '', '2025-10-22', '2026-10-22', '0.00', 'ativo', NULL, 52, '2025-10-22 13:20:21', '2025-10-22 13:20:21'),
(68, 'Moda e Luxo int.', 'moda', '', '2025-10-23', '2026-10-23', '0.00', 'ativo', NULL, 2, '2025-10-23 14:46:47', '2025-10-23 14:46:47'),
(69, 'Moda e Luxo int.', 'moda', '', '2025-10-23', '2026-10-23', '0.00', 'ativo', NULL, 2, '2025-10-23 14:46:48', '2025-10-23 14:46:48'),
(70, 'Fornecedor 16 - SomMaster', 'buffet', '', '2025-10-29', '2026-10-29', '0.00', 'ativo', NULL, 2, '2025-10-29 11:43:35', '2025-10-29 11:43:35');

-- --------------------------------------------------------

--
-- Estrutura da tabela `eventos`
--

CREATE TABLE `eventos` (
  `id_evento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nome_evento` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_evento` date NOT NULL,
  `horario` time NOT NULL,
  `local` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `prioridade` enum('alta','media','baixa') COLLATE utf8mb4_unicode_ci DEFAULT 'media',
  `cor_tag` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'azul'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `eventos`
--

INSERT INTO `eventos` (`id_evento`, `id_usuario`, `nome_evento`, `data_evento`, `horario`, `local`, `tags`, `descricao`, `status`, `prioridade`, `cor_tag`) VALUES
(1, 3, 'Casamento Ana & Carlos', '2025-05-10', '16:00:00', 'Igreja Matriz', 'casamento,cerimonia', NULL, 'pendente', 'media', 'azul'),
(2, 4, 'Aniversário Bruno', '2025-08-08', '20:00:00', 'Salão Central', 'aniversario,festa', NULL, 'pendente', 'media', 'azul'),
(3, 5, 'Ensaio Fotográfico Carla', '2025-04-20', '10:00:00', 'Estúdio FotoLux', 'ensaio,fotos', NULL, 'pendente', 'media', 'azul'),
(4, 6, 'Reunião Cerimonial', '2025-03-02', '09:00:00', 'Espaço Cowork', 'reuniao,cerimonial', NULL, 'concluido', 'media', 'azul'),
(5, 7, 'Degustação Buffet', '2025-02-14', '14:00:00', 'Sabor & Festa', 'degustacao,buffet', NULL, 'concluido', 'media', 'azul'),
(6, 8, 'Prova Vestido', '2025-06-12', '11:00:00', 'Vestidos & Cia', 'prova,vestido', NULL, 'pendente', 'media', 'azul'),
(7, 9, 'Feira Fornecedores', '2025-09-30', '09:00:00', 'Centro de Convenções', 'feira,fornecedores', NULL, 'pendente', 'media', 'azul'),
(8, 10, 'Cerimônia Laura & Diego', '2025-10-12', '17:00:00', 'Espaço Jardim', 'casamento,cerimonia', NULL, 'pendente', 'media', 'azul'),
(9, 11, 'Aniversário Infantil', '2025-11-05', '15:00:00', 'Buffet Kids', 'aniversario,infa', NULL, 'pendente', 'media', 'azul'),
(10, 12, 'Workshop Fotografia', '2025-07-18', '08:30:00', 'FotoLux', 'workshop,foto', NULL, 'pendente', 'media', 'azul'),
(11, 13, 'Wedding Expo', '2025-09-15', '10:00:00', 'Centro de Eventos', 'expo,casamento', NULL, 'pendente', 'media', 'azul'),
(12, 14, 'Coquetel Corporativo', '2025-06-22', '19:00:00', 'Hotel Plaza', 'corporativo,coquetel', NULL, 'concluido', 'media', 'azul'),
(13, 15, 'Aniversário 30 anos', '2025-04-05', '21:00:00', 'Salão Ouro', 'aniversario,festa', NULL, 'concluido', 'media', 'azul'),
(14, 16, 'Prova de Cardápio', '2025-05-27', '12:00:00', 'Sabor & Festa', 'prova,cardapio', NULL, 'pendente', 'media', 'azul'),
(15, 17, 'Ensaio Noivos', '2025-08-28', '09:30:00', 'Praça Central', 'ensaio,noivos', NULL, 'pendente', 'media', 'azul'),
(16, 18, 'Feira Gastronomia', '2025-10-01', '11:00:00', 'Parque das Exposições', 'feira,gastronomia', NULL, 'pendente', 'media', 'azul'),
(17, 19, 'Degustação Doces', '2025-03-21', '15:00:00', 'Bolos & Doces', 'degustacao,doces', NULL, 'concluido', 'media', 'azul'),
(18, 20, 'Cerimônia Simples', '2025-06-14', '16:30:00', 'Igreja do Bairro', 'cerimonia,simples', NULL, 'pendente', 'media', 'azul'),
(19, 21, 'Ensaio Sunset', '2025-09-01', '17:30:00', 'Praia', 'ensaio,fotografia', NULL, 'pendente', 'media', 'azul'),
(20, 22, 'Reunião Fornecedores', '2025-04-09', '10:00:00', 'Sala Reuniões', 'reuniao,fornecedores', NULL, 'concluido', 'media', 'azul'),
(21, 23, 'Cerimônia de Votos', '2025-11-22', '18:00:00', 'Capela', 'casamento,votos', NULL, 'pendente', 'media', 'azul'),
(22, 24, 'Degustação Vegetariana', '2025-12-05', '13:00:00', 'Buffet Veg', 'degustacao,veg', NULL, 'pendente', 'media', 'azul'),
(23, 25, 'Aniversário Surpresa', '2025-03-12', '20:00:00', 'Casa de Festas', 'surpresa,aniversario', NULL, 'concluido', 'media', 'azul'),
(24, 26, 'Prova Convites', '2025-02-25', '15:00:00', 'Convites Express', 'prova,convites', NULL, 'concluido', 'media', 'azul'),
(25, 27, 'Montagem Espaço', '2025-07-30', '08:00:00', 'Espaço Jardim', 'montagem,decoracao', NULL, 'pendente', 'media', 'azul'),
(26, 28, 'Ensaio Família', '2025-01-18', '10:30:00', 'Estúdio Foto', 'ensaio,familia', NULL, 'concluido', 'media', 'azul'),
(27, 29, 'Reunião Banda', '2025-06-07', '14:00:00', 'Sala Ensaios', 'banda,musica', NULL, 'pendente', 'media', 'azul'),
(28, 30, 'Evento Corporativo', '2025-11-10', '09:00:00', 'Centro Empresarial', 'corporativo,eventos', NULL, 'pendente', 'media', 'azul'),
(29, 31, 'Montagem Toldos', '2025-08-03', '07:00:00', 'Parque Externo', 'montagem,toldos', NULL, 'pendente', 'media', 'azul'),
(30, 32, 'Transporte Convidados', '2025-09-09', '18:00:00', 'Hotel Centro', 'transporte,convidados', NULL, 'pendente', 'media', 'azul'),
(31, 33, 'Teste Som', '2025-10-20', '10:00:00', 'Local Evento', 'teste,som', NULL, 'pendente', 'media', 'azul'),
(32, 34, 'Reunião Assessoria', '2025-03-29', '11:00:00', 'Assessoria Top', 'reuniao,assessoria', NULL, 'concluido', 'media', 'azul'),
(33, 35, 'Prova Menu Infantil', '2025-02-11', '14:30:00', 'Buffet Infantil', 'prova,infantil', NULL, 'concluido', 'media', 'azul'),
(34, 36, 'Barista Demo', '2025-05-19', '16:00:00', 'Espaço Café', 'demo,barista', NULL, 'pendente', 'media', 'azul'),
(35, 37, 'DJ Set Especial', '2025-04-28', '22:00:00', 'Salão Central', 'dj,musica', NULL, 'pendente', 'media', 'azul'),
(36, 38, 'Ensaios Vintage', '2025-06-02', '09:00:00', 'Estúdio Retro', 'ensaio,retro', NULL, 'pendente', 'media', 'azul'),
(37, 39, 'Reunião Segurança', '2025-07-15', '10:00:00', 'Sala Segurança', 'reuniao,seguranca', NULL, 'concluido', 'media', 'azul'),
(38, 40, 'Prova Bolos', '2025-08-25', '13:00:00', 'Fornecedora Cakes', 'prova,bolos', NULL, 'pendente', 'media', 'azul'),
(39, 41, 'Filmagem Teste', '2025-09-12', '09:00:00', 'Local Filmagem', 'teste,filmagem', NULL, 'pendente', 'media', 'azul'),
(40, 42, 'Reunião Lua de Mel', '2025-10-05', '11:00:00', 'Agência Viagens', 'reuniao,viagem', NULL, 'pendente', 'media', 'azul'),
(41, 43, 'Prova Beleza Noiva', '2025-01-30', '10:00:00', 'Estética Noivas', 'prova,belez', NULL, 'concluido', 'media', 'azul'),
(42, 44, 'Pacote Viagem', '2025-02-14', '12:00:00', 'Agência Central', 'viagem,pacote', NULL, 'concluido', 'media', 'azul'),
(43, 45, 'Coquetel Demo Bar', '2025-03-23', '19:00:00', 'Bar Móvel', 'coquetel,bar', NULL, 'pendente', 'media', 'azul'),
(44, 46, 'Ensaio Estúdio', '2025-04-16', '09:30:00', 'Foto Estúdio', 'ensaio,estudio', NULL, 'concluido', 'media', 'azul'),
(45, 47, 'Decoração Centro Mesa', '2025-05-06', '08:00:00', 'Ateliê Floral', 'decoracao,floral', NULL, 'pendente', 'media', 'azul'),
(46, 48, 'Reunião Convites', '2025-06-11', '15:30:00', 'Arte Convites', 'reuniao,convites', NULL, 'concluido', 'media', 'azul'),
(47, 49, 'Montagem Balões', '2025-07-21', '07:30:00', 'Espaço A', 'montagem,baloes', NULL, 'pendente', 'media', 'azul'),
(48, 50, 'Catering Express', '2025-08-14', '12:00:00', 'Local B', 'catering,almoco', NULL, 'pendente', 'media', 'azul'),
(49, 2, 'Acho que é verde', '2025-10-29', '11:05:00', 'Grama', '', 'Verde', 'pendente', 'baixa', 'verde'),
(50, 52, 'EEEEEEEEEEE', '2025-10-31', '13:34:00', 'Casa', '', 'dasdas', 'pendente', 'media', 'amarelo'),
(51, 52, 'w12321', '2025-10-30', '10:37:00', 'dsa', '', 'sadasd', 'pendente', 'media', 'verde'),
(52, 52, 'Testa da Silva', '2025-10-28', '13:35:00', '21321', '', 'Jantar', 'pendente', 'media', 'vermelho');

-- --------------------------------------------------------

--
-- Estrutura da tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id_fornecedor` int(11) NOT NULL,
  `nome_fornecedor` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avaliacao` decimal(2,1) DEFAULT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'buffet',
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apenas_pacotes` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `foto_perfil` varchar(225) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id_fornecedor`, `nome_fornecedor`, `avaliacao`, `categoria`, `telefone`, `email`, `descricao`, `senha`, `remember_token`, `apenas_pacotes`, `created_at`, `foto_perfil`) VALUES
(6, 'Fornecedor 6 - Bolo & Cia', '4.7', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(7, 'Fornecedor 7 - Vestidos & Cia', '4.0', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(8, 'Fornecedor 8 - Decora Festa', '3.9', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(9, 'Fornecedor 9 - Cerimonial Express', '4.3', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(10, 'Fornecedor 10 - Locadora de Móveis', '4.0', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(11, 'Fornecedor 11 - Bar Itinerante', '4.4', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(12, 'Fornecedor 12 - Foto360', '4.2', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(13, 'Fornecedor 13 - Open Bar Pro', '4.6', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(14, 'Fornecedor 14 - DJ Fest', '4.1', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(15, 'Fornecedor 15 - Ilumina Eventos', '4.2', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(16, 'Fornecedor 16 - SomMaster', '3.8', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(17, 'Fornecedor 17 - Foto Glam', '4.5', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(18, 'Fornecedor 18 - Bolos & Doces', '4.3', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(19, 'Fornecedor 19 - Buffet Premium', '4.7', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(20, 'Fornecedor 20 - Flores do Campo', '4.4', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(21, 'Fornecedor 21 - FotoRápida', '3.9', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(22, 'Fornecedor 22 - Aluguel Trajes', '4.0', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(23, 'Fornecedor 23 - Carro Cerimonial', '4.6', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(24, 'Fornecedor 24 - Convites Express', '4.1', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(25, 'Fornecedor 25 - Lembrancinhas Ltda', '3.8', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(26, 'Fornecedor 26 - Móveis Rústicos', '4.0', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(27, 'Fornecedor 27 - Buffet Vegetariano', '4.2', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(28, 'Fornecedor 28 - Espaço Jardim', '4.5', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(29, 'Fornecedor 29 - Fotógrafo Drone', '4.3', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(30, 'Fornecedor 30 - Banda Viva', '4.4', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(31, 'Fornecedor 31 - Segurança Eventos', '3.7', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(32, 'Fornecedor 32 - Transporte VIP', '4.1', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(33, 'Fornecedor 33 - Som Profissional', '4.0', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(34, 'Fornecedor 34 - Iluminação LED', '4.2', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(35, 'Fornecedor 35 - Buffet Infantil', '4.0', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(36, 'Fornecedor 36 - Barista Eventos', '4.3', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(37, 'Fornecedor 37 - DJ Premium', '4.6', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(38, 'Fornecedor 38 - Foto Retro', '3.9', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(39, 'Fornecedor 39 - Aluguel Toldos', '3.8', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(40, 'Fornecedor 40 - Fornecedora Cakes', '4.5', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(41, 'Fornecedor 41 - Filmagem Pro', '4.6', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(42, 'Fornecedor 42 - Assessoria Top', '4.7', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(43, 'Fornecedor 43 - Estética Noivas', '4.2', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(44, 'Fornecedor 44 - Viagens Lua de Mel', '4.1', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(45, 'Fornecedor 45 - Bar Móvel', '3.9', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(46, 'Fornecedor 46 - Foto Estúdio', '4.0', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(47, 'Fornecedor 47 - Art Floral', '4.3', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(48, 'Fornecedor 48 - Arte Convites', '4.1', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(49, 'Fornecedor 49 - Balões & Festa', '3.8', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(50, 'Fornecedor 50 - Catering Express', '4.0', 'buffet', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-20 11:22:37', NULL),
(54, 'a', NULL, 'catering', '47991149148', 'a@hotmail.com', 'Empresa cadastrada através do sistema. CNPJ: 2131241245124. Endereço: Rua Otávio Joaquim Emilio, 130. Preço mínimo: R$ 9.000,00. Horário: esgas.', '$2y$10$oXYXujesGAV9YPIzBHTAkuajj7YuSXgoX5BfhkW75K/F4joS7vv/G', NULL, 0, '2025-11-16 19:59:16', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `itens`
--

CREATE TABLE `itens` (
  `id_item` int(11) NOT NULL,
  `nome_item` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_fornecedor` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `itens`
--

INSERT INTO `itens` (`id_item`, `nome_item`, `id_fornecedor`, `valor_unitario`, `descricao`, `data_criacao`) VALUES
(5, 'Bolo 3 andares', 6, '350.00', NULL, '2025-10-22 12:35:53'),
(6, 'Vestido de Noiva (Aluguel)', 7, '600.00', NULL, '2025-10-22 12:35:53'),
(7, 'Decoração Cerimonial', 8, '2200.00', NULL, '2025-10-22 12:35:53'),
(8, 'Cerimonialista 8h', 9, '900.00', NULL, '2025-10-22 12:35:53'),
(9, 'Locação de Mesas', 10, '25.00', NULL, '2025-10-22 12:35:53'),
(10, 'Bar Itinerante - pacote', 11, '700.00', NULL, '2025-10-22 12:35:53'),
(11, 'Cabine Foto 360', 12, '650.00', NULL, '2025-10-22 12:35:53'),
(12, 'Open Bar (pessoa)', 13, '35.00', NULL, '2025-10-22 12:35:53'),
(13, 'DJ 6h', 14, '800.00', NULL, '2025-10-22 12:35:53'),
(14, 'Iluminação Cênica', 15, '400.00', NULL, '2025-10-22 12:35:53'),
(15, 'Paredes de Fotos', 16, '300.00', NULL, '2025-10-22 12:35:53'),
(16, 'Mini Bolo (por pessoa)', 18, '8.00', NULL, '2025-10-22 12:35:53'),
(17, 'Buffet Premium (pessoa)', 19, '120.00', NULL, '2025-10-22 12:35:53'),
(18, 'Bouquet Noiva', 20, '120.00', NULL, '2025-10-22 12:35:53'),
(19, 'Foto Impressa 20x30', 21, '40.00', NULL, '2025-10-22 12:35:53'),
(20, 'Aluguel Traje Noivo', 22, '180.00', NULL, '2025-10-22 12:35:53'),
(21, 'Carro Cerimonial (horas)', 23, '120.00', NULL, '2025-10-22 12:35:53'),
(22, 'Convite Papel Luxo', 24, '6.50', NULL, '2025-10-22 12:35:53'),
(23, 'Lembrancinha Padrão', 25, '4.00', NULL, '2025-10-22 12:35:53'),
(24, 'Aluguel Banco Rústico', 26, '15.00', NULL, '2025-10-22 12:35:53'),
(25, 'Menu Vegetariano (pessoa)', 27, '55.00', NULL, '2025-10-22 12:35:53'),
(26, 'Locação Espaço Jardim (dia)', 28, '3500.00', NULL, '2025-10-22 12:35:53'),
(27, 'Filmagem Drone', 29, '900.00', NULL, '2025-10-22 12:35:53'),
(28, 'Banda 5 integrantes', 30, '2500.00', NULL, '2025-10-22 12:35:53'),
(29, 'Segurança 1h', 31, '20.00', NULL, '2025-10-22 12:35:53'),
(30, 'Transporte VIP (hora)', 32, '90.00', NULL, '2025-10-22 12:35:53'),
(31, 'Microfone sem fio', 33, '50.00', NULL, '2025-10-22 12:35:53'),
(32, 'Iluminação LED (kit)', 34, '250.00', NULL, '2025-10-22 12:35:53'),
(33, 'Buffet Infantil (pessoa)', 35, '30.00', NULL, '2025-10-22 12:35:53'),
(34, 'Barista - 4h', 36, '220.00', NULL, '2025-10-22 12:35:53'),
(35, 'DJ Premium 8h', 37, '1500.00', NULL, '2025-10-22 12:35:53'),
(36, 'Ensaio Vintage', 38, '500.00', NULL, '2025-10-22 12:35:53'),
(37, 'Aluguel Toldo 10x10', 39, '800.00', NULL, '2025-10-22 12:35:53'),
(38, 'Bolo Decorado Especial', 40, '450.00', NULL, '2025-10-22 12:35:53'),
(39, 'Filmagem FullHD', 41, '1800.00', NULL, '2025-10-22 12:35:53'),
(40, 'Assessoria Completa', 42, '1200.00', NULL, '2025-10-22 12:35:53'),
(41, 'Penteado e Maquiagem', 43, '350.00', NULL, '2025-10-22 12:35:53'),
(42, 'Pacote Viagem Lua de Mel', 44, '6000.00', NULL, '2025-10-22 12:35:53'),
(43, 'Bar Móvel Básico', 45, '550.00', NULL, '2025-10-22 12:35:53'),
(44, 'Ensaios Estúdio', 46, '300.00', NULL, '2025-10-22 12:35:53'),
(45, 'Centro de Mesa Floral', 47, '90.00', NULL, '2025-10-22 12:35:53'),
(46, 'Convite Digital + Impressão', 48, '7.00', NULL, '2025-10-22 12:35:53'),
(47, 'Decoração com Balões', 49, '200.00', NULL, '2025-10-22 12:35:53'),
(48, 'Catering Express (pessoa)', 50, '38.00', NULL, '2025-10-22 12:35:53'),
(50, 'Montagem Backdrop', 8, '270.00', NULL, '2025-10-22 12:35:53');

-- --------------------------------------------------------

--
-- Estrutura da tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id_mensagem` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `destinatario_id` int(11) NOT NULL,
  `assunto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Mensagem',
  `conteudo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_envio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lida` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `orcamentos`
--

CREATE TABLE `orcamentos` (
  `id_orcamento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `item` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fornecedor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantidade` int(11) DEFAULT '1',
  `valor_unitario` decimal(10,2) NOT NULL,
  `avaliacao` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `orcamentos`
--

INSERT INTO `orcamentos` (`id_orcamento`, `id_usuario`, `item`, `fornecedor`, `quantidade`, `valor_unitario`, `avaliacao`) VALUES
(4, 3, 'Arranjo Floral Padrão', 'Fornecedor 1 - Flores & Arte', 10, '150.00', 5),
(5, 4, 'Menu Buffet Adulto', 'Fornecedor 2 - Sabor & Festa', 120, '45.00', 4),
(6, 5, 'Ensaio Fotográfico', 'Fornecedor 3 - FotoLux', 1, '800.00', 5),
(7, 6, 'Sistema de Som', 'Fornecedor 4 - Som & Luz', 1, '1200.00', 4),
(8, 7, 'Bolo 3 andares', 'Fornecedor 6 - Bolo & Cia', 1, '350.00', 5),
(9, 8, 'Vestido de Noiva (Aluguel)', 'Fornecedor 7 - Vestidos & Cia', 1, '600.00', 4),
(10, 9, 'Decoração Cerimonial', 'Fornecedor 8 - Decora Festa', 1, '2200.00', 5),
(11, 10, 'Cerimonialista 8h', 'Fornecedor 9 - Cerimonial Express', 1, '900.00', 4),
(12, 11, 'Locação de Mesas', 'Fornecedor 10 - Locadora de Móveis', 20, '25.00', 3),
(13, 12, 'Bar Itinerante - pacote', 'Fornecedor 11 - Bar Itinerante', 1, '700.00', 4),
(14, 13, 'Cabine Foto 360', 'Fornecedor 12 - Foto360', 1, '650.00', 5),
(15, 14, 'Open Bar (pessoa)', 'Fornecedor 13 - Open Bar Pro', 150, '35.00', 4),
(16, 15, 'DJ 6h', 'Fornecedor 14 - DJ Fest', 1, '800.00', 4),
(17, 16, 'Iluminação Cênica', 'Fornecedor 15 - Ilumina Eventos', 1, '400.00', 4),
(18, 17, 'Paredes de Fotos', 'Fornecedor 16 - SomMaster', 1, '300.00', 3),
(19, 18, 'Mini Bolo (por pessoa)', 'Fornecedor 18 - Bolos & Doces', 100, '8.00', 5),
(20, 19, 'Buffet Premium (pessoa)', 'Fornecedor 19 - Buffet Premium', 200, '120.00', 5),
(21, 20, 'Bouquet Noiva', 'Fornecedor 20 - Flores do Campo', 1, '120.00', 4),
(22, 21, 'Foto Impressa 20x30', 'Fornecedor 21 - FotoRápida', 10, '40.00', 3),
(23, 22, 'Aluguel Traje Noivo', 'Fornecedor 22 - Aluguel Trajes', 1, '180.00', 4),
(24, 23, 'Carro Cerimonial (horas)', 'Fornecedor 23 - Carro Cerimonial', 4, '120.00', 5),
(25, 24, 'Convite Papel Luxo', 'Fornecedor 24 - Convites Express', 100, '6.50', 4),
(26, 25, 'Lembrancinha Padrão', 'Fornecedor 25 - Lembrancinhas Ltda', 100, '4.00', 4),
(27, 26, 'Aluguel Banco Rústico', 'Fornecedor 26 - Móveis Rústicos', 30, '15.00', 4),
(28, 27, 'Menu Vegetariano (pessoa)', 'Fornecedor 27 - Buffet Vegetariano', 50, '55.00', 5),
(29, 28, 'Locação Espaço Jardim (dia)', 'Fornecedor 28 - Espaço Jardim', 1, '3500.00', 5),
(30, 29, 'Filmagem Drone', 'Fornecedor 29 - Fotógrafo Drone', 1, '900.00', 4),
(31, 30, 'Banda 5 integrantes', 'Fornecedor 30 - Banda Viva', 1, '2500.00', 4),
(32, 31, 'Segurança 1h', 'Fornecedor 31 - Segurança Eventos', 10, '20.00', 3),
(33, 32, 'Transporte VIP (hora)', 'Fornecedor 32 - Transporte VIP', 5, '90.00', 4),
(34, 33, 'Microfone sem fio', 'Fornecedor 33 - Som Profissional', 6, '50.00', 4),
(35, 34, 'Iluminação LED (kit)', 'Fornecedor 34 - Iluminação LED', 2, '250.00', 4),
(36, 35, 'Buffet Infantil (pessoa)', 'Fornecedor 35 - Buffet Infantil', 40, '30.00', 4),
(37, 36, 'Barista - 4h', 'Fornecedor 36 - Barista Eventos', 1, '220.00', 5),
(38, 37, 'DJ Premium 8h', 'Fornecedor 37 - DJ Premium', 1, '1500.00', 5),
(39, 38, 'Ensaio Vintage', 'Fornecedor 38 - Foto Retro', 1, '500.00', 4),
(40, 39, 'Aluguel Toldo 10x10', 'Fornecedor 39 - Aluguel Toldos', 1, '800.00', 4),
(41, 40, 'Bolo Decorado Especial', 'Fornecedor 40 - Fornecedora Cakes', 1, '450.00', 5),
(42, 41, 'Filmagem FullHD', 'Fornecedor 41 - Filmagem Pro', 1, '1800.00', 5),
(43, 42, 'Assessoria Completa', 'Fornecedor 42 - Assessoria Top', 1, '1200.00', 5),
(44, 43, 'Penteado e Maquiagem', 'Fornecedor 43 - Estética Noivas', 1, '350.00', 4),
(45, 44, 'Pacote Viagem Lua de Mel', 'Fornecedor 44 - Viagens Lua de Mel', 1, '6000.00', 5),
(46, 45, 'Bar Móvel Básico', 'Fornecedor 45 - Bar Móvel', 1, '550.00', 4),
(47, 46, 'Ensaios Estúdio', 'Fornecedor 46 - Foto Estúdio', 1, '300.00', 4),
(48, 47, 'Centro de Mesa Floral', 'Fornecedor 47 - Art Floral', 20, '90.00', 5),
(49, 48, 'Convite Digital + Impressão', 'Fornecedor 48 - Arte Convites', 120, '7.00', 4),
(50, 49, 'Decoração com Balões', 'Fornecedor 49 - Balões & Festa', 1, '200.00', 4),
(51, 50, 'Catering Express (pessoa)', 'Fornecedor 50 - Catering Express', 150, '38.00', 4),
(52, 3, 'Foto Extras (por hora)', 'Fornecedor 3 - FotoLux', 3, '80.00', 4),
(53, 4, 'Montagem Backdrop', 'Fornecedor 8 - Decora Festa', 1, '270.00', 4),
(54, 52, 'dasda', 'fasdasd', 6, '10.00', 4),
(58, 52, 'casaca de baluda', 'Receba int.', 1, '3000.00', 1),
(59, 52, 'Barista - 4h', 'Fornecedor 36 - Barista Eventos', 1, '220.00', 4),
(60, 52, 'Centro de Mesa Floral', 'Fornecedor 47 - Art Floral', 1, '90.00', 2),
(61, 52, 'Menu Vegetariano (pessoa)', 'Fornecedor 27 - Buffet Vegetariano', 1, '55.00', 4),
(69, 2, 'Vestido noiva', 'Moda e Luxo int.', 1, '9000.00', 3),
(70, 2, 'Vestido e Terno', 'Moda e Luxo int.', 1, '12000.00', 4),
(71, 2, 'Paredes de Fotos', 'Fornecedor 16 - SomMaster', 1, '300.00', 3);

-- --------------------------------------------------------

--
-- Estrutura da tabela `pacotes`
--

CREATE TABLE `pacotes` (
  `id_pacote` int(11) NOT NULL,
  `id_fornecedor` int(11) NOT NULL,
  `nome_pacote` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `valor_total` decimal(10,2) NOT NULL,
  `quantidade_itens` int(11) DEFAULT '0',
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `foto_pacote` varchar(225) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pacote_itens`
--

CREATE TABLE `pacote_itens` (
  `id_pacote_item` int(11) NOT NULL,
  `id_pacote` int(11) NOT NULL,
  `id_item` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `respostas_mensagens`
--

CREATE TABLE `respostas_mensagens` (
  `id_resposta` int(11) NOT NULL,
  `mensagem_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `assunto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resposta` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_resposta` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `tarefas`
--

CREATE TABLE `tarefas` (
  `id_tarefa` int(11) NOT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsavel` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prazo` date DEFAULT NULL,
  `status` enum('pendente','progresso','concluido') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `data_conclusao` timestamp NULL DEFAULT NULL,
  `concluido_por` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `tarefas`
--

INSERT INTO `tarefas` (`id_tarefa`, `titulo`, `responsavel`, `prazo`, `status`, `observacoes`, `data_conclusao`, `concluido_por`, `id_usuario`) VALUES
(1, 'Confirmar buffet', 'Ana', '2025-03-01', 'concluido', NULL, NULL, NULL, 3),
(2, 'Fechar decorador', 'Bruno', '2025-03-15', 'concluido', NULL, NULL, NULL, 4),
(3, 'Agendar prova vestido', 'Carla', '2025-04-01', 'pendente', NULL, NULL, NULL, 5),
(4, 'Reunião com fotógrafo', 'Diego', '2025-04-05', 'concluido', NULL, NULL, NULL, 6),
(5, 'Prova cardápio', 'Eduarda', '2025-02-20', 'concluido', NULL, NULL, NULL, 7),
(6, 'Assinar contrato DJ', 'Felipe', '2025-05-10', 'pendente', NULL, NULL, NULL, 8),
(7, 'Contratar cerimonialista', 'Gabriela', '2025-06-01', 'pendente', NULL, NULL, NULL, 9),
(8, 'Verificar transporte', 'Hugo', '2025-07-10', 'pendente', NULL, NULL, NULL, 10),
(9, 'Montagem espaço', 'Isadora', '2025-07-30', 'pendente', NULL, NULL, NULL, 11),
(10, 'Confirmação lista convidados', 'João', '2025-08-05', 'pendente', NULL, NULL, NULL, 12),
(11, 'Finalizar lista músicas', 'Karla', '2025-09-01', 'pendente', NULL, NULL, NULL, 13),
(12, 'Reunião final com assessoria', 'Lucas', '2025-09-10', 'pendente', NULL, NULL, NULL, 14),
(13, 'Prova maquiagem', 'Marina', '2025-03-20', 'concluido', NULL, NULL, NULL, 15),
(14, 'Verificar iluminação', 'Nicolas', '2025-04-12', 'concluido', NULL, NULL, NULL, 16),
(15, 'Testar som', 'Olívia', '2025-05-05', 'concluido', NULL, NULL, NULL, 17),
(16, 'Confirmar aluguel carros', 'Pedro', '2025-06-15', 'pendente', NULL, NULL, NULL, 18),
(17, 'Reunião floral', 'Queila', '2025-07-02', 'pendente', NULL, NULL, NULL, 19),
(18, 'Buscar lembrancinhas', 'Rafael', '2025-07-20', 'pendente', NULL, NULL, NULL, 20),
(19, 'Confirmação fornecedores extras', 'Sonia', '2025-08-01', 'pendente', NULL, NULL, NULL, 21),
(20, 'Agendar prova bolo', 'Tiago', '2025-08-10', 'pendente', NULL, NULL, NULL, 22),
(21, 'Montagem backdrop', 'Ullyana', '2025-08-20', 'pendente', NULL, NULL, NULL, 23),
(22, 'Verificar som da cerimonia', 'Victor', '2025-09-01', 'pendente', NULL, NULL, NULL, 24),
(23, 'Inspeção local', 'Wendy', '2025-09-10', 'pendente', NULL, NULL, NULL, 25),
(24, 'Confirmação iluminação LED', 'Xavier', '2025-09-15', 'pendente', NULL, NULL, NULL, 26),
(25, 'Reunião com banda', 'Yasmin', '2025-09-20', 'pendente', NULL, NULL, NULL, 27),
(26, 'Revisar contrato espaçamento', 'Zé', '2025-10-01', 'pendente', NULL, NULL, NULL, 28),
(27, 'Enviar convites', 'Amanda', '2025-10-10', 'pendente', NULL, NULL, NULL, 29),
(28, 'Checar segurança', 'Bruno M.', '2025-10-15', 'pendente', NULL, NULL, NULL, 30),
(29, 'Testar microfones', 'Clara', '2025-10-20', 'pendente', NULL, NULL, NULL, 31),
(30, 'Confirmar filmagem drone', 'Daniela', '2025-10-25', 'pendente', NULL, NULL, NULL, 32),
(31, 'Reunião com cerimonial', 'Emanuel', '2025-11-01', 'pendente', NULL, NULL, NULL, 33),
(32, 'Ajuste final cardápio', 'Fabiana', '2025-11-05', 'pendente', NULL, NULL, NULL, 34),
(33, 'Montagem mobiliário rústico', 'Guilherme', '2025-11-10', 'pendente', NULL, NULL, NULL, 35),
(34, 'Teste barista', 'Helena', '2025-11-15', 'pendente', NULL, NULL, NULL, 36),
(35, 'Confirmação DJ premium', 'Igor', '2025-11-20', 'pendente', NULL, NULL, NULL, 37),
(36, 'Reunião logística transporte', 'Jéssica', '2025-11-25', 'pendente', NULL, NULL, NULL, 38),
(37, 'Prova foto impressa', 'Kevin', '2025-11-30', 'pendente', NULL, NULL, NULL, 39),
(38, 'Checagem iluminação cênica', 'Laura', '2025-12-05', 'pendente', NULL, NULL, NULL, 40),
(39, 'Ensaios finais', 'Marcelo', '2025-12-10', 'pendente', NULL, NULL, NULL, 41),
(40, 'Confirmação penteado', 'Natália', '2025-12-15', 'pendente', NULL, NULL, NULL, 42),
(41, 'Verificar pacotes viagem', 'Otávio', '2025-12-20', 'pendente', NULL, NULL, NULL, 43),
(42, 'Reunião final com equipe', 'Priscila', '2025-12-22', 'pendente', NULL, NULL, NULL, 44),
(43, 'Revisar cronograma', 'Quirino', '2025-12-24', 'pendente', NULL, NULL, NULL, 45),
(44, 'Teste bar móvel', 'Rafaela', '2025-12-26', 'pendente', NULL, NULL, NULL, 46),
(45, 'Retirada trajes', 'Samuel', '2025-12-28', 'pendente', NULL, NULL, NULL, 47),
(46, 'Conferir lista VIP', 'Tainá', '2025-12-29', 'pendente', NULL, NULL, NULL, 48),
(47, 'Montagem balões', 'Ulisses', '2026-01-05', 'pendente', NULL, NULL, NULL, 49),
(48, 'Checklist final fornecedor', 'Vanessa', '2026-01-10', 'pendente', NULL, NULL, NULL, 50),
(49, 'Reunião pós-evento', 'Wagner', '2026-01-15', 'pendente', NULL, NULL, NULL, 51),
(50, 'dasd', 'Meu pai', '2030-03-12', 'progresso', NULL, NULL, NULL, 52);

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_conjuge` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `genero` enum('Masculino','Feminino','Outro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `idade` int(11) DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cargo` enum('cliente','dev','cerimonialista','fornecedor') COLLATE utf8mb4_unicode_ci DEFAULT 'cliente',
  `tipo_usuario` enum('cliente','cerimonialista','dev') COLLATE utf8mb4_unicode_ci DEFAULT 'cliente',
  `avaliacao` decimal(2,1) DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `plano` enum('basico','premium') COLLATE utf8mb4_unicode_ci DEFAULT 'basico',
  `remember_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_perfil` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default.png',
  `notificacoes_email` tinyint(1) DEFAULT '1',
  `tema_cor` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'azul',
  `ultima_atividade` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `orcamento_total` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nome`, `nome_conjuge`, `genero`, `idade`, `telefone`, `email`, `senha`, `cargo`, `tipo_usuario`, `avaliacao`, `bio`, `plano`, `remember_token`, `foto_perfil`, `notificacoes_email`, `tema_cor`, `ultima_atividade`, `created_at`, `orcamento_total`) VALUES
(1, 'Teste de Lima', 'Testa de Souza', 'Masculino', 28, '47991149148', 'testedsilva@gmail.com', '$2y$10$ADFUzpw3Ek1xDbk9Ws/JH.u4buCM05eUHEU9Hs/cFZEq9KnmQorq.', 'cliente', 'cliente', NULL, NULL, 'basico', '7f724f03820ffd2c833f0be2c1f6a08e', 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(2, 'Vinicius Gabriel Bizarro', 'Mariana testa da silva', 'Masculino', 18, '47999999999', 'vinibizarro@email.com', '$2y$10$L7MhpDthxOhUKoSx6HqrVe1gLA6ESbbBFYwuD3k65urbUjDpDZFdy', 'dev', 'cliente', NULL, NULL, 'basico', '2f1b5088e411005bfbce4589057219db', 'user_2_1763172923.jpg', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(3, 'Ana Pereira', 'Carlos Pereira', 'Feminino', 29, '47991100001', 'ana.pereira@example.com', '$2y$10$aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaab', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(4, 'Bruno Souza', 'Mariana Souza', 'Masculino', 33, '47991100002', 'bruno.souza@example.com', '$2y$10$bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(5, 'Carla Mendes', 'Felipe Mendes', 'Feminino', 27, '47991100003', 'carla.mendes@example.com', '$2y$10$ccccccccccccccccccccccccccccccccccccc', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(6, 'Diego Rocha', 'Luana Rocha', 'Masculino', 31, '47991100004', 'diego.rocha@example.com', '$2y$10$ddddddddddddddddddddddddddddddddddddd', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(7, 'Eduarda Lima', 'Rafael Lima', 'Feminino', 26, '47991100005', 'eduarda.lima@example.com', '$2y$10$eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(8, 'Felipe Castro', 'Bianca Castro', 'Masculino', 35, '47991100006', 'felipe.castro@example.com', '$2y$10$fffffffffffffffffffffffffffffffffffff', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(9, 'Gabriela Alves', 'Thiago Alves', 'Feminino', 30, '47991100007', 'gabriela.alves@example.com', '$2y$10$ggggggggggggggggggggggggggggggggggggg', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(10, 'Hugo Santos', 'Isabela Santos', 'Masculino', 28, '47991100008', 'hugo.santos@example.com', '$2y$10$hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(11, 'Isadora Moraes', 'Vitor Moraes', 'Feminino', 32, '47991100009', 'isadora.moraes@example.com', '$2y$10$iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(12, 'João Paulo', 'Marina Paulo', 'Masculino', 34, '47991100010', 'joao.paulo@example.com', '$2y$10$jjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjj', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(13, 'Karla Fernandes', 'Ramon Fernandes', 'Feminino', 27, '47991100011', 'karla.fernandes@example.com', '$2y$10$kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(14, 'Lucas Ribeiro', 'Patrícia Ribeiro', 'Masculino', 29, '47991100012', 'lucas.ribeiro@example.com', '$2y$10$lllllllllllllllllllllllllllllllllllll', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(15, 'Marina Costa', 'Daniel Costa', 'Feminino', 31, '47991100013', 'marina.costa@example.com', '$2y$10$mmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(16, 'Nicolas Vieira', 'Sofia Vieira', 'Masculino', 25, '47991100014', 'nicolas.vieira@example.com', '$2y$10$nnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnn', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(17, 'Olívia Martins', 'Gustavo Martins', 'Feminino', 28, '47991100015', 'olivia.martins@example.com', '$2y$10$ooooooooooooooooooooooooooooooooooooo', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(18, 'Pedro Henrique', 'Laura Henrique', 'Masculino', 36, '47991100016', 'pedro.henrique@example.com', '$2y$10$ppppppppppppppppppppppppppppppppppppp', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(19, 'Queila Ramos', 'André Ramos', 'Feminino', 33, '47991100017', 'queila.ramos@example.com', '$2y$10$qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(20, 'Rafael Nunes', 'Camila Nunes', 'Masculino', 37, '47991100018', 'rafael.nunes@example.com', '$2y$10$rrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(21, 'Sonia Oliveira', 'Marcelo Oliveira', 'Feminino', 40, '47991100019', 'sonia.oliveira@example.com', '$2y$10$sssssssssssssssssssssssssssssssssssss', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(22, 'Tiago Leal', 'Priscila Leal', 'Masculino', 26, '47991100020', 'tiago.leal@example.com', '$2y$10$ttttttttttttttttttttttttttttttttttttt', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(23, 'Ullyana Rocha', 'Mateus Rocha', 'Feminino', 29, '47991100021', 'ullyana.rocha@example.com', '$2y$10$uuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuu', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(24, 'Victor Hugo', 'Renata Hugo', 'Masculino', 38, '47991100022', 'victor.hugo@example.com', '$2y$10$vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(25, 'Wendy Alves', 'Bruno Alves', 'Feminino', 24, '47991100023', 'wendy.alves@example.com', '$2y$10$wwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwww', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(26, 'Xavier Pinto', 'Lara Pinto', 'Masculino', 41, '47991100024', 'xavier.pinto@example.com', '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(27, 'Yasmin Faria', 'Leandro Faria', 'Feminino', 27, '47991100025', 'yasmin.faria@example.com', '$2y$10$yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(28, 'Zé Carlos', 'Ana Clara', 'Masculino', 45, '47991100026', 'ze.carlos@example.com', '$2y$10$zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(29, 'Amanda Lopes', 'Rogério Lopes', 'Feminino', 32, '47991100027', 'amanda.lopes@example.com', '$2y$10$a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(30, 'Bruno Martins', 'Helena Martins', 'Masculino', 30, '47991100028', 'bruno.martins2@example.com', '$2y$10$b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b2b', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(31, 'Clara Nascimento', 'Igor Nascimento', 'Feminino', 28, '47991100029', 'clara.nascimento@example.com', '$2y$10$c3c3c3c3c3c3c3c3c3c3c3c3c3c3c3c3c3c3c', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(32, 'Daniela Araújo', 'Fábio Araújo', 'Feminino', 34, '47991100030', 'daniela.araujo@example.com', '$2y$10$d4d4d4d4d4d4d4d4d4d4d4d4d4d4d4d4d4d4d', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(33, 'Emanuel Costa', 'Bianca Costa', 'Masculino', 29, '47991100031', 'emanuel.costa@example.com', '$2y$10$e5e5e5e5e5e5e5e5e5e5e5e5e5e5e5e5e5e5e', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(34, 'Fabiana Lima', 'Ronaldo Lima', 'Feminino', 36, '47991100032', 'fabiana.lima@example.com', '$2y$10$f6f6f6f6f6f6f6f6f6f6f6f6f6f6f6f6f6f6f', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(35, 'Guilherme Souza', 'Patrícia Souza', 'Masculino', 27, '47991100033', 'guilherme.souza@example.com', '$2y$10$g7g7g7g7g7g7g7g7g7g7g7g7g7g7g7g7g7g7g', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(36, 'Helena Rocha', 'Mateus Rocha', 'Feminino', 26, '47991100034', 'helena.rocha@example.com', '$2y$10$h8h8h8h8h8h8h8h8h8h8h8h8h8h8h8h8h8h8', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(37, 'Igor Silva', 'Mariana Silva', 'Masculino', 31, '47991100035', 'igor.silva@example.com', '$2y$10$i9i9i9i9i9i9i9i9i9i9i9i9i9i9i9i9i9i9i', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(38, 'Jéssica Freitas', 'Rafael Freitas', 'Feminino', 33, '47991100036', 'jessica.freitas@example.com', '$2y$10$j0j0j0j0j0j0j0j0j0j0j0j0j0j0j0j0j0j0', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(39, 'Kevin Borges', 'Larissa Borges', 'Masculino', 24, '47991100037', 'kevin.borges@example.com', '$2y$10$k1k1k1k1k1k1k1k1k1k1k1k1k1k1k1k1k1k1k', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(40, 'Laura Gomes', 'Diego Gomes', 'Feminino', 29, '47991100038', 'laura.gomes@example.com', '$2y$10$l2l2l2l2l2l2l2l2l2l2l2l2l2l2l2l2l2l2l2', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(41, 'Marcelo Pinto', 'Patrícia Pinto', 'Masculino', 39, '47991100039', 'marcelo.pinto@example.com', '$2y$10$m3m3m3m3m3m3m3m3m3m3m3m3m3m3m3m3m3m3m3', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(42, 'Natália Reis', 'Hugo Reis', 'Feminino', 27, '47991100040', 'natalia.reis@example.com', '$2y$10$n4n4n4n4n4n4n4n4n4n4n4n4n4n4n4n4n4n4n4', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(43, 'Otávio Cardoso', 'Bruna Cardoso', 'Masculino', 28, '47991100041', 'otavio.cardoso@example.com', '$2y$10$o5o5o5o5o5o5o5o5o5o5o5o5o5o5o5o5o5o5o5', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(44, 'Priscila Barros', 'Leandro Barros', 'Feminino', 35, '47991100042', 'priscila.barros@example.com', '$2y$10$p6p6p6p6p6p6p6p6p6p6p6p6p6p6p6p6p6p6p6', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(45, 'Quirino Alves', 'Elaine Alves', 'Masculino', 42, '47991100043', 'quirino.alves@example.com', '$2y$10$q7q7q7q7q7q7q7q7q7q7q7q7q7q7q7q7q7q7q7', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(46, 'Rafaela Dias', 'Eduardo Dias', 'Feminino', 30, '47991100044', 'rafaela.dias@example.com', '$2y$10$r8r8r8r8r8r8r8r8r8r8r8r8r8r8r8r8r8r8r8', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(47, 'Samuel Nogueira', 'Júlia Nogueira', 'Masculino', 33, '47991100045', 'samuel.nogueira@example.com', '$2y$10$s9s9s9s9s9s9s9s9s9s9s9s9s9s9s9s9s9s9s9', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(48, 'Tainá Souza', 'Rayan Souza', 'Feminino', 26, '47991100046', 'taina.souza@example.com', '$2y$10$t0t0t0t0t0t0t0t0t0t0t0t0t0t0t0t0t0t0t0', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(49, 'Ulisses Rocha', 'Caroline Rocha', 'Masculino', 37, '47991100047', 'ulisses.rocha@example.com', '$2y$10$u1u1u1u1u1u1u1u1u1u1u1u1u1u1u1u1u1u1u1', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(50, 'Vanessa Melo', 'Pedro Melo', 'Feminino', 28, '47991100048', 'vanessa.melo@example.com', '$2y$10$v2v2v2v2v2v2v2v2v2v2v2v2v2v2v2v2v2v2v2', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(51, 'Wagner Lopes', 'Sílvia Lopes', 'Masculino', 36, '47991100049', 'wagner.lopes@example.com', '$2y$10$w3w3w3w3w3w3w3w3w3w3w3w3w3w3w3w3w3w3w3', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(52, 'Kauê Feltrin', 'Duda', 'Masculino', 18, '47999999999', 'kauekfs@hotmail.com', '$2y$10$q3Fb.zOUDu0wQ.I7Ds6JvuVHuN4600IyJ3oec5KMNW9MJ8laZy4cG', 'cliente', 'cliente', NULL, NULL, 'basico', 'd86b45b08308abbd5ca90ef1f8370aed', 'default.png', 1, 'azul', NULL, '2025-10-15 12:05:01', '0.00'),
(53, 'ab', NULL, 'Masculino', NULL, '47991149148', 'ab@email.com', '$2y$10$b63gaH7ejcZP9FGyeRbo8O35RtzNZ1sGiSGB0z.wj7Py5c/vQU67q', 'dev', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-11-16 20:09:25', '0.00'),
(54, 'c', 'd', 'Masculino', 18, '47991149148', 'cb@hotmail.com', '$2y$10$ySXETW63SJk7hhklkmSmAe3c8KSsi8JpejpW2UUIXV7XrOsRaZleG', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-11-16 20:40:35', '0.00'),
(55, 'acd', 'asv', 'Feminino', 18, '47991149148', 'acb@hotmail.com', '$2y$10$syc6NJYR7PNWR7PHlmzEN.CEhNLYn5mgOCTLgflWKfMWisOvdwiMi', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-11-16 20:41:07', '0.00'),
(56, 'qwe', 'qwe', 'Masculino', 18, '47991149148', 'qwe@hotmail.com', '$2y$10$eKeFcfWBo/osWeefUdUAle6fMGiqUOhXh.R6poufi.G8qKmPREsYu', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-11-16 20:48:36', '0.00'),
(57, 'wer', 'wer', 'Feminino', 23, '47991149148', 'wer@hotmail.com', '$2y$10$kOb/ERyixx4avGrciWEBKeT7E9JJpox52psJg35kwVh2UGmUlygoe', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-11-16 20:50:27', '0.00'),
(58, 'a', 'a', 'Masculino', 18, '47991149148', 'a@hotmail.com', '$2y$10$XzJvsl9SCTTHK80su4bkXOk4OzeaZBaaH1SKIKA0m3o9nxLKkBI8u', 'cliente', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-11-16 21:11:25', '12.00'),
(59, 'a', NULL, 'Outro', NULL, '47991149148', 'a@email.com', '$2y$10$IkI20egSWX8YrCNYbigQsuT9gMZ8DtJaaiRrEPIjUsB4XMY6RV76i', 'cerimonialista', 'cliente', NULL, NULL, 'basico', NULL, 'default.png', 1, 'azul', NULL, '2025-11-16 21:17:36', '0.00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `atividades_usuario`
--
ALTER TABLE `atividades_usuario`
  ADD PRIMARY KEY (`id_atividade`),
  ADD KEY `idx_usuario_data` (`id_usuario`,`data_atividade`);

--
-- Indexes for table `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id_avaliacao`),
  ADD UNIQUE KEY `uq_avaliacao` (`avaliador_id`,`avaliado_id`),
  ADD KEY `idx_avaliador` (`avaliador_id`),
  ADD KEY `idx_avaliado` (`avaliado_id`);

--
-- Indexes for table `cerimonialista_datas_bloqueadas`
--
ALTER TABLE `cerimonialista_datas_bloqueadas`
  ADD PRIMARY KEY (`id_bloqueio`),
  ADD UNIQUE KEY `uq_cerimo_data` (`id_cerimonialista`,`data_bloqueada`);

--
-- Indexes for table `cerimonialista_fornecedores`
--
ALTER TABLE `cerimonialista_fornecedores`
  ADD PRIMARY KEY (`id_relacao`),
  ADD UNIQUE KEY `unique_cerimonial_fornecedor` (`id_cerimonialista`,`id_fornecedor`),
  ADD KEY `id_cerimonialista` (`id_cerimonialista`),
  ADD KEY `id_fornecedor` (`id_fornecedor`);

--
-- Indexes for table `cerimonialista_indisponibilidade`
--
ALTER TABLE `cerimonialista_indisponibilidade`
  ADD PRIMARY KEY (`id_indisponibilidade`),
  ADD KEY `id_cerimonialista` (`id_cerimonialista`);

--
-- Indexes for table `cliente_cerimonialista`
--
ALTER TABLE `cliente_cerimonialista`
  ADD PRIMARY KEY (`id_assoc`),
  ADD UNIQUE KEY `unique_cliente_data` (`id_cliente`,`data_casamento`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_cerimonialista` (`id_cerimonialista`);

--
-- Indexes for table `contatos`
--
ALTER TABLE `contatos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_resposta` (`status_resposta`),
  ADD KEY `fk_contatos_respondido_por` (`respondido_por`);

--
-- Indexes for table `contratos`
--
ALTER TABLE `contratos`
  ADD PRIMARY KEY (`id_contrato`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indexes for table `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indexes for table `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id_fornecedor`),
  ADD UNIQUE KEY `email_unique` (`email`),
  ADD KEY `idx_fornecedores_categoria` (`categoria`);

--
-- Indexes for table `itens`
--
ALTER TABLE `itens`
  ADD PRIMARY KEY (`id_item`),
  ADD KEY `id_fornecedor` (`id_fornecedor`);

--
-- Indexes for table `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id_mensagem`),
  ADD KEY `remetente_id` (`remetente_id`),
  ADD KEY `destinatario_id` (`destinatario_id`),
  ADD KEY `idx_conversas` (`remetente_id`,`destinatario_id`);

--
-- Indexes for table `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD PRIMARY KEY (`id_orcamento`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indexes for table `pacotes`
--
ALTER TABLE `pacotes`
  ADD PRIMARY KEY (`id_pacote`),
  ADD KEY `id_fornecedor` (`id_fornecedor`);

--
-- Indexes for table `pacote_itens`
--
ALTER TABLE `pacote_itens`
  ADD PRIMARY KEY (`id_pacote_item`),
  ADD KEY `id_pacote` (`id_pacote`),
  ADD KEY `id_item` (`id_item`);

--
-- Indexes for table `respostas_mensagens`
--
ALTER TABLE `respostas_mensagens`
  ADD PRIMARY KEY (`id_resposta`),
  ADD KEY `mensagem_id` (`mensagem_id`),
  ADD KEY `remetente_id` (`remetente_id`);

--
-- Indexes for table `tarefas`
--
ALTER TABLE `tarefas`
  ADD PRIMARY KEY (`id_tarefa`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `fk_tarefas_concluido_por` (`concluido_por`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `atividades_usuario`
--
ALTER TABLE `atividades_usuario`
  MODIFY `id_atividade` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id_avaliacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `cerimonialista_datas_bloqueadas`
--
ALTER TABLE `cerimonialista_datas_bloqueadas`
  MODIFY `id_bloqueio` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `cerimonialista_fornecedores`
--
ALTER TABLE `cerimonialista_fornecedores`
  MODIFY `id_relacao` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `cerimonialista_indisponibilidade`
--
ALTER TABLE `cerimonialista_indisponibilidade`
  MODIFY `id_indisponibilidade` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `cliente_cerimonialista`
--
ALTER TABLE `cliente_cerimonialista`
  MODIFY `id_assoc` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `contatos`
--
ALTER TABLE `contatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;
--
-- AUTO_INCREMENT for table `contratos`
--
ALTER TABLE `contratos`
  MODIFY `id_contrato` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;
--
-- AUTO_INCREMENT for table `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;
--
-- AUTO_INCREMENT for table `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id_fornecedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;
--
-- AUTO_INCREMENT for table `itens`
--
ALTER TABLE `itens`
  MODIFY `id_item` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;
--
-- AUTO_INCREMENT for table `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id_mensagem` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `orcamentos`
--
ALTER TABLE `orcamentos`
  MODIFY `id_orcamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;
--
-- AUTO_INCREMENT for table `pacotes`
--
ALTER TABLE `pacotes`
  MODIFY `id_pacote` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `pacote_itens`
--
ALTER TABLE `pacote_itens`
  MODIFY `id_pacote_item` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `respostas_mensagens`
--
ALTER TABLE `respostas_mensagens`
  MODIFY `id_resposta` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `tarefas`
--
ALTER TABLE `tarefas`
  MODIFY `id_tarefa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;
--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;
--
-- Constraints for dumped tables
--

--
-- Limitadores para a tabela `atividades_usuario`
--
ALTER TABLE `atividades_usuario`
  ADD CONSTRAINT `atividades_usuario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD CONSTRAINT `avaliacoes_ibfk_1` FOREIGN KEY (`avaliador_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacoes_ibfk_2` FOREIGN KEY (`avaliado_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `cerimonialista_datas_bloqueadas`
--
ALTER TABLE `cerimonialista_datas_bloqueadas`
  ADD CONSTRAINT `cerimonialista_datas_bloqueadas_ibfk_1` FOREIGN KEY (`id_cerimonialista`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `cerimonialista_fornecedores`
--
ALTER TABLE `cerimonialista_fornecedores`
  ADD CONSTRAINT `cerimonialista_fornecedores_ibfk_1` FOREIGN KEY (`id_cerimonialista`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `cerimonialista_fornecedores_ibfk_2` FOREIGN KEY (`id_fornecedor`) REFERENCES `fornecedores` (`id_fornecedor`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `cerimonialista_indisponibilidade`
--
ALTER TABLE `cerimonialista_indisponibilidade`
  ADD CONSTRAINT `cerimonialista_indisponibilidade_ibfk_1` FOREIGN KEY (`id_cerimonialista`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `cliente_cerimonialista`
--
ALTER TABLE `cliente_cerimonialista`
  ADD CONSTRAINT `cliente_cerimonialista_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `cliente_cerimonialista_ibfk_2` FOREIGN KEY (`id_cerimonialista`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `contatos`
--
ALTER TABLE `contatos`
  ADD CONSTRAINT `fk_contatos_respondido_por` FOREIGN KEY (`respondido_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `contratos`
--
ALTER TABLE `contratos`
  ADD CONSTRAINT `contratos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `eventos`
--
ALTER TABLE `eventos`
  ADD CONSTRAINT `eventos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `itens`
--
ALTER TABLE `itens`
  ADD CONSTRAINT `itens_ibfk_1` FOREIGN KEY (`id_fornecedor`) REFERENCES `fornecedores` (`id_fornecedor`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensagens_ibfk_2` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD CONSTRAINT `orcamentos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `pacotes`
--
ALTER TABLE `pacotes`
  ADD CONSTRAINT `pacotes_ibfk_1` FOREIGN KEY (`id_fornecedor`) REFERENCES `fornecedores` (`id_fornecedor`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `pacote_itens`
--
ALTER TABLE `pacote_itens`
  ADD CONSTRAINT `pacote_itens_ibfk_1` FOREIGN KEY (`id_pacote`) REFERENCES `pacotes` (`id_pacote`) ON DELETE CASCADE,
  ADD CONSTRAINT `pacote_itens_ibfk_2` FOREIGN KEY (`id_item`) REFERENCES `itens` (`id_item`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `respostas_mensagens`
--
ALTER TABLE `respostas_mensagens`
  ADD CONSTRAINT `respostas_mensagens_ibfk_1` FOREIGN KEY (`mensagem_id`) REFERENCES `contatos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `respostas_mensagens_ibfk_2` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `tarefas`
--
ALTER TABLE `tarefas`
  ADD CONSTRAINT `fk_tarefas_concluido_por` FOREIGN KEY (`concluido_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  ADD CONSTRAINT `tarefas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
