CREATE TABLE IF NOT EXISTS project_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    is_system_role BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE control_center_user_projects 
ADD COLUMN role_id INT DEFAULT NULL,
ADD CONSTRAINT fk_user_project_role 
    FOREIGN KEY (role_id) REFERENCES project_roles(id) ON DELETE SET NULL;

INSERT INTO project_roles (name, slug, description, permissions, is_system_role) VALUES 
(
    'Owner', 
    'owner', 
    'Vollständiger Zugriff auf alle Funktionen des Projekts', 
    '{
        "project": {
            "view": true,
            "edit": true,
            "delete": true,
            "manage_users": true,
            "manage_roles": true,
            "export": true
        },
        "tools": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true
        },
        "components": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true,
            "export": true
        },
        "pages": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true
        },
        "services": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true,
            "configure": true
        },
        "filesystem": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true,
            "upload": true,
            "download": true
        },
        "database": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true
        },
        "settings": {
            "view": true,
            "edit": true
        }
    }',
    TRUE
),
(
    'Admin', 
    'admin', 
    'Administrativer Zugriff mit erweiterten Berechtigungen', 
    '{
        "project": {
            "view": true,
            "edit": true,
            "delete": false,
            "manage_users": true,
            "manage_roles": false,
            "export": true
        },
        "tools": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true
        },
        "components": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true,
            "export": true
        },
        "pages": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true
        },
        "services": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": false,
            "configure": true
        },
        "filesystem": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true,
            "upload": true,
            "download": true
        },
        "database": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true
        },
        "settings": {
            "view": true,
            "edit": true
        }
    }',
    TRUE
),
(
    'Editor', 
    'editor', 
    'Bearbeiten von Inhalten und Komponenten', 
    '{
        "project": {
            "view": true,
            "edit": false,
            "delete": false,
            "manage_users": false,
            "manage_roles": false,
            "export": true
        },
        "tools": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": false
        },
        "components": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": false,
            "export": true
        },
        "pages": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": false
        },
        "services": {
            "view": true,
            "create": false,
            "edit": false,
            "delete": false,
            "configure": false
        },
        "filesystem": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": false,
            "upload": true,
            "download": true
        },
        "database": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": false
        },
        "settings": {
            "view": true,
            "edit": false
        }
    }',
    TRUE
),
(
    'Developer', 
    'developer', 
    'Zugriff auf Entwicklungstools und Code', 
    '{
        "project": {
            "view": true,
            "edit": false,
            "delete": false,
            "manage_users": false,
            "manage_roles": false,
            "export": true
        },
        "tools": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": false
        },
        "components": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": false,
            "export": true
        },
        "pages": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": false
        },
        "services": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": false,
            "configure": true
        },
        "filesystem": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true,
            "upload": true,
            "download": true
        },
        "database": {
            "view": true,
            "create": true,
            "edit": true,
            "delete": true
        },
        "settings": {
            "view": true,
            "edit": false
        }
    }',
    TRUE
),
(
    'Viewer', 
    'viewer', 
    'Nur Lesezugriff auf Projektinhalte', 
    '{
        "project": {
            "view": true,
            "edit": false,
            "delete": false,
            "manage_users": false,
            "manage_roles": false,
            "export": false
        },
        "tools": {
            "view": true,
            "create": false,
            "edit": false,
            "delete": false
        },
        "components": {
            "view": true,
            "create": false,
            "edit": false,
            "delete": false,
            "export": false
        },
        "pages": {
            "view": true,
            "create": false,
            "edit": false,
            "delete": false
        },
        "services": {
            "view": true,
            "create": false,
            "edit": false,
            "delete": false,
            "configure": false
        },
        "filesystem": {
            "view": true,
            "create": false,
            "edit": false,
            "delete": false,
            "upload": false,
            "download": true
        },
        "database": {
            "view": true,
            "create": false,
            "edit": false,
            "delete": false
        },
        "settings": {
            "view": true,
            "edit": false
        }
    }',
    TRUE
);

UPDATE control_center_user_projects 
SET role_id = (SELECT id FROM project_roles WHERE slug = 'owner' LIMIT 1)
WHERE role_id IS NULL;
