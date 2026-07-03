<?php
require_once "head.php";
require_once "project_helper.php";
require_once __DIR__ . '/helpers/cloudflare.php';

function slugExists($projectID, $slug)
{
    $result = query("SELECT id FROM project_codespaces WHERE project_id='" . escape_string($projectID) . "' AND slug='" . escape_string($slug) . "'");
    return mysqli_num_rows($result) > 0;
}

if (isset($_POST['createCodespace']) && isset($_POST['project']) && isset($_POST['name'])) {
    $projectID = getProjectID(escape_string($_POST['project']));
    $name = escape_string($_POST['name']);
    $description = escape_string($_POST['description'] ?? '');
    $icon = escape_string($_POST['icon'] ?? 'code-outline');
    $language = escape_string($_POST['language'] ?? 'javascript');
    $template = escape_string($_POST['template'] ?? 'default');
    $slug = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)), '-');

    if (!checkUserProjectPermission($userID, $projectID)) {
        echo jsonResponse("No permission for this project", false);
        exit;
    }

    if (slugExists($projectID, $slug)) {
        echo jsonResponse("A codespace with this name already exists", false);
        exit;
    }

    $orderResult = query("SELECT MAX(order_index) as max_order FROM project_codespaces WHERE project_id='$projectID'");
    $maxOrder = fetch_assoc($orderResult)['max_order'] ?? 0;
    $newOrder = $maxOrder + 1;

    $result = query("INSERT INTO project_codespaces (name, slug, description, icon, language, template, project_id, user_id, order_index)
                    VALUES ('$name', '$slug', '$description', '$icon', '$language', '$template', '$projectID', '$userID', '$newOrder')");

    if ($result) {
        $codespaceId = mysqli_insert_id($GLOBALS['con']);
        $project = getProjectByID($projectID);

        if ($project) {
            createMonacoCodespaceDirectory($project['link'], $slug, $name, $userID, $template, $projectID);
        }

        if (isset($_POST['createGithubRepo']) && $_POST['createGithubRepo'] === 'true') {
            createCodespaceGithubRepo($codespaceId, $name, $userID);
        }

        if (isset($_POST['createVercelProject']) && $_POST['createVercelProject'] === 'true') {
            createCodespaceVercelProject($codespaceId, $name, $userID);
        }

        echo jsonResponse([
            'message' => 'Codespace created successfully',
            'codespace' => [
                'id' => $codespaceId,
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon,
                'language' => $language,
                'status' => 'active'
            ]
        ]);
    } else {
        echo jsonResponse("Failed to create codespace", false);
    }
} elseif (isset($_POST['updateCodespace']) && isset($_POST['codespaceID'])) {
    $codespaceID = (int)$_POST['codespaceID'];
    $name = escape_string($_POST['name'] ?? '');
    $description = escape_string($_POST['description'] ?? '');
    $icon = escape_string($_POST['icon'] ?? '');
    $language = escape_string($_POST['language'] ?? '');
    $status = escape_string($_POST['status'] ?? '');

    $codespace = fetch_assoc(query("SELECT * FROM project_codespaces WHERE id='$codespaceID'"));
    if (!$codespace || !checkUserProjectPermission($userID, $codespace['project_id'])) {
        echo jsonResponse("Codespace not found or no permission", false);
        exit;
    }

    $updates = [];
    if (!empty($name)) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');
        $updates[] = "name='$name'";
        $updates[] = "slug='$slug'";
    }
    if (!empty($description)) $updates[] = "description='$description'";
    if (!empty($icon)) $updates[] = "icon='$icon'";
    if (!empty($language)) $updates[] = "language='$language'";
    if (!empty($status)) $updates[] = "status='$status'";

    if (!empty($updates)) {
        $updateQuery = "UPDATE project_codespaces SET " . implode(', ', $updates) . " WHERE id='$codespaceID'";
        $result = query($updateQuery);

        if ($result) {
            echo jsonResponse("Codespace updated successfully");
        } else {
            echo jsonResponse("Failed to update codespace", false);
        }
    } else {
        echo jsonResponse("No fields to update", false);
    }
} elseif (isset($_POST['deleteCodespace']) && isset($_POST['codespaceID'])) {
    $codespaceID = (int)$_POST['codespaceID'];

    $codespace = fetch_assoc(query("SELECT * FROM project_codespaces WHERE id='$codespaceID'"));
    if (!$codespace || !checkUserProjectPermission($userID, $codespace['project_id'])) {
        echo jsonResponse("Codespace not found or no permission", false);
        exit;
    }

    $project = getProjectByID($codespace['project_id']);
    if ($project) {
        $codespaceDir = __DIR__ . "/../data/projects/" . $userID . "/" . $project['link'] . "/" . $codespace['slug'];
        if (is_dir($codespaceDir)) {
            deleteDirectory($codespaceDir);
        }
    }

    $result = query("DELETE FROM project_codespaces WHERE id='$codespaceID'");

    if ($result) {
        echo jsonResponse("Codespace deleted successfully");
    } else {
        echo jsonResponse("Failed to delete codespace", false);
    }
} elseif (isset($_POST['reorderCodespaces']) && isset($_POST['projectID']) && isset($_POST['codespaces'])) {
    $projectID = escape_string($_POST['projectID']);
    $codespaces = $_POST['codespaces'];

    if (!checkUserProjectPermission($userID, $projectID)) {
        echo jsonResponse("No permission for this project", false);
        exit;
    }

    foreach ($codespaces as $index => $codespaceData) {
        $codespaceID = (int)$codespaceData['id'];
        $orderIndex = (int)$index;
        query("UPDATE project_codespaces SET order_index='$orderIndex' WHERE id='$codespaceID' AND project_id='$projectID'");
    }

    echo jsonResponse("Codespaces reordered successfully");
} elseif (isset($_POST['getCodespaces']) && isset($_POST['project'])) {
    $projectID = getProjectID(escape_string($_POST['project']));

    if (!checkUserProjectPermission($userID, $projectID)) {
        echo jsonResponse("No permission for this project", false);
        exit;
    }

    $codespaces = query("SELECT * FROM project_codespaces WHERE project_id='$projectID' ORDER BY order_index ASC");
    $result = [];

    foreach ($codespaces as $codespace) {
        $result[] = [
            'id' => $codespace['id'],
            'name' => $codespace['name'],
            'slug' => $codespace['slug'],
            'description' => $codespace['description'],
            'icon' => $codespace['icon'],
            'language' => $codespace['language'],
            'template' => $codespace['template'],
            'status' => $codespace['status'],
            'created_at' => $codespace['created_at'],
            'updated_at' => $codespace['updated_at'],
            'order_index' => $codespace['order_index']
        ];
    }

    echo jsonResponse(['codespaces' => $result]);
} elseif (isset($_POST['getAvailableTemplates'])) {

    $templatesDir = __DIR__ . "/templates/codespace/";
    $templates = [];

    if (is_dir($templatesDir)) {
        $templateDirs = array_filter(scandir($templatesDir), function($item) use ($templatesDir) {
            return $item != '.' && $item != '..' && is_dir($templatesDir . $item);
        });

        foreach ($templateDirs as $templateDir) {
            $templatePath = $templatesDir . $templateDir;
            $templateInfo = [
                'id' => $templateDir,
                'name' => ucfirst(str_replace(['-', '_'], ' ', $templateDir)),
                'description' => getTemplateDescription($templateDir),
                'icon' => getTemplateIcon($templateDir)
            ];
            $templates[] = $templateInfo;
        }
    }

    if (empty($templates)) {
        $templates[] = [
            'id' => 'vanilla-js',
            'name' => 'Vanilla JavaScript',
            'description' => 'Basic HTML, CSS and JavaScript setup',
            'icon' => 'logo-javascript'
        ];
    }

    echo jsonResponse(['templates' => $templates]);
} elseif (isset($_POST['transferCodespace']) && isset($_POST['codespaceID']) && isset($_POST['targetProject'])) {
    $codespaceID = (int)$_POST['codespaceID'];
    $targetProjectLink = escape_string($_POST['targetProject']);

    $codespace = fetch_assoc(query("SELECT * FROM project_codespaces WHERE id='$codespaceID'"));
    if (!$codespace || !checkUserProjectPermission($userID, $codespace['project_id'])) {
        echo jsonResponse("Codespace not found or no permission", false);
        exit;
    }

    $targetProjectID = getProjectID($targetProjectLink);
    if (!$targetProjectID || !checkUserProjectPermission($userID, $targetProjectID)) {
        echo jsonResponse("Target project not found or no permission", false);
        exit;
    }

    $targetSlug = $codespace['slug'];
    $conflictCount = 1;
    while (slugExists($targetProjectID, $targetSlug)) {
        $targetSlug = $codespace['slug'] . '-' . $conflictCount;
        $conflictCount++;
    }

    try {

        $sourceProject = getProjectByID($codespace['project_id']);
        $targetProject = getProjectByID($targetProjectID);

        if (!$sourceProject || !$targetProject) {
            echo jsonResponse("Project data error", false);
            exit;
        }

        $sourceDir = __DIR__ . "/../data/projects/" . $userID . "/" . $sourceProject['link'] . "/" . $codespace['slug'];
        $targetDir = __DIR__ . "/../data/projects/" . $userID . "/" . $targetProject['link'] . "/" . $targetSlug;

        if (!is_dir($sourceDir)) {
            echo jsonResponse("Source codespace directory not found", false);
            exit;
        }

        if (!copyCodespaceDirectory($sourceDir, $targetDir)) {
            echo jsonResponse("Failed to copy codespace files", false);
            exit;
        }

        $orderResult = query("SELECT MAX(order_index) as max_order FROM project_codespaces WHERE project_id='$targetProjectID'");
        $maxOrder = fetch_assoc($orderResult)['max_order'] ?? 0;
        $newOrder = $maxOrder + 1;

        $newName = ($targetSlug !== $codespace['slug']) ? $codespace['name'] . ' (Copy)' : $codespace['name'];

        $insertResult = query("INSERT INTO project_codespaces (name, slug, description, icon, language, template, project_id, user_id, order_index, status)
                              VALUES ('" . escape_string($newName) . "', '" . escape_string($targetSlug) . "', '" . escape_string($codespace['description']) . "', '" . escape_string($codespace['icon']) . "', '" . escape_string($codespace['language']) . "', '" . escape_string($codespace['template']) . "', '$targetProjectID', '$userID', '$newOrder', '" . escape_string($codespace['status']) . "')");

        if (!$insertResult) {

            deleteDirectory($targetDir);
            echo jsonResponse("Failed to create codespace record", false);
            exit;
        }

        $newCodespaceID = mysqli_insert_id($GLOBALS['con']);

        $githubResult = query("SELECT * FROM codespace_github_repos WHERE codespace_id='$codespaceID'");
        if ($githubRow = fetch_assoc($githubResult)) {
            query("INSERT INTO codespace_github_repos (codespace_id, repo_id, repo_name, repo_full_name, user_id) VALUES ('$newCodespaceID', '" . escape_string($githubRow['repo_id']) . "', '" . escape_string($githubRow['repo_name']) . "', '" . escape_string($githubRow['repo_full_name']) . "', '$userID')");
        }

        $vercelResult = query("SELECT * FROM codespace_vercel_projects WHERE codespace_id='$codespaceID'");
        if ($vercelRow = fetch_assoc($vercelResult)) {
            query("INSERT INTO codespace_vercel_projects (codespace_id, vercel_project_id, vercel_project_name, user_id) VALUES ('$newCodespaceID', '" . escape_string($vercelRow['vercel_project_id']) . "', '" . escape_string($vercelRow['vercel_project_name']) . "', '$userID')");
        }

        if (isset($_POST['moveCodespace']) && $_POST['moveCodespace'] === 'true') {

            $domainResult = query("SELECT * FROM codespace_domains WHERE codespace_id='$codespaceID'");
            if ($domainRow = fetch_assoc($domainResult)) {

                if ($vercelRow) {
                    removeDomainFromVercel($domainRow['domain'], $codespaceID, $userID);
                }

                removeDomainFromCloudflare($domainRow['domain']);
            }

            query("DELETE FROM codespace_github_repos WHERE codespace_id='$codespaceID'");
            query("DELETE FROM codespace_vercel_projects WHERE codespace_id='$codespaceID'");
            query("DELETE FROM codespace_domains WHERE codespace_id='$codespaceID'");

            query("DELETE FROM project_codespaces WHERE id='$codespaceID'");

            deleteDirectory($sourceDir);

            $message = "Codespace moved successfully";
        } else {
            $message = "Codespace copied successfully";
        }

        echo jsonResponse([
            'message' => $message,
            'newCodespace' => [
                'id' => $newCodespaceID,
                'name' => $newName,
                'slug' => $targetSlug,
                'project_id' => $targetProjectID
            ]
        ]);

    } catch (Exception $e) {

        if (isset($targetDir) && is_dir($targetDir)) {
            deleteDirectory($targetDir);
        }
        if (isset($newCodespaceID)) {
            query("DELETE FROM codespace_github_repos WHERE codespace_id='$newCodespaceID'");
            query("DELETE FROM codespace_vercel_projects WHERE codespace_id='$newCodespaceID'");
            query("DELETE FROM codespace_domains WHERE codespace_id='$newCodespaceID'");
            query("DELETE FROM project_codespaces WHERE id='$newCodespaceID'");
        }

        echo jsonResponse("Transfer failed: " . $e->getMessage(), false);
    }
} elseif (isset($_POST['getUserProjects'])) {

    $projects = query("SELECT p.projectID, p.name, p.link, p.icon
                      FROM projects p
                      JOIN control_center_user_projects cup ON p.projectID = cup.projectID
                      WHERE cup.userID = '$userID'
                      ORDER BY p.name ASC");

    $result = [];
    foreach ($projects as $project) {
        $result[] = [
            'id' => $project['projectID'],
            'name' => $project['name'],
            'link' => $project['link'],
            'icon' => $project['icon'] ?? 'folder-outline'
        ];
    }

    echo jsonResponse(['projects' => $result]);
} else {
    echo jsonResponse("Invalid request", false);
}

function deleteDirectory($dir)
{
    if (!is_dir($dir)) return false;

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }

    return rmdir($dir);
}

function copyCodespaceDirectory($sourceDir, $targetDir)
{
    if (!is_dir($sourceDir)) {
        return false;
    }

    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            return false;
        }
    }

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relativePath = substr($sourcePath, strlen($sourceDir) + 1);
            $targetPath = $targetDir . '/' . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0777, true);
                }
            } else {
                $targetDirPath = dirname($targetPath);
                if (!is_dir($targetDirPath)) {
                    mkdir($targetDirPath, 0777, true);
                }
                copy($sourcePath, $targetPath);
            }
        }

        return true;
    } catch (Exception $e) {
        error_log("Error copying codespace directory: " . $e->getMessage());
        return false;
    }
}

