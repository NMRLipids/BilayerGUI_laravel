-- MySQL dump 10.13  Distrib 8.0.38, for Linux (x86_64)
--
-- Host: localhost    Database: nmrlipid_trajectdb
-- ------------------------------------------------------
-- Server version	8.0.38

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
START TRANSACTION;
--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


-- 
-- Table structure for table `experiments`
--
DROP TABLE IF EXISTS `experiments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experiments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `article_doi` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `data_doi` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `section` bigint DEFAULT 1 NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `type` enum('FF','OP') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'FF',
  `data` JSON DEFAULT NULL, 
  -- JSON data with experiment details, e.g. FF parameters or OP details
  PRIMARY KEY (`id`),
  UNIQUE KEY `experiments_path_unique` (`path`, `type`),
  UNIQUE KEY `experiments_doi_section_type_path_unique` (`article_doi`,`section`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;




--
-- Table structure for table `experiments_properties`
-- 
DROP TABLE IF EXISTS `experiments_properties_linker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experiments_properties_linker` (
  `experiment_id` bigint unsigned NOT NULL,
  `property_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`experiment_id`,`property_id`),
  CONSTRAINT `experiments_properties_ibfk_1` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`),
  CONSTRAINT `experiments_properties_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `experiment_property` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `experiment_properties`
--
DROP TABLE IF EXISTS `experiment_property`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experiment_property` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` ENUM('string', 'integer', 'numeric', 'float', 'dict', 'array') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;



-- 
-- Table structure for table `experiments_membrane_composition`
--

DROP TABLE IF EXISTS `experiments_membrane_composition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experiments_membrane_composition` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `experiment_id` bigint unsigned NOT NULL,
  `lipid_id` bigint unsigned NOT NULL,
  `mol_fraction` float NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- JSON data with OP details
  PRIMARY KEY (`id`),
  UNIQUE KEY (`experiment_id`,`lipid_id`),
  CONSTRAINT `experiments_membrane_composition_ibfk_1` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`),
  CONSTRAINT `experiments_membrane_composition_ibfk_2` FOREIGN KEY (`lipid_id`) REFERENCES `lipids` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `solution_composition`
--
DROP TABLE IF EXISTS `experiments_solution_composition`; 
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experiments_solution_composition` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `experiment_id` bigint unsigned NOT NULL,
  `compound` varchar(255) NOT NULL,
  `concentration` float NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- JSON data with additional solution composition details
  PRIMARY KEY (`id`),
  UNIQUE KEY (`experiment_id`,`compound`),
  CONSTRAINT `solution_composition_ibfk_1` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





--
-- Table structure for table `forcefields`
--

