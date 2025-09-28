-- Tabellen für Link-Tracking-Modul
CREATE TABLE IF NOT EXISTS link_tracker_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    slug VARCHAR(64) NOT NULL,
    target_url TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY (slug, project_id)
);

CREATE TABLE IF NOT EXISTS link_tracker_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_id INT NOT NULL,
    ip VARCHAR(64),
    device TEXT,
    referer TEXT,
    visited_at DATETIME NOT NULL,
    FOREIGN KEY (link_id) REFERENCES link_tracker_links(id) ON DELETE CASCADE
);