function getTemplateDescription($templateDir)
{
    $descriptions = [
        'vanilla-js' => 'Basic HTML, CSS and JavaScript setup',
        'react' => 'React application with Vite build tool',
        'vue' => 'Vue.js application with Vite build tool',
        'node' => 'Node.js server with Express framework',
        'angular' => 'Angular application with TypeScript',
        'svelte' => 'Svelte application with modern tooling',
        'next' => 'Next.js React framework',
        'nuxt' => 'Nuxt.js Vue framework'
    ];

    return $descriptions[$templateDir] ?? 'Custom development environment';
}

function getTemplateIcon($templateDir)
{
    $icons = [
        'vanilla-js' => 'logo-javascript',
        'react' => 'logo-react',
        'vue' => 'logo-vue',
        'node' => 'logo-nodejs',
        'angular' => 'logo-angular',
        'svelte' => 'logo-web-component',
        'next' => 'logo-react',
        'nuxt' => 'logo-vue'
    ];

    return $icons[$templateDir] ?? 'code-outline';
}

function getVercelFrameworkPreset($template)
{
    $frameworks = [
        'vanilla-js' => null,
        'react' => 'vite',
        'vue' => 'vite',
        'node' => null,
        'next' => 'nextjs',
        'nuxt' => 'nuxtjs',
        'angular' => 'angular',
        'svelte' => 'svelte'
    ];

    return $frameworks[$template] ?? null;
}

