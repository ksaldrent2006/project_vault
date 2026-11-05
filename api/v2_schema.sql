-- v2_schema.sql

-- 1) link each request to the project id it is for (US-07)
ALTER TABLE `request`
  ADD COLUMN `project_id` INT NULL AFTER `requestor_id`,
  ADD CONSTRAINT `request_project_fk`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

-- 2) stop the same student from requesting the same project twice
ALTER TABLE `request`
  ADD UNIQUE INDEX `one_request_per_project` (`project_id`, `requestor_id`);