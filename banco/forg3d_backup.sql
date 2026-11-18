/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


/*!40000 DROP DATABASE IF EXISTS `forg3d`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `forg3d` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `forg3d`;


DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `tipo` enum('PF','PJ') NOT NULL DEFAULT 'PF',
  `documento` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cidade` varchar(80) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES
(1,'Ana Paula Ribeiro','PF','111.222.333-44','ana.ribeiro@email.com','(18) 99712-3344','Presidente Prudente','Prefere peças brancas e acabamento lixado.','2026-06-06 20:54:45'),
(2,'Oficina Maker Prudente','PJ','11.222.333/0001-44','contato@makerprudente.com.br','(18) 3221-4567','Presidente Prudente','Cliente recorrente — peças técnicas.','2026-06-15 20:54:45'),
(3,'Rafael Monteiro','PF','222.333.444-55','rafa.monteiro@email.com','(18) 99654-1122','Álvares Machado','','2026-06-24 20:54:45'),
(4,'Clínica OdontoSorriso','PJ','22.333.444/0001-55','compras@odontosorriso.com.br','(18) 3903-7788','Pirapozinho','Brindes com a logo da clínica.','2026-07-03 20:54:45'),
(5,'Juliana Costa','PF','333.444.555-66','ju.costa@email.com','(18) 99801-5566','Regente Feijó','','2026-07-12 20:54:45'),
(6,'Escola Técnica Futuro','PJ','33.444.555/0001-66','coordenacao@etfuturo.edu.br','(18) 3222-9900','Presidente Prudente','Kits didáticos para o laboratório maker.','2026-07-21 20:54:45');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `configuracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracoes` (
  `id` tinyint(4) NOT NULL,
  `nome_empresa` varchar(80) NOT NULL DEFAULT 'FORG3D',
  `tarifa_kwh` decimal(6,3) NOT NULL DEFAULT 0.950,
  `margem_padrao` decimal(5,2) NOT NULL DEFAULT 60.00,
  `taxa_falha_padrao` decimal(5,2) NOT NULL DEFAULT 10.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `configuracoes` WRITE;
/*!40000 ALTER TABLE `configuracoes` DISABLE KEYS */;
INSERT INTO `configuracoes` VALUES
(1,'FORG3D',0.950,60.00,10.00);
/*!40000 ALTER TABLE `configuracoes` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `ia_consultas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ia_consultas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `pergunta` text NOT NULL,
  `resposta` text NOT NULL,
  `modo` enum('gemini','demo') NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `ia_consultas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `ia_consultas` WRITE;
/*!40000 ALTER TABLE `ia_consultas` DISABLE KEYS */;
INSERT INTO `ia_consultas` VALUES
(1,1,'Suporte de sensor para uso externo, vai ficar no sol o dia todo.','{\"material\":\"ASA\",\"preenchimento\":\"40% giroide\",\"camada\":\"0,20 mm\",\"paredes\":\"4\",\"suportes\":\"Não\",\"observacoes\":\"Usar enclosure fechado para evitar warping.\"}','demo','2026-11-18 20:54:45');
/*!40000 ALTER TABLE `ia_consultas` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `impressoras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `impressoras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(60) NOT NULL,
  `marca` varchar(60) NOT NULL,
  `modelo` varchar(60) NOT NULL,
  `tecnologia` enum('FDM','SLA') NOT NULL DEFAULT 'FDM',
  `potencia_w` int(11) NOT NULL,
  `valor_compra` decimal(10,2) NOT NULL,
  `vida_util_h` int(11) NOT NULL DEFAULT 8000,
  `horas_uso` decimal(10,1) NOT NULL DEFAULT 0.0,
  `status` enum('disponivel','em_uso','manutencao') NOT NULL DEFAULT 'disponivel',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `impressoras` WRITE;
/*!40000 ALTER TABLE `impressoras` DISABLE KEYS */;
INSERT INTO `impressoras` VALUES
(1,'Bigorna 01','Bambu Lab','P1S','FDM',350,6500.00,8000,1352.2,'em_uso','2026-06-03 20:54:45'),
(2,'Martelo 02','Creality','Ender-3 V3','FDM',270,1800.00,6000,2659.4,'disponivel','2026-06-03 20:54:45'),
(3,'Brasa 03','Prusa','MK4S','FDM',240,7200.00,10000,860.0,'manutencao','2026-06-03 20:54:45');
/*!40000 ALTER TABLE `impressoras` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `materiais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `materiais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(30) NOT NULL,
  `marca` varchar(60) NOT NULL,
  `cor` varchar(40) NOT NULL,
  `cor_hex` char(7) NOT NULL DEFAULT '#888888',
  `diametro` decimal(4,2) NOT NULL DEFAULT 1.75,
  `preco_kg` decimal(8,2) NOT NULL,
  `estoque_g` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estoque_minimo_g` decimal(10,2) NOT NULL DEFAULT 300.00,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `materiais` WRITE;
/*!40000 ALTER TABLE `materiais` DISABLE KEYS */;
INSERT INTO `materiais` VALUES
(1,'PLA','Voolt3D','Preto Carvão','#1c1c1e',1.75,89.90,2350.00,500.00,'2026-06-03 20:54:45'),
(2,'PLA','Voolt3D','Branco Gelo','#eef0f2',1.75,89.90,1800.00,500.00,'2026-06-03 20:54:45'),
(3,'PETG','3D Fila','Laranja Forja','#ff6a1a',1.75,109.90,950.00,400.00,'2026-06-03 20:54:45'),
(4,'ABS','GTMax3D','Cinza Grafite','#4a4f55',1.75,79.90,300.00,500.00,'2026-06-03 20:54:45'),
(5,'TPU','3D Lab','Translúcido','#cfe8ff',1.75,159.90,420.00,250.00,'2026-06-03 20:54:45'),
(6,'ASA','Voolt3D','Prata Metálico','#b8bec6',1.75,139.90,150.00,300.00,'2026-06-03 20:54:45'),
(7,'PLA Silk','Sunlu','Cobre','#b87333',1.75,119.90,780.00,300.00,'2026-06-03 20:54:45');
/*!40000 ALTER TABLE `materiais` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `modelos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modelos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `categoria` varchar(60) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `peso_g` decimal(8,2) NOT NULL,
  `tempo_h` decimal(6,2) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `imagem` varchar(150) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  CONSTRAINT `modelos_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materiais` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `modelos` WRITE;
/*!40000 ALTER TABLE `modelos` DISABLE KEYS */;
INSERT INTO `modelos` VALUES
(1,'Suporte de celular articulado','Utilidades','Suporte de mesa com dobradiça print-in-place, ângulo ajustável.',48.00,2.60,1,'modelo-suporte.svg','2026-06-16 20:54:45'),
(2,'Vaso geométrico low-poly','Decoração','Vaso facetado em modo vaso (parede única), 18 cm de altura.',120.00,5.50,2,'modelo-vaso.svg','2026-06-21 20:54:45'),
(3,'Engrenagem reforçada 40 dentes','Peças técnicas','Engrenagem de dentes retos, módulo 1,5, furo de 8 mm.',35.00,1.80,3,'modelo-engrenagem.svg','2026-06-26 20:54:45'),
(4,'Chaveiro personalizado','Brindes','Chaveiro com nome ou logo em relevo, 5 cm.',8.00,0.40,1,'modelo-chaveiro.svg','2026-07-01 20:54:45'),
(5,'Organizador de mesa modular','Escritório','Módulos encaixáveis para canetas, clipes e celular.',210.00,9.00,3,'modelo-organizador.svg','2026-07-06 20:54:45'),
(6,'Robô articulado','Brinquedos','Robozinho com braços e pernas articulados, impresso em uma peça só (print-in-place).',42.00,2.80,1,'modelo-robo.svg','2026-07-11 20:54:45');
/*!40000 ALTER TABLE `modelos` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `movimentacoes_estoque`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimentacoes_estoque` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida','ajuste') NOT NULL,
  `quantidade_g` decimal(10,2) NOT NULL,
  `motivo` varchar(150) DEFAULT NULL,
  `orcamento_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `orcamento_id` (`orcamento_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `movimentacoes_estoque_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materiais` (`id`),
  CONSTRAINT `movimentacoes_estoque_ibfk_2` FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimentacoes_estoque_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `movimentacoes_estoque` WRITE;
/*!40000 ALTER TABLE `movimentacoes_estoque` DISABLE KEYS */;
INSERT INTO `movimentacoes_estoque` VALUES
(1,1,'entrada',2422.00,'Estoque inicial do cadastro',NULL,1,'2026-06-03 20:54:45'),
(2,1,'entrada',1000.00,'Compra de 1 bobina (1 kg)',NULL,2,'2026-09-19 20:54:45'),
(3,1,'saida',-400.00,'Baixa automática — orçamento #2',2,1,'2026-07-21 20:54:45'),
(4,1,'saida',-288.00,'Baixa automática — orçamento #4',4,1,'2026-09-09 20:54:45'),
(5,1,'saida',-144.00,'Baixa automática — orçamento #7',7,1,'2026-11-16 20:54:45'),
(6,1,'saida',-240.00,'Baixa automática — orçamento #8',8,1,'2026-11-17 20:54:45'),
(7,2,'entrada',2040.00,'Estoque inicial do cadastro',NULL,1,'2026-06-03 20:54:45'),
(8,2,'saida',-240.00,'Baixa automática — orçamento #3',3,1,'2026-08-15 20:54:45'),
(9,3,'entrada',1700.00,'Estoque inicial do cadastro',NULL,1,'2026-06-03 20:54:45'),
(10,3,'entrada',1000.00,'Compra de 1 bobina (1 kg)',NULL,2,'2026-09-19 20:54:45'),
(11,3,'saida',-350.00,'Baixa automática — orçamento #1',1,1,'2026-06-21 20:54:45'),
(12,3,'saida',-840.00,'Baixa automática — orçamento #4',4,1,'2026-09-09 20:54:45'),
(13,3,'saida',-560.00,'Baixa automática — orçamento #6',6,1,'2026-11-04 20:54:45'),
(14,4,'entrada',300.00,'Estoque inicial do cadastro',NULL,1,'2026-06-03 20:54:45'),
(15,5,'entrada',550.00,'Estoque inicial do cadastro',NULL,1,'2026-06-03 20:54:45'),
(16,5,'saida',-130.00,'Baixa automática — orçamento #5',5,1,'2026-10-09 20:54:45'),
(17,6,'entrada',150.00,'Estoque inicial do cadastro',NULL,1,'2026-06-03 20:54:45'),
(18,7,'entrada',900.00,'Estoque inicial do cadastro',NULL,1,'2026-06-03 20:54:45'),
(19,7,'saida',-120.00,'Baixa automática — orçamento #6',6,1,'2026-11-04 20:54:45');
/*!40000 ALTER TABLE `movimentacoes_estoque` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `orcamento_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orcamento_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orcamento_id` int(11) NOT NULL,
  `modelo_id` int(11) DEFAULT NULL,
  `descricao` varchar(150) NOT NULL,
  `material_id` int(11) NOT NULL,
  `impressora_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `peso_g` decimal(8,2) NOT NULL,
  `tempo_h` decimal(6,2) NOT NULL,
  `custo_material` decimal(10,2) NOT NULL,
  `custo_energia` decimal(10,2) NOT NULL,
  `custo_maquina` decimal(10,2) NOT NULL,
  `custo_unit` decimal(10,2) NOT NULL,
  `preco_unit` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orcamento_id` (`orcamento_id`),
  KEY `modelo_id` (`modelo_id`),
  KEY `material_id` (`material_id`),
  KEY `impressora_id` (`impressora_id`),
  CONSTRAINT `orcamento_itens_ibfk_1` FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orcamento_itens_ibfk_2` FOREIGN KEY (`modelo_id`) REFERENCES `modelos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orcamento_itens_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `materiais` (`id`),
  CONSTRAINT `orcamento_itens_ibfk_4` FOREIGN KEY (`impressora_id`) REFERENCES `impressoras` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `orcamento_itens` WRITE;
/*!40000 ALTER TABLE `orcamento_itens` DISABLE KEYS */;
INSERT INTO `orcamento_itens` VALUES
(1,1,3,'Engrenagem reforçada 40 dentes',3,1,10,35.00,1.80,3.85,0.60,1.46,6.50,10.40),
(2,2,4,'Chaveiro com logo OdontoSorriso',1,2,50,8.00,0.40,0.72,0.10,0.12,1.04,1.66),
(3,3,2,'Vaso geométrico low-poly',2,1,2,120.00,5.50,10.79,1.83,4.47,18.79,30.07),
(4,4,5,'Organizador de mesa modular',3,1,4,210.00,9.00,23.08,2.99,7.31,36.72,58.76),
(5,4,1,'Suporte de celular articulado',1,2,6,48.00,2.60,4.32,0.67,0.78,6.34,10.14),
(6,5,NULL,'Capa flexível para controle',5,1,2,65.00,3.20,10.39,1.06,2.60,15.46,24.74),
(7,6,3,'Engrenagem reforçada 40 dentes',3,1,16,35.00,1.80,3.85,0.60,1.46,6.50,10.40),
(8,6,NULL,'Bucha espaçadora 12 mm',7,2,20,6.00,0.30,0.72,0.08,0.09,0.97,1.56),
(9,7,1,'Suporte de celular articulado',1,2,3,48.00,2.60,4.32,0.67,0.78,6.34,10.14),
(10,8,4,'Chaveiro com logo OdontoSorriso',1,1,30,8.00,0.40,0.72,0.13,0.33,1.29,2.07),
(11,9,5,'Organizador de mesa modular',3,1,2,210.00,9.00,23.08,2.99,7.31,36.72,58.76),
(12,10,NULL,'Miniatura de dragão para pintura à mão',2,3,1,95.00,7.50,8.54,1.71,5.40,17.22,27.54),
(13,11,NULL,'Trava de janela (peça de reposição)',4,2,4,22.00,1.20,1.76,0.31,0.36,2.67,4.27),
(14,12,2,'Vaso geométrico low-poly',2,2,1,120.00,5.50,10.79,1.41,1.65,15.23,24.37),
(15,13,NULL,'Suporte de sensor para uso externo',6,1,6,40.00,2.20,5.60,0.73,1.79,8.93,14.28);
/*!40000 ALTER TABLE `orcamento_itens` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `orcamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orcamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `status` enum('pendente','aprovado','em_producao','concluido','cancelado') NOT NULL DEFAULT 'pendente',
  `margem` decimal(5,2) NOT NULL,
  `taxa_falha` decimal(5,2) NOT NULL,
  `tarifa_kwh` decimal(6,3) NOT NULL,
  `custo_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `preco_final` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prazo_entrega` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `sugestao_ia` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `concluido_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `orcamentos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `orcamentos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `orcamentos` WRITE;
/*!40000 ALTER TABLE `orcamentos` DISABLE KEYS */;
INSERT INTO `orcamentos` VALUES
(1,2,1,'concluido',60.00,10.00,0.950,65.00,104.00,'2026-06-20','Lote de engrenagens para manutenção de máquinas.',NULL,'2026-06-13 20:54:45','2026-06-21 20:54:45'),
(2,4,1,'concluido',60.00,10.00,0.950,52.00,83.00,'2026-07-20','Brindes para campanha de saúde bucal.',NULL,'2026-07-13 20:54:45','2026-07-21 20:54:45'),
(3,1,2,'concluido',60.00,10.00,0.950,37.58,60.14,'2026-08-17','',NULL,'2026-08-10 20:54:45','2026-08-15 20:54:45'),
(4,6,1,'concluido',60.00,10.00,0.950,184.92,295.88,'2026-09-08','Kit para o laboratório maker.',NULL,'2026-09-01 20:54:45','2026-09-09 20:54:45'),
(5,3,1,'concluido',60.00,10.00,0.950,30.92,49.48,'2026-10-11','',NULL,'2026-10-04 20:54:45','2026-10-09 20:54:45'),
(6,2,2,'concluido',60.00,10.00,0.950,123.40,197.60,'2026-11-05','Segundo lote.',NULL,'2026-10-29 20:54:45','2026-11-04 20:54:45'),
(7,5,1,'concluido',60.00,10.00,0.950,19.02,30.42,'2026-11-21','',NULL,'2026-11-14 20:54:45','2026-11-16 20:54:45'),
(8,4,1,'concluido',60.00,10.00,0.950,38.70,62.10,'2026-11-22','Reposição de brindes.',NULL,'2026-11-15 20:54:45','2026-11-17 20:54:45'),
(9,6,2,'em_producao',60.00,10.00,0.950,73.44,117.52,'2026-11-22','Entrega na secretaria da escola.',NULL,'2026-11-15 20:54:45',NULL),
(10,1,1,'aprovado',60.00,10.00,0.950,17.22,27.54,'2026-11-23','Imprimir com camada 0,12 mm.',NULL,'2026-11-16 20:54:45',NULL),
(11,3,1,'pendente',60.00,10.00,0.950,10.68,17.08,'2026-11-24','Cliente vai buscar pessoalmente.',NULL,'2026-11-17 20:54:45',NULL),
(12,5,2,'cancelado',60.00,10.00,0.950,15.23,24.37,'2026-10-26','Cliente desistiu.',NULL,'2026-10-19 20:54:45',NULL),
(13,2,1,'pendente',60.00,10.00,0.950,53.58,85.68,'2026-11-25','Peça vai ficar exposta ao sol.','Material: ASA — resistente a UV e calor, ideal para uso externo. Preenchimento 40% giroide, 4 paredes, camada 0,20 mm. Usar enclosure fechado para evitar warping.','2026-11-18 20:54:45',NULL);
/*!40000 ALTER TABLE `orcamentos` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `tokens_senha`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tokens_senha` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token` char(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `tokens_senha_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `tokens_senha` WRITE;
/*!40000 ALTER TABLE `tokens_senha` DISABLE KEYS */;
/*!40000 ALTER TABLE `tokens_senha` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('admin','operador') NOT NULL DEFAULT 'operador',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES
(1,'Diego Date','admin@forg3d.com','$2y$10$Hri8/Kz523S5jbrnC0DV6.PsrJl81llizkhoHrSowZRF4zIfTf5cW','admin',1,'2026-06-01 20:54:45'),
(2,'Luan Bariani','operador@forg3d.com','$2y$10$G94PpfVINwqc2VqGiJYl3eTz9SftiI9GIJldEZwZLdjby0pZR05qW','operador',1,'2026-06-01 20:54:45');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

