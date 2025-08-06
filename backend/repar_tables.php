<?php
include "head.php";

query("DROP TABLE IF EXISTS project_api_subscriptions");

query("CREATE TABLE IF NOT EXISTS `project_api_subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `projectID` varchar(255) NOT NULL,
    `api_id` int(11) NOT NULL,
    `api_key` varchar(255) NOT NULL, -- unique API key for this project
    `rate_limit` int(11) DEFAULT 100,
    `is_enabled` boolean DEFAULT TRUE,
    `usage_count` int(11) DEFAULT 0,
    `last_used` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`projectID`) REFERENCES `projects`(`projectID`) ON DELETE CASCADE,
    FOREIGN KEY (`api_id`) REFERENCES `cms_apis`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_project_api` (`projectID`, `api_id`)
)");