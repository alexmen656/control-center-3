-- App Store Metadata Manager Schema
-- This module allows managing App Store Connect apps and their localized metadata

-- Table to store connected apps
CREATE TABLE IF NOT EXISTS appstore_apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    app_id VARCHAR(255) NOT NULL COMMENT 'Apple App Store App ID',
    bundle_id VARCHAR(255) NOT NULL COMMENT 'App Bundle Identifier',
    name VARCHAR(255) NOT NULL COMMENT 'Primary App Name',
    sku VARCHAR(255) COMMENT 'App SKU',
    primary_locale VARCHAR(10) DEFAULT 'en-US' COMMENT 'Primary Locale',
    content_rights_declaration ENUM('doesNotUseThirdPartyContent', 'usesThirdPartyContent') DEFAULT NULL,
    is_available_in_new_territories BOOLEAN DEFAULT TRUE,
    status ENUM('draft', 'ready_for_submission', 'in_review', 'approved', 'rejected', 'live') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_project_app (project_id, app_id),
    INDEX idx_project (project_id),
    INDEX idx_bundle (bundle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store app versions
CREATE TABLE IF NOT EXISTS appstore_app_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    version_string VARCHAR(50) NOT NULL COMMENT 'Version number (e.g., 1.0.0)',
    build_number VARCHAR(50) COMMENT 'Build number',
    platform ENUM('iOS', 'macOS', 'tvOS', 'watchOS', 'visionOS') DEFAULT 'iOS',
    release_type ENUM('manual', 'afterApproval', 'scheduled') DEFAULT 'afterApproval',
    earliest_release_date DATETIME DEFAULT NULL COMMENT 'For scheduled releases',
    copyright VARCHAR(255) COMMENT 'Copyright text',
    review_notes TEXT COMMENT 'Notes for app reviewers',
    status ENUM('draft', 'ready_for_submission', 'waiting_for_review', 'in_review', 'approved', 'rejected', 'ready_for_sale') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES appstore_apps(id) ON DELETE CASCADE,
    INDEX idx_app (app_id),
    INDEX idx_version (version_string)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store localized app metadata
CREATE TABLE IF NOT EXISTS appstore_app_localizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    locale VARCHAR(10) NOT NULL COMMENT 'Locale code (e.g., en-US, de-DE)',
    name VARCHAR(255) COMMENT 'Localized App Name',
    subtitle VARCHAR(30) COMMENT 'App Subtitle (max 30 chars)',
    privacy_policy_url VARCHAR(500) COMMENT 'Privacy Policy URL',
    privacy_policy_text TEXT COMMENT 'Privacy Policy Text',
    privacy_choices_url VARCHAR(500) COMMENT 'Privacy Choices URL',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES appstore_apps(id) ON DELETE CASCADE,
    UNIQUE KEY unique_app_locale (app_id, locale),
    INDEX idx_app (app_id),
    INDEX idx_locale (locale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store version-specific localized metadata
CREATE TABLE IF NOT EXISTS appstore_version_localizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    locale VARCHAR(10) NOT NULL COMMENT 'Locale code',
    description TEXT COMMENT 'App Description (max 4000 chars)',
    keywords VARCHAR(100) COMMENT 'Keywords (max 100 chars, comma separated)',
    whats_new TEXT COMMENT 'Release Notes / What is New',
    marketing_url VARCHAR(500) COMMENT 'Marketing URL',
    support_url VARCHAR(500) COMMENT 'Support URL',
    promotional_text VARCHAR(170) COMMENT 'Promotional Text (max 170 chars)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (version_id) REFERENCES appstore_app_versions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_version_locale (version_id, locale),
    INDEX idx_version (version_id),
    INDEX idx_locale (locale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store app screenshots and preview videos
CREATE TABLE IF NOT EXISTS appstore_screenshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    locale VARCHAR(10) NOT NULL,
    display_type ENUM(
        'APP_IPHONE_67',
        'APP_IPHONE_65',
        'APP_IPHONE_61',
        'APP_IPHONE_58',
        'APP_IPHONE_55',
        'APP_IPHONE_47',
        'APP_IPHONE_40',
        'APP_IPHONE_35',
        'APP_IPAD_PRO_3GEN_129',
        'APP_IPAD_PRO_3GEN_11',
        'APP_IPAD_PRO_129',
        'APP_IPAD_105',
        'APP_IPAD_97',
        'APP_WATCH_ULTRA',
        'APP_WATCH_SERIES_7',
        'APP_WATCH_SERIES_4',
        'APP_WATCH_SERIES_3',
        'APP_DESKTOP',
        'APP_APPLE_TV'
    ) NOT NULL COMMENT 'Screenshot display type/device',
    asset_type ENUM('screenshot', 'preview') DEFAULT 'screenshot',
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT COMMENT 'File size in bytes',
    width INT COMMENT 'Image width',
    height INT COMMENT 'Image height',
    position INT DEFAULT 0 COMMENT 'Order position',
    preview_frame_time_code VARCHAR(20) COMMENT 'For video previews',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (version_id) REFERENCES appstore_app_versions(id) ON DELETE CASCADE,
    INDEX idx_version_locale (version_id, locale),
    INDEX idx_display_type (display_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store App Store Connect API credentials
CREATE TABLE IF NOT EXISTS appstore_api_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    issuer_id VARCHAR(100) NOT NULL COMMENT 'App Store Connect Issuer ID',
    key_id VARCHAR(20) NOT NULL COMMENT 'API Key ID',
    private_key TEXT NOT NULL COMMENT 'Private Key (.p8 content, encrypted)',
    vendor_number VARCHAR(20) COMMENT 'Vendor Number for reports',
    is_active BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_project (project_id),
    INDEX idx_issuer (issuer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store app categories
CREATE TABLE IF NOT EXISTS appstore_app_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    category_type ENUM('primary', 'secondary') NOT NULL,
    category_id VARCHAR(100) NOT NULL COMMENT 'Apple Category ID',
    category_name VARCHAR(255) NOT NULL COMMENT 'Category Name',
    subcategory_id VARCHAR(100) COMMENT 'Subcategory ID',
    subcategory_name VARCHAR(255) COMMENT 'Subcategory Name',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES appstore_apps(id) ON DELETE CASCADE,
    UNIQUE KEY unique_app_category_type (app_id, category_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for age rating declarations
CREATE TABLE IF NOT EXISTS appstore_age_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    alcohol_tobacco_or_drug_use_or_references ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    contests ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    gambling_simulated ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    gambling BOOLEAN DEFAULT FALSE,
    horror_fear_themes ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    mature_suggestive_themes ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    medical_treatment_info ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    profanity_or_crude_humor ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    sexual_content_graphic_nudity ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    sexual_content_or_nudity ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    violence_cartoon_or_fantasy ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    violence_realistic ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    violence_realistic_prolonged_graphic ENUM('NONE', 'INFREQUENT_OR_MILD', 'FREQUENT_OR_INTENSE') DEFAULT 'NONE',
    unrestricted_web_access BOOLEAN DEFAULT FALSE,
    kids_band ENUM('NOT_MADE_FOR_KIDS', 'FIVE_AND_UNDER', 'SIX_TO_EIGHT', 'NINE_TO_ELEVEN') DEFAULT 'NOT_MADE_FOR_KIDS',
    seventeen_plus BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES appstore_apps(id) ON DELETE CASCADE,
    UNIQUE KEY unique_app (app_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to log sync operations
CREATE TABLE IF NOT EXISTS appstore_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    app_id INT,
    operation ENUM('pull', 'push', 'create_app', 'create_version', 'upload_screenshot', 'submit_review') NOT NULL,
    status ENUM('started', 'success', 'failed') NOT NULL,
    details TEXT COMMENT 'JSON details of the operation',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project (project_id),
    INDEX idx_app (app_id),
    INDEX idx_operation (operation),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supported locales reference table
CREATE TABLE IF NOT EXISTS appstore_supported_locales (
    code VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    native_name VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert supported locales
INSERT IGNORE INTO appstore_supported_locales (code, name, native_name) VALUES
('ar-SA', 'Arabic', 'العربية'),
('ca', 'Catalan', 'Català'),
('cs', 'Czech', 'Čeština'),
('da', 'Danish', 'Dansk'),
('de-DE', 'German', 'Deutsch'),
('el', 'Greek', 'Ελληνικά'),
('en-AU', 'English (Australia)', 'English (Australia)'),
('en-CA', 'English (Canada)', 'English (Canada)'),
('en-GB', 'English (UK)', 'English (UK)'),
('en-US', 'English (US)', 'English (US)'),
('es-ES', 'Spanish (Spain)', 'Español (España)'),
('es-MX', 'Spanish (Mexico)', 'Español (México)'),
('fi', 'Finnish', 'Suomi'),
('fr-CA', 'French (Canada)', 'Français (Canada)'),
('fr-FR', 'French (France)', 'Français (France)'),
('he', 'Hebrew', 'עברית'),
('hi', 'Hindi', 'हिन्दी'),
('hr', 'Croatian', 'Hrvatski'),
('hu', 'Hungarian', 'Magyar'),
('id', 'Indonesian', 'Bahasa Indonesia'),
('it', 'Italian', 'Italiano'),
('ja', 'Japanese', '日本語'),
('ko', 'Korean', '한국어'),
('ms', 'Malay', 'Bahasa Melayu'),
('nl-NL', 'Dutch', 'Nederlands'),
('no', 'Norwegian', 'Norsk'),
('pl', 'Polish', 'Polski'),
('pt-BR', 'Portuguese (Brazil)', 'Português (Brasil)'),
('pt-PT', 'Portuguese (Portugal)', 'Português (Portugal)'),
('ro', 'Romanian', 'Română'),
('ru', 'Russian', 'Русский'),
('sk', 'Slovak', 'Slovenčina'),
('sv', 'Swedish', 'Svenska'),
('th', 'Thai', 'ไทย'),
('tr', 'Turkish', 'Türkçe'),
('uk', 'Ukrainian', 'Українська'),
('vi', 'Vietnamese', 'Tiếng Việt'),
('zh-Hans', 'Chinese (Simplified)', '简体中文'),
('zh-Hant', 'Chinese (Traditional)', '繁體中文');
