-- v3_schema.sql

-- 1) contributors: names of people who worked on a project,
--    even if they do not have an account
CREATE TABLE IF NOT EXISTS `project_contributor` (
  `contributor_id` INT NOT NULL AUTO_INCREMENT,
  `project_id` INT NOT NULL,
  `contributor_name` VARCHAR(100) NOT NULL, -- typed name, works for anyone
  `person_id` INT NULL, -- isn't linked to accounts, but could be...
  PRIMARY KEY (`contributor_id`),
  INDEX `contrib_project_idx` (`project_id` ASC),
  INDEX `contrib_person_idx` (`person_id` ASC),
  CONSTRAINT `contrib_project_fk`
    FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`project_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `contrib_person_fk`
    FOREIGN KEY (`person_id`)
    REFERENCES `person` (`person_id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- 2) make an admin: register the account on the site first,
--    then change the email below and run this line
-- UPDATE person SET role = 'admin' WHERE email_address = 'admin123@etown.edu';