 anzubieten <?php
include '../head.php';

// Tabelle für Projekt-Templates
$createTemplatesTable = "CREATE TABLE IF NOT EXISTS `control_center_project_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Tabelle für Template-Komponenten (zugehörige Tools, Services, APIs, Pages)
$createTemplateComponentsTable = "CREATE TABLE IF NOT EXISTS `control_center_project_template_components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `component_type` enum('tool','service','api','page') NOT NULL,
  `name` varchar(255) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `config` text DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `template_components_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `control_center_project_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Füge ein paar Demo-Templates hinzu
$insertTemplates = "INSERT INTO `control_center_project_templates` 
(`name`, `description`, `thumbnail`, `category`) VALUES
('Leeres Projekt', 'Ein leeres Projekt ohne vordefinierte Komponenten.', NULL, 'Basic'),
('Web App Projekt', 'Ein vollständiges Web App Projekt mit Frontend, Backend und API-Anbindung.', NULL, 'Web'),
('IoT Monitoring', 'Ein Projekt zur Überwachung von IoT-Geräten mit Dashboards und Alerts.', NULL, 'IoT'),
('E-Commerce Starter', 'Eine Basis für E-Commerce Anwendungen mit Produktverwaltung und Bestellsystem.', NULL, 'E-Commerce'),
('Service Monitor', 'Überwachung und Management von externen Diensten mit automatischen Alerts.', NULL, 'DevOps');";

// Demo-Template-Komponenten für "Web App Projekt"
$insertTemplateComponents = "INSERT INTO `control_center_project_template_components` 
(`template_id`, `component_type`, `name`, `icon`, `config`, `order`) VALUES
(2, 'tool', 'Dashboard', 'bar-chart-outline', NULL, 1),
(2, 'tool', 'Filesystem', 'folder-outline', NULL, 2),
(2, 'service', 'Frontend', 'desktop-outline', '{\"url\":\"http://localhost:3000\"}', 1),
(2, 'service', 'Backend API', 'server-outline', '{\"url\":\"http://localhost:5000\"}', 2),
(2, 'api', 'Auth API', 'key-outline', NULL, 1),
(2, 'page', 'Home', 'home-outline', '{\"is_home\":true}', 1),
(2, 'page', 'About', 'information-circle-outline', NULL, 2);";

// IoT Monitoring Template-Komponenten
$insertIoTComponents = "INSERT INTO `control_center_project_template_components` 
(`template_id`, `component_type`, `name`, `icon`, `config`, `order`) VALUES
(3, 'tool', 'Dashboard', 'bar-chart-outline', NULL, 1),
(3, 'tool', 'Device Manager', 'hardware-chip-outline', NULL, 2),
(3, 'service', 'MQTT Broker', 'radio-outline', '{\"url\":\"mqtt://localhost:1883\"}', 1),
(3, 'service', 'Data Storage', 'database-outline', NULL, 2),
(3, 'api', 'Sensor API', 'pulse-outline', NULL, 1),
(3, 'page', 'Monitoring', 'analytics-outline', '{\"is_home\":true}', 1),
(3, 'page', 'Devices', 'list-outline', NULL, 2);";

// E-Commerce Template-Komponenten
$insertEcommerceComponents = "INSERT INTO `control_center_project_template_components` 
(`template_id`, `component_type`, `name`, `icon`, `config`, `order`) VALUES
(4, 'tool', 'Products', 'pricetag-outline', NULL, 1),
(4, 'tool', 'Orders', 'cart-outline', NULL, 2),
(4, 'service', 'Payment Gateway', 'card-outline', NULL, 1),
(4, 'service', 'Inventory', 'cube-outline', NULL, 2),
(4, 'api', 'Stripe API', 'cash-outline', NULL, 1),
(4, 'page', 'Store', 'storefront-outline', '{\"is_home\":true}', 1),
(4, 'page', 'Checkout', 'bag-check-outline', NULL, 2);";

// Service Monitor Template-Komponenten
$insertMonitorComponents = "INSERT INTO `control_center_project_template_components` 
(`template_id`, `component_type`, `name`, `icon`, `config`, `order`) VALUES
(5, 'tool', 'Service Dashboard', 'bar-chart-outline', NULL, 1),
(5, 'tool', 'Alerts', 'notifications-outline', NULL, 2),
(5, 'service', 'Main API', 'cloud-outline', '{\"url\":\"https://api.example.com\"}', 1),
(5, 'service', 'Database', 'server-outline', '{\"url\":\"db.example.com\"}', 2),
(5, 'api', 'Notifications API', 'chatbox-outline', NULL, 1),
(5, 'page', 'Overview', 'eye-outline', '{\"is_home\":true}', 1),
(5, 'page', 'Logs', 'list-outline', NULL, 2);";

// Führe die Queries aus
try {
    query($createTemplatesTable);
    echo "✅ Tabelle für Projekt-Templates erstellt\n";
    
    query($createTemplateComponentsTable);
    echo "✅ Tabelle für Template-Komponenten erstellt\n";
    
    query($insertTemplates);
    echo "✅ Demo-Templates eingefügt\n";
    
    query($insertTemplateComponents);
    query($insertIoTComponents);
    query($insertEcommerceComponents);
    query($insertMonitorComponents);
    echo "✅ Demo-Template-Komponenten eingefügt\n";
    
    echo "Migration erfolgreich abgeschlossen\n";
} catch (Exception $e) {
    echo "❌ Fehler bei der Migration: " . $e->getMessage() . "\n";
}
?>