<?php
require_once 'config.php';
require_once 'head.php';
require_once __DIR__ . '/helpers/cloudflare.php';

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

function updateVercelProjectFramework($vercel_project_id, $template, $user_id)
{
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $codespace_id = (int) ($_POST['codespace_id'] ?? 0);
    $user_id = escape_string($_POST['user_id'] ?? '');

    $codespace = null;
    if ($codespace_id > 0) {
        $codespaceResult = query("SELECT * FROM project_codespaces WHERE id='$codespace_id'");
        if ($codespace = fetch_assoc($codespaceResult)) {
            if (!checkUserProjectPermission($user_id, $codespace['project_id'])) {
                echo json_encode(['error' => 'No permission for this codespace']);
                exit;
            }
        } else {
            echo json_encode(['error' => 'Codespace not found']);
            exit;
        }
    }

    if ($action === 'connect_github' && $codespace_id && $user_id && isset($_POST['repo'])) {
        $repo = json_decode($_POST['repo'], true);
        if (!$repo || !isset($repo['id'])) {
            echo json_encode(['error' => 'Invalid repo data']);
            exit;
        }

        $repo_id = escape_string($repo['id']);
        $repo_name = escape_string($repo['name']);
        $repo_full_name = escape_string($repo['full_name']);

        $exists = query("SELECT id FROM codespace_github_repos WHERE codespace_id='$codespace_id' LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            echo json_encode(['error' => 'GitHub repo already connected to this codespace']);
            exit;
        }

        $insert = query("INSERT INTO codespace_github_repos (codespace_id, repo_id, repo_name, repo_full_name, user_id) VALUES ('$codespace_id', '$repo_id', '$repo_name', '$repo_full_name', '$user_id')");

        if ($insert) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to connect GitHub repo']);
        }
        exit;
    }

    if ($action === 'create_and_connect_github' && $codespace_id && $user_id) {
        echo json_encode(['success' => true, 'message' => 'Codespace uses the built-in git server']);
        exit;
    }

    if ($action === 'disconnect_github' && $codespace_id) {
        $delete = query("DELETE FROM codespace_github_repos WHERE codespace_id='$codespace_id'");

        if ($delete) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to disconnect GitHub repo']);
        }
        exit;
    }

    if ($action === 'get_github' && $codespace_id) {
        $result = query("SELECT * FROM codespace_github_repos WHERE codespace_id='$codespace_id' LIMIT 1");

        if ($row = fetch_assoc($result)) {
            echo json_encode(['repo' => $row]);
        } else {
            echo json_encode(['repo' => null]);
        }
        exit;
    }

    if ($action === 'connect_vercel' && $codespace_id && $user_id) {
        echo json_encode(['success' => true, 'message' => 'Codespace uses the built-in deploy system']);
        exit;
    }

    if ($action === 'create_and_connect_vercel' && $codespace_id && $user_id) {
        echo json_encode(['success' => true, 'message' => 'Codespace uses the built-in deploy system']);
        exit;
    }

    if ($action === 'disconnect_vercel' && $codespace_id) {
        $delete = query("DELETE FROM codespace_vercel_projects WHERE codespace_id='$codespace_id'");

        if ($delete) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to disconnect Vercel project']);
        }
        exit;
    }

    if ($action === 'get_vercel' && $codespace_id) {
        $result = query("SELECT * FROM codespace_vercel_projects WHERE codespace_id='$codespace_id' LIMIT 1");

        if ($row = fetch_assoc($result)) {
            echo json_encode(['vercel_project_id' => $row['vercel_project_id'], 'vercel_project_name' => $row['vercel_project_name']]);
        } else {
            echo json_encode(['vercel_project_id' => null, 'vercel_project_name' => null]);
        }
        exit;
    }

    if ($action === 'get_all_connections' && $codespace_id) {
        $github = null;
        $vercel = null;
        $domain = null;

        $githubResult = query("SELECT * FROM codespace_github_repos WHERE codespace_id='$codespace_id' LIMIT 1");
        if ($githubRow = fetch_assoc($githubResult)) {
            $github = $githubRow;
        }

        $vercelResult = query("SELECT * FROM codespace_vercel_projects WHERE codespace_id='$codespace_id' LIMIT 1");
        if ($vercelRow = fetch_assoc($vercelResult)) {
            $vercel = $vercelRow;
        }

        $domainResult = query("SELECT * FROM codespace_domains WHERE codespace_id='$codespace_id' LIMIT 1");
        if ($domainRow = fetch_assoc($domainResult)) {
            $domain = $domainRow;
        }

        echo json_encode([
            'github' => $github,
            'vercel' => $vercel,
            'domain' => $domain
        ]);
        exit;
    }

    if ($action === 'connect_domain' && $codespace_id && $user_id) {
        $subdomain = strtolower(trim($_POST['subdomain'] ?? ''));
        $is_main = isset($_POST['is_main']) && $_POST['is_main'] === 'true';

        if (!$is_main && (!$subdomain || !preg_match('/^[a-z0-9-]+$/', $subdomain))) {
            echo json_encode(['error' => 'Ungültiges Subdomain-Format. Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.']);
            exit;
        }

        $projectResult = query("SELECT project_id FROM project_codespaces WHERE id='$codespace_id'");
        if (!$projectRow = fetch_assoc($projectResult)) {
            echo json_encode(['error' => 'Codespace nicht gefunden']);
            exit;
        }

        $project_id = $projectRow['project_id'];
        $projectInfoResult = query("SELECT link FROM projects WHERE projectID='$project_id'");
        if (!$projectInfoRow = fetch_assoc($projectInfoResult)) {
            echo json_encode(['error' => 'Projekt nicht gefunden']);
            exit;
        }

        $project_link = $projectInfoRow['link'];

        $projectDomainResult = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link' LIMIT 1");
        if (!$projectDomainRow = fetch_assoc($projectDomainResult)) {
            echo json_encode(['error' => 'Projekt hat keine Domain konfiguriert. Bitte zuerst in den Projekt-Einstellungen eine Domain einrichten.']);
            exit;
        }

        $base_domain = $projectDomainRow['domain'];

        if ($is_main) {
            $full_domain = $base_domain;

            $existingMainResult = query("
                SELECT cd.id FROM codespace_domains cd
                JOIN project_codespaces pc ON cd.codespace_id = pc.id
                WHERE pc.project_id = '$project_id' AND cd.is_main = 1 AND cd.codespace_id != '$codespace_id'
            ");
            if (mysqli_num_rows($existingMainResult) > 0) {
                echo json_encode(['error' => 'Ein anderer Codespace verwendet bereits die Haupt-Domain. Bitte zuerst die Haupt-Domain des anderen Codespaces entfernen.']);
                exit;
            }

            $webBuilderCheck = query("
                SELECT id FROM web_builder_domains
                WHERE projectID = '$project_link' AND domain = '$base_domain'
            ");
            if (mysqli_num_rows($webBuilderCheck) > 0) {
                echo json_encode(['error' => 'Die Main Domain wird bereits vom Web Builder verwendet. Bitte zuerst dort die Main Domain entfernen.']);
                exit;
            }
        } else {
            $full_domain = $subdomain . '.' . $base_domain;
        }

        $exists = query("SELECT id FROM codespace_domains WHERE domain='$full_domain' LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            echo json_encode(['error' => 'Domain bereits vergeben.']);
            exit;
        }

        query("DELETE FROM codespace_domains WHERE codespace_id='$codespace_id'");

        $insert = query("INSERT INTO codespace_domains (codespace_id, domain, is_main, user_id) VALUES ('$codespace_id', '$full_domain', " . ($is_main ? 1 : 0) . ", '$user_id')");

        if (!$insert) {
            echo json_encode(['error' => 'Fehler beim Speichern der Domain']);
            exit;
        }

        $cnameTarget = 'apps.fringelo.com';
        $cloudflareResult = cloudflare_createCNAMERecord($full_domain, $cnameTarget, false);

        if (!$cloudflareResult['success']) {
            query("DELETE FROM codespace_domains WHERE codespace_id='$codespace_id'");
            echo json_encode(['error' => 'Cloudflare: ' . ($cloudflareResult['message'] ?? 'Cloudflare API Fehler')]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'domain' => $full_domain,
            'is_main' => $is_main,
            'cloudflare' => $cloudflareResult
        ]);
        exit;
    }

    if ($action === 'disconnect_domain' && $codespace_id) {
        $delete = query("DELETE FROM codespace_domains WHERE codespace_id='$codespace_id'");

        if ($delete) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to disconnect domain']);
        }
        exit;
    }

    if ($action === 'get_domain' && $codespace_id) {
        $result = query("SELECT * FROM codespace_domains WHERE codespace_id='$codespace_id' LIMIT 1");

        if ($row = fetch_assoc($result)) {
            echo json_encode(['domain' => $row]);
        } else {
            echo json_encode(['domain' => null]);
        }
        exit;
    }

    if ($action === 'get_project_domain_info' && $codespace_id) {

        $projectResult = query("SELECT project_id FROM project_codespaces WHERE id='$codespace_id'");
        if (!$projectRow = fetch_assoc($projectResult)) {
            echo json_encode(['error' => 'Codespace nicht gefunden']);
            exit;
        }

        $project_id = $projectRow['project_id'];
        $projectInfoResult = query("SELECT link FROM projects WHERE projectID='$project_id'");
        if (!$projectInfoRow = fetch_assoc($projectInfoResult)) {
            echo json_encode(['error' => 'Projekt nicht gefunden']);
            exit;
        }

        $project_link = $projectInfoRow['link'];

        $projectDomainResult = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link' LIMIT 1");
        if ($projectDomainRow = fetch_assoc($projectDomainResult)) {
            $base_domain = $projectDomainRow['domain'];

            $existingMainResult = query("
                SELECT cd.id, pc.name as codespace_name FROM codespace_domains cd
                JOIN project_codespaces pc ON cd.codespace_id = pc.id
                WHERE pc.project_id = '$project_id' AND cd.is_main = 1
            ");

            $main_domain_taken = false;
            $main_domain_codespace = null;
            if ($mainRow = fetch_assoc($existingMainResult)) {
                $main_domain_taken = true;
                $main_domain_codespace = $mainRow['codespace_name'];
            }

            echo json_encode([
                'base_domain' => $base_domain,
                'main_domain_taken' => $main_domain_taken,
                'main_domain_codespace' => $main_domain_codespace
            ]);
        } else {
            echo json_encode(['error' => 'Projekt hat keine Domain konfiguriert']);
        }
        exit;
    }
}

echo json_encode(['error' => 'Invalid request']);

function createInitialCommitAndPush($codespaceId, $repoFullName, $githubToken, $userID)
{
    return true;
}
