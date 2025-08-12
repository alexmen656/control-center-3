-- Create table for codespace-specific API activations
-- This allows individual codespaces to activate/deactivate APIs that their project has subscribed to
CREATE TABLE IF NOT EXISTS `codespace_api_activations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `codespace_id` int(11) NOT NULL,
    `subscription_id` int(11) NOT NULL, -- references project_api_subscriptions
    `is_active` boolean DEFAULT TRUE,
    `api_key` varchar(255) NULL, -- optional codespace-specific API key override
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`codespace_id`) REFERENCES `project_codespaces`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subscription_id`) REFERENCES `project_api_subscriptions`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_codespace_subscription` (`codespace_id`, `subscription_id`)
);

-- Create table for API usage logs specific to codespaces
CREATE TABLE IF NOT EXISTS `cms_api_usage_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `activation_id` int(11) NOT NULL, -- references codespace_api_activations
    `method` varchar(10) NOT NULL,
    `path` varchar(500) NOT NULL,
    `status_code` int(3) NOT NULL,
    `response_time` int(11) DEFAULT 0, -- in milliseconds
    `ip_address` varchar(45),
    `user_agent` text,
    `request_headers` json,
    `response_headers` json,
    `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`activation_id`) REFERENCES `codespace_api_activations`(`id`) ON DELETE CASCADE,
    INDEX `idx_activation_timestamp` (`activation_id`, `timestamp`),
    INDEX `idx_timestamp` (`timestamp`)
);
