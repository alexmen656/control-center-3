CREATE TABLE IF NOT EXISTS deployments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codespace_id INT NOT NULL,
    commit_sha VARCHAR(64) DEFAULT NULL,
    status ENUM('queued','building','ready','error','canceled') NOT NULL DEFAULT 'queued',
    runtime ENUM('static','node') NOT NULL DEFAULT 'static',
    url VARCHAR(255) DEFAULT NULL,
    internal_port INT DEFAULT NULL,
    build_log VARCHAR(255) DEFAULT NULL,
    error_msg TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ready_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_codespace (codespace_id, id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS codespace_deploy_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codespace_id INT NOT NULL UNIQUE,
    framework VARCHAR(32) DEFAULT NULL,
    install_cmd VARCHAR(255) DEFAULT NULL,
    build_cmd VARCHAR(255) DEFAULT NULL,
    output_dir VARCHAR(128) DEFAULT NULL,
    runtime ENUM('static','node') NOT NULL DEFAULT 'static',
    start_cmd VARCHAR(255) DEFAULT NULL,
    node_version VARCHAR(16) NOT NULL DEFAULT '22',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS codespace_env_vars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codespace_id INT NOT NULL,
    var_key VARCHAR(255) NOT NULL,
    value_encrypted TEXT NOT NULL,
    target ENUM('build','runtime','both') NOT NULL DEFAULT 'both',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_codespace_key (codespace_id, var_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
