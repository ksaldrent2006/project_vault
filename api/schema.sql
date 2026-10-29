-- database schema

CREATE SCHEMA `projectvault_local` DEFAULT CHARACTER SET utf8mb4 ;
USE `projectvault_local` ;

CREATE TABLE IF NOT EXISTS `person` (
  `person_id` INT NOT NULL AUTO_INCREMENT,
  `email_address` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(45) NULL,
  `last_name` VARCHAR(45) NULL,
  `role` VARCHAR(45) NOT NULL DEFAULT 'student',
  `currently_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`person_id`),
  UNIQUE INDEX `email_UNIQUE` (`email_address` ASC))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `team` (
  `team_id` INT NOT NULL AUTO_INCREMENT,
  `team_name` VARCHAR(100) NULL,
  `advisor` VARCHAR(100) NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  PRIMARY KEY (`team_id`))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `team_member` (
  `member_id` INT NOT NULL,
  `team_id` INT NOT NULL,
  PRIMARY KEY (`member_id`, `team_id`),
  INDEX `team_fk_idx` (`team_id` ASC),
  CONSTRAINT `member_fk`
    FOREIGN KEY (`member_id`)
    REFERENCES `person` (`person_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `team_fk`
    FOREIGN KEY (`team_id`)
    REFERENCES `team` (`team_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `request` (
  `request_id` INT NOT NULL AUTO_INCREMENT,
  `requestor_id` INT NOT NULL,
  `request_description` TEXT NULL,
  `date_requested` DATE NULL,
  `acceptance_status` VARCHAR(45) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`request_id`),
  INDEX `requestor_fk_idx` (`requestor_id` ASC),
  CONSTRAINT `requestor_fk`
    FOREIGN KEY (`requestor_id`)
    REFERENCES `person` (`person_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `repository` (
  `repo_id` INT NOT NULL AUTO_INCREMENT,
  `repo_name` VARCHAR(100) NULL,
  `owner` VARCHAR(100) NULL,
  `repo_location` VARCHAR(255) NULL,
  `last_commit_id` VARCHAR(64) NULL,
  `last_commit_date` DATE NULL,
  PRIMARY KEY (`repo_id`))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `projects` (
  `project_id` INT NOT NULL AUTO_INCREMENT,
  `team_id` INT NOT NULL,
  `request_id` INT NULL,
  `repo_id` INT NULL,
  `project_description` TEXT NULL,
  `project_requirements` TEXT NULL,
  `project_status` VARCHAR(45) NULL,
  `department` VARCHAR(100) NULL,
  `repo_link` VARCHAR(255) NULL,
  PRIMARY KEY (`project_id`),
  INDEX `proj_team_idx` (`team_id` ASC),
  UNIQUE INDEX `request_id_UNIQUE` (`request_id` ASC),
  UNIQUE INDEX `repo_id_UNIQUE` (`repo_id` ASC),
  CONSTRAINT `proj_team_fk`
    FOREIGN KEY (`team_id`)
    REFERENCES `team` (`team_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `proj_request_fk`
    FOREIGN KEY (`request_id`)
    REFERENCES `request` (`request_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `proj_repo_fk`
    FOREIGN KEY (`repo_id`)
    REFERENCES `repository` (`repo_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `progress` (
  `progress_id` INT NOT NULL AUTO_INCREMENT,
  `project_id` INT NOT NULL,
  `status` VARCHAR(45) NULL,
  `challenges` TEXT NULL,
  `task_steps` TEXT NULL,
  `visual_materials` VARCHAR(255) NULL,
  `project_start_date` DATE NULL,
  `project_end_date` DATE NULL,
  PRIMARY KEY (`progress_id`),
  INDEX `progress_project_idx` (`project_id` ASC),
  CONSTRAINT `progress_project_fk`
    FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`project_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;