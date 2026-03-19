/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: barbearia
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `acessos`
--

DROP TABLE IF EXISTS `acessos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `acessos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `chave` varchar(50) NOT NULL,
  `grupo` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acessos`
--

LOCK TABLES `acessos` WRITE;
/*!40000 ALTER TABLE `acessos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `acessos` VALUES
(1,'Usuários','usuarios',1),
(2,'Funcionários','funcionarios',1),
(3,'Clientes','clientes',1),
(4,'Clientes Retornos','clientes_retorno',1),
(5,'Fornecedores','fornecedores',1),
(6,'Serviços','servicos',2),
(7,'Cargos','cargos',2),
(8,'Categoria Serviços','cat_servicos',2),
(9,'Grupo Acessos','grupos',2),
(10,'Acessos','acessos',2),
(11,'Produtos','produtos',3),
(12,'Categorias','cat_produtos',3),
(13,'Estoque Baixo','estoque',3),
(14,'Saídas','saidas',3),
(15,'Entradas','entradas',3),
(16,'Vendas','vendas',4),
(17,'Compras','compras',4),
(18,'Contas à Pagar','pagar',4),
(19,'Contas à Receber','receber',4),
(20,'Agendamentos','agendamentos',5),
(21,'Serviços Agendamentos','servicos_agenda',5),
(22,'Home','home',0),
(25,'Relatório Produtos','rel_produtos',7),
(26,'Relatório de Entradas','rel_entradas',7),
(27,'Relatório de Saídas','rel_saidas',7),
(28,'Relatório de Comissões','rel_comissoes',7),
(29,'Relatório de Contas','rel_contas',7),
(30,'Aniversáriantes','rel_aniv',7),
(31,'Relatório de Lucro','rel_lucro',7),
(32,'Textos Banner Index','textos_index',8),
(33,'Comentários / Depoimentos','comentarios',8),
(34,'Configurações do Sistema','configuracoes',0);
/*!40000 ALTER TABLE `acessos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `agendamentos`
--

DROP TABLE IF EXISTS `agendamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `funcionario` int(11) NOT NULL,
  `cliente` int(11) NOT NULL,
  `data` date NOT NULL,
  `hora` time NOT NULL,
  `obs` varchar(100) DEFAULT NULL,
  `data_lanc` date NOT NULL,
  `usuario` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `servico` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_booking` (`funcionario`,`data`,`hora`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agendamentos`
--

LOCK TABLES `agendamentos` WRITE;
/*!40000 ALTER TABLE `agendamentos` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `agendamentos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cargos`
--

DROP TABLE IF EXISTS `cargos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cargos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(35) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cargos`
--

LOCK TABLES `cargos` WRITE;
/*!40000 ALTER TABLE `cargos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `cargos` VALUES
(9,'Administrador'),
(10,'Gerente'),
(11,'Recepcionista'),
(12,'Barbeiro');
/*!40000 ALTER TABLE `cargos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cat_produtos`
--

DROP TABLE IF EXISTS `cat_produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_produtos`
--

LOCK TABLES `cat_produtos` WRITE;
/*!40000 ALTER TABLE `cat_produtos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `cat_produtos` VALUES
(1,'Pomadas'),
(2,'Cremes'),
(3,'Lâminas e Giletes'),
(4,'Bebidas'),
(5,'Gel');
/*!40000 ALTER TABLE `cat_produtos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cat_servicos`
--

DROP TABLE IF EXISTS `cat_servicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_servicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_servicos`
--

LOCK TABLES `cat_servicos` WRITE;
/*!40000 ALTER TABLE `cat_servicos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `cat_servicos` VALUES
(1,'Corte');
/*!40000 ALTER TABLE `cat_servicos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `data_nasc` date NOT NULL,
  `data_cad` date NOT NULL,
  `cartoes` int(11) NOT NULL,
  `data_retorno` date DEFAULT NULL,
  `ultimo_servico` int(11) NOT NULL,
  `alertado` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `clientes` VALUES
(15,'Fernando Machado','(55) 62991-2526',NULL,'0000-00-00','2025-07-11',0,NULL,0,'Não');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `comentarios`
--

DROP TABLE IF EXISTS `comentarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `texto` varchar(500) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `ativo` varchar(5) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comentarios`
--

LOCK TABLES `comentarios` WRITE;
/*!40000 ALTER TABLE `comentarios` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `comentarios` VALUES
(2,'Paulo Campos','Aqui será o texto do comentário referente a essa mensagem e poderá ser aprovado ou não pelo administrador do site. Aqui será o texto do comentário referente a essa mensagem e poderá ser aprovado ou não pelo administrador do site.','14-06-2022-19-10-25-c1.jpg','Sim'),
(3,'Marcos Silva','Aqui será o texto do comentário referente a essa mensagem e poderá ser aprovado ou não pelo administrador do site.','14-06-2022-19-11-32-30-05-2022-13-19-34-08-03-2022-22-21-20-02-03-2022-09-59-04-Arthur.jpg','Sim'),
(4,'Marcos Santos','Aqui será o texto do comentário referente a essa mensagem e poderá ser aprovado ou não pelo administrador do site.','14-06-2022-19-11-50-c2.jpg','Sim'),
(15,'Fabricio Campos','Excelente barbearia, sempre bom atendimento, preços assecíveis, Excelente barbearia, sempre bom atendimento, preços assecíveis, Excelente barbearia, sempre bom atendimento, preços assecíveis, ','14-06-2022-20-10-40-c1.jpg','Sim'),
(16,'Marcelo Silva','Excelente barbearia, sempre bom atendimento, preços assecíveis, Excelente barbearia, sempre bom atendimento, preços assecíveis, Excelente barbearia, sempre bom atendimento, preços assecíveis, ','14-06-2022-20-13-02-c2.jpg','Sim'),
(18,'Priscila Matos','Excelente Salão de cabereira, muito bom atendimento.','14-06-2022-20-17-37-favicon.png','Não');
/*!40000 ALTER TABLE `comentarios` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `telefone_fixo` varchar(20) DEFAULT NULL,
  `telefone_whatsapp` varchar(20) NOT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `icone` varchar(100) DEFAULT NULL,
  `logo_rel` varchar(100) DEFAULT NULL,
  `tipo_rel` varchar(10) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `tipo_comissao` varchar(25) NOT NULL,
  `texto_rodape` varchar(255) DEFAULT NULL,
  `img_banner_index` varchar(100) DEFAULT NULL,
  `texto_sobre` varchar(600) DEFAULT NULL,
  `imagem_sobre` varchar(100) DEFAULT NULL,
  `icone_site` varchar(100) NOT NULL,
  `mapa` varchar(1000) DEFAULT NULL,
  `texto_fidelidade` varchar(255) DEFAULT NULL,
  `quantidade_cartoes` int(11) NOT NULL,
  `texto_agendamento` varchar(30) DEFAULT NULL,
  `msg_agendamento` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `config` VALUES
(2,'Barbearia PRO','admin@admin','(81) 9866-0751','(81) 98660-7510','Rua X Número 150 - Bairro Centro','logo.png','favicon.png','logo_rel.jpg','PDF','https://www.instagram.com/iluminatto/','Porcentagem','Este texto pode ser modificado no painel do sistema, nas opções de configurações, é um texto com tamanho para até 255 caracteres e será apresentado no rodapé de todo o site!','banner.jpg','Este texto pode ser alterado no painel administrativo nas configurações, aqui voce vai colocar um texto escrevendo sobre sua empresa. Este texto pode ser alterado no painel administrativo nas configurações, aqui voce vai colocar um texto escrevendo sobre.','getlstd-property-photo.jpg','favicon.png','<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15111.737337923378!2d-44.43875433848488!3d-18.756469927096727!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xa862fa2d52b993%3A0xe3bd921aa58bc4a8!2sCentro%2C%20Curvelo%20-%20MG%2C%2035790-000!5e0!3m2!1spt-BR!2sbr!4v1655243351692!5m2!1spt-BR!2sbr\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>','Parabéns, você completou seus cartões Fidelidade, seu próximo corte será gratuito!!',10,'Selecionar Barbeiro','Não');
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `dias`
--

DROP TABLE IF EXISTS `dias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dia` varchar(25) NOT NULL,
  `funcionario` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dias`
--

LOCK TABLES `dias` WRITE;
/*!40000 ALTER TABLE `dias` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `dias` VALUES
(1,'Segunda-Feira',11),
(2,'Terça-Feira',11),
(3,'Quarta-Feira',11),
(4,'Quinta-Feira',11),
(5,'Sexta-Feira',11),
(6,'Sábado',11),
(8,'Terça-Feira',10),
(9,'Quarta-Feira',10),
(10,'Quinta-Feira',10),
(11,'Sexta-Feira',10),
(12,'Terça-Feira',6),
(13,'Quarta-Feira',6),
(14,'Quinta-Feira',6),
(15,'Sexta-Feira',6),
(16,'Sábado',6),
(17,'Segunda-Feira',10);
/*!40000 ALTER TABLE `dias` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `entradas`
--

DROP TABLE IF EXISTS `entradas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `entradas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `motivo` varchar(50) NOT NULL,
  `usuario` int(11) NOT NULL,
  `data` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entradas`
--

LOCK TABLES `entradas` WRITE;
/*!40000 ALTER TABLE `entradas` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `entradas` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `fornecedores`
--

DROP TABLE IF EXISTS `fornecedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `data_cad` date NOT NULL,
  `tipo_chave` varchar(25) DEFAULT NULL,
  `chave_pix` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fornecedores`
--

LOCK TABLES `fornecedores` WRITE;
/*!40000 ALTER TABLE `fornecedores` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `fornecedores` VALUES
(1,'Fornecedor 2','(55) 55555-5555','Rua Guajajaras 140 Centro','2022-05-31','Email','fornecedor2@hotmail.com'),
(2,'Fornecedor 1','(88) 88888-8888','Teste','2022-05-31','CPF','123111111'),
(4,'Fornecedor 3','(31) 78999-9999','Rua C','2022-06-13','CNPJ','11.111.111/1111-11');
/*!40000 ALTER TABLE `fornecedores` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `grupo_acessos`
--

DROP TABLE IF EXISTS `grupo_acessos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupo_acessos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupo_acessos`
--

LOCK TABLES `grupo_acessos` WRITE;
/*!40000 ALTER TABLE `grupo_acessos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `grupo_acessos` VALUES
(1,'Pessoas'),
(2,'Cadastros'),
(3,'Produtos'),
(4,'Financeiro'),
(5,'Agendamento / Serviços'),
(7,'Relatórios'),
(8,'Dados Site');
/*!40000 ALTER TABLE `grupo_acessos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `horarios`
--

DROP TABLE IF EXISTS `horarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `horarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `horario` time NOT NULL,
  `funcionario` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `horarios`
--

LOCK TABLES `horarios` WRITE;
/*!40000 ALTER TABLE `horarios` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `horarios` VALUES
(1,'08:00:00',10),
(2,'09:00:00',10),
(4,'12:00:00',10),
(5,'10:00:00',10),
(6,'13:00:00',10),
(7,'11:00:00',10),
(8,'08:00:00',9),
(9,'09:00:00',9),
(10,'08:00:00',6),
(11,'09:00:00',6),
(12,'10:00:00',6),
(14,'08:00:00',11),
(15,'08:30:00',11),
(16,'09:00:00',11),
(17,'09:30:00',11),
(18,'14:00:00',10),
(19,'15:00:00',10),
(20,'16:00:00',10),
(21,'17:00:00',10),
(22,'11:00:00',6),
(23,'12:00:00',6),
(24,'15:00:00',6),
(25,'16:00:00',6),
(26,'17:00:00',6),
(27,'10:00:00',11),
(28,'12:00:00',11);
/*!40000 ALTER TABLE `horarios` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pagar`
--

DROP TABLE IF EXISTS `pagar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(50) DEFAULT NULL,
  `tipo` varchar(35) DEFAULT NULL,
  `valor` decimal(8,2) NOT NULL,
  `data_lanc` date NOT NULL,
  `data_venc` date NOT NULL,
  `data_pgto` date NOT NULL,
  `usuario_lanc` int(11) NOT NULL,
  `usuario_baixa` int(11) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `pessoa` int(11) NOT NULL,
  `pago` varchar(5) NOT NULL,
  `produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `funcionario` int(11) NOT NULL,
  `servico` int(11) NOT NULL,
  `cliente` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagar`
--

LOCK TABLES `pagar` WRITE;
/*!40000 ALTER TABLE `pagar` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pagar` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `produtos`
--

DROP TABLE IF EXISTS `produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `categoria` int(11) NOT NULL,
  `valor_compra` decimal(8,2) NOT NULL,
  `valor_venda` decimal(8,2) NOT NULL,
  `estoque` int(11) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `nivel_estoque` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos`
--

LOCK TABLES `produtos` WRITE;
/*!40000 ALTER TABLE `produtos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `produtos` VALUES
(2,'Creme Hidratação','Creme para hidratar...',2,2.00,35.00,41,'27-08-2025-20-48-46-creme.png',5),
(4,'Loção Pós Barba','Loção para barba creme X',5,10.00,15.00,48,'27-08-2025-20-45-49-lamina.png',5),
(5,'Pomada Modeladora','Pomada Modeladora para Cabelos',1,10.00,10.00,8,'27-08-2025-20-33-52-pomada.png',5),
(7,'Pomada Gel Modeladora','Pomada que tem uma ação de gel que mantém seu cabelo modelado mais tempo',1,10.00,30.00,60,'27-08-2025-20-39-25-gel.png',5);
/*!40000 ALTER TABLE `produtos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `receber`
--

DROP TABLE IF EXISTS `receber`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `receber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(50) DEFAULT NULL,
  `tipo` varchar(35) DEFAULT NULL,
  `valor` decimal(8,2) NOT NULL,
  `data_lanc` date NOT NULL,
  `data_venc` date NOT NULL,
  `data_pgto` date NOT NULL,
  `usuario_lanc` int(11) NOT NULL,
  `usuario_baixa` int(11) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `pessoa` int(11) NOT NULL,
  `pago` varchar(5) NOT NULL,
  `produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `servico` int(11) NOT NULL,
  `funcionario` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receber`
--

LOCK TABLES `receber` WRITE;
/*!40000 ALTER TABLE `receber` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `receber` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `saidas`
--

DROP TABLE IF EXISTS `saidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saidas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `motivo` varchar(50) NOT NULL,
  `usuario` int(11) NOT NULL,
  `data` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saidas`
--

LOCK TABLES `saidas` WRITE;
/*!40000 ALTER TABLE `saidas` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `saidas` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `servicos`
--

DROP TABLE IF EXISTS `servicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `servicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `categoria` int(11) NOT NULL,
  `valor` decimal(8,2) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `dias_retorno` int(11) NOT NULL,
  `ativo` varchar(5) NOT NULL,
  `comissao` decimal(8,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicos`
--

LOCK TABLES `servicos` WRITE;
/*!40000 ALTER TABLE `servicos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `servicos` VALUES
(1,'Corte',1,35.00,'27-08-2025-20-53-30-corte.png',15,'Sim',10.00),
(2,'Barba',1,30.00,'27-08-2025-20-53-41-barba.png',7,'Sim',10.00),
(10,'Corte + Barba',1,60.00,'27-08-2025-20-53-48-corte_barba.png',15,'Sim',15.00);
/*!40000 ALTER TABLE `servicos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `textos_index`
--

DROP TABLE IF EXISTS `textos_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `textos_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(25) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `textos_index`
--

LOCK TABLES `textos_index` WRITE;
/*!40000 ALTER TABLE `textos_index` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `textos_index` VALUES
(1,'Cortes Profissionais','Descrição relacionado ao texto, você poderá alterar essa descrição e o título do texto acima no painel administrativo, lá terá uma opção para gerenciar os recursos do site.'),
(2,'Faça sua Barba','Descrição relacionado ao texto, você poderá alterar essa descrição e o título do texto acima no painel administrativo, lá terá uma opção para gerenciar os recursos do site.');
/*!40000 ALTER TABLE `textos_index` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `senha` varchar(25) NOT NULL,
  `senha_crip` varchar(100) NOT NULL,
  `nivel` varchar(35) NOT NULL,
  `data` date NOT NULL,
  `ativo` varchar(5) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(100) DEFAULT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `atendimento` varchar(5) NOT NULL,
  `tipo_chave` varchar(25) DEFAULT NULL,
  `chave_pix` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `usuarios` VALUES
(6,'Admin','admin@admin','000.000.000-00','123','202cb962ac59075b964b07152d234b70','Administrador','2022-05-25','Sim','(31) 99999-9999','Rua xxx, 8 ','27-08-2025-21-15-48-carlos.png','Sim',NULL,NULL),
(10,'João','joao@email.com','999.999.999-99','123','202cb962ac59075b964b07152d234b70','Barbeiro','2022-05-30','Sim','(11) 99999-9999','Rua 10','sem-foto.jpg','Sim','','');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `usuarios_permissoes`
--

DROP TABLE IF EXISTS `usuarios_permissoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios_permissoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` int(11) NOT NULL,
  `permissao` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=287 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios_permissoes`
--

LOCK TABLES `usuarios_permissoes` WRITE;
/*!40000 ALTER TABLE `usuarios_permissoes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `usuarios_permissoes` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-02-25  8:08:49