function getVercelBuildSettings($template)
{
    $settings = [
        'vanilla-js' => [
            'buildCommand' => null,
            'devCommand' => null,
            'installCommand' => null,
            'outputDirectory' => null
        ],
        'react' => [
            'buildCommand' => 'npm run build',
            'devCommand' => 'npm run dev',
            'installCommand' => 'npm install',
            'outputDirectory' => 'dist'
        ],
        'vue' => [
            'buildCommand' => 'npm run build',
            'devCommand' => 'npm run dev',
            'installCommand' => 'npm install',
            'outputDirectory' => 'dist'
        ],
        'node' => [
            'buildCommand' => null,
            'devCommand' => 'npm run dev',
            'installCommand' => 'npm install',
            'outputDirectory' => null
        ],
        'next' => [
            'buildCommand' => null,
            'devCommand' => null,
            'installCommand' => null,
            'outputDirectory' => null
        ],
        'nuxt' => [
            'buildCommand' => null,
            'devCommand' => null,
            'installCommand' => null,
            'outputDirectory' => null
        ]
    ];

    return $settings[$template] ?? $settings['vanilla-js'];
}

function createCodespaceGithubRepo($codespaceId, $name, $userID)
{
    return true;
}

function createCodespaceVercelProject($codespaceId, $name, $userID)
{

    $repoResult = query("SELECT * FROM codespace_github_repos WHERE codespace_id='$codespaceId' LIMIT 1");
    if (!($repoRow = fetch_assoc($repoResult))) {
        return false;
    }

    $repo_full_name = $repoRow['repo_full_name'];
    $repo_id = $repoRow['repo_id'];

    $tokenResult = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID='" . escape_string($userID) . "' LIMIT 1");
    if (!($tokenRow = fetch_assoc($tokenResult))) {
        return false;
    }

    $codespaceResult = query("SELECT template FROM project_codespaces WHERE id='$codespaceId' LIMIT 1");
    $template = 'vanilla-js';
    if ($codespaceRow = fetch_assoc($codespaceResult)) {
        $template = $codespaceRow['template'] ?? 'vanilla-js';
    }

    $vercel_token = $tokenRow['vercel_token'];
    $vercelApiUrl = 'https://api.vercel.com/v9/projects';

    $framework = getVercelFrameworkPreset($template);
    $buildSettings = getVercelBuildSettings($template);

    $vercelData = [
        'name' => strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '-', $name)),
        'gitRepository' => [
            'type' => 'github',
            'repo' => $repo_full_name,
            'repoId' => (string)$repo_id
        ]
    ];

    if ($framework) {
        $vercelData['framework'] = $framework;
    }

    if ($buildSettings['buildCommand']) {
        $vercelData['buildCommand'] = $buildSettings['buildCommand'];
    }
    if ($buildSettings['devCommand']) {
        $vercelData['devCommand'] = $buildSettings['devCommand'];
    }
    if ($buildSettings['installCommand']) {
        $vercelData['installCommand'] = $buildSettings['installCommand'];
    }
    if ($buildSettings['outputDirectory']) {
        $vercelData['outputDirectory'] = $buildSettings['outputDirectory'];
    }

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer $vercel_token\r\nUser-Agent: ControlCenter\r\nAccept: application/json\r\nContent-Type: application/json\r\n",
            'content' => json_encode($vercelData)
        ]
    ];

    $context = stream_context_create($opts);
    $response = @file_get_contents($vercelApiUrl, false, $context);
    $data = $response ? json_decode($response, true) : null;

    if ($response && isset($data['id'])) {
        query("INSERT INTO codespace_vercel_projects (codespace_id, vercel_project_id, vercel_project_name, user_id) VALUES ('$codespaceId', '" . escape_string($data['id']) . "', '" . escape_string($data['name']) . "', '$userID')");

        return $data;
    }

    return false;
}

