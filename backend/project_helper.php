<?php

function createFileSystemMainDir($projectID)
{
    $dir = "/data/project_filesystems/" . $projectID;
    if (!mkdir($dir, 0777, true)) {
        return false;
    }

    return chmod($dir, 0777);
}

function createFileSystem($projectID)
{
    if (!query("INSERT INTO project_filesystem VALUES (0, '', '', NULL, 0, '$projectID')")) {
        return false;
    }

    return createFileSystemMainDir($projectID);
}
function getProjectByLink($link)
{
    $link = escape_string($link);
    $query = query("SELECT * FROM projects WHERE link='$link'");

    return (mysqli_num_rows($query) == 1) ? fetch_assoc($query) : null;
}

function getProjectByID($projectID)
{
    $projectID = escape_string($projectID);
    $query = query("SELECT * FROM projects WHERE projectID='$projectID'");

    return (mysqli_num_rows($query) == 1) ? fetch_assoc($query) : null;
}

function getUserByToken($token)
{
    $token = escape_string($token);
    $data = query("SELECT * FROM control_center_users WHERE loginToken='$token'");

    return (mysqli_num_rows($data) == 1) ? fetch_assoc($data) : null;
}

function getUsersByProjectID($projectID)
{
    $users = query("SELECT * FROM control_center_user_projects WHERE projectID='$projectID'");
    $result = [];

    if (mysqli_num_rows($users) > 0) {
        foreach ($users as $user) {
            $userID = $user['userID'];
            $userData = fetch_assoc(query("SELECT * FROM control_center_users WHERE userID='$userID'"));

            if ($userData) {
                $result[] = [
                    'id' => $userData['userID'],
                    'name' => $userData['firstname'] . " " . $userData['lastname'],
                    'email' => $userData['email']
                ];
            }
        }
    }

    return $result;
}

function getProjectViewsByProjectID($projectID)
{
    $views = query("SELECT * FROM control_center_project_views WHERE projectID='$projectID'");
    $result = [];

    if (mysqli_num_rows($views) > 0) {
        foreach ($views as $view) {
            $viewID = $view['pageID'];
            $viewData = fetch_assoc(query("SELECT * FROM control_center_pages WHERE id='$viewID'"));

            if ($viewData) {
                $result[] = [
                    'id' => $viewData['id'],
                    'name' => $viewData['title'],
                    'url' => $viewData['url'],
                    'icon' => $viewData['icon']
                ];
            }
        }
    }

    return $result;
}

/**
 * Ruft alle Projekte eines Benutzers ab
 *
 * @param int $userID Die Benutzer-ID
 * @return array Ein Array mit Projektdaten
 */
function getUserProjectsByUserID($userID)
{
    $projects = query("SELECT * FROM control_center_user_projects WHERE userID='$userID'");
    $result = [];

    foreach ($projects as $p) {
        $projectID = $p['projectID'];
        $project = query("SELECT * FROM projects WHERE projectID='$projectID'");

        if (mysqli_num_rows($project) == 1) {
            $projectData = fetch_assoc($project);
            $result[] = [
                "id" => $projectData['id'],
                "icon" => $projectData['icon'],
                "name" => $projectData['name'],
                "link" => $projectData['link'],
                "hidden" => isset($projectData['hidden']) ? (bool)$projectData['hidden'] : false,
                "createdOn" => isset($projectData['createdOn']) ? $projectData['createdOn'] : null
            ];
        }
    }

    return $result;
}

function jsonResponse($data, $success = true)
{
    if ($success) {
        if (is_string($data)) {
            $response = ['success' => true, 'message' => $data];
        } else {
            $response = array_merge(['success' => true], $data);
        }
    } else {
        $response = ['success' => false, 'message' => $data];
    }

    return echoJson($response);
}

function projectExists($name)
{
    $name = escape_string($name);
    $href = str_replace("\\", "", createLink($name));
    $check = query("SELECT * FROM projects WHERE link='$href' OR name='$name'");

    return mysqli_num_rows($check) > 0;
}

