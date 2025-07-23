-- Erstellt die Tabelle für Vercel-OAuth-Tokens pro User
CREATE TABLE IF NOT EXISTS control_center_vercel_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    vercel_token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (userID)
);
