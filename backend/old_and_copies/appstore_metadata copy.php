<?php
/**
 * App Store Metadata Manager API
 * 
 * Manages App Store Connect apps, versions, and localized metadata
 */

require_once 'config.php';
require_once 'head.php';
require_once 'db_connection.php';

// Get project from query or session (projectLink is the project identifier)
$project = $_GET['project'] ?? $_POST['project'] ?? $_SESSION['project'] ?? null;

if (!$project) {
    http_response_code(400);
    echo json_encode(['error' => 'Project is required']);
    exit;
}

// Get numeric project_id from project link
$project_stmt = $pdo->prepare("SELECT id FROM projects WHERE link = ?");
$project_stmt->execute([$project]);
$project_row = $project_stmt->fetch();
$project_id = $project_row ? (int)$project_row['id'] : null;

if (!$project_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Project not found']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        // ============================================
        // APP MANAGEMENT
        // ============================================
        case 'apps':
            handleApps($pdo, $project_id, $method);
            break;
            
        case 'app':
            $app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : null;
            handleSingleApp($pdo, $project_id, $app_id, $method);
            break;
            
        // ============================================
        // VERSION MANAGEMENT
        // ============================================
        case 'versions':
            $app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : null;
            handleVersions($pdo, $project_id, $app_id, $method);
            break;
            
        case 'version':
            $version_id = isset($_GET['version_id']) ? (int)$_GET['version_id'] : null;
            handleSingleVersion($pdo, $project_id, $version_id, $method);
            break;
            
        // ============================================
        // LOCALIZATION MANAGEMENT
        // ============================================
        case 'app_localizations':
            $app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : null;
            handleAppLocalizations($pdo, $project_id, $app_id, $method);
            break;
            
        case 'version_localizations':
            $version_id = isset($_GET['version_id']) ? (int)$_GET['version_id'] : null;
            handleVersionLocalizations($pdo, $project_id, $version_id, $method);
            break;
            
        // ============================================
        // SCREENSHOTS MANAGEMENT
        // ============================================
        case 'screenshots':
            $version_id = isset($_GET['version_id']) ? (int)$_GET['version_id'] : null;
            handleScreenshots($pdo, $project_id, $version_id, $method);
            break;
            
        // ============================================
        // API CREDENTIALS
        // ============================================
        case 'credentials':
            handleCredentials($pdo, $project_id, $method);
            break;
            
        // ============================================
        // SUPPORTED LOCALES
        // ============================================
        case 'locales':
            handleLocales($pdo, $method);
            break;
            
        // ============================================
        // CATEGORIES
        // ============================================
        case 'categories':
            $app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : null;
            handleCategories($pdo, $project_id, $app_id, $method);
            break;
            
        // ============================================
        // AGE RATINGS
        // ============================================
        case 'age_ratings':
            $app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : null;
            handleAgeRatings($pdo, $project_id, $app_id, $method);
            break;
            
        // ============================================
        // SYNC WITH APP STORE CONNECT
        // ============================================
        case 'sync_pull':
            $app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : null;
            handleSyncPull($pdo, $project_id, $app_id);
            break;
            
        case 'sync_push':
            $app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : null;
            handleSyncPush($pdo, $project_id, $app_id);
            break;
            
        // ============================================
        // DASHBOARD / OVERVIEW
        // ============================================
        case 'dashboard':
            handleDashboard($pdo, $project_id);
            break;
            
        default:
            // Return overview if no action
            handleDashboard($pdo, $project_id);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => DEBUG_MODE ? $e->getTraceAsString() : null
    ]);
}

// ============================================
// HANDLER FUNCTIONS
// ============================================