function createProjectDirectories($href, $name, $projectID)
{
    global $userID; // Make userID available

    // Create web directory
    if (!mkdir("/www/" . $href, 0777, true) || !chmod("/www/" . $href, 0777)) {
        return false;
    }

    // Template-Dateien erstellen
    $indexContent = str_replace(
        array('[{[pLink]}]', '[{[pName]}]', '[{[pID]}]'),
        array($href, $name, $projectID),
        file_get_contents("templates/website/index.php")
    );

    file_put_contents("/www/" . $href . "/index.php", $indexContent, 0777);
    chmod("/www/" . $href . "/index.php", 0777);
    file_put_contents("/www/" . $href . "/main.php", "//Put here main content of your site", 0777);
    chmod("/www/" . $href . "/main.php", 0777);

    // Create Monaco IDE data directory
    createMonacoProjectDirectory($href, $name, $userID, $projectID);

    return true;
}

/**
 * Creates a Monaco IDE project directory structure
 */
function createMonacoProjectDirectory($href, $name, $userID, $projectID = 1)
{
    // Create project data directory for Monaco IDE
    $dataDir = __DIR__ . "/../data/projects/" . $userID . "/" . $href;

    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }

    // Initialize Monaco IDE metadata instead of git
    if (!file_exists($dataDir . '/.monaco_initialized')) {
        // Create initial Monaco IDE metadata files
        file_put_contents($dataDir . '/.monaco_initialized', json_encode([
            'initialized' => true,
            'created' => date('c'),
            'project_name' => $name
        ]));

        file_put_contents($dataDir . '/.monaco_staged.json', '{}');
        file_put_contents($dataDir . '/.monaco_lastcommit.json', '{}');
        file_put_contents($dataDir . '/.monaco_commits.json', '[]');

        // Create initial files
        //file_put_contents($dataDir . '/README.md', "# " . $name . "\n\nCreated with Control Center IDE\n");
        file_put_contents($dataDir . '/main.js', "// Welcome to " . $name . "\nconsole.log('Hello World!');\n");
        file_put_contents($dataDir . '/style.css', "/* Styles for " . $name . " */\nbody {\n    font-family: Arial, sans-serif;\n}\n");
        file_put_contents($dataDir . '/index.html', "<!DOCTYPE html>\n<html>\n<head>\n    <title>" . $name . "</title>\n    <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n    <h1>Welcome to " . $name . "</h1>\n    <script src=\"main.js\"></script>\n</body>\n</html>");
        $vercelConfig = [
            "version" => 2,
            "builds" => [
                [
                    "src" => "main.js",
                    "use" => "@vercel/node"
                ]
            ],
            "routes" => [
                [
                    "src" => "/(.*)",
                    "dest" => "/main.js"
                ]
            ]
        ];
        file_put_contents($dataDir . '/vercel.json', json_encode($vercelConfig, JSON_PRETTY_PRINT));

        $packageConfig = [
            "name" => strtolower(str_replace([' ', '-'], ['_', '_'], $name)),
            "version" => "1.0.0",
            "main" => "main.js",
            "scripts" => [
                "start" => "node main.js",
                "test" => "echo \"Error: no test specified\" && exit 1"
            ],
            "author" => "Control Center IDE",
            "license" => "ISC",
            "description" => "A project created with Control Center IDE",
            "dependencies" => [
                "vercel" => "^41.7.0"
            ],
            "devDependencies" => [
                "vite" => "^4.0.0"
            ]
        ];
        file_put_contents($dataDir . '/package.json', json_encode($packageConfig, JSON_PRETTY_PRINT));

        $viteConfig = <<<JS
                        import { defineConfig } from "vite";
                        import path from "path";

                        export default defineConfig({
                            resolve: {
                                alias: {
                                    apis: path.resolve(__dirname, ".monaco_apis/index.js")
                                }
                            }
                        });
                        JS;

        file_put_contents($dataDir . '/vite.config.js', $viteConfig);


        // Create initial commit metadata
        /* $initialCommit = [
            'hash' => 'initial-' . time(),
            'short_hash' => 'initial',
            'author' => 'Control Center IDE',
            'email' => 'ide@controlcenter.dev',
            'date' => date('c'),
            'message' => 'Initial project setup',
            'files' => [
                // ['path' => 'README.md'],
                ['path' => 'main.js'],
                ['path' => 'style.css'],
                ['path' => 'index.html'],
                ['path' => 'vercel.json'],
                ['path' => 'package.json'],
                ['path' => 'vite.config.js'],
            ],
            'parents' => []
        ];

        file_put_contents($dataDir . '/.monaco_commits.json', json_encode([$initialCommit], JSON_PRETTY_PRINT));*/

        // Set last commit state
        $lastCommit = [];
        $files = ['main.js', 'style.css', 'index.html', 'vercel.json', 'package.json', 'vite.config.js']; //'README.md', 
        foreach ($files as $file) {
            $lastCommit[$file] = md5(file_get_contents($dataDir . '/' . $file));
        }
        file_put_contents($dataDir . '/.monaco_lastcommit.json', json_encode($lastCommit, JSON_PRETTY_PRINT));

        // Create CMS APIs setup and copy subscribed APIs
        createCMSAPISetup($dataDir, $projectID);
    }
}

