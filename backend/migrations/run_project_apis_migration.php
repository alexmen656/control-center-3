<?php
require_once __DIR__ . '/head.php';

// Include the SQL file and execute it
$sql_file = __DIR__ . '/create_project_apis_table.sql';

if (!file_exists($sql_file)) {
    die("SQL file not found: " . $sql_file);
}

$sql_content = file_get_contents($sql_file);

// Split by semicolons to handle multiple statements
$statements = explode(';', $sql_content);

$success_count = 0;
$error_count = 0;
$errors = [];

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    try {
        db_query($statement);
        $success_count++;
        echo "✓ Executed successfully: " . substr($statement, 0, 50) . "...\n";
    } catch (Exception $e) {
        $error_count++;
        $errors[] = $e->getMessage();
        echo "✗ Error executing: " . substr($statement, 0, 50) . "...\n";
        echo "  Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Migration Summary ===\n";
echo "Success: $success_count statements\n";
echo "Errors: $error_count statements\n";

if ($error_count > 0) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
    exit(1);
}

// Insert sample data
echo "\n=== Inserting Sample Data ===\n";

try {
    // Sample CMS APIs
    $sample_apis = [
        [
            'name' => 'User Management API',
            'slug' => 'user-management',
            'description' => 'Complete user management system with authentication, profiles, and permissions',
            'category' => 'Authentication',
            'version' => '1.0.0',
            'base_url' => '/api/v1/users',
            'documentation_url' => '/docs/api/users',
            'status' => 'active',
            'rate_limit_per_hour' => 1000,
            'supports_webhooks' => 1
        ],
        [
            'name' => 'File Storage API',
            'slug' => 'file-storage',
            'description' => 'Upload, manage, and serve files with built-in CDN and image processing',
            'category' => 'Storage',
            'version' => '1.0.0',
            'base_url' => '/api/v1/files',
            'documentation_url' => '/docs/api/files',
            'status' => 'active',
            'rate_limit_per_hour' => 500,
            'supports_webhooks' => 1
        ],
        [
            'name' => 'Database API',
            'slug' => 'database',
            'description' => 'Direct database operations with SQL query builder and ORM-like interface',
            'category' => 'Database',
            'version' => '1.0.0',
            'base_url' => '/api/v1/database',
            'documentation_url' => '/docs/api/database',
            'status' => 'beta',
            'rate_limit_per_hour' => 2000,
            'supports_webhooks' => 0
        ],
        [
            'name' => 'Analytics API',
            'slug' => 'analytics',
            'description' => 'Track events, generate reports, and analyze user behavior',
            'category' => 'Analytics',
            'version' => '1.0.0',
            'base_url' => '/api/v1/analytics',
            'documentation_url' => '/docs/api/analytics',
            'status' => 'active',
            'rate_limit_per_hour' => 5000,
            'supports_webhooks' => 1
        ],
        [
            'name' => 'Notification API',
            'slug' => 'notifications',
            'description' => 'Send emails, push notifications, SMS, and manage notification preferences',
            'category' => 'Communication',
            'version' => '1.0.0',
            'base_url' => '/api/v1/notifications',
            'documentation_url' => '/docs/api/notifications',
            'status' => 'active',
            'rate_limit_per_hour' => 1000,
            'supports_webhooks' => 1
        ]
    ];

    foreach ($sample_apis as $api) {
        $query = "INSERT INTO cms_apis (name, slug, description, category, version, base_url, documentation_url, status, rate_limit_per_hour, supports_webhooks, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        db_query($query, [
            $api['name'],
            $api['slug'],
            $api['description'],
            $api['category'],
            $api['version'],
            $api['base_url'],
            $api['documentation_url'],
            $api['status'],
            $api['rate_limit_per_hour'],
            $api['supports_webhooks']
        ]);
        echo "✓ Inserted API: " . $api['name'] . "\n";
    }

    // Sample endpoints for User Management API
    $user_api_id = db_insert_id();
    $endpoints = [
        [
            'method' => 'GET',
            'path' => '/users',
            'name' => 'Get All Users',
            'description' => 'Retrieve a paginated list of all users',
            'parameters' => json_encode([
                'page' => ['type' => 'integer', 'description' => 'Page number (default: 1)', 'required' => false],
                'limit' => ['type' => 'integer', 'description' => 'Items per page (default: 10, max: 100)', 'required' => false],
                'search' => ['type' => 'string', 'description' => 'Search term for filtering users', 'required' => false],
                'status' => ['type' => 'string', 'description' => 'Filter by user status (active, inactive, suspended)', 'required' => false]
            ]),
            'response_example' => json_encode([
                'users' => [
                    [
                        'userID' => '1',
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'accountStatus' => 'active',
                        'created_at' => '2024-01-15T10:30:00Z'
                    ]
                ],
                'pagination' => [
                    'total' => 150,
                    'page' => 1,
                    'limit' => 10,
                    'pages' => 15
                ]
            ])
        ],
        [
            'method' => 'GET',
            'path' => '/users/{id}',
            'name' => 'Get User by ID',
            'description' => 'Retrieve a specific user by their ID',
            'parameters' => json_encode([
                'id' => ['type' => 'integer', 'description' => 'User ID', 'required' => true, 'location' => 'path']
            ]),
            'response_example' => json_encode([
                'user' => [
                    'userID' => '1',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john@example.com',
                    'accountStatus' => 'active',
                    'created_at' => '2024-01-15T10:30:00Z',
                    'updated_at' => '2024-01-20T15:45:00Z'
                ]
            ])
        ],
        [
            'method' => 'POST',
            'path' => '/users',
            'name' => 'Create User',
            'description' => 'Create a new user account',
            'parameters' => json_encode([
                'firstName' => ['type' => 'string', 'description' => 'User first name', 'required' => true],
                'lastName' => ['type' => 'string', 'description' => 'User last name', 'required' => true],
                'email' => ['type' => 'string', 'description' => 'User email address', 'required' => true],
                'password' => ['type' => 'string', 'description' => 'User password (min 8 characters)', 'required' => true],
                'accountStatus' => ['type' => 'string', 'description' => 'Initial account status (default: active)', 'required' => false]
            ]),
            'response_example' => json_encode([
                'success' => true,
                'user' => [
                    'userID' => '151',
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                    'email' => 'jane@example.com',
                    'accountStatus' => 'active',
                    'created_at' => '2024-01-25T12:00:00Z'
                ]
            ])
        ],
        [
            'method' => 'PUT',
            'path' => '/users/{id}',
            'name' => 'Update User',
            'description' => 'Update an existing user account',
            'parameters' => json_encode([
                'id' => ['type' => 'integer', 'description' => 'User ID', 'required' => true, 'location' => 'path'],
                'firstName' => ['type' => 'string', 'description' => 'User first name', 'required' => false],
                'lastName' => ['type' => 'string', 'description' => 'User last name', 'required' => false],
                'email' => ['type' => 'string', 'description' => 'User email address', 'required' => false],
                'accountStatus' => ['type' => 'string', 'description' => 'Account status', 'required' => false]
            ]),
            'response_example' => json_encode([
                'success' => true,
                'user' => [
                    'userID' => '1',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john.doe@example.com',
                    'accountStatus' => 'active',
                    'updated_at' => '2024-01-25T14:30:00Z'
                ]
            ])
        ],
        [
            'method' => 'DELETE',
            'path' => '/users/{id}',
            'name' => 'Delete User',
            'description' => 'Delete a user account (soft delete - sets status to deleted)',
            'parameters' => json_encode([
                'id' => ['type' => 'integer', 'description' => 'User ID', 'required' => true, 'location' => 'path']
            ]),
            'response_example' => json_encode([
                'success' => true,
                'message' => 'User successfully deleted'
            ])
        ]
    ];

    // Get the first API ID (User Management API)
    $result = db_query("SELECT id FROM cms_apis WHERE slug = 'user-management' LIMIT 1");
    if ($result && $row = db_fetch_assoc($result)) {
        $api_id = $row['id'];
        
        foreach ($endpoints as $endpoint) {
            $query = "INSERT INTO cms_api_endpoints (api_id, method, path, name, description, parameters, response_example, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            db_query($query, [
                $api_id,
                $endpoint['method'],
                $endpoint['path'],
                $endpoint['name'],
                $endpoint['description'],
                $endpoint['parameters'],
                $endpoint['response_example']
            ]);
            echo "✓ Inserted endpoint: " . $endpoint['method'] . " " . $endpoint['path'] . "\n";
        }
    }

    echo "\n✓ Sample data inserted successfully!\n";

} catch (Exception $e) {
    echo "✗ Error inserting sample data: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== CMS API System Migration Complete ===\n";
echo "The CMS can now provide APIs for projects to subscribe to and use.\n";
echo "Access the API management at: /project/{project}/manage/apis\n";
echo "\nNext steps:\n";
echo "1. Visit the API management page\n";
echo "2. Subscribe to APIs for your projects\n";
echo "3. Use the API keys in your web builder or Monaco editor\n";
echo "4. Monitor API usage in the Usage & Stats tab\n";
?>