function handleApps($pdo, $project_id, $method) {
    if ($method === 'GET') {
        // List all apps for project
        $stmt = $pdo->prepare("
            SELECT a.*, 
                   COUNT(DISTINCT v.id) as version_count,
                   COUNT(DISTINCT l.id) as locale_count,
                   MAX(v.version_string) as latest_version
            FROM appstore_apps a
            LEFT JOIN appstore_app_versions v ON a.id = v.app_id
            LEFT JOIN appstore_app_localizations l ON a.id = l.app_id
            WHERE a.project_id = ?
            GROUP BY a.id
            ORDER BY a.updated_at DESC
        ");
        $stmt->execute([$project_id]);
        $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'apps' => $apps,
            'count' => count($apps)
        ]);
    } elseif ($method === 'POST') {
        // Create new app
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['app_id', 'bundle_id', 'name'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO appstore_apps (project_id, app_id, bundle_id, name, sku, primary_locale, status)
            VALUES (?, ?, ?, ?, ?, ?, 'draft')
        ");
        
        $stmt->execute([
            $project_id,
            $input['app_id'],
            $input['bundle_id'],
            $input['name'],
            $input['sku'] ?? null,
            $input['primary_locale'] ?? 'en-US'
        ]);
        
        $newId = $pdo->lastInsertId();
        
        // Create default localization for primary locale
        $primaryLocale = $input['primary_locale'] ?? 'en-US';
        $stmt = $pdo->prepare("
            INSERT INTO appstore_app_localizations (app_id, locale, name)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$newId, $primaryLocale, $input['name']]);
        
        // Log operation
        logOperation($pdo, $project_id, $newId, 'create_app', 'success', ['app_id' => $input['app_id']]);
        
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'message' => 'App created successfully'
        ]);
    }
}

function handleSingleApp($pdo, $project_id, $app_id, $method) {
    if (!$app_id) {
        http_response_code(400);
        echo json_encode(['error' => 'App ID is required']);
        return;
    }
    
    // Verify app belongs to project
    $stmt = $pdo->prepare("SELECT * FROM appstore_apps WHERE id = ? AND project_id = ?");
    $stmt->execute([$app_id, $project_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$app) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }
    
    if ($method === 'GET') {
        // Get full app details with localizations
        $stmt = $pdo->prepare("SELECT * FROM appstore_app_localizations WHERE app_id = ?");
        $stmt->execute([$app_id]);
        $localizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT * FROM appstore_app_versions WHERE app_id = ? ORDER BY created_at DESC");
        $stmt->execute([$app_id]);
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT * FROM appstore_app_categories WHERE app_id = ?");
        $stmt->execute([$app_id]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT * FROM appstore_age_ratings WHERE app_id = ?");
        $stmt->execute([$app_id]);
        $ageRating = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'app' => $app,
            'localizations' => $localizations,
            'versions' => $versions,
            'categories' => $categories,
            'age_rating' => $ageRating
        ]);
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        // Update app
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['name', 'sku', 'primary_locale', 'content_rights_declaration', 
                         'is_available_in_new_territories', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }
        
        $params[] = $app_id;
        $params[] = $project_id;
        
        $stmt = $pdo->prepare("
            UPDATE appstore_apps 
            SET " . implode(', ', $updateFields) . "
            WHERE id = ? AND project_id = ?
        ");
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'App updated successfully'
        ]);
    } elseif ($method === 'DELETE') {
        // Delete app
        $stmt = $pdo->prepare("DELETE FROM appstore_apps WHERE id = ? AND project_id = ?");
        $stmt->execute([$app_id, $project_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'App deleted successfully'
        ]);
    }
}

function handleVersions($pdo, $project_id, $app_id, $method) {
    if (!$app_id) {
        http_response_code(400);
        echo json_encode(['error' => 'App ID is required']);
        return;
    }
    
    // Verify app belongs to project
    $stmt = $pdo->prepare("SELECT id FROM appstore_apps WHERE id = ? AND project_id = ?");
    $stmt->execute([$app_id, $project_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }
    
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT v.*, 
                   COUNT(DISTINCT l.id) as locale_count,
                   COUNT(DISTINCT s.id) as screenshot_count
            FROM appstore_app_versions v
            LEFT JOIN appstore_version_localizations l ON v.id = l.version_id
            LEFT JOIN appstore_screenshots s ON v.id = s.version_id
            WHERE v.app_id = ?
            GROUP BY v.id
            ORDER BY v.created_at DESC
        ");
        $stmt->execute([$app_id]);
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'versions' => $versions
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['version_string'])) {
            http_response_code(400);
            echo json_encode(['error' => 'version_string is required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO appstore_app_versions 
            (app_id, version_string, build_number, platform, release_type, copyright, review_notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')
        ");
        
        $stmt->execute([
            $app_id,
            $input['version_string'],
            $input['build_number'] ?? null,
            $input['platform'] ?? 'iOS',
            $input['release_type'] ?? 'afterApproval',
            $input['copyright'] ?? null,
            $input['review_notes'] ?? null
        ]);
        
        $newId = $pdo->lastInsertId();
        
        // Log operation
        logOperation($pdo, $project_id, $app_id, 'create_version', 'success', [
            'version_id' => $newId,
            'version_string' => $input['version_string']
        ]);
        
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'message' => 'Version created successfully'
        ]);
    }
}