/**
 * Creates the CMS APIs setup for a Monaco project
 */
function createCMSAPISetup($projectDir, $projectID)
{
    // Create .monaco_apis directory
    $apisDir = $projectDir . '/.monaco_apis';
    if (!is_dir($apisDir)) {
        mkdir($apisDir, 0777, true);
    }

    // Create the main APIs index file (empty initially)
    $indexContent = '// CMS APIs Integration
// This file contains all subscribed APIs for your project
// Subscribe to APIs in the main Control Center to get access

// No APIs are currently subscribed for this project
// When you subscribe to APIs, they will be available here like:
// import { UsersAPI, FilesAPI } from \'apis\';

export default {};
';

    file_put_contents($apisDir . '/index.js', $indexContent);

    // Create API configuration template
    $configContent = '// CMS API Configuration
// This file contains API keys and configuration for your project
// Keys are automatically injected at runtime

const API_CONFIG = {
  baseUrl: \'/backend/api/v1\',
  timeout: 30000,
  retries: 3,
  
  // API keys will be injected here automatically
  keys: {
    // Auto-populated based on your subscriptions
  }
};

export default API_CONFIG;
';

    file_put_contents($apisDir . '/config.js', $configContent);

    // Create a README for developers
    $readmeContent = '# CMS APIs Integration

This directory contains the CMS APIs integration for your project.

## Currently Available APIs

No APIs are currently subscribed.

## Usage

To start using APIs:
1. Go to your project in Control Center
2. Navigate to the API Management section  
3. Subscribe to the APIs you need
4. Refresh your Monaco project to get the latest SDKs

Once subscribed, import APIs like this:
```javascript
import { UsersAPI, FilesAPI } from \'apis\';

// Use the APIs
const users = await UsersAPI.getAll();
const uploadResult = await FilesAPI.upload(file);
```

## Files in this directory

- `index.js` - Main API exports (auto-generated when APIs are subscribed)
- `config.js` - API configuration  
- `*SDK.js` - Individual API SDKs (copied when APIs are subscribed)

## Security

- API keys are never exposed in your code
- All requests are authenticated automatically
- Rate limiting is enforced per project
';

    file_put_contents($apisDir . '/README.md', $readmeContent);
}

function addUserToProject($userID, $projectID)
{
    $userID = (int)$userID;
    $projectID = escape_string($projectID);
    $check = query("SELECT * FROM control_center_user_projects WHERE userID=$userID AND projectID='$projectID'");

    if (mysqli_num_rows($check) == 0) {
        return (bool)query("INSERT INTO control_center_user_projects VALUES (0, $userID, '$projectID', 1)");
    }

    return true;
}

function getWebBuilderUrl($projectLink, $page = '')
{
    $baseUrl = "https://web-builder.control-center.eu/";

    if (!empty($page)) {
        return $baseUrl . "editor/" . $projectLink . "/" . $page;
    }

    return $baseUrl . "project/" . $projectLink;
}

