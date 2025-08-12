-- Tabelle für die Verknüpfung von Projekten zu Vercel-Projekten
CREATE TABLE IF NOT EXISTS control_center_project_vercel_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project VARCHAR(128) NOT NULL,
    vercel_project_id VARCHAR(128) NOT NULL,
    vercel_project_name VARCHAR(255),
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (project)
);
