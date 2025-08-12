-- Project Templates Table
CREATE TABLE IF NOT EXISTS project_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) DEFAULT 'general',
    thumbnail VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

-- Project Template Components Table
CREATE TABLE IF NOT EXISTS project_template_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    component_type ENUM('tool', 'page', 'service', 'api') NOT NULL DEFAULT 'tool',
    icon VARCHAR(100) DEFAULT 'cube-outline',
    config JSON,
    component_order INT DEFAULT 0,
    FOREIGN KEY (template_id) REFERENCES project_templates(id) ON DELETE CASCADE
);

-- Insert some sample templates
INSERT INTO project_templates (name, description, category, thumbnail) VALUES
('Empty Project', 'A basic empty project with no components', 'basic', 'empty-template.png'),
('Dashboard Project', 'Project with dashboard, forms, and data visualization', 'business', 'dashboard-template.png'),
('Web App', 'Complete web application with frontend pages and API', 'web', 'webapp-template.png'),
('Service Monitor', 'Project for monitoring services with notifications', 'devops', 'monitor-template.png');

-- Insert components for Empty Project
SET @empty_template_id = LAST_INSERT_ID();

-- Insert components for Dashboard Project
INSERT INTO project_templates (name, description, category, thumbnail) VALUES
('Dashboard Project', 'Project with dashboard, forms, and data visualization', 'business', 'dashboard-template.png');
SET @dashboard_template_id = LAST_INSERT_ID();

INSERT INTO project_template_components (template_id, name, component_type, icon, config, component_order) VALUES
(@dashboard_template_id, 'Dashboard', 'tool', 'bar-chart-outline', '{"hasConfig": 1}', 0),
(@dashboard_template_id, 'User Form', 'tool', 'document-text-outline', '{"hasConfig": 1}', 1),
(@dashboard_template_id, 'Overview', 'page', 'home-outline', '{"is_home": true, "title": "Dashboard Overview"}', 2),
(@dashboard_template_id, 'Analytics Service', 'service', 'analytics-outline', '{"link": "analytics", "description": "Analytics data processing service"}', 3);

-- Insert components for Web App
INSERT INTO project_templates (name, description, category, thumbnail) VALUES
('Web App', 'Complete web application with frontend pages and API', 'web', 'webapp-template.png');
SET @webapp_template_id = LAST_INSERT_ID();

INSERT INTO project_template_components (template_id, name, component_type, icon, config, component_order) VALUES
(@webapp_template_id, 'Users', 'tool', 'people-outline', '{"hasConfig": 1}', 0),
(@webapp_template_id, 'Content', 'tool', 'document-text-outline', '{"hasConfig": 1}', 1),
(@webapp_template_id, 'Home', 'page', 'home-outline', '{"is_home": true, "title": "Welcome to Your Web App"}', 2),
(@webapp_template_id, 'About', 'page', 'information-circle-outline', '{"is_home": false, "title": "About Us"}', 3),
(@webapp_template_id, 'Contact', 'page', 'mail-outline', '{"is_home": false, "title": "Contact Us"}', 4),
(@webapp_template_id, 'API Service', 'service', 'code-slash-outline', '{"link": "api", "description": "REST API service"}', 5),
(@webapp_template_id, 'Weather API', 'api', 'cloud-outline', '{}', 6);

-- Insert components for Service Monitor
INSERT INTO project_templates (name, description, category, thumbnail) VALUES
('Service Monitor', 'Project for monitoring services with notifications', 'devops', 'monitor-template.png');
SET @monitor_template_id = LAST_INSERT_ID();

INSERT INTO project_template_components (template_id, name, component_type, icon, config, component_order) VALUES
(@monitor_template_id, 'Monitoring Dashboard', 'tool', 'pulse-outline', '{"hasConfig": 1}', 0),
(@monitor_template_id, 'Alerts', 'tool', 'alert-circle-outline', '{"hasConfig": 1}', 1),
(@monitor_template_id, 'Status', 'page', 'stats-chart-outline', '{"is_home": true, "title": "Service Status"}', 2),
(@monitor_template_id, 'Database Service', 'service', 'server-outline', '{"link": "database", "description": "Database monitoring service"}', 3),
(@monitor_template_id, 'Web Server', 'service', 'globe-outline', '{"link": "web-server", "description": "Web server monitoring"}', 4),
(@monitor_template_id, 'API Server', 'service', 'code-outline', '{"link": "api-server", "description": "API server monitoring"}', 5);