function handleSingleVersion($pdo, $project_id, $version_id, $method) {
    if (!$version_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Version ID is required']);
        return;
    }
    
    // Verify version belongs to project
    $stmt = $pdo->prepare("
        SELECT v.* FROM appstore_app_versions v
        JOIN appstore_apps a ON v.app_id = a.id
        WHERE v.id = ? AND a.project_id = ?
    ");
    $stmt->execute([$version_id, $project_id]);
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$version) {
        http_response_code(404);
        echo json_encode(['error' => 'Version not found']);
        return;
    }
    
    if ($method === 'GET') {
        // Get full version details with localizations
        $stmt = $pdo->prepare("SELECT * FROM appstore_version_localizations WHERE version_id = ?");
        $stmt->execute([$version_id]);
        $localizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT * FROM appstore_screenshots WHERE version_id = ? ORDER BY locale, display_type, position");
        $stmt->execute([$version_id]);
        $screenshots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'version' => $version,
            'localizations' => $localizations,
            'screenshots' => $screenshots
        ]);
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['version_string', 'build_number', 'platform', 'release_type',
                         'earliest_release_date', 'copyright', 'review_notes', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }
        
        $params[] = $version_id;
        
        $stmt = $pdo->prepare("
            UPDATE appstore_app_versions 
            SET " . implode(', ', $updateFields) . "
            WHERE id = ?
        ");
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Version updated successfully'
        ]);
    } elseif ($method === 'DELETE') {
        $stmt = $pdo->prepare("DELETE FROM appstore_app_versions WHERE id = ?");
        $stmt->execute([$version_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Version deleted successfully'
        ]);
    }
}

