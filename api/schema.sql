SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

CREATE SCHEMA IF NOT EXISTS `projectvault_local` DEFAULT CHARACTER SET utf8mb4 ;
USE `projectvault_local` ;

CREATE TABLE IF NOT EXISTS `team` (
  `team_id` INT NOT NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `advisor` VARCHAR(45) NULL,
  `team_name` VARCHAR(45) NULL,
  PRIMARY KEY (`team_id`))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `projects` (
  `project_description` VARCHAR(45) NULL,
  `team_id` INT NOT NULL,
  `project_requirements` VARCHAR(45) NULL,
  `project_status` VARCHAR(45) NULL,
  `repo_id` INT NULL,
  `repo_link` VARCHAR(45) NULL,
  `department` VARCHAR(45) NULL,
  `request_id` INT NULL,
  `project_id` VARCHAR(45) NOT NULL,
  INDEX `proj_team_key_idx` (`team_id` ASC),
  PRIMARY KEY (`project_id`, `team_id`),
  UNIQUE INDEX `repo_id_UNIQUE` (`repo_id` ASC),
  UNIQUE INDEX `request_id_UNIQUE` (`request_id` ASC),
  CONSTRAINT `proj_team_key`
    FOREIGN KEY (`team_id`)
    REFERENCES `team` (`team_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `person` (
  `person_id` INT NOT NULL,
  `password` VARCHAR(255) NULL,
  `currently_active` VARCHAR(45) NULL,
  `role` VARCHAR(45) NULL,
  `first_name` VARCHAR(45) NULL,
  `last_name` VARCHAR(45) NULL,
  `email_address` VARCHAR(45) NULL,
  PRIMARY KEY (`person_id`))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `team_member` (
  `member_id` INT NOT NULL,
  `team_id` INT NOT NULL,
  PRIMARY KEY (`member_id`, `team_id`),
  INDEX `team_fk_idx` (`team_id` ASC),
  CONSTRAINT `team_fk`
    FOREIGN KEY (`team_id`)
    REFERENCES `team` (`team_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `member_fk`
    FOREIGN KEY (`member_id`)
    REFERENCES `person` (`person_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `progress` (
  `progress_id` INT NOT NULL,
  `status` VARCHAR(45) NULL,
  `challenges` VARCHAR(45) NULL,
  `team_id` INT NOT NULL,
  `task_steps` VARCHAR(45) NULL,
  `visual_materials` VARCHAR(45) NULL,
  `project_start_date` DATE NULL,
  `project_end_date` DATE NULL,
  `project_id` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`progress_id`, `project_id`, `team_id`),
  INDEX `progress_key_idx` (`project_id` ASC),
  INDEX `team_progress_fk_idx` (`team_id` ASC),
  CONSTRAINT `progress_key`
    FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`project_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `team_progress_fk`
    FOREIGN KEY (`team_id`)
    REFERENCES `team` (`team_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `request` (
  `date_requested` DATE NULL,
  `acceptance_status` VARCHAR(45) NULL,
  `request_description` VARCHAR(45) NULL,
  `requestor_id` INT NOT NULL,
  `request_id` INT NOT NULL,
  PRIMARY KEY (`request_id`, `requestor_id`),
  INDEX `requestor_fk_idx` (`requestor_id` ASC),
  CONSTRAINT `proj_req_fk`
    FOREIGN KEY (`request_id`)
    REFERENCES `projects` (`request_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `requestor_fk`
    FOREIGN KEY (`requestor_id`)
    REFERENCES `person` (`person_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `repository` (
  `last_commit_date` DATE NULL,
  `last_commit_id` VARCHAR(45) NULL,
  `owner` VARCHAR(45) NULL,
  `repo_location` VARCHAR(45) NULL,
  `repo_name` VARCHAR(45) NULL,
  `repo_id` INT NOT NULL,
  PRIMARY KEY (`repo_id`),
  CONSTRAINT `repo_id_fk`
    FOREIGN KEY (`repo_id`)
    REFERENCES `projects` (`repo_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;