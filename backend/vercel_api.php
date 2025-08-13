<?php
require_once 'head.php';
require_once 'vercel_helper.php';

try {
    $project = $_GET['project'] ?? $_POST['project'] ?? null;
    $codespace = $_GET['codespace'] ?? $_POST['codespace'] ?? 'main';

    if (!$project) {
        throw new Exception('Project parameter is required');
    }

    $vercelHelper = new VercelHelper($userID);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'deployments':
                    $deployments = $vercelHelper->getDeployments($project, $codespace);
                    echo json_encode(['success' => true, 'deployments' => $deployments]);
                    break;

                case 'projects':
                    $projects = $vercelHelper->getProjects();
                    echo json_encode(['success' => true, 'projects' => $projects]);
                    break;

                case 'project':
                    $projectData = $vercelHelper->getProject($project, $codespace);
                    echo json_encode(['success' => true, 'project' => $projectData]);
                    break;

                case 'env':
                    $envVars = $vercelHelper->getEnvironmentVariablesWithValues($project, $codespace);
                    echo json_encode(['success' => true, 'envVars' => $envVars]);
                    break;

                default:
                    throw new Exception('Unknown action');
            }
        } else {
            // Default: return recent deployments
            $deployments = $vercelHelper->getDeployments($project, $codespace);
            echo json_encode(['success' => true, 'deployments' => $deployments]);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['action'])) {
            switch ($input['action']) {
                case 'deploy':
                    $files = $input['files'] ?? [];
                    $gitSource = $input['gitSource'] ?? null;

                    $deployment = $vercelHelper->createDeployment($project, $codespace, $files, $gitSource);
                    echo json_encode(['success' => true, 'deployment' => $deployment]);
                    break;

                case 'status':
                    $deploymentId = $input['deploymentId'] ?? '';
                    if (empty($deploymentId)) {
                        throw new Exception('Deployment ID is required');
                    }

                    $status = $vercelHelper->getDeploymentStatus($deploymentId);
                    echo json_encode(['success' => true, 'status' => $status]);
                    break;

                case 'create_env':
                    $key = $input['key'] ?? '';
                    $value = $input['value'] ?? '';
                    $target = $input['target'] ?? ['production', 'preview', 'development'];

                    if (empty($key) || empty($value)) {
                        throw new Exception('Key and value are required');
                    }

                    $result = $vercelHelper->createEnvironmentVariable($project, $codespace, $key, $value, $target);
                    echo json_encode(['success' => true, 'result' => $result]);
                    break;

                case 'update_env':
                    $envId = $input['envId'] ?? '';
                    $key = $input['key'] ?? '';
                    $value = $input['value'] ?? '';
                    $target = $input['target'] ?? ['production', 'preview', 'development'];

                    if (empty($envId) || empty($key) || empty($value)) {
                        throw new Exception('Environment ID, key and value are required');
                    }

                    $result = $vercelHelper->updateEnvironmentVariable($project, $codespace, $envId, $key, $value, $target);
                    echo json_encode(['success' => true, 'result' => $result]);
                    break;

                case 'delete_env':
                    $envId = $input['envId'] ?? '';

                    if (empty($envId)) {
                        throw new Exception('Environment ID is required');
                    }

                    $result = $vercelHelper->deleteEnvironmentVariable($project, $codespace, $envId);
                    echo json_encode(['success' => true, 'result' => $result]);
                    break;

                default:
                    throw new Exception('Unknown action');
            }
        } else {
            throw new Exception('Action parameter is required');
        }
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