function handleAppLocalizations($pdo, $project_id, $app_id, $method) {
    if (!$app_id) {
        http_response_code(400);
        echo json_encode(['error' => 'App ID is required']);
        return;
    }
    
    // Verify app belongs to project
    $stmt = $pdo->prepare("SELECT id FROM appstore_apps WHERE id = ? AND project_id = ?");
    $stmt->execute([$app_id, $project_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }
    
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT l.*, s.name as locale_name, s.native_name 
            FROM appstore_app_localizations l
            LEFT JOIN appstore_supported_locales s ON l.locale = s.code
            WHERE l.app_id = ?
            ORDER BY l.locale
        ");
        $stmt->execute([$app_id]);
        $localizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'localizations' => $localizations
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['locale'])) {
            http_response_code(400);
            echo json_encode(['error' => 'locale is required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO appstore_app_localizations 
            (app_id, locale, name, subtitle, privacy_policy_url, privacy_policy_text, privacy_choices_url)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                subtitle = VALUES(subtitle),
                privacy_policy_url = VALUES(privacy_policy_url),
                privacy_policy_text = VALUES(privacy_policy_text),
                privacy_choices_url = VALUES(privacy_choices_url),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $app_id,
            $input['locale'],
            $input['name'] ?? null,
            $input['subtitle'] ?? null,
            $input['privacy_policy_url'] ?? null,
            $input['privacy_policy_text'] ?? null,
            $input['privacy_choices_url'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Localization saved successfully'
        ]);
    } elseif ($method === 'DELETE') {
        $locale = $_GET['locale'] ?? null;
        if (!$locale) {
            http_response_code(400);
            echo json_encode(['error' => 'locale is required']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM appstore_app_localizations WHERE app_id = ? AND locale = ?");
        $stmt->execute([$app_id, $locale]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Localization deleted successfully'
        ]);
    }
}

function handleVersionLocalizations($pdo, $project_id, $version_id, $method) {
    if (!$version_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Version ID is required']);
        return;
    }
    
    // Verify version belongs to project
    $stmt = $pdo->prepare("
        SELECT v.id FROM appstore_app_versions v
        JOIN appstore_apps a ON v.app_id = a.id
        WHERE v.id = ? AND a.project_id = ?
    ");
    $stmt->execute([$version_id, $project_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Version not found']);
        return;
    }
    
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT l.*, s.name as locale_name, s.native_name 
            FROM appstore_version_localizations l
            LEFT JOIN appstore_supported_locales s ON l.locale = s.code
            WHERE l.version_id = ?
            ORDER BY l.locale
        ");
        $stmt->execute([$version_id]);
        $localizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'localizations' => $localizations
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['locale'])) {
            http_response_code(400);
            echo json_encode(['error' => 'locale is required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO appstore_version_localizations 
            (version_id, locale, description, keywords, whats_new, marketing_url, support_url, promotional_text)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                description = VALUES(description),
                keywords = VALUES(keywords),
                whats_new = VALUES(whats_new),
                marketing_url = VALUES(marketing_url),
                support_url = VALUES(support_url),
                promotional_text = VALUES(promotional_text),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $version_id,
            $input['locale'],
            $input['description'] ?? null,
            $input['keywords'] ?? null,
            $input['whats_new'] ?? null,
            $input['marketing_url'] ?? null,
            $input['support_url'] ?? null,
            $input['promotional_text'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Version localization saved successfully'
        ]);
    } elseif ($method === 'DELETE') {
        $locale = $_GET['locale'] ?? null;
        if (!$locale) {
            http_response_code(400);
            echo json_encode(['error' => 'locale is required']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM appstore_version_localizations WHERE version_id = ? AND locale = ?");
        $stmt->execute([$version_id, $locale]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Version localization deleted successfully'
        ]);
    }
}

function handleScreenshots($pdo, $project_id, $version_id, $method) {
    if (!$version_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Version ID is required']);
        return;
    }
    
    // Verify version belongs to project
    $stmt = $pdo->prepare("
        SELECT v.id FROM appstore_app_versions v
        JOIN appstore_apps a ON v.app_id = a.id
        WHERE v.id = ? AND a.project_id = ?
    ");
    $stmt->execute([$version_id, $project_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Version not found']);
        return;
    }
    
    if ($method === 'GET') {
        $locale = $_GET['locale'] ?? null;
        $display_type = $_GET['display_type'] ?? null;
        
        $sql = "SELECT * FROM appstore_screenshots WHERE version_id = ?";
        $params = [$version_id];
        
        if ($locale) {
            $sql .= " AND locale = ?";
            $params[] = $locale;
        }
        if ($display_type) {
            $sql .= " AND display_type = ?";
            $params[] = $display_type;
        }
        
        $sql .= " ORDER BY locale, display_type, position";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $screenshots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'screenshots' => $screenshots
        ]);
    } elseif ($method === 'POST') {
        // Handle file upload or metadata update
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            // Handle file upload via multipart form
            $locale = $_POST['locale'] ?? 'en-US';
            $display_type = $_POST['display_type'] ?? 'APP_IPHONE_67';
            $position = (int)($_POST['position'] ?? 0);
            
            if (!isset($_FILES['screenshot'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No file uploaded']);
                return;
            }
            
            $file = $_FILES['screenshot'];
            $uploadDir = '../uploads/screenshots/' . $project_id . '/' . $version_id . '/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = uniqid() . '_' . basename($file['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $imageSize = getimagesize($filePath);
                
                $stmt = $pdo->prepare("
                    INSERT INTO appstore_screenshots 
                    (version_id, locale, display_type, asset_type, file_name, file_path, file_size, width, height, position)
                    VALUES (?, ?, ?, 'screenshot', ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $version_id,
                    $locale,
                    $display_type,
                    $fileName,
                    $filePath,
                    $file['size'],
                    $imageSize[0] ?? null,
                    $imageSize[1] ?? null,
                    $position
                ]);
                
                echo json_encode([
                    'success' => true,
                    'id' => $pdo->lastInsertId(),
                    'message' => 'Screenshot uploaded successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to upload file']);
            }
        } else {
            // Update metadata
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Screenshot ID is required']);
                return;
            }
            
            $stmt = $pdo->prepare("
                UPDATE appstore_screenshots 
                SET position = ?, display_type = ?
                WHERE id = ? AND version_id = ?
            ");
            
            $stmt->execute([
                $input['position'] ?? 0,
                $input['display_type'] ?? 'APP_IPHONE_67',
                $input['id'],
                $version_id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Screenshot updated successfully'
            ]);
        }
    } elseif ($method === 'DELETE') {
        $screenshot_id = $_GET['screenshot_id'] ?? null;
        if (!$screenshot_id) {
            http_response_code(400);
            echo json_encode(['error' => 'screenshot_id is required']);
            return;
        }
        
        // Get file path before deleting
        $stmt = $pdo->prepare("SELECT file_path FROM appstore_screenshots WHERE id = ? AND version_id = ?");
        $stmt->execute([$screenshot_id, $version_id]);
        $screenshot = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($screenshot && file_exists($screenshot['file_path'])) {
            unlink($screenshot['file_path']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM appstore_screenshots WHERE id = ? AND version_id = ?");
        $stmt->execute([$screenshot_id, $version_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Screenshot deleted successfully'
        ]);
    }
}

function handleCredentials($pdo, $project_id, $method) {
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT id, issuer_id, key_id, vendor_number, is_active, last_used_at, created_at, updated_at
            FROM appstore_api_credentials 
            WHERE project_id = ?
        ");
        $stmt->execute([$project_id]);
        $credentials = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'credentials' => $credentials,
            'has_credentials' => (bool)$credentials
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['issuer_id', 'key_id', 'private_key'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }
        
        // In production, encrypt the private key before storing
        $encryptedKey = base64_encode($input['private_key']); // Simple encoding for now
        
        $stmt = $pdo->prepare("
            INSERT INTO appstore_api_credentials 
            (project_id, issuer_id, key_id, private_key, vendor_number)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                issuer_id = VALUES(issuer_id),
                key_id = VALUES(key_id),
                private_key = VALUES(private_key),
                vendor_number = VALUES(vendor_number),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $project_id,
            $input['issuer_id'],
            $input['key_id'],
            $encryptedKey,
            $input['vendor_number'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Credentials saved successfully'
        ]);
    } elseif ($method === 'DELETE') {
        $stmt = $pdo->prepare("DELETE FROM appstore_api_credentials WHERE project_id = ?");
        $stmt->execute([$project_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Credentials deleted successfully'
        ]);
    }
}

function handleLocales($pdo, $method) {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM appstore_supported_locales WHERE is_active = 1 ORDER BY name");
        $locales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'locales' => $locales
        ]);
    }
}

function handleCategories($pdo, $project_id, $app_id, $method) {
    if (!$app_id) {
        // Return available categories list
        $categories = getAppStoreCategories();
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
        return;
    }
    
    // Verify app belongs to project
    $stmt = $pdo->prepare("SELECT id FROM appstore_apps WHERE id = ? AND project_id = ?");
    $stmt->execute([$app_id, $project_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }
    
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM appstore_app_categories WHERE app_id = ?");
        $stmt->execute([$app_id]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['category_type']) || empty($input['category_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'category_type and category_id are required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO appstore_app_categories 
            (app_id, category_type, category_id, category_name, subcategory_id, subcategory_name)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                category_id = VALUES(category_id),
                category_name = VALUES(category_name),
                subcategory_id = VALUES(subcategory_id),
                subcategory_name = VALUES(subcategory_name)
        ");
        
        $stmt->execute([
            $app_id,
            $input['category_type'],
            $input['category_id'],
            $input['category_name'] ?? '',
            $input['subcategory_id'] ?? null,
            $input['subcategory_name'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Category saved successfully'
        ]);
    }
}

function handleAgeRatings($pdo, $project_id, $app_id, $method) {
    if (!$app_id) {
        http_response_code(400);
        echo json_encode(['error' => 'App ID is required']);
        return;
    }
    
    // Verify app belongs to project
    $stmt = $pdo->prepare("SELECT id FROM appstore_apps WHERE id = ? AND project_id = ?");
    $stmt->execute([$app_id, $project_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }
    
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM appstore_age_ratings WHERE app_id = ?");
        $stmt->execute([$app_id]);
        $ageRating = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'age_rating' => $ageRating
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $fields = [
            'alcohol_tobacco_or_drug_use_or_references', 'contests', 'gambling_simulated',
            'gambling', 'horror_fear_themes', 'mature_suggestive_themes', 'medical_treatment_info',
            'profanity_or_crude_humor', 'sexual_content_graphic_nudity', 'sexual_content_or_nudity',
            'violence_cartoon_or_fantasy', 'violence_realistic', 'violence_realistic_prolonged_graphic',
            'unrestricted_web_access', 'kids_band', 'seventeen_plus'
        ];
        
        $insertFields = ['app_id'];
        $insertPlaceholders = ['?'];
        $insertValues = [$app_id];
        $updateParts = [];
        
        foreach ($fields as $field) {
            if (isset($input[$field])) {
                $insertFields[] = $field;
                $insertPlaceholders[] = '?';
                $insertValues[] = $input[$field];
                $updateParts[] = "$field = VALUES($field)";
            }
        }
        
        $sql = "INSERT INTO appstore_age_ratings (" . implode(', ', $insertFields) . ") 
                VALUES (" . implode(', ', $insertPlaceholders) . ")
                ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts) . ", updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($insertValues);
        
        echo json_encode([
            'success' => true,
            'message' => 'Age rating saved successfully'
        ]);
    }
}

function handleSyncPull($pdo, $project_id, $app_id) {
    // This would connect to App Store Connect API and pull data
    // For now, return a placeholder response
    
    logOperation($pdo, $project_id, $app_id, 'pull', 'started', []);
    
    // TODO: Implement actual API call to App Store Connect
    // This requires JWT authentication with the stored credentials
    
    echo json_encode([
        'success' => true,
        'message' => 'Sync pull initiated. This feature requires App Store Connect API implementation.',
        'status' => 'pending'
    ]);
}

function handleSyncPush($pdo, $project_id, $app_id) {
    // This would push local changes to App Store Connect
    
    logOperation($pdo, $project_id, $app_id, 'push', 'started', []);
    
    // TODO: Implement actual API call to App Store Connect
    
    echo json_encode([
        'success' => true,
        'message' => 'Sync push initiated. This feature requires App Store Connect API implementation.',
        'status' => 'pending'
    ]);
}

function handleDashboard($pdo, $project_id) {
    // Get overview stats
    $stats = [];
    
    // Total apps
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appstore_apps WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $stats['total_apps'] = (int)$stmt->fetchColumn();
    
    // Total versions
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM appstore_app_versions v
        JOIN appstore_apps a ON v.app_id = a.id
        WHERE a.project_id = ?
    ");
    $stmt->execute([$project_id]);
    $stats['total_versions'] = (int)$stmt->fetchColumn();
    
    // Total localizations
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT l.locale) FROM appstore_app_localizations l
        JOIN appstore_apps a ON l.app_id = a.id
        WHERE a.project_id = ?
    ");
    $stmt->execute([$project_id]);
    $stats['total_locales'] = (int)$stmt->fetchColumn();
    
    // Has credentials
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appstore_api_credentials WHERE project_id = ? AND is_active = 1");
    $stmt->execute([$project_id]);
    $stats['has_credentials'] = (int)$stmt->fetchColumn() > 0;
    
    // Recent apps
    $stmt = $pdo->prepare("
        SELECT a.*, COUNT(DISTINCT v.id) as version_count
        FROM appstore_apps a
        LEFT JOIN appstore_app_versions v ON a.id = v.app_id
        WHERE a.project_id = ?
        GROUP BY a.id
        ORDER BY a.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([$project_id]);
    $recentApps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent activity
    $stmt = $pdo->prepare("
        SELECT * FROM appstore_sync_log
        WHERE project_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$project_id]);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'recent_apps' => $recentApps,
        'recent_activity' => $recentActivity
    ]);
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function logOperation($pdo, $project_id, $app_id, $operation, $status, $details, $error = null) {
    $stmt = $pdo->prepare("
        INSERT INTO appstore_sync_log (project_id, app_id, operation, status, details, error_message)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $project_id,
        $app_id,
        $operation,
        $status,
        json_encode($details),
        $error
    ]);
}

