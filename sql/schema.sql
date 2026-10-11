-- =====================================================================
--  Academia System - Estrutura do banco de dados (MySQL 5.7+ / MariaDB)
--  Compativel com hospedagem cPanel
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Academias (tenants)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academy` (
  `id`                CHAR(36)      NOT NULL,
  `name`              VARCHAR(160)  NOT NULL,
  `slug`              VARCHAR(80)   DEFAULT NULL,
  `cnpj`              VARCHAR(20)   DEFAULT NULL,
  `email`             VARCHAR(160)  DEFAULT NULL,
  `telefone`          VARCHAR(40)   DEFAULT NULL,
  `logo_url`          VARCHAR(255)  DEFAULT NULL,
  `cor_primaria`      VARCHAR(9)    NOT NULL DEFAULT '#D97634',
  `endereco_cep`      VARCHAR(15)   DEFAULT NULL,
  `endereco_rua`      VARCHAR(200)  DEFAULT NULL,
  `endereco_numero`   VARCHAR(20)   DEFAULT NULL,
  `endereco_bairro`   VARCHAR(100)  DEFAULT NULL,
  `endereco_cidade`   VARCHAR(100)  DEFAULT NULL,
  `endereco_uf`       CHAR(2)       DEFAULT NULL,
  `capacity_per_class` INT          NOT NULL DEFAULT 30,
  `business_hours`    VARCHAR(255)  DEFAULT NULL,
  `plano`             ENUM('starter','pro','premium') NOT NULL DEFAULT 'pro',
  `status`            ENUM('active','trial','blocked') NOT NULL DEFAULT 'trial',
  `valor_mensal`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `dia_vencimento`    TINYINT       NOT NULL DEFAULT 10,
  `trial_ate`         DATE          DEFAULT NULL,
  `owner_nome`        VARCHAR(160)  DEFAULT NULL,
  `owner_email`       VARCHAR(160)  DEFAULT NULL,
  `owner_telefone`    VARCHAR(40)   DEFAULT NULL,
  `ultimo_acesso`     DATETIME      DEFAULT NULL,
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_academy_slug` (`slug`),
  KEY `ix_academy_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Usuarios do sistema (login)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuario` (
  `id`                    CHAR(36)     NOT NULL,
  `academy_id`            CHAR(36)     DEFAULT NULL,
  `nome`                  VARCHAR(160) DEFAULT NULL,
  `email`                 VARCHAR(190) NOT NULL,
  `senha_hash`            VARCHAR(255) NOT NULL,
  `role`                  ENUM('super_admin','admin','professor','recepcao','personal') NOT NULL DEFAULT 'admin',
  `ativo`                 TINYINT(1)   NOT NULL DEFAULT 1,
  `must_change_password`  TINYINT(1)   NOT NULL DEFAULT 0,
  `reset_token`           VARCHAR(64)  DEFAULT NULL,
  `reset_expira`          DATETIME     DEFAULT NULL,
  `ultimo_login`          DATETIME     DEFAULT NULL,
  `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_email` (`email`),
  KEY `ix_usuario_academy` (`academy_id`),
  CONSTRAINT `fk_usuario_academy` FOREIGN KEY (`academy_id`) REFERENCES `academy` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Planos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plan` (
  `id`               CHAR(36)      NOT NULL,
  `academy_id`       CHAR(36)      NOT NULL,
  `name`             VARCHAR(120)  NOT NULL,
  `description`      VARCHAR(255)  DEFAULT NULL,
  `price`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `duration_months`  INT           NOT NULL DEFAULT 1,
  `included_classes` VARCHAR(255)  DEFAULT NULL,
  `total_checkins`   INT           DEFAULT NULL,
  `featured`         TINYINT(1)    NOT NULL DEFAULT 0,
  `active`           TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_plan_academy` (`academy_id`),
  CONSTRAINT `fk_plan_academy` FOREIGN KEY (`academy_id`) REFERENCES `academy` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Equipe
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_member` (
  `id`          CHAR(36)     NOT NULL,
  `academy_id`  CHAR(36)     NOT NULL,
  `name`        VARCHAR(160) NOT NULL,
  `email`       VARCHAR(190) NOT NULL,
  `telefone`    VARCHAR(40)  DEFAULT NULL,
  `role`        ENUM('admin','professor','recepcao','personal') NOT NULL DEFAULT 'professor',
  `especialidade` VARCHAR(120) DEFAULT NULL,
  `comissao_pct`  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_team_academy` (`academy_id`),
  CONSTRAINT `fk_team_academy` FOREIGN KEY (`academy_id`) REFERENCES `academy` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Alunos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student` (
  `id`                CHAR(36)     NOT NULL,
  `academy_id`        CHAR(36)     NOT NULL,
  `name`              VARCHAR(160) NOT NULL,
  `email`             VARCHAR(190) DEFAULT NULL,
  `phone`             VARCHAR(40)  DEFAULT NULL,
  `cpf`               VARCHAR(20)  DEFAULT NULL,
  `birth_date`        DATE         DEFAULT NULL,
  `gender`            ENUM('M','F','Outro') DEFAULT NULL,
  `address`           VARCHAR(255) DEFAULT NULL,
  `photo_url`         VARCHAR(255) DEFAULT NULL,
  `emergency_contact` VARCHAR(160) DEFAULT NULL,
  `medical_notes`     TEXT         DEFAULT NULL,
  `objetivo`          VARCHAR(120) DEFAULT NULL,
  `plan_id`           CHAR(36)     DEFAULT NULL,
  `plan_name`         VARCHAR(120) DEFAULT NULL,
  `plan_start`        DATE         DEFAULT NULL,
  `plan_end`          DATE         DEFAULT NULL,
  `payment_status`    ENUM('em_dia','atrasado','pendente') NOT NULL DEFAULT 'em_dia',
  `status`            ENUM('active','inactive','blocked','trial') NOT NULL DEFAULT 'active',
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_student_academy` (`academy_id`),
  KEY `ix_student_status` (`academy_id`,`status`),
  KEY `ix_student_cpf` (`cpf`),
  CONSTRAINT `fk_student_academy` FOREIGN KEY (`academy_id`) REFERENCES `academy` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Grade de aulas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedule` (
  `id`              CHAR(36)     NOT NULL,
  `academy_id`      CHAR(36)     NOT NULL,
  `name`            VARCHAR(140) NOT NULL,
  `modality`        VARCHAR(40)  DEFAULT NULL,
  `day_of_week`     TINYINT      NOT NULL DEFAULT 1,
  `start_time`      TIME         NOT NULL,
  `end_time`        TIME         NOT NULL,
  `instructor_id`   CHAR(36)     DEFAULT NULL,
  `instructor_name` VARCHAR(160) DEFAULT NULL,
  `max_capacity`    INT          NOT NULL DEFAULT 20,
  `active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_schedule_academy` (`academy_id`,`active`),
  CONSTRAINT `fk_schedule_academy` FOREIGN KEY (`academy_id`) REFERENCES `academy` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Check-ins
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checkin` (
  `id`           CHAR(36)     NOT NULL,
  `academy_id`   CHAR(36)     NOT NULL,
  `student_id`   CHAR(36)     NOT NULL,
  `student_name` VARCHAR(160) DEFAULT NULL,
  `schedule_id`  CHAR(36)     DEFAULT NULL,
  `modality`     VARCHAR(40)  DEFAULT NULL,
  `date`         DATE         NOT NULL,
  `time`         TIME         NOT NULL,
  `source`       ENUM('catraca','manual','app') NOT NULL DEFAULT 'manual',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_checkin_academy_date` (`academy_id`,`date`),
  KEY `ix_checkin_student` (`student_id`),
  CONSTRAINT `fk_checkin_academy` FOREIGN KEY (`academy_id`) REFERENCES `academy` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Financeiro
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `financial` (
  `id`             CHAR(36)      NOT NULL,
  `academy_id`     CHAR(36)      NOT NULL,
  `type`           ENUM('receita','despesa') NOT NULL DEFAULT 'receita',
  `category`       VARCHAR(80)   DEFAULT NULL,
  `description`    VARCHAR(255)  DEFAULT NULL,
  `amount`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `date`           DATE          NOT NULL,
  `due_date`       DATE          DEFAULT NULL,
  `payment_method` VARCHAR(40)   DEFAULT NULL,
  `status`         ENUM('pendente','pago','atrasado','cancelado') NOT NULL DEFAULT 'pendente',
  `student_id`     CHAR(36)      DEFAULT NULL,
  `student_name`   VARCHAR(160)  DEFAULT NULL,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_financial_academy_date` (`academy_id`,`date`),
  CONSTRAINT `fk_financial_academy` FOREIGN KEY (`academy_id`) REFERENCES `academy` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Leads (link publico de matricula)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lead` (
  `id`          CHAR(36)     NOT NULL,
  `academy_id`  CHAR(36)     NOT NULL,
  `type`        ENUM('aula_experimental','trial_7dias','interesse_plano') NOT NULL,
  `name`        VARCHAR(160) NOT NULL,
  `phone`       VARCHAR(40)  NOT NULL,
  `email`       VARCHAR(190) DEFAULT NULL,
  `modality`    VARCHAR(40)  DEFAULT NULL,
  `objetivo`    VARCHAR(120) DEFAULT NULL,
  `idade`       INT          DEFAULT NULL,
  `plan_id`     CHAR(36)     DEFAULT NULL,
  `schedule_id` CHAR(36)     DEFAULT NULL,
  `status`      ENUM('novo','contatado','convertido','perdido') NOT NULL DEFAULT 'novo',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_lead_academy` (`academy_id`,`status`),
  CONSTRAINT `fk_lead_academy` FOREIGN KEY (`academy_id`) REFERENCES `academy` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Configuracao global da plataforma
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `app_config` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `chave`      VARCHAR(80)  NOT NULL,
  `valor`      TEXT         DEFAULT NULL,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_app_config_chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `app_config` (`chave`,`valor`) VALUES
  ('app_name', 'Academia System'),
  ('email_remetente', ''),
  ('email_from_name', 'Academia System');

SET FOREIGN_KEY_CHECKS = 1;