function createInitialCommitAndPush($codespaceId, $repoFullName, $githubToken, $userID)
{
    return true;
}

function removeDomainFromVercel($domain, $codespaceID, $userID)
{
    try {

        $tokenResult = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID='" . escape_string($userID) . "' LIMIT 1");
        if (!($tokenRow = fetch_assoc($tokenResult))) {
            error_log("No Vercel token found for user $userID");
            return false;
        }

        $vercel_token = $tokenRow['vercel_token'];

        $vercelResult = query("SELECT vercel_project_id FROM codespace_vercel_projects WHERE codespace_id='$codespaceID' LIMIT 1");
        if (!($vercelRow = fetch_assoc($vercelResult))) {
            error_log("No Vercel project found for codespace $codespaceID");
            return false;
        }

        $projectId = $vercelRow['vercel_project_id'];

        $deleteDomainUrl = "https://api.vercel.com/v9/projects/$projectId/domains/$domain";
        $deleteOpts = [
            'http' => [
                'method' => 'DELETE',
                'header' => "Authorization: Bearer $vercel_token\r\nUser-Agent: ControlCenter\r\nAccept: application/json\r\n"
            ]
        ];

        $deleteContext = stream_context_create($deleteOpts);
        $deleteResult = @file_get_contents($deleteDomainUrl, false, $deleteContext);

        $http_response_header = $http_response_header ?? [];
        $status = 0;
        foreach ($http_response_header as $header) {
            if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $header, $m)) {
                $status = (int)$m[1];
                break;
            }
        }

        if ($status === 200 || $status === 204) {
            error_log("Successfully removed domain $domain from Vercel project $projectId");
            return true;
        } else {
            error_log("Failed to remove domain $domain from Vercel project $projectId (HTTP $status)");
            return false;
        }

    } catch (Exception $e) {
        error_log("Error removing domain from Vercel: " . $e->getMessage());
        return false;
    }
}

function removeDomainFromCloudflare($domain)
{
    $result = cloudflare_deleteRecordByDomain($domain, 'CNAME');

    if ($result['success']) {
        error_log("Successfully removed domain $domain from Cloudflare");
        return true;
    } else {
        error_log("Failed to remove domain $domain from Cloudflare: " . ($result['message'] ?? 'Unknown error'));
        return false;
    }
}