function getAppStoreCategories() {
    return [
        ['id' => 'BOOKS', 'name' => 'Books'],
        ['id' => 'BUSINESS', 'name' => 'Business'],
        ['id' => 'DEVELOPER_TOOLS', 'name' => 'Developer Tools'],
        ['id' => 'EDUCATION', 'name' => 'Education'],
        ['id' => 'ENTERTAINMENT', 'name' => 'Entertainment'],
        ['id' => 'FINANCE', 'name' => 'Finance'],
        ['id' => 'FOOD_AND_DRINK', 'name' => 'Food & Drink'],
        ['id' => 'GAMES', 'name' => 'Games', 'subcategories' => [
            ['id' => 'GAMES_ACTION', 'name' => 'Action'],
            ['id' => 'GAMES_ADVENTURE', 'name' => 'Adventure'],
            ['id' => 'GAMES_BOARD', 'name' => 'Board'],
            ['id' => 'GAMES_CARD', 'name' => 'Card'],
            ['id' => 'GAMES_CASINO', 'name' => 'Casino'],
            ['id' => 'GAMES_CASUAL', 'name' => 'Casual'],
            ['id' => 'GAMES_FAMILY', 'name' => 'Family'],
            ['id' => 'GAMES_PUZZLE', 'name' => 'Puzzle'],
            ['id' => 'GAMES_RACING', 'name' => 'Racing'],
            ['id' => 'GAMES_ROLE_PLAYING', 'name' => 'Role Playing'],
            ['id' => 'GAMES_SIMULATION', 'name' => 'Simulation'],
            ['id' => 'GAMES_SPORTS', 'name' => 'Sports'],
            ['id' => 'GAMES_STRATEGY', 'name' => 'Strategy'],
            ['id' => 'GAMES_TRIVIA', 'name' => 'Trivia'],
            ['id' => 'GAMES_WORD', 'name' => 'Word'],
        ]],
        ['id' => 'GRAPHICS_AND_DESIGN', 'name' => 'Graphics & Design'],
        ['id' => 'HEALTH_AND_FITNESS', 'name' => 'Health & Fitness'],
        ['id' => 'LIFESTYLE', 'name' => 'Lifestyle'],
        ['id' => 'MAGAZINES_AND_NEWSPAPERS', 'name' => 'Magazines & Newspapers'],
        ['id' => 'MEDICAL', 'name' => 'Medical'],
        ['id' => 'MUSIC', 'name' => 'Music'],
        ['id' => 'NAVIGATION', 'name' => 'Navigation'],
        ['id' => 'NEWS', 'name' => 'News'],
        ['id' => 'PHOTO_AND_VIDEO', 'name' => 'Photo & Video'],
        ['id' => 'PRODUCTIVITY', 'name' => 'Productivity'],
        ['id' => 'REFERENCE', 'name' => 'Reference'],
        ['id' => 'SHOPPING', 'name' => 'Shopping'],
        ['id' => 'SOCIAL_NETWORKING', 'name' => 'Social Networking'],
        ['id' => 'SPORTS', 'name' => 'Sports'],
        ['id' => 'TRAVEL', 'name' => 'Travel'],
        ['id' => 'UTILITIES', 'name' => 'Utilities'],
        ['id' => 'WEATHER', 'name' => 'Weather'],
    ];
}

// Define DEBUG_MODE if not defined
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}
?>