DROP TABLE IF EXISTS `forcefields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forcefields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `date` varchar(255) NOT NULL DEFAULT '',
  `source` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forcefields_name_unique` (`name`,`date`,`source`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ions`
--

DROP TABLE IF EXISTS `ions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `molecule` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `forcefield_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mapping` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `forcefield_id` (`forcefield_id`),
  CONSTRAINT `ions_ibfk_1` FOREIGN KEY (`forcefield_id`) REFERENCES `forcefields` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lipids`
--

DROP TABLE IF EXISTS `lipids`;
CREATE TABLE `lipids` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `molecule` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mapping` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lipids_name_key` (`name`),
  UNIQUE KEY `lipids_molecule_key` (`molecule`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `lipids_synonyms`
--
DROP TABLE IF EXISTS `lipids_synonyms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lipids_synonyms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lipid_id` bigint unsigned NOT NULL,
  `synonym` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lipid_id` (`lipid_id`),
  KEY `synonym` (`synonym`),
  CONSTRAINT `lipids_synonyms_ibfk_1` FOREIGN KEY (`lipid_id`) REFERENCES `lipids` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `lipids_forcefields`
-- This table is used to store the relationship between 
-- lipids and forcefields. 
-- Each lipid can be associated with multiple forcefields.
--

DROP TABLE IF EXISTS `lipids_forcefields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lipids_forcefields` (
  `lipid_id` bigint unsigned NOT NULL,
  `forcefield_id` bigint unsigned NOT NULL,
  `mapping` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  -- PRIMARY KEY (`lipid_id`,`forcefield_id`, `mapping`),
  KEY (`lipid_id`,`forcefield_id`),
  CONSTRAINT `lipids_forcefields_ibfk_1` FOREIGN KEY (`lipid_id`) REFERENCES `lipids` (`id`),
  CONSTRAINT `lipids_forcefields_ibfk_2` FOREIGN KEY (`forcefield_id`) REFERENCES `forcefields` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lipid_properties`
-- Mapping table for lipids and property entries.
-- Many-to-many relationship.
-- Each lipid can have multiple properties, and each 
-- property can be associated with multiple lipids.
--

DROP TABLE IF EXISTS `lipid_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lipid_properties` (
  `lipid_id` bigint unsigned NOT NULL,
  `property_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`lipid_id`,`property_id`),
  CONSTRAINT `lipid_id_fk_constraint` FOREIGN KEY (`lipid_id`) REFERENCES `lipids` (`id`),
  CONSTRAINT `property_id_fk_constraint` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `properties`
-- This table stores various properties associated with lipids.
-- Each property has a name, description, value, unit, and type.
-- The type can be used to categorize the property (e.g., "integer", "string", etc.).
-- The `created_at` and `updated_at` timestamps track when the 
-- property was created and last updated.
-- 

DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `properties` ( 
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` ENUM('string', 'integer', 'numeric', 'float') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Table structure for table `db` 
-- This table is used to store external databases to cross-reference to.
-- The `url` field is used to link to the database, and the `version` field
-- indicates the version of the database.
-- URL schema is used to define the URL structure for accessing the database.
-- 

DROP TABLE IF EXISTS `db`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `db` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_schema` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  UNIQUE KEY `name_version_constraint` (`name`, `version`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cross_references`
-- This table is used to store cross-references between lipids and external databases.
-- Each cross-reference links a lipid to an external database entry.
-- The `db_id` field references the `db` table, and the `lipid_id` field references the `lipids` table.
-- 
DROP TABLE IF EXISTS `cross_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cross_references` (
  `cross_ref_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `db_id` bigint unsigned NOT NULL,
  `lipid_id` bigint unsigned NOT NULL,
  `external_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`cross_ref_id`),
  CONSTRAINT `db_id_fk_constraint` FOREIGN KEY (`db_id`) REFERENCES `db` (`id`),
  UNIQUE KEY `db_lipid_external_id_constraint` (`db_id`, `lipid_id`, `external_id`), 
  KEY `db_id` (`db_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;    



--
-- Table structure for table `membranes`
-- This table is mainly used to count the number of unique membranes in the database
-- 

DROP TABLE IF EXISTS `membranes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membranes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `forcefield_id` bigint unsigned NOT NULL,
  `lipid_names_l1` varchar(255) DEFAULT NULL,
  `lipid_names_l2` varchar(255) DEFAULT NULL,
  `lipid_number_l1` varchar(255) DEFAULT NULL,
  `lipid_number_l2` varchar(255) DEFAULT NULL,
  `geometry` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `membranes_unique` (`forcefield_id`,`lipid_names_l1`, `lipid_number_l1`,`lipid_names_l2`,`lipid_number_l2`,`geometry`), 
  KEY `forcefield_id` (`forcefield_id`),
  CONSTRAINT `membranes_ibfk_1` FOREIGN KEY (`forcefield_id`) REFERENCES `forcefields` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ranking_global`
--

-- Ranking of trajectories based on global properties has been removed.


--
-- Table structure for table `ranking_lipids`
--

-- Ranking of trajectories based on lipid-specific properties has been removed.

--
-- Table structure for table `trajectories`
--

DROP TABLE IF EXISTS `trajectories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trajectories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `forcefield_id` bigint unsigned NOT NULL,
  `membrane_id` bigint unsigned NOT NULL,
  `git_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `system` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  -- `dir_wrk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `doi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_of_atoms` int NOT NULL,
  `preeq_time` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `publication` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `temperature` double NOT NULL,
  `software` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `trj_size` double NOT NULL,
  `trj_length` double NOT NULL,
  `timeleftout` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `water_resname` varchar(8) NOT NULL DEFAULT 'IMPLICIT',
  PRIMARY KEY (`id`),
  UNIQUE KEY `trajectories_unique` (`forcefield_id`,`membrane_id`,`system`, `git_path`),
  KEY `forcefield_id` (`forcefield_id`),
  KEY `membrane_id` (`membrane_id`),
  CONSTRAINT `trajectories_ibfk_1` FOREIGN KEY (`forcefield_id`) REFERENCES `forcefields` (`id`),
  CONSTRAINT `trajectories_ibfk_2` FOREIGN KEY (`membrane_id`) REFERENCES `membranes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `trajectories_analysis`
--

DROP TABLE IF EXISTS `trajectories_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trajectories_analysis` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `trajectory_id` bigint unsigned NOT NULL,
  `bilayer_thickness` double DEFAULT NULL,
  `area_per_lipid` double DEFAULT NULL,
  `area_per_lipid_file` varchar(255) NOT NULL,
  `area_per_lipid_data` JSON DEFAULT NULL,
  `form_factor_file` varchar(255) NOT NULL,
  `form_factor_data` JSON DEFAULT NULL,
  `op_quality_total` double DEFAULT NULL,
  `op_quality_headgroups` double DEFAULT NULL,
  `op_quality_tails` double DEFAULT NULL,
  `ff_quality` double DEFAULT NULL,
  `ff_scaling` double DEFAULT NULL,
   -- REMOVE `form_factor_experiment` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  
  KEY `trajectory_id` (`trajectory_id`),
  CONSTRAINT `trajectories_analysis_ibfk_1` FOREIGN KEY (`trajectory_id`) REFERENCES `trajectories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `trajectories_analysis_ions`
--

DROP TABLE IF EXISTS `trajectories_analysis_ions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trajectories_analysis_ions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `trajectory_id` bigint NOT NULL,
  `ion_id` bigint NOT NULL,
  `density_file` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trajectory_id` (`trajectory_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `trajectories_analysis_lipids`
--

DROP TABLE IF EXISTS `trajectories_analysis_lipids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trajectories_analysis_lipids` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `trajectory_id` bigint unsigned NOT NULL,
  `lipid_id` bigint unsigned NOT NULL,
  `op_quality_total` double DEFAULT NULL,
  `op_quality_headgroups` double DEFAULT NULL,
  `op_quality_tails` double DEFAULT NULL,
  `order_parameters_file` varchar(255) DEFAULT NULL,
  `order_parameters_quality` varchar(255) DEFAULT NULL,
  `order_parameters_experiment` varchar(255) DEFAULT NULL,
  `op_plot_data` JSON DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trajectory_lipid_unique` (`trajectory_id`,`lipid_id`),
  KEY `trajectory_id` (`trajectory_id`),
  KEY `lipid_id` (`lipid_id`),
  CONSTRAINT `trajectories_analysis_lipids_ibfk_1` FOREIGN KEY (`trajectory_id`) REFERENCES `trajectories` (`id`),
  CONSTRAINT `trajectories_analysis_lipids_ibfk_2` FOREIGN KEY (`lipid_id`) REFERENCES `lipids` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `trajectories_experiments_FF`
--

DROP TABLE IF EXISTS `trajectories_experiments_FF`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trajectories_experiments_FF` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trajectory_id` bigint unsigned NOT NULL,
  `experiment_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trajectory_id` (`trajectory_id`),
  KEY `experiment_id` (`experiment_id`),
  CONSTRAINT `experiment_id` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`),
  CONSTRAINT `trajectory_id` FOREIGN KEY (`trajectory_id`) REFERENCES `trajectories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `trajectories_experiments_OP`
--

DROP TABLE IF EXISTS `trajectories_experiments_OP`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trajectories_experiments_OP` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trajectory_id` bigint unsigned NOT NULL,
  `lipid_id` bigint unsigned NOT NULL,
  `experiment_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trajectory_id` (`trajectory_id`),
  KEY `lipid_id` (`lipid_id`),
  KEY `experiment_id` (`experiment_id`),
  CONSTRAINT `trajectories_experiments_OP_ibfk_1` FOREIGN KEY (`trajectory_id`) REFERENCES `trajectories` (`id`),
  CONSTRAINT `trajectories_experiments_OP_ibfk_2` FOREIGN KEY (`lipid_id`) REFERENCES `lipids` (`id`),
  CONSTRAINT `trajectories_experiments_OP_ibfk_3` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `trajectories_ions`
--

DROP TABLE IF EXISTS `trajectories_ions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trajectories_ions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trajectory_id` bigint unsigned NOT NULL,
  `ion_id` bigint unsigned NOT NULL,
  `ion_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'from ions select name',
  `number` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analysis_trajectory_id_foreign` (`trajectory_id`),
  KEY `Ion_ID` (`ion_id`) USING BTREE,
  CONSTRAINT `trajectories_ions_ibfk_1` FOREIGN KEY (`trajectory_id`) REFERENCES `trajectories` (`id`),
  CONSTRAINT `trajectories_ions_ibfk_2` FOREIGN KEY (`ion_id`) REFERENCES `ions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `trajectories_lipids`
--

DROP TABLE IF EXISTS `trajectories_lipids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trajectories_lipids` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trajectory_id` bigint unsigned NOT NULL,
  `lipid_id` bigint unsigned NOT NULL,
  `leaflet_1` int NOT NULL,
  `leaflet_2` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `analysis_trajectory_id_foreign` (`trajectory_id`),
  KEY `Lipid_ID` (`lipid_id`),
  CONSTRAINT `trajectories_lipids_ibfk_1` FOREIGN KEY (`trajectory_id`) REFERENCES `trajectories` (`id`),
  CONSTRAINT `trajectories_lipids_ibfk_2` FOREIGN KEY (`lipid_id`) REFERENCES `lipids` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `trajectories_membranes`
--

DROP TABLE IF EXISTS `trajectories_membranes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trajectories_membranes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trajectory_id` bigint unsigned NOT NULL,
  `membrane_id` bigint unsigned NOT NULL,
  `bulk` int DEFAULT NULL,
  `name` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trajectory_id` (`trajectory_id`,`membrane_id`),
  KEY `membrane_id` (`membrane_id`),
  CONSTRAINT `trajectories_membranes_ibfk_1` FOREIGN KEY (`trajectory_id`) REFERENCES `trajectories` (`id`),
  CONSTRAINT `trajectories_membranes_ibfk_2` FOREIGN KEY (`membrane_id`) REFERENCES `membranes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuarios_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;




---- Supplementary Views for Experiments ----


--
-- View structure for table `experiments_FF`
--

DROP VIEW IF EXISTS `experiments_FF`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;  
/*!50503 SET character_set_client = utf8mb4 */;
CREATE VIEW `experiments_FF` AS
 SELECT `experiments`.`id` AS `id`,
    `experiments`.`article_doi` AS `article_doi`,
    `experiments`.`article_doi` AS `doi`,
    `experiments`.`data_doi` AS `data_doi`,
    `experiments`.`path` AS `path`,
    `experiments`.`section` AS `section`,
    `experiments`.`data` AS `data`
   FROM `experiments`
  WHERE (`experiments`.`type` = 'FF');

--
-- View structure for table `experiments_OP`
--

DROP VIEW IF EXISTS `experiments_OP`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;  
/*!50503 SET character_set_client = utf8mb4 */;
CREATE VIEW `experiments_OP` AS
 SELECT `experiments`.`id` AS `id`,
`experiments`.`article_doi` AS `article_doi`,
  `experiments`.`article_doi` AS `doi`,
    `experiments`.`path` AS `path`,
    `experiments`.`section` AS `section`
   FROM `experiments`
  WHERE (`experiments`.`type` = 'OP');

--
-- View structure for view `experiments_OP_data`
-- Extracts OP data from membrane_composition JSON field
--
DROP VIEW IF EXISTS `experiments_OP_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */; 
/*!50503 SET character_set_client = utf8mb4 */;
CREATE VIEW `experiments_OP_data` AS
 SELECT `experiments`.`id` AS `experiment_id`,
        `lipids`.`id` AS `lipid_id`,
        `experiments`.`article_doi` AS `doi`,
        `experiments`.`path` AS `path`,
        `lipids`.`name` AS `lipid_name`,
        `lipids`.`molecule` AS `lipid_molecule`,
        `experiments_membrane_composition`.`data` AS `OP_data`    
  FROM `experiments`
  LEFT JOIN `experiments_membrane_composition` ON `experiments`.`id` = `experiments_membrane_composition`.`experiment_id`
  LEFT JOIN `lipids` ON `experiments_membrane_composition`.`lipid_id` = `lipids`.`id`
     WHERE `experiments`.`type` = 'OP'
       AND `experiments_membrane_composition`.`data` IS NOT NULL;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


COMMIT;