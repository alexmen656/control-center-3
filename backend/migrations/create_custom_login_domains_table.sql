-- Tabelle für Custom Login Domains
CREATE TABLE IF NOT EXISTS custom_login_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projectID VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL UNIQUE,
    is_enabled BOOLEAN DEFAULT FALSE,
    primary_color VARCHAR(20) DEFAULT '#e53e3e',
    logo_url VARCHAR(500) DEFAULT NULL,
    company_name VARCHAR(255) DEFAULT NULL,
    cloudflare_record_id VARCHAR(100) DEFAULT NULL,
    ssl_status ENUM('pending', 'active', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (projectID) REFERENCES projects(projectID) ON DELETE CASCADE,
    INDEX idx_domain (domain),
    INDEX idx_projectID (projectID)
);

-- Beispiel-Insert
-- INSERT INTO custom_login_domains (projectID, domain, is_enabled, primary_color, logo_url, company_name) 
-- VALUES ('abc123', 'login.meinefirma.de', 1, '#3b82f6', 'https://example.com/logo.png', 'Meine Firma GmbH');