function createMonacoCodespaceDirectory($projectLink, $codespaceSlug, $codespaceName, $userID, $template, $projectID = 1)
{
    $dataDir = __DIR__ . "/../data/projects/" . $userID . "/" . $projectLink . "/" . $codespaceSlug;

    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }

    if (!file_exists($dataDir . '/.monaco_initialized')) {
        file_put_contents($dataDir . '/.monaco_initialized', json_encode([
            'codespace_name' => $codespaceName,
            'project_link' => $projectLink,
            'template' => $template,
            'created_at' => date('c')
        ]));

        file_put_contents($dataDir . '/.monaco_staged.json', '{}');
        file_put_contents($dataDir . '/.monaco_lastcommit.json', '{}');
        file_put_contents($dataDir . '/.monaco_commits.json', '[]');

        // Copy template files if template exists
        $templateDir = __DIR__ . "/templates/codespace/" . $template;
        $packageName = strtolower(str_replace([' ', '-'], ['_', '_'], $codespaceName));

        if (is_dir($templateDir)) {
            // Recursively copy template directory contents
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($templateDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $path = $item->getPathname();
                $subPath = substr($path, strlen($templateDir) + 1);
                $destPath = $dataDir . '/' . $subPath;
                if ($item->isDir()) {
                    if (!is_dir($destPath)) {
                        mkdir($destPath, 0777, true);
                    }
                } else {
                    $content = file_get_contents($path);
                    $content = str_replace('[{[codespaceName]}]', $codespaceName, $content);
                    $content = str_replace('[{[packageName]}]', $packageName, $content);
                    $content = str_replace('[{[projectLink]}]', $projectLink, $content);
                    file_put_contents($destPath, $content);
                }
            }
        } else {
            // Fallback to default vanilla-js template if template not found
            file_put_contents($dataDir . '/main.js', "// Welcome to " . $codespaceName . "\nconsole.log('Hello from " . $codespaceName . "!');\n");
            file_put_contents($dataDir . '/style.css', "/* Styles for " . $codespaceName . " */\nbody {\n    font-family: Arial, sans-serif;\n    margin: 0;\n    padding: 20px;\n}\n");
            file_put_contents($dataDir . '/index.html', "<!DOCTYPE html>\n<html>\n<head>\n    <title>" . $codespaceName . "</title>\n    <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n    <h1>Welcome to " . $codespaceName . "</h1>\n    <p>This is your new codespace!</p>\n    <script src=\"main.js\"></script>\n</body>\n</html>");

            $vercelConfig = [
                "version" => 2,
                "name" => $packageName,
                "builds" => [
                    [
                        "src" => "index.html",
                        "use" => "@vercel/static"
                    ]
                ]
            ];
            file_put_contents($dataDir . '/vercel.json', json_encode($vercelConfig, JSON_PRETTY_PRINT));

            $packageConfig = [
                "name" => $packageName,
                "version" => "1.0.0",
                "main" => "main.js",
                "scripts" => [
                    "start" => "node main.js",
                    "test" => "echo \"Error: no test specified\" && exit 1"
                ],
                "author" => "Control Center IDE",
                "license" => "ISC",
                "description" => "A codespace created with Control Center IDE",
                "dependencies" => [
                    "vercel" => "^41.7.0"
                ],
                "devDependencies" => [
                    "vite" => "^4.0.0"
                ]
            ];
            file_put_contents($dataDir . '/package.json', json_encode($packageConfig, JSON_PRETTY_PRINT));

            $viteConfig = <<<JS
import { defineConfig } from "vite";
import path from "path";

export default defineConfig({
    resolve: {
        alias: {
            apis: path.resolve(__dirname, ".monaco_apis/index.js")
        }
    }
});
JS;
            file_put_contents($dataDir . '/vite.config.js', $viteConfig);
        }

        /* $lastCommit = [];
        $createdFiles = glob($dataDir . '/*');
        foreach ($createdFiles as $file) {
            if (is_file($file) && !strpos(basename($file), '.monaco_')) {
                $fileName = basename($file);
                $lastCommit[$fileName] = md5(file_get_contents($file));
            }
        }
        file_put_contents($dataDir . '/.monaco_lastcommit.json', json_encode($lastCommit, JSON_PRETTY_PRINT));
*/
        createCMSAPISetup($dataDir, $projectID);
    }

    return true;
}
