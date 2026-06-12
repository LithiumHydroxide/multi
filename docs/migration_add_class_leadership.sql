-- Migration: Add Class Leadership Columns
-- This adds support for assigning class teachers, assistant teachers, and class prefects

ALTER TABLE `classes` ADD COLUMN `class_teacher_id` INT(10) UNSIGNED DEFAULT NULL AFTER `capacity`;
ALTER TABLE `classes` ADD COLUMN `assistant_teacher_id` INT(10) UNSIGNED DEFAULT NULL AFTER `class_teacher_id`;
ALTER TABLE `classes` ADD COLUMN `prefect_id` INT(10) UNSIGNED DEFAULT NULL AFTER `assistant_teacher_id`;

-- Add foreign key constraints
ALTER TABLE `classes` ADD CONSTRAINT `fk_class_teacher` FOREIGN KEY (`class_teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL;
ALTER TABLE `classes` ADD CONSTRAINT `fk_assistant_teacher` FOREIGN KEY (`assistant_teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL;
ALTER TABLE `classes` ADD CONSTRAINT `fk_prefect` FOREIGN KEY (`prefect_id`) REFERENCES `students`(`id`) ON DELETE SET NULL;

-- Verify the changes
-- SELECT id, name, class_teacher_id, assistant_teacher_id, prefect_id FROM classes;
