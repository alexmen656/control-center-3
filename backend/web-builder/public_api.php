<?php
/**
 * Public API for Web Builder Vue Sites
 *
 * This API runs on the Fringelo server and provides
 * dynamic data to Vue-based websites hosted elsewhere.
 *
 * No authentication required - public read-only access.
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Cache-Control: public, max-age=60'); // Cache for 1 minute

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Include database connection
include_once __DIR__ . '/../config.php';
require_once '/www/paxar/components/php_head.php';

if (isset($con)) {
    mysqli_set_charset($con, "utf8mb4");
}

$action = $_GET['action'] ?? '';
$projectSlug = $_GET['project'] ?? '';

if (empty($projectSlug)) {
    echo json_encode(['success' => false, 'message' => 'Project slug required'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate project slug
if (!preg_match('/^[a-z0-9-]+$/', $projectSlug)) {
    echo json_encode(['success' => false, 'message' => 'Invalid project slug'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Get web builder project ID
$projectResult = query("SELECT wb.id, wb.project_id, p.link
                        FROM control_center_modul_web_builder_projects wb
                        JOIN projects p ON wb.project_id = p.projectID OR wb.project_id = p.link
                        WHERE p.link = '" . escape_string($projectSlug) . "'
                        LIMIT 1");

if (!$projectResult || mysqli_num_rows($projectResult) === 0) {
    echo json_encode(['success' => false, 'message' => 'Project not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$project = fetch_assoc($projectResult);
$wbProjectId = $project['id'];

switch ($action) {
    case 'page':
        handleGetPage($wbProjectId);
        break;
    case 'pages':
        handleGetPages($wbProjectId);
        break;
    case 'table':
        handleGetTable();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action. Available: page, pages, table'], JSON_UNESCAPED_UNICODE);
}

/**
 * Get a single page with its components
 */
function handleGetPage($wbProjectId)
{
    $slug = $_GET['slug'] ?? '';

    if (empty($slug)) {
        echo json_encode(['success' => false, 'message' => 'Slug required'], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Get page - handle 'home' slug specially
    if ($slug === 'home') {
        $pageResult = query("SELECT * FROM control_center_modul_web_builder_pages
                             WHERE project_id = $wbProjectId AND is_home = 1
                             LIMIT 1");
    } else {
        $pageResult = query("SELECT * FROM control_center_modul_web_builder_pages
                             WHERE project_id = $wbProjectId
                             AND slug = '" . escape_string($slug) . "'
                             LIMIT 1");
    }

    // Fallback: if no home page found, get first page
    if ((!$pageResult || mysqli_num_rows($pageResult) === 0) && $slug === 'home') {
        $pageResult = query("SELECT * FROM control_center_modul_web_builder_pages
                             WHERE project_id = $wbProjectId
                             ORDER BY id ASC
                             LIMIT 1");
    }

    if (!$pageResult || mysqli_num_rows($pageResult) === 0) {
        echo json_encode(['success' => false, 'message' => 'Page not found'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $page = fetch_assoc($pageResult);
    $pageId = $page['id'];

    // Get components
    $componentsResult = query("SELECT component_id, html_code, position
                               FROM control_center_modul_web_builder_components
                               WHERE page_id = $pageId
                               ORDER BY position ASC");

    $components = [];
    if ($componentsResult) {
        while ($comp = fetch_assoc($componentsResult)) {
            $components[] = [
                'id' => $comp['component_id'],
                'html' => $comp['html_code'],
                'position' => (int)$comp['position']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $page['id'],
            'name' => $page['name'],
            'slug' => $page['slug'],
            'title' => $page['title'] ?: $page['name'],
            'meta_description' => $page['meta_description'] ?: '',
            'is_home' => (bool)$page['is_home'],
            'components' => $components
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get all pages for navigation
 */
function handleGetPages($wbProjectId)
{
    $pagesResult = query("SELECT id, name, slug, title, is_home
                          FROM control_center_modul_web_builder_pages
                          WHERE project_id = $wbProjectId
                          ORDER BY is_home DESC, name ASC");

    $pages = [];
    if ($pagesResult) {
        while ($page = fetch_assoc($pagesResult)) {
            $pages[] = [
                'id' => $page['id'],
                'name' => $page['name'],
                'slug' => $page['slug'],
                'title' => $page['title'] ?: $page['name'],
                'path' => $page['is_home'] ? '/' : '/' . $page['slug'],
                'is_home' => (bool)$page['is_home']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $pages
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get data from a CC Forms table (for dynamic content)
 */
function handleGetTable()
{
    $table = $_GET['table'] ?? '';
    $filter = $_GET['filter'] ?? '';
    $sort = $_GET['sort'] ?? 'id';
    $order = strtoupper($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    $limit = intval($_GET['limit'] ?? 0);

    if (empty($table)) {
        echo json_encode(['success' => false, 'message' => 'Table name required'], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Sanitize table name (security: only alphanumeric and underscore)
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    // Check if table exists
    $tableExists = query("SHOW TABLES LIKE '$table'");
    if (!$tableExists || mysqli_num_rows($tableExists) === 0) {
        // Return empty array instead of error (table might not exist yet)
        echo json_encode(['success' => true, 'data' => [], 'count' => 0], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Build query
    $sql = "SELECT * FROM `$table`";

    // Add filter if provided (format: key=value)
    if ($filter) {
        $filterParts = explode('=', $filter, 2);
        if (count($filterParts) === 2) {
            $filterKey = preg_replace('/[^a-zA-Z0-9_]/', '', $filterParts[0]);
            $filterVal = escape_string($filterParts[1]);
            $sql .= " WHERE `$filterKey` = '$filterVal'";
        }
    }

    // Add sorting
    $sort = preg_replace('/[^a-zA-Z0-9_]/', '', $sort);
    $sql .= " ORDER BY `$sort` $order";

    // Add limit
    if ($limit > 0) {
        $sql .= " LIMIT $limit";
    }

    $result = query($sql);

    $data = [];
    if ($result) {
        while ($row = fetch_assoc($result)) {
            $data[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ], JSON_UNESCAPED_UNICODE);
}
