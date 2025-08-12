-- Migration: Tabelle für GitHub-Tokens pro User
CREATE TABLE IF NOT EXISTS control_center_github_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    github_token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES control_center_users(userID) ON DELETE CASCADE
);
