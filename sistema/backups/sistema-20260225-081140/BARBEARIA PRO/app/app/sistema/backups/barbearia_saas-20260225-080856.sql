/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: barbearia_saas
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
-- Table structure for table `empresas`
--

DROP TABLE IF EXISTS `empresas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `banco` varchar(120) NOT NULL,
  `db_host` varchar(120) NOT NULL DEFAULT 'localhost',
  `db_usuario` varchar(120) NOT NULL DEFAULT 'root',
  `db_senha` varchar(190) NOT NULL,
  `ativo` enum('Sim','Nao') NOT NULL DEFAULT 'Sim',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_empresas_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas`
--

LOCK TABLES `empresas` WRITE;
/*!40000 ALTER TABLE `empresas` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `empresas` VALUES
(1,'Barbearia Principal','barbearia-principal','barbearia','127.0.0.1','barbearia_app','@Vito4747','Sim','2026-02-19 22:14:35','2026-02-20 02:16:16'),
(2,'Barbearia Demo SaaS','barbearia-demo-saas','barbearia','127.0.0.1','barbearia_app','@Vito4747','Sim','2026-02-19 22:29:47','2026-02-20 02:16:16'),
(5,'Iluminatto Dev','minhabarber','barbearia_minhabarber','127.0.0.1','barbearia_app','@Vito4747','Sim','2026-02-20 03:40:58','2026-02-20 03:40:58'),
(6,'Iluminatto Dev','barber123','barbearia_barber123','127.0.0.1','barbearia_app','@Vito4747','Sim','2026-02-20 07:14:56','2026-02-20 07:14:56'),
(7,'Barber ama','barberama','barbearia_barberama','127.0.0.1','barbearia_app','@Vito4747','Sim','2026-02-20 11:51:38','2026-02-20 11:51:38');
/*!40000 ALTER TABLE `empresas` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `empresas_assinaturas`
--

DROP TABLE IF EXISTS `empresas_assinaturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas_assinaturas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `plano_id` int(10) unsigned NOT NULL,
  `status` enum('Trial','Ativa','Suspensa','Cancelada') NOT NULL DEFAULT 'Trial',
  `inicio_em` datetime NOT NULL DEFAULT current_timestamp(),
  `trial_ate` date DEFAULT NULL,
  `ciclo_ate` date DEFAULT NULL,
  `ultimo_pagamento_em` datetime DEFAULT NULL,
  `suspensa_em` datetime DEFAULT NULL,
  `observacoes` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_empresas_assinaturas_empresa` (`empresa_id`),
  KEY `idx_empresas_assinaturas_plano` (`plano_id`),
  CONSTRAINT `fk_empresas_assinaturas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_empresas_assinaturas_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas_assinaturas`
--

LOCK TABLES `empresas_assinaturas` WRITE;
/*!40000 ALTER TABLE `empresas_assinaturas` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `empresas_assinaturas` VALUES
(1,1,1,'Ativa','2026-02-20 02:45:00',NULL,NULL,NULL,NULL,NULL,'2026-02-20 02:45:06','2026-02-20 19:43:51'),
(3,2,1,'Ativa','2026-02-20 02:45:16',NULL,NULL,NULL,NULL,NULL,'2026-02-20 02:45:16','2026-02-20 02:46:08'),
(14,5,1,'Trial','2026-02-20 03:40:58','2026-03-06',NULL,NULL,NULL,NULL,'2026-02-20 03:40:58','2026-02-20 03:40:58'),
(15,6,1,'Trial','2026-02-20 07:15:08','2026-03-06',NULL,NULL,NULL,NULL,'2026-02-20 07:15:08','2026-02-20 07:15:08'),
(16,7,1,'Trial','2026-02-20 11:51:38','2026-03-06',NULL,NULL,NULL,NULL,'2026-02-20 11:51:38','2026-02-20 11:51:38');
/*!40000 ALTER TABLE `empresas_assinaturas` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `empresas_dominios`
--

DROP TABLE IF EXISTS `empresas_dominios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas_dominios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `dominio` varchar(190) NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_empresas_dominios_dominio` (`dominio`),
  KEY `idx_empresas_dominios_empresa` (`empresa_id`),
  CONSTRAINT `fk_empresas_dominios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas_dominios`
--

LOCK TABLES `empresas_dominios` WRITE;
/*!40000 ALTER TABLE `empresas_dominios` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `empresas_dominios` VALUES
(1,1,'barbearia.superzap.fun',1,'2026-02-19 22:14:35'),
(2,1,'localhost',0,'2026-02-19 22:14:35'),
(3,1,'127.0.0.1',0,'2026-02-19 22:14:35'),
(6,2,'demo-saas.superzap.fun',1,'2026-02-19 22:29:47'),
(24,5,'minhabarber.superzap.fun',1,'2026-02-20 03:40:58'),
(25,6,'barber123.superzap.fun',1,'2026-02-20 07:14:57'),
(26,7,'barberama.0.1',1,'2026-02-20 11:51:38');
/*!40000 ALTER TABLE `empresas_dominios` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `empresas_eventos_billing`
--

DROP TABLE IF EXISTS `empresas_eventos_billing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas_eventos_billing` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `tipo` varchar(120) NOT NULL,
  `recurso` varchar(120) DEFAULT NULL,
  `detalhe` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_empresas_eventos_empresa` (`empresa_id`),
  CONSTRAINT `fk_empresas_eventos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas_eventos_billing`
--

LOCK TABLES `empresas_eventos_billing` WRITE;
/*!40000 ALTER TABLE `empresas_eventos_billing` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `empresas_eventos_billing` VALUES
(1,1,'limite_total_excedido','limite_produtos','Limite de produtos do plano atingido. Limite: 4. Atual: 4.','2026-02-20 02:47:07'),
(13,1,'assinatura_atualizada','assinatura','Plano atualizado para Starter com status Ativa','2026-02-20 19:35:09'),
(14,1,'assinatura_atualizada','assinatura','Plano atualizado para Starter | status: Ativa | trial ate: - | ciclo ate: -','2026-02-20 19:43:51');
/*!40000 ALTER TABLE `empresas_eventos_billing` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `empresas_pagamentos`
--

DROP TABLE IF EXISTS `empresas_pagamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas_pagamentos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `assinatura_id` int(10) unsigned DEFAULT NULL,
  `plano_id` int(10) unsigned DEFAULT NULL,
  `gateway` varchar(40) NOT NULL DEFAULT 'pagbank',
  `metodo_pagamento` enum('PIX','Cartao') NOT NULL DEFAULT 'PIX',
  `pedido_referencia` varchar(120) NOT NULL,
  `idempotency_key` varchar(120) DEFAULT NULL,
  `pagbank_order_id` varchar(120) DEFAULT NULL,
  `webhook_evento_id` varchar(120) DEFAULT NULL,
  `status` enum('Pendente','Pago','Cancelado','Expirado','Falha') NOT NULL DEFAULT 'Pendente',
  `status_detalhe` varchar(80) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `moeda` char(3) NOT NULL DEFAULT 'BRL',
  `qr_code_text` longtext DEFAULT NULL,
  `qr_code_link` varchar(255) DEFAULT NULL,
  `expiracao_em` datetime DEFAULT NULL,
  `payload_criacao` longtext DEFAULT NULL,
  `payload_status` longtext DEFAULT NULL,
  `payload_webhook` longtext DEFAULT NULL,
  `tentativas_consulta` int(10) unsigned NOT NULL DEFAULT 0,
  `pago_em` datetime DEFAULT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_empresas_pagamentos_referencia` (`pedido_referencia`),
  UNIQUE KEY `uq_empresas_pagamentos_idempotency` (`empresa_id`,`idempotency_key`),
  KEY `idx_empresas_pagamentos_empresa` (`empresa_id`),
  KEY `idx_empresas_pagamentos_empresa_status` (`empresa_id`,`status`),
  KEY `idx_empresas_pagamentos_pagbank_order` (`pagbank_order_id`),
  KEY `idx_empresas_pagamentos_status_exp` (`status`,`expiracao_em`),
  CONSTRAINT `fk_empresas_pagamentos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas_pagamentos`
--

LOCK TABLES `empresas_pagamentos` WRITE;
/*!40000 ALTER TABLE `empresas_pagamentos` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `empresas_pagamentos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `empresas_tunnels`
--

DROP TABLE IF EXISTS `empresas_tunnels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas_tunnels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `tunnel_nome` varchar(120) NOT NULL,
  `tunnel_id` char(36) NOT NULL,
  `dominio` varchar(190) NOT NULL,
  `service_url` varchar(255) NOT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_empresas_tunnels_id` (`tunnel_id`),
  UNIQUE KEY `uq_empresas_tunnels_empresa_dominio` (`empresa_id`,`dominio`),
  KEY `idx_empresas_tunnels_empresa` (`empresa_id`),
  CONSTRAINT `fk_empresas_tunnels_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas_tunnels`
--

LOCK TABLES `empresas_tunnels` WRITE;
/*!40000 ALTER TABLE `empresas_tunnels` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `empresas_tunnels` VALUES
(1,2,'tenant-barbearia-demo-saas','10626efc-0fd1-4c8a-b0e8-622f007fac90','demo-saas.superzap.fun','http://127.0.0.1:8000','Ativo','2026-02-19 22:29:52','2026-02-19 22:29:52'),
(7,5,'tenant-minhabarber','173c1840-6431-4970-b407-0c3d8f146850','minhabarber.superzap.fun','http://127.0.0.1:8000','Ativo','2026-02-20 03:41:06','2026-02-20 03:41:06'),
(8,6,'tenant-barber123','6cf09e6a-b20b-41f7-9ef6-81f7a09ee289','barber123.superzap.fun','http://127.0.0.1:8000','Ativo','2026-02-20 07:15:47','2026-02-20 07:15:47'),
(9,7,'tenant-barberama','7115d079-40ef-42c2-8ff2-a26ffff79534','barberama.0.1','http://127.0.0.1:8000','Ativo','2026-02-20 11:51:43','2026-02-20 11:51:43');
/*!40000 ALTER TABLE `empresas_tunnels` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `empresas_uso_mensal`
--

DROP TABLE IF EXISTS `empresas_uso_mensal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas_uso_mensal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `recurso` varchar(120) NOT NULL,
  `referencia` char(7) NOT NULL,
  `quantidade` int(10) unsigned NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_empresas_uso_ref` (`empresa_id`,`recurso`,`referencia`),
  KEY `idx_empresas_uso_empresa` (`empresa_id`),
  CONSTRAINT `fk_empresas_uso_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas_uso_mensal`
--

LOCK TABLES `empresas_uso_mensal` WRITE;
/*!40000 ALTER TABLE `empresas_uso_mensal` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `empresas_uso_mensal` VALUES
(1,1,'limite_agendamentos_mes','2026-02',4,'2026-02-20 19:42:52','2026-02-20 19:48:07');
/*!40000 ALTER TABLE `empresas_uso_mensal` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `empresas_usuarios`
--

DROP TABLE IF EXISTS `empresas_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas_usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `senha_crip` varchar(100) NOT NULL,
  `ativo` enum('Sim','Nao') NOT NULL DEFAULT 'Sim',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_empresas_usuarios_email` (`email`),
  KEY `idx_empresas_usuarios_empresa` (`empresa_id`),
  CONSTRAINT `fk_empresas_usuarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas_usuarios`
--

LOCK TABLES `empresas_usuarios` WRITE;
/*!40000 ALTER TABLE `empresas_usuarios` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `empresas_usuarios` VALUES
(3,5,'Iluminatto','anacintra2002@gmail.com','e10adc3949ba59abbe56e057f20f883e','Sim','2026-02-20 03:40:58','2026-02-20 03:40:58'),
(4,6,'Iluminatto','iluminatto@gmail.com','e10adc3949ba59abbe56e057f20f883e','Sim','2026-02-20 07:15:25','2026-02-20 07:15:25'),
(5,7,'Marinho','iluminatto@msn.com','e10adc3949ba59abbe56e057f20f883e','Sim','2026-02-20 11:51:38','2026-02-20 11:51:38');
/*!40000 ALTER TABLE `empresas_usuarios` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `planos`
--

DROP TABLE IF EXISTS `planos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `descricao` text DEFAULT NULL,
  `trial_dias` int(10) unsigned NOT NULL DEFAULT 14,
  `valor_mensal` decimal(10,2) NOT NULL DEFAULT 79.90,
  `pagbank_referencia` varchar(120) DEFAULT NULL,
  `ativo` enum('Sim','Nao') NOT NULL DEFAULT 'Sim',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_planos_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `planos`
--

LOCK TABLES `planos` WRITE;
/*!40000 ALTER TABLE `planos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `planos` VALUES
(1,'Starter','starter','Plano padrao inicial para operacao SaaS',14,79.90,NULL,'Sim','2026-02-20 02:45:04','2026-02-20 02:45:04');
/*!40000 ALTER TABLE `planos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `planos_recursos`
--

DROP TABLE IF EXISTS `planos_recursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planos_recursos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plano_id` int(10) unsigned NOT NULL,
  `recurso` varchar(120) NOT NULL,
  `permitido` enum('Sim','Nao') NOT NULL DEFAULT 'Sim',
  `limite` int(11) DEFAULT NULL,
  `periodo` enum('mensal','total') NOT NULL DEFAULT 'total',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_planos_recursos` (`plano_id`,`recurso`),
  KEY `idx_planos_recursos_plano` (`plano_id`),
  CONSTRAINT `fk_planos_recursos_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=225 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `planos_recursos`
--

LOCK TABLES `planos_recursos` WRITE;
/*!40000 ALTER TABLE `planos_recursos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `planos_recursos` VALUES
(1,1,'acesso_painel','Sim',NULL,'total','2026-02-20 02:45:04','2026-02-20 02:45:04'),
(2,1,'menu_home','Sim',NULL,'total','2026-02-20 02:45:05','2026-02-20 02:45:05'),
(3,1,'menu_configuracoes','Sim',NULL,'total','2026-02-20 02:45:05','2026-02-20 02:45:05'),
(4,1,'menu_pessoas','Sim',NULL,'total','2026-02-20 02:45:05','2026-02-20 02:45:05'),
(5,1,'menu_cadastros','Sim',NULL,'total','2026-02-20 02:45:05','2026-02-20 02:45:05'),
(6,1,'menu_produtos','Sim',NULL,'total','2026-02-20 02:45:05','2026-02-20 02:46:47'),
(7,1,'menu_financeiro','Sim',NULL,'total','2026-02-20 02:45:05','2026-02-20 02:45:05'),
(8,1,'menu_agendamentos','Sim',NULL,'total','2026-02-20 02:45:05','2026-02-20 02:45:05'),
(9,1,'menu_relatorios','Sim',NULL,'total','2026-02-20 02:45:05','2026-02-20 02:45:05'),
(10,1,'menu_site','Sim',NULL,'total','2026-02-20 02:45:05','2026-02-20 02:45:05'),
(11,1,'limite_usuarios','Sim',20,'total','2026-02-20 02:45:05','2026-02-20 02:45:05'),
(12,1,'limite_produtos','Sim',500,'total','2026-02-20 02:45:05','2026-02-20 02:47:07'),
(13,1,'limite_servicos','Sim',120,'total','2026-02-20 02:45:06','2026-02-20 02:45:06'),
(14,1,'limite_agendamentos_mes','Sim',2000,'mensal','2026-02-20 02:45:06','2026-02-20 02:45:06');
/*!40000 ALTER TABLE `planos_recursos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `saas_admins`
--

DROP TABLE IF EXISTS `saas_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` enum('Sim','Nao') DEFAULT 'Sim',
  `super_admin` tinyint(1) DEFAULT 0,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_admins`
--

LOCK TABLES `saas_admins` WRITE;
/*!40000 ALTER TABLE `saas_admins` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `saas_admins` VALUES
(1,'Administrador','admin@superzap.fun','$2y$12$QMZW9yz0DYZMeHAoJOVMUuHEcolK4hj4RLCekH6BWaSZfQm5Kh91m','Sim',1,'2026-02-24 08:36:04','2026-02-24 08:36:04');
/*!40000 ALTER TABLE `saas_admins` ENABLE KEYS */;
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

-- Dump completed on 2026-02-25  8:08:56
