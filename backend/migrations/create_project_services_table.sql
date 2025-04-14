-- Create the project_services table if it doesn't exist
CREATE TABLE IF NOT EXISTS `project_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icon` varchar(255) NOT NULL DEFAULT 'cog-outline',
  `name` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `description` text,
  `status` enum('active', 'maintenance', 'inactive') NOT NULL DEFAULT 'active',
  `projectID` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `projectID` (`projectID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;