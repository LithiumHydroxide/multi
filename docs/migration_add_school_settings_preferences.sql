-- Migration: Add dashboard layout and preference columns to school_settings
ALTER TABLE school_settings
    ADD COLUMN dashboard_layout ENUM('default','compact','analytics') NOT NULL DEFAULT 'default',
    ADD COLUMN default_term TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN academic_year_start DATE NULL;
