-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: voracms
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Table structure for table `content_types`
--

DROP TABLE IF EXISTS `content_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `base` tinyint(4) NOT NULL DEFAULT 0,
  `auto_clone` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B2F3DDE2A76ED395` (`user_id`),
  KEY `IDX_B2F3DDE2166D1F9C` (`project_id`),
  CONSTRAINT `FK_B2F3DDE2166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `FK_B2F3DDE2A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `content_types`
--

LOCK TABLES `content_types` WRITE;
/*!40000 ALTER TABLE `content_types` DISABLE KEYS */;
INSERT INTO `content_types` VALUES (21,'noticia','Not├¡cies','Articles i not├¡cies del projecte',1,1,1,'2026-06-30 10:12:07',16,NULL),(22,'event','Events','Esdeveniments i actes',1,1,1,'2026-06-30 10:12:07',16,NULL),(68,'event','Events','Esdeveniments i actes',1,0,1,'2026-06-30 14:03:32',17,36),(69,'noticia','Not├¡cies','Articles i not├¡cies del projecte',1,0,1,'2026-06-30 14:03:32',17,36),(74,'vorastudio-projects','Projectes VoraStudio','Els projectes de la web www.vorastudio.cat',1,0,0,'2026-07-01 11:01:18',16,20),(75,'noticia','Not├¡cies','Articles i not├¡cies del projecte',1,0,0,'2026-07-06 09:37:14',16,20),(76,'noticia','Not├¡cies','Articles i not├¡cies del projecte',1,0,0,'2026-07-06 09:37:14',18,22),(77,'noticia','Not├¡cies','Articles i not├¡cies del projecte',1,0,0,'2026-07-06 09:37:14',17,34),(78,'event','Events','Esdeveniments i actes',1,0,0,'2026-07-06 09:37:14',16,20),(79,'event','Events','Esdeveniments i actes',1,0,0,'2026-07-06 09:37:14',18,22),(80,'event','Events','Esdeveniments i actes',1,0,0,'2026-07-06 09:37:14',17,34),(81,'event','Events','Esdeveniments i actes',1,0,1,'2026-07-06 09:47:50',17,37),(82,'noticia','Not├¡cies','Articles i not├¡cies del projecte',1,0,1,'2026-07-06 09:47:50',17,37),(84,'artistes_victoria_taylor','Artistes Victoria Taylor','Artistes de la web Victoria Taylor',1,0,1,'2026-07-06 12:06:47',17,37);
/*!40000 ALTER TABLE `content_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctrine_migration_versions`
--

LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;
INSERT INTO `doctrine_migration_versions` VALUES ('DoctrineMigrations\\Version20260701082931','2026-07-01 10:29:56',1242);
/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entries`
--

DROP TABLE IF EXISTS `entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `locale` varchar(5) NOT NULL DEFAULT 'ca',
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `published_at` date DEFAULT NULL,
  `content_type_id` int(11) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2DF8B3C51A445520` (`content_type_id`),
  KEY `IDX_2DF8B3C5F675F31B` (`author_id`),
  KEY `IDX_2DF8B3C5A76ED395` (`user_id`),
  KEY `IDX_2DF8B3C51A4455207B00651C` (`content_type_id`,`status`),
  KEY `IDX_2DF8B3C51A4455204180C698` (`content_type_id`,`locale`),
  CONSTRAINT `FK_2DF8B3C51A445520` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`),
  CONSTRAINT `FK_2DF8B3C5A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_2DF8B3C5F675F31B` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entries`
--

LOCK TABLES `entries` WRITE;
/*!40000 ALTER TABLE `entries` DISABLE KEYS */;
INSERT INTO `entries` VALUES (28,'published','ca',1,'2026-07-01 13:29:07',NULL,'2026-07-01',74,16,16),(29,'published','ca',1,'2026-07-01 14:08:23',NULL,'2026-07-01',74,16,16),(30,'published','ca',1,'2026-07-03 12:44:52',NULL,'2026-07-03',74,16,16),(31,'archived','ca',1,'2026-07-06 09:50:15',NULL,'2026-07-06',82,16,16),(32,'published','ca',1,'2026-07-06 09:53:12',NULL,'2026-07-06',81,16,16),(33,'published','ca',1,'2026-07-06 11:14:00',NULL,'2026-07-06',81,17,17),(34,'archived','ca',1,'2026-07-06 11:19:20',NULL,NULL,82,17,17),(35,'published','ca',1,'2026-07-06 11:22:10',NULL,'2026-07-06',77,17,17),(36,'archived','ca',1,'2026-07-06 11:26:05',NULL,NULL,80,16,16),(37,'published','ca',1,'2026-07-06 12:25:01',NULL,'2026-07-06',84,17,17),(38,'published','ca',1,'2026-07-06 13:56:14',NULL,'2026-07-06',84,16,16);
/*!40000 ALTER TABLE `entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `field_definitions`
--

DROP TABLE IF EXISTS `field_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `field_definitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `field_type` varchar(50) NOT NULL,
  `required` tinyint(4) NOT NULL DEFAULT 0,
  `translatable` tinyint(4) NOT NULL DEFAULT 1,
  `help_text` longtext DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `content_type_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_56D916151A445520` (`content_type_id`),
  CONSTRAINT `FK_56D916151A445520` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=465 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `field_definitions`
--

LOCK TABLES `field_definitions` WRITE;
/*!40000 ALTER TABLE `field_definitions` DISABLE KEYS */;
INSERT INTO `field_definitions` VALUES (291,'T├¡tol','titol','text',1,1,NULL,0,68),(292,'Subt├¡tol','subtitol','text',1,1,NULL,1,68),(293,'Descripci├│','descripcio','richtext',1,1,NULL,2,68),(294,'Data','data','date',0,1,NULL,3,68),(295,'Ubicaci├│','ubicacio','text',0,1,NULL,4,68),(296,'T├¡tol','titol','text',1,1,NULL,0,69),(297,'Descripci├│','descripcio','richtext',1,1,NULL,1,69),(298,'Data','data','date',0,1,NULL,2,69),(299,'Ubicaci├│','ubicacio','text',0,1,NULL,3,69),(300,'Imatge','imatge','image',0,1,NULL,4,69),(318,'titol','titol','text',1,1,NULL,0,74),(319,'descripcio','descripcio','textarea',1,1,NULL,1,74),(320,'logo','logo','image',0,1,NULL,2,74),(321,'website','website','text',0,1,NULL,3,74),(322,'tags','tags','text',0,1,NULL,4,74),(323,'repte','repte','textarea',0,1,NULL,5,74),(324,'estrategia','estrategia','textarea',0,1,NULL,6,74),(325,'resultat','resultat','textarea',0,1,NULL,7,74),(326,'galeria','galeria','gallery',0,1,NULL,8,74),(345,'Slug del projecte','project_slug','text',1,1,NULL,9,74),(346,'T├¡tol','titol','text',1,1,NULL,0,21),(347,'Descripci├│','descripcio','richtext',1,1,NULL,1,21),(348,'Data','data','date',0,1,NULL,2,21),(349,'Ubicaci├│','ubicacio','text',0,1,NULL,3,21),(350,'Imatge','imatge','image',0,1,NULL,4,21),(351,'Rang de dates','rang_de_dates','date_range',0,1,NULL,5,21),(352,'Galeria','galeria','gallery',0,1,NULL,6,21),(353,'T├¡tol','titol','text',1,1,NULL,0,22),(354,'Subt├¡tol','subtitol','text',1,1,NULL,1,22),(355,'Descripci├│','descripcio','richtext',1,1,NULL,2,22),(356,'Data','data','date',0,1,NULL,3,22),(357,'Ubicaci├│','ubicacio','text',0,1,NULL,4,22),(358,'imatge','imatge','image',0,1,NULL,5,22),(359,'galeria','galeria','gallery',0,1,NULL,6,22),(360,'dates','dates','date_range',0,1,NULL,7,22),(361,'T├¡tol','titol','text',1,1,NULL,0,75),(362,'Descripci├│','descripcio','richtext',1,1,NULL,1,75),(363,'Data','data','date',0,1,NULL,2,75),(364,'Ubicaci├│','ubicacio','text',0,1,NULL,3,75),(365,'Imatge','imatge','image',0,1,NULL,4,75),(366,'Rang de dates','rang_de_dates','date_range',0,1,NULL,5,75),(367,'Galeria','galeria','gallery',0,1,NULL,6,75),(368,'T├¡tol','titol','text',1,1,NULL,0,76),(369,'Descripci├│','descripcio','richtext',1,1,NULL,1,76),(370,'Data','data','date',0,1,NULL,2,76),(371,'Ubicaci├│','ubicacio','text',0,1,NULL,3,76),(372,'Imatge','imatge','image',0,1,NULL,4,76),(373,'Rang de dates','rang_de_dates','date_range',0,1,NULL,5,76),(374,'Galeria','galeria','gallery',0,1,NULL,6,76),(379,'T├¡tol','titol','text',1,1,NULL,0,77),(380,'Descripci├│','descripcio','richtext',1,1,NULL,1,77),(381,'Data','data','date',0,1,NULL,2,77),(382,'Ubicaci├│','ubicacio','text',0,1,NULL,3,77),(383,'Imatge','imatge','image',0,1,NULL,4,77),(384,'Rang de dates','rang_de_dates','date_range',0,1,NULL,5,77),(385,'Galeria','galeria','gallery',0,1,NULL,6,77),(388,'Rang de dates','rang_de_dates','date_range',0,1,NULL,5,69),(389,'Galeria','galeria','gallery',0,1,NULL,6,69),(390,'T├¡tol','titol','text',1,1,NULL,0,78),(391,'Subt├¡tol','subtitol','text',1,1,NULL,1,78),(392,'Descripci├│','descripcio','richtext',1,1,NULL,2,78),(393,'Data','data','date',0,1,NULL,3,78),(394,'Ubicaci├│','ubicacio','text',0,1,NULL,4,78),(395,'imatge','imatge','image',0,1,NULL,5,78),(396,'galeria','galeria','gallery',0,1,NULL,6,78),(397,'dates','dates','date_range',0,1,NULL,7,78),(398,'T├¡tol','titol','text',1,1,NULL,0,79),(399,'Subt├¡tol','subtitol','text',1,1,NULL,1,79),(400,'Descripci├│','descripcio','richtext',1,1,NULL,2,79),(401,'Data','data','date',0,1,NULL,3,79),(402,'Ubicaci├│','ubicacio','text',0,1,NULL,4,79),(403,'imatge','imatge','image',0,1,NULL,5,79),(404,'galeria','galeria','gallery',0,1,NULL,6,79),(405,'dates','dates','date_range',0,1,NULL,7,79),(411,'T├¡tol','titol','text',1,1,NULL,0,80),(412,'Subt├¡tol','subtitol','text',1,1,NULL,1,80),(413,'Descripci├│','descripcio','richtext',1,1,NULL,2,80),(414,'Data','data','date',0,1,NULL,3,80),(415,'Ubicaci├│','ubicacio','text',0,1,NULL,4,80),(416,'imatge','imatge','image',0,1,NULL,5,80),(417,'galeria','galeria','gallery',0,1,NULL,6,80),(418,'dates','dates','date_range',0,1,NULL,7,80),(421,'imatge','imatge','image',0,1,NULL,5,68),(422,'galeria','galeria','gallery',0,1,NULL,6,68),(423,'dates','dates','date_range',0,1,NULL,7,68),(424,'T├¡tol','titol','text',1,1,NULL,0,81),(425,'Subt├¡tol','subtitol','text',1,1,NULL,1,81),(426,'Descripci├│','descripcio','richtext',1,1,NULL,2,81),(427,'Data','data','date',0,1,NULL,3,81),(428,'Ubicaci├│','ubicacio','text',0,1,NULL,4,81),(429,'imatge','imatge','image',0,1,NULL,5,81),(430,'galeria','galeria','gallery',0,1,NULL,6,81),(431,'dates','dates','date_range',0,1,NULL,7,81),(432,'T├¡tol','titol','text',1,1,NULL,0,82),(433,'Descripci├│','descripcio','richtext',1,1,NULL,1,82),(434,'Data','data','date',0,1,NULL,2,82),(435,'Ubicaci├│','ubicacio','text',0,1,NULL,3,82),(436,'Imatge','imatge','image',0,1,NULL,4,82),(437,'Rang de dates','rang_de_dates','date_range',0,1,NULL,5,82),(438,'Galeria','galeria','gallery',0,1,NULL,6,82),(459,'Titol','titol','text',1,1,NULL,0,84),(460,'Subtitol','subtitol','text',1,1,NULL,1,84),(461,'Descripcio','descripcio','textarea',1,1,NULL,2,84),(462,'Imatge','imatge','image',1,1,NULL,3,84),(463,'Logros','logros','repeater',0,1,NULL,4,84),(464,'Galeria','galeria','gallery',0,1,NULL,5,84);
/*!40000 ALTER TABLE `field_definitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `field_values`
--

DROP TABLE IF EXISTS `field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `field_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext DEFAULT NULL,
  `entry_id` int(11) NOT NULL,
  `field_definition_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_10E3C0E4BA364942` (`entry_id`),
  KEY `IDX_10E3C0E44D0FDD48` (`field_definition_id`),
  CONSTRAINT `FK_10E3C0E44D0FDD48` FOREIGN KEY (`field_definition_id`) REFERENCES `field_definitions` (`id`),
  CONSTRAINT `FK_10E3C0E4BA364942` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=217 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `field_values`
--

LOCK TABLES `field_values` WRITE;
/*!40000 ALTER TABLE `field_values` DISABLE KEYS */;
INSERT INTO `field_values` VALUES (130,'Aurex Immobles',28,318),(131,'Aurex neixi amb la necessitat de construir una identitat s├▓lida i una pres├¿ncia digital capa├º de transmetre professionalitat, confian├ºa i diferenciaci├│ dins del seu sector. L\'objectiu principal era transformar una idea de negoci en una marca coherent, preparada per connectar amb el seu p├║blic i generar oportunitats de creixement. Prova de dilluns amb en Carles',28,319),(132,'31',28,320),(133,'www.aureximmobles.com',28,321),(134,'Briefing - Marketing - Consulting',28,322),(135,'Crear una marca forta en un sector molt competitiu on la confian├ºa i la proximitat s├│n clau.',28,323),(136,'Posicionar Aurex com la immobili├ária de proximitat que ent├®n les necessitats reals del client.',28,324),(137,'Una imatge renovada, moderna i propera que transmet professionalitat i confian├ºa des del primer moment.',28,325),(138,'32,34,33,35',28,326),(139,'aurex',28,345),(140,'Wiar',29,318),(141,'Analitzem la marca, els seus valors i el p├║blic objectiu per definir una estrat├¿gia de continguts alineada amb els objectius de negoci. Estudiem la compet├¿ncia, el posicionament i les oportunitats de comunicaci├│ per construir una identitat visual coherent i diferenciadora.',29,319),(142,'40',29,320),(143,'wiar.cat',29,321),(144,'marca - marqueting - disseny',29,322),(145,'Auditoria del perfil d\'Instagram.\r\nDefinici├│ de l\'estrat├¿gia de continguts.\r\nCreaci├│ del calendari editorial.\r\nDisseny de publicacions, carrusels i reels.\r\nRedacci├│ de copies i hashtags.\r\nProgramaci├│ i publicaci├│ del contingut.\r\nSeguiment de m├¿triques i optimitzaci├│ cont├¡nua.',29,323),(146,'S\'ha desenvolupat un perfil professional, coherent i alineat amb la identitat sostenible de la marca. Mitjan├ºant contingut de valor, una est├¿tica uniforme i una comunicaci├│ estrat├¿gica, s\'ha refor├ºat la imatge de marca, incrementat la visibilitat digital i millorat la connexi├│ amb la seva comunitat.',29,324),(147,'Un exit total',29,325),(148,'42,43,44',29,326),(149,'wiar',29,345),(150,'D\' Tast',30,318),(151,'Redisseny de la web i del packaging de DÔÇÖTast per refor├ºar la seva imatge com a marca de  gastronomia artesanal premium catalana, orientada tant a client final com a canal HORECA i gourmet.',30,319),(152,'45',30,320),(153,'www.dtast.cat',30,321),(154,'Disseny Web  - Disseny logo - Branding',30,322),(155,'Molt feina',30,323),(156,'SÔÇÖha treballat una web clara i visual, prioritzant lÔÇÖexperi├¿ncia dÔÇÖusuari i la presentaci├│ del producte de manera ordenada i apetible.\r\nEn packaging, sÔÇÖha mantingut una l├¡nia artesanal i elegant, amb coher├¿ncia visual amb la marca i enfocament en la qualitat del producte i lÔÇÖexperi├¿ncia dÔÇÖunboxing.',30,324),(157,'Una pres├¿ncia digital m├®s s├▓lida i professional i un packaging coherent amb el posicionament artesanal premium de DÔÇÖTast.\r\nMarca m├®s reconeixible, cuidada i preparada per cr├®ixer en canal HORECA i gourmet.',30,325),(158,'46,47,48',30,326),(159,'dtast',30,345),(160,'Cristina Montero',31,432),(161,'<p><br></p><p>Artista y arquitecta con una formaci├│n diversa. Realiz├│ el Bachillerato Tecnol├│gico en el Instituto</p><p>Montsacopa, en Olot, entre 2010 y 2012. Posteriormente, curs├│ el grado y m├íster en Arquitectura en</p><p>la Universidad de Girona, de 2012 a 2018.</p><p><br></p><p>En 2019 asisti├│ a clases de escultura en arcilla en el Morean Arts Center, en Florida, seguido por un</p><p>curso de escultura en vidrio. Entre 2020 y 2023 se form├│ en microfusi├│n y joyer├¡a art├¡stica en la Escola</p><p>d\'Expressi├│ d\'Olot.</p>',31,433),(162,'2026-07-23T11:53',31,434),(163,'Girona',31,435),(164,'49',31,436),(165,'{\"start\":\"2026-07-30T11:51\",\"end\":\"2026-08-26T13:51\"}',31,437),(166,'41,29,17,15',31,438),(167,'La Sara ',32,424),(168,'Sobre el artista',32,425),(169,'<p><br></p><p>Artista y arquitecta con una formaci├│n diversa. Realiz├│ el Bachillerato Tecnol├│gico en el Instituto</p><p>Montsacopa, en Olot, entre 2010 y 2012. Posteriormente, curs├│ el grado y m├íster en Arquitectura en</p><p>la Universidad de Girona, de 2012 a 2018.</p><p><br></p><p><br></p><p>En 2019 asisti├│ a clases de escultura en arcilla en el Morean Arts Center, en Florida, seguido por un</p><p>curso de escultura en vidrio. Entre 2020 y 2023 se form├│ en microfusi├│n y joyer├¡a art├¡stica en la Escola</p><p>d\'Expressi├│ d\'Olot.</p><p><br></p>',32,426),(170,'2026-07-10T10:54',32,427),(171,'St Feliu',32,428),(172,'26',32,429),(173,'14,15',32,430),(174,'{\"start\":\"2026-08-07T13:56\",\"end\":\"2026-09-15T10:54\"}',32,431),(175,'En Manel torna a Girona',33,424),(176,'maria de la o',33,425),(177,'<p><br></p><p>Desde los seis a├▒os ha asistido a clases particulares de dibujo y pintura con diversos artistas. En 2015</p><p>inici├│ su trayectoria en el mundo de la escultura, combin├índola con sus estudios de arquitectura. A</p><p>partir de 2016 se adentr├│ en el ├ímbito laboral de la arquitectura y en 2017 present├│ al p├║blico su</p><p>primera colecci├│n de esculturas titulada \"Reality, Sense, Emotions\".</p><p><br></p><p><br></p><p>En octubre de 2019 expuso la colecci├│n \"Unitat\" en su propia galer├¡a-estudio de escultura y</p><p>arquitectura, ubicada en Olot. En julio de 2023, continuando con la fusi├│n entre la figura humana y la</p><p>naturaleza, present├│ la colecci├│n \"Fotos├¡ntesi\". Actualmente, compagina sus dos grandes pasiones:</p><p>la arquitectura y la escultura.</p><p><br></p>',33,426),(178,'2026-07-19T11:11',33,427),(179,'Barcelona',33,428),(180,'53',33,429),(181,'50,51,52',33,430),(182,'{\"start\":\"\",\"end\":\"\"}',33,431),(183,'La Maria ',34,432),(184,'<p>Desde los seis a├▒os ha asistido a clases particulares de dibujo y pintura con diversos artistas. En 2015</p><p>inici├│ su trayectoria en el mundo de la escultura, combin├índola con sus estudios de arquitectura. A</p><p>partir de 2016 se adentr├│ en el ├ímbito laboral de la arquitectura y en 2017 present├│ al p├║blico su</p><p>primera colecci├│n de esculturas titulada \"Reality, Sense, Emotions\".</p><p><br></p><p><br></p><p>En octubre de 2019 expuso la colecci├│n \"Unitat\" en su propia galer├¡a-estudio de escultura y</p><p>arquitectura, ubicada en Olot. En julio de 2023, continuando con la fusi├│n entre la figura humana y la</p><p>naturaleza, present├│ la colecci├│n \"Fotos├¡ntesi\". Actualmente, compagina sus dos grandes pasiones:</p><p>la arquitectura y la escultura.</p><p><br></p>',34,433),(185,'2026-07-14T11:18',34,434),(186,'Tarragona',34,435),(187,'27',34,436),(188,'{\"start\":\"2026-07-18T11:19\",\"end\":\"2026-07-15T11:19\"}',34,437),(189,'23,27,30,50',34,438),(190,'La casa dels somnis',35,379),(191,'<p>Desde los seis a├▒os ha asistido a clases particulares de dibujo y pintura con diversos artistas. En 2015</p><p>inici├│ su trayectoria en el mundo de la escultura, combin├índola con sus estudios de arquitectura. A</p><p>partir de 2016 se adentr├│ en el ├ímbito laboral de la arquitectura y en 2017 present├│ al p├║blico su</p><p>primera colecci├│n de esculturas titulada \"Reality, Sense, Emotions\".</p><p><br></p><p>En octubre de 2019 expuso la colecci├│n \"Unitat\" en su propia galer├¡a-estudio de escultura y</p><p>arquitectura, ubicada en Olot. En julio de 2023, continuando con la fusi├│n entre la figura humana y la</p><p>naturaleza, present├│ la colecci├│n \"Fotos├¡ntesi\". Actualmente, compagina sus dos grandes pasiones:</p><p>la arquitectura y la escultura.</p><p><br></p>',35,380),(192,'2026-07-10T13:24',35,381),(193,'Barcelona',35,382),(194,'54',35,383),(195,'{\"start\":\"2026-07-17T11:20\",\"end\":\"2026-07-19T11:20\"}',35,384),(196,'58,59,60',35,385),(197,'La casa delm estiu',36,411),(198,'asasasas',36,412),(199,'<p><br></p><p>Desde los seis a├▒os ha asistido a clases particulares de dibujo y pintura con diversos artistas. En 2015</p><p>inici├│ su trayectoria en el mundo de la escultura, combin├índola con sus estudios de arquitectura. A</p><p>partir de 2016 se adentr├│ en el ├ímbito laboral de la arquitectura y en 2017 present├│ al p├║blico su</p><p>primera colecci├│n de esculturas titulada \"Reality, Sense, Emotions\".</p><p><br></p><p>En octubre de 2019 expuso la colecci├│n \"Unitat\" en su propia galer├¡a-estudio de escultura y</p><p>arquitectura, ubicada en Olot. En julio de 2023, continuando con la fusi├│n entre la figura humana y la</p><p>naturaleza, present├│ la colecci├│n \"Fotos├¡ntesi\". Actualmente, compagina sus dos grandes pasiones:</p><p>la arquitectura y la escultura.</p><p><br></p>',36,413),(200,'2026-07-16T14:25',36,414),(201,'Angles',36,415),(202,'58',36,416),(203,'59,60,56,55',36,417),(204,'{\"start\":\"2026-07-09T11:26\",\"end\":\"2026-07-28T11:26\"}',36,418),(205,'La Maria de la O',37,459),(206,'Artista buenisiima de Girona',37,460),(207,'David Thorne ├®s el pseud├▓nim d\'un artista pl├ástic contemporani la identitat real del qual roman en\r\nl\'anonimat. Compagina la seva activitat professional amb una intensa i selecta producci├│ art├¡stica.\r\n\r\nDivideix la seva vida entre Londres i la costa catalana, prop de Barcelona, cosa que li permet nodrir-se de\r\nl\'energia creativa del mar Mediterrani i del cosmopolitisme de la capital anglesa, aix├¡ com del contrast\r\ncultural que les caracteritza.\r\n\r\nLa seva obra gira al voltant d\'un ├║nic icona: la pajarita de paper, reinterpretada en escultures de diverses\r\nmides, materials i t├¿cniques. Per a Thorne, la pajarita ├®s molt m├®s que una figura; ├®s un llenguatge visual\r\npropi, una signatura tridimensional reconeixible a l\'instant.',37,461),(208,'29',37,462),(209,'[{\"a├▒o\":\"2024\",\"texto\":\"Membre del jurat al concurs \\\"LA BATALLA DE LES FLORS\\\" ÔÇö Centre d\'Iniciatives Tur├¡stiques d\'Olot\\n\\nExposici├│ de l\'escultura \\\"FLORACI├ô\\\" ÔÇö Festival VALLVIVA\"},{\"a├▒o\":\"2023\",\"texto\":\"Exposici├│ de l\'escultura \\\"FORTUNA I DEST├ì\\\" ÔÇö Festival VALLVIVA\\n\\nPresentaci├│ de la col┬Àlecci├│ \\\"Fotos├¡ntesi\\\"\"},{\"a├▒o\":\"2022\",\"texto\":\"Exposici├│ de l\'escultura \\\"IL┬ÀLUSI├ô\\\" ÔÇö Festival VALLVIVA\"},{\"a├▒o\":\"2020\",\"texto\":\"Espai HVNGARI ÔÇö Andorra\\n\\nM├│n d\'Harmonia ÔÇö Barcelona\\n\\n├Çmbit Sant Lluc ÔÇö Olot\\n\\nClaustre de la Merc├¿ ÔÇö Girona\\n\\nJardins de la Riba ÔÇö Vall de Bianya\"},{\"a├▒o\":\"2019\",\"texto\":\"Exposici├│ de la col┬Àlecci├│ \\\"Unitat\\\" ÔÇö Galeria-estudi, Olot\\n\\n├Çmbit Sant Lluc ÔÇö Olot\"}]',37,463),(210,'51,50,52,53,27',37,464),(211,'Carla Garcia',38,459),(212,'la maquetadora incipient',38,460),(213,'Su obra gira en torno a un ├║nico icono: la pajarita de papel, reinterpretada en esculturas de diversos\r\ntama├▒os, materiales y t├®cnicas. Para Thorne, la pajarita es mucho m├ís que una figura; es un lenguaje\r\nvisual propio, una firma tridimensional reconocible al instante.\r\n\r\nCada pieza combina un equilibrio entre la pureza formal y la experimentaci├│n crom├ítica, transmitiendo\r\ncar├ícter, emoci├│n y un toque l├║dico que contrasta con la aparente sencillez de la silueta.\r\n\r\nSu decisi├│n de mantener el anonimato responde tanto a un deseo de preservar su vida privada como a la\r\nintenci├│n de a├▒adir un halo de misterio que potencie la interpretaci├│n libre de su obra, capaz de\r\ntransformar un gesto cotidiano ÔÇöel plegado de una pajarita de papelÔÇö en un s├¡mbolo ic├│nico y atemporal.',38,461),(214,'28',38,462),(215,'[]',38,463),(216,'53,52,50,49',38,464);
/*!40000 ALTER TABLE `field_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `mime_type` varchar(50) NOT NULL,
  `path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `file_size` int(11) NOT NULL,
  `alt_text` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `uploaded_by_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6A2CA10CA2B28FE8` (`uploaded_by_id`),
  KEY `IDX_6A2CA10CA76ED395` (`user_id`),
  KEY `IDX_6A2CA10C166D1F9C` (`project_id`),
  CONSTRAINT `FK_6A2CA10C166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6A2CA10CA2B28FE8` FOREIGN KEY (`uploaded_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6A2CA10CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
INSERT INTO `media` VALUES (14,'6a44b787ea00b_1782888327.avif','rosa1.avif','avif','image/avif','/uploads/16/6a44b787ea00b_1782888327.avif',NULL,712892,NULL,'2026-07-01 08:45:28',16,16,NULL),(15,'6a44b79dbcc0e_1782888349.avif','juda-6.avif','avif','image/avif','/uploads/16/6a44b79dbcc0e_1782888349.avif',NULL,585882,NULL,'2026-07-01 08:45:49',16,16,NULL),(16,'6a44baa114306_1782889121.jpg','QUIM.jpg','jpg','image/jpeg','/uploads/16/6a44baa114306_1782889121.jpg',NULL,129831,NULL,'2026-07-01 08:58:41',16,16,22),(17,'6a44bae414b8a_1782889188.avif','cristinamontero2.avif','avif','image/avif','/uploads/16/6a44bae414b8a_1782889188.avif',NULL,57361,NULL,'2026-07-01 08:59:48',16,16,NULL),(19,'6a44bb16ca435_1782889238.avif','hab-1-2.avif','avif','image/avif','/uploads/16/6a44bb16ca435_1782889238.avif',NULL,70523,NULL,'2026-07-01 09:00:38',16,16,34),(21,'6a44bba61370a_1782889382.avif','95db20cf-2a26-479f-b286-5bc36968072f.avif','avif','image/avif','/uploads/16/6a44bba61370a_1782889382.avif',NULL,41019,NULL,'2026-07-01 09:03:02',16,16,NULL),(22,'6a44c7875f6aa_1782892423.avif','5a5e62f1-13c3-4be4-b29b-d7bad09a240d.avif','avif','image/avif','/uploads/16/6a44c7875f6aa_1782892423.avif',NULL,81688,NULL,'2026-07-01 09:53:43',16,16,34),(23,'6a44c7a15661b_1782892449.avif','2cc9cc6b-f0ce-4178-bf32-4ef16f3b5d14.avif','avif','image/avif','/uploads/17/6a44c7a15661b_1782892449.avif',NULL,52609,NULL,'2026-07-01 09:54:09',17,17,NULL),(24,'6a44c84a16b06_1782892618.avif','0e4430ab-cff0-4787-a6a1-1f5e384df69f.avif','avif','image/avif','/uploads/16/6a44c84a16b06_1782892618.avif',NULL,129344,NULL,'2026-07-01 09:56:58',16,16,34),(26,'6a44c947ac48f_1782892871.webp','slide1.webp','webp','image/webp','/uploads/16/6a44c947ac48f_1782892871.webp',NULL,193848,NULL,'2026-07-01 10:01:11',16,16,NULL),(27,'6a44cc9b9aeb5_1782893723.webp','slide3.webp','webp','image/webp','/uploads/17/6a44cc9b9aeb5_1782893723.webp',NULL,187022,NULL,'2026-07-01 10:15:23',17,17,36),(28,'6a44d21c6f75b_1782895132.webp','slide5.webp','webp','image/webp','/uploads/16/6a44d21c6f75b_1782895132.webp',NULL,372614,NULL,'2026-07-01 10:38:52',16,16,NULL),(29,'6a44d28df1bbc_1782895245.avif','juda-1.avif','avif','image/avif','/uploads/17/6a44d28df1bbc_1782895245.avif',NULL,310250,NULL,'2026-07-01 10:40:45',17,17,NULL),(30,'6a44d3cd78f08_1782895565.webp','slide1.webp','webp','image/webp','/uploads/17/6a44d3cd78f08_1782895565.webp',NULL,193848,NULL,'2026-07-01 10:46:05',17,17,NULL),(31,'6a44d8ed75b5f_1782896877.png','Aurex 1x.png','png','image/png','/uploads/16/6a44d8ed75b5f_1782896877.png',NULL,11981,NULL,'2026-07-01 11:07:57',16,16,20),(32,'6a44d8ed7be13_1782896877.webp','aurexFinestra.webp','webp','image/webp','/uploads/16/6a44d8ed7be13_1782896877.webp',NULL,67734,NULL,'2026-07-01 11:07:57',16,16,20),(33,'6a44d8ed7f06d_1782896877.webp','Aurex-scaled.webp','webp','image/webp','/uploads/16/6a44d8ed7f06d_1782896877.webp',NULL,178004,NULL,'2026-07-01 11:07:57',16,16,20),(34,'6a44d8ed80fef_1782896877.webp','aurex-web2.webp','webp','image/webp','/uploads/16/6a44d8ed80fef_1782896877.webp',NULL,43808,NULL,'2026-07-01 11:07:57',16,16,20),(35,'6a44d8ed83225_1782896877.webp','Targeta_.webp','webp','image/webp','/uploads/16/6a44d8ed83225_1782896877.webp',NULL,20648,NULL,'2026-07-01 11:07:57',16,16,20),(36,'6a44d98cd1a45_1782897036.png','cros_web.png','png','image/png','/uploads/16/6a44d98cd1a45_1782897036.png',NULL,1954511,NULL,'2026-07-01 11:10:36',16,16,20),(37,'6a44d999e621c_1782897049.webp','para3.webp','webp','image/webp','/uploads/16/6a44d999e621c_1782897049.webp',NULL,180670,NULL,'2026-07-01 11:10:49',16,16,20),(38,'6a44d99d8e071_1782897053.png','Comercial-ros_negre.png','png','image/png','/uploads/16/6a44d99d8e071_1782897053.png',NULL,9838,NULL,'2026-07-01 11:10:53',16,16,20),(39,'6a44df8cd957e_1782898572.avif','img-5824.avif','avif','image/avif','/uploads/16/6a44df8cd957e_1782898572.avif',NULL,38515,NULL,'2026-07-01 11:36:12',16,16,NULL),(40,'6a44fe3346bea_1782906419.png','Wiar.png','png','image/png','/uploads/16/6a44fe3346bea_1782906419.png',NULL,11595,NULL,'2026-07-01 13:46:59',16,16,20),(41,'6a4501d00cec1_1782907344.webp','gironaFoto.webp','webp','image/webp','/uploads/16/6a4501d00cec1_1782907344.webp',NULL,319430,NULL,'2026-07-01 14:02:24',16,16,20),(42,'6a450320c4f9e_1782907680.webp','wiar.webp','webp','image/webp','/uploads/16/6a450320c4f9e_1782907680.webp',NULL,67508,NULL,'2026-07-01 14:08:00',16,16,20),(43,'6a450328a5b79_1782907688.webp','wiar_pic.webp','webp','image/webp','/uploads/16/6a450328a5b79_1782907688.webp',NULL,40272,NULL,'2026-07-01 14:08:08',16,16,20),(44,'6a45032fd614e_1782907695.webp','wiat-2.webp','webp','image/webp','/uploads/16/6a45032fd614e_1782907695.webp',NULL,37574,NULL,'2026-07-01 14:08:15',16,16,20),(45,'6a4792a41cdaa_1783075492.png','logo_certical_marror.png','png','image/png','/uploads/16/6a4792a41cdaa_1783075492.png',NULL,10881,NULL,'2026-07-03 12:44:52',16,16,20),(46,'6a47937270546_1783075698.jpeg','dtas2.jpeg','jpeg','image/jpeg','/uploads/16/6a47937270546_1783075698.jpeg',NULL,130658,NULL,'2026-07-03 12:48:18',16,16,20),(47,'6a47937811965_1783075704.jpeg','dtas3.jpeg','jpeg','image/jpeg','/uploads/16/6a47937811965_1783075704.jpeg',NULL,175303,NULL,'2026-07-03 12:48:24',16,16,20),(48,'6a47937d17068_1783075709.jpeg','WhatsApp Image 2026-07-03 at 12.44.49.jpeg','jpeg','image/jpeg','/uploads/16/6a47937d17068_1783075709.jpeg',NULL,212967,NULL,'2026-07-03 12:48:29',16,16,20),(49,'6a4b63689ddbc_1783325544.avif','muha4.avif','avif','image/avif','/uploads/16/6a4b63689ddbc_1783325544.avif',NULL,441354,NULL,'2026-07-06 10:12:24',16,16,37),(50,'6a4b7153844b8_1783329107.avif','juda-3.avif','avif','image/avif','/uploads/17/6a4b7153844b8_1783329107.avif',NULL,122126,NULL,'2026-07-06 11:11:47',17,17,37),(51,'6a4b71bc3172b_1783329212.avif','juda-2.avif','avif','image/avif','/uploads/17/6a4b71bc3172b_1783329212.avif',NULL,100837,NULL,'2026-07-06 11:13:32',17,17,37),(52,'6a4b71ce69af7_1783329230.avif','juda-4.avif','avif','image/avif','/uploads/17/6a4b71ce69af7_1783329230.avif',NULL,83789,NULL,'2026-07-06 11:13:50',17,17,37),(53,'6a4b71d8cac38_1783329240.avif','2024-vallviva.avif','avif','image/avif','/uploads/17/6a4b71d8cac38_1783329240.avif',NULL,153196,NULL,'2026-07-06 11:14:00',17,17,37),(54,'6a4b7361085c9_1783329633.avif','d5771a1d-a121-4693-b840-a0bff53eec75.avif','avif','image/avif','/uploads/17/6a4b7361085c9_1783329633.avif',NULL,133913,NULL,'2026-07-06 11:20:33',17,17,34),(55,'6a4b737e24613_1783329662.avif','0ba5eaf6-94c3-42c8-b4be-4e5a5ee1430e.avif','avif','image/avif','/uploads/17/6a4b737e24613_1783329662.avif',NULL,177787,NULL,'2026-07-06 11:21:02',17,17,34),(56,'6a4b737e28873_1783329662.avif','0e4430ab-cff0-4787-a6a1-1f5e384df69f.avif','avif','image/avif','/uploads/17/6a4b737e28873_1783329662.avif',NULL,129344,NULL,'2026-07-06 11:21:02',17,17,34),(57,'6a4b737e2a4eb_1783329662.avif','2add523d-50a5-422a-ae3e-dbf6f1f97f13.avif','avif','image/avif','/uploads/17/6a4b737e2a4eb_1783329662.avif',NULL,55781,NULL,'2026-07-06 11:21:02',17,17,34),(58,'6a4b738b7fb88_1783329675.avif','0ba5eaf6-94c3-42c8-b4be-4e5a5ee1430e.avif','avif','image/avif','/uploads/17/6a4b738b7fb88_1783329675.avif',NULL,177787,NULL,'2026-07-06 11:21:15',17,17,34),(59,'6a4b738b849e3_1783329675.avif','0e4430ab-cff0-4787-a6a1-1f5e384df69f.avif','avif','image/avif','/uploads/17/6a4b738b849e3_1783329675.avif',NULL,129344,NULL,'2026-07-06 11:21:15',17,17,34),(60,'6a4b738b878b0_1783329675.avif','2add523d-50a5-422a-ae3e-dbf6f1f97f13.avif','avif','image/avif','/uploads/17/6a4b738b878b0_1783329675.avif',NULL,55781,NULL,'2026-07-06 11:21:15',17,17,34);
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `color` varchar(7) DEFAULT '#4945FF',
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_slug_user` (`slug`,`user_id`),
  KEY `IDX_5C93B3A4A76ED395` (`user_id`),
  CONSTRAINT `FK_5C93B3A4A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (20,'Web','web','Projecte base de la web','#ffdd00',1,'2026-06-30 10:12:07',16),(22,'Web Principal','web-principal','Projecte per defecte','#2198f2',1,'2026-06-30 10:16:19',18),(34,'Palmito House','palmito-house','awwwe','#7aea1f',1,'2026-06-30 12:01:20',17),(36,'Wiar','wiar','web Wiar','#047139',1,'2026-06-30 14:03:32',17),(37,'Victoria Taylor','victoria-taylor','web Victoria Taylor','#6e03a0',1,'2026-07-06 09:47:50',17);
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(180) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `locale` varchar(255) NOT NULL DEFAULT 'ca',
  `company` varchar(255) DEFAULT NULL,
  `api_token` varchar(32) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9989D9B62` (`slug`),
  UNIQUE KEY `UNIQ_1483A5E97BA2F5EB` (`api_token`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (16,'admin@vora.es','Vora Studio','vora-studio','[\"ROLE_ADMIN\"]','$2y$13$f/CU4dEX8WAQPdNcNzFd2.oN5ex5cQlAbsMa7PM/ANQwU5n.EKvm6',1,'ca','Vora Studio','UJIv45gTpMGckBdJjDg3UmkuqZzOWqHV','2026-06-30 10:12:06'),(17,'xavi@vora.es','Xavi','gloabl-brands','[\"ROLE_USUARIO\"]','$2y$13$qFSJssYjqPAYTNHV.EQaS.V/GArngxfgx8I.GFSRa3yDNpOiivs2C',1,'ca','Global Brands','7Y0zI9cG1wRiy9Dw1kwSOgFd5YB12rUL','2026-06-30 10:12:06'),(18,'aula@vora.es','Aula Gastronomica','aula-gastronomica','[\"ROLE_USUARIO\"]','$2y$13$6x0pxpqc8qmj4jZ990uWKe6K/TAG3GSz6hBbJJY5wrvkW9Gm.jI4e',1,'ca','Aula Gastronomica','JIfJnaAQXCCtPehFFYBiM9UDagZEjZ3H','2026-06-30 10:16:18');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visit`
--

DROP TABLE IF EXISTS `visit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `visited_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `entry_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_437EE939A76ED395` (`user_id`),
  KEY `IDX_437EE939BA364942` (`entry_id`),
  KEY `IDX_437EE939A76ED395EDA764E3` (`user_id`,`visited_at`),
  CONSTRAINT `FK_437EE939A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_437EE939BA364942` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visit`
--

LOCK TABLES `visit` WRITE;
/*!40000 ALTER TABLE `visit` DISABLE KEYS */;
/*!40000 ALTER TABLE `visit` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-07  8:46:59
