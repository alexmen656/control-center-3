-- Create user_project_assignments table for project assignment system
-- This table links users to projects for JWT token-based project redirection

CREATE TABLE IF NOT EXISTS user_project_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_link VARCHAR(255) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints (assuming control_center_users table exists)
    -- FOREIGN KEY (user_id) REFERENCES control_center_users(userID) ON DELETE CASCADE,
    -- FOREIGN KEY (project_link) REFERENCES projects(link) ON DELETE CASCADE,
    
    -- Ensure one assignment per user
    UNIQUE KEY unique_user_assignment (user_id),
    
    -- Index for fast lookups
    INDEX idx_user_id (user_id),
    INDEX idx_project_link (project_link)
);

-- Insert some example data (remove in production)
-- INSERT INTO user_project_assignments (user_id, project_link) VALUES
-- (1, 'test-project'),
-- (2, 'another-project');
