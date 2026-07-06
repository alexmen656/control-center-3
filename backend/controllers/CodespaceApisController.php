<?php

require_once __DIR__ . '/../apis_helper.php';
require_once __DIR__ . '/../api_keys.php';
require_once __DIR__ . '/../deploy_helper.php';

class CodespaceApisController
{
    public function list(Request $request, Response $response): void
    {
        $ctx = $this->resolveCodespace($request, $response);
        if (!$ctx) return;
        [$projectID, $codespaceId] = $ctx;

        $apis = query("
            SELECT
                pas.id as subscription_id,
                pas.api_id,
                ca.name,
                ca.slug,
                ca.description,
                ca.icon,
                ca.category,
                ca.endpoint_base,
                ca.documentation_url,
                caa.id as activation_id,
                caa.is_active,
                caa.api_key as codespace_api_key,
                pas.api_key as project_api_key,
                pas.rate_limit
            FROM project_api_subscriptions pas
            JOIN cms_apis ca ON pas.api_id = ca.id
            LEFT JOIN codespace_api_activations caa ON caa.subscription_id = pas.id AND caa.codespace_id = '$codespaceId'
            WHERE pas.projectID='$projectID' AND pas.is_enabled=1
            ORDER BY ca.category, ca.name ASC
        ");

        $json = [];
        foreach ($apis as $api) {
            $json[] = formatCodespaceApiData($api);
        }
        $response->json($json);
    }

    public function details(Request $request, Response $response): void
    {
        $ctx = $this->resolveCodespace($request, $response);
        if (!$ctx) return;
        [$projectID, $codespaceId] = $ctx;

        $apiSlug = escape_string($request->input('api_slug', ''));
        if (!$apiSlug) {
            $response->error('api_slug is required', 400);
            return;
        }

        $api_query = query("
            SELECT
                ca.*,
                pas.id as subscription_id,
                pas.api_key as project_api_key,
                pas.rate_limit,
                pas.usage_count,
                pas.last_used,
                pas.is_enabled,
                caa.id as activation_id,
                caa.is_active as codespace_active,
                caa.api_key as codespace_api_key
            FROM cms_apis ca
            JOIN project_api_subscriptions pas ON ca.id = pas.api_id AND pas.projectID='$projectID'
            LEFT JOIN codespace_api_activations caa ON caa.subscription_id = pas.id AND caa.codespace_id = '$codespaceId'
            WHERE ca.slug='$apiSlug'
        ");

        if (mysqli_num_rows($api_query) == 0) {
            $response->error('API not found or not subscribed', 404);
            return;
        }

        $api = fetch_assoc($api_query);

        $endpoints = query("SELECT * FROM cms_api_endpoints WHERE api_id='" . $api['id'] . "' ORDER BY endpoint ASC");
        $api['endpoints'] = [];
        foreach ($endpoints as $endpoint) {
            $api['endpoints'][] = formatEndpointData($endpoint);
        }

        if ($api['activation_id']) {
            $api['usage_stats'] = calculateCodespaceUsageStats($api['activation_id']);

            $activity_query = query("
                SELECT method, path, status_code, response_time, timestamp
                FROM cms_api_usage_logs
                WHERE activation_id='" . $api['activation_id'] . "'
                ORDER BY timestamp DESC
                LIMIT 10
            ");

            $api['recent_activity'] = [];
            foreach ($activity_query as $activity) {
                $api['recent_activity'][] = [
                    'method' => $activity['method'],
                    'path' => $activity['path'],
                    'status' => $activity['status_code'],
                    'response_time' => $activity['response_time'],
                    'timestamp' => $activity['timestamp']
                ];
            }
        } else {
            $api['usage_stats'] = [
                'totalRequests' => 0,
                'avgResponseTime' => 0,
                'successRate' => 0,
                'requestsToday' => 0
            ];
            $api['recent_activity'] = [];
        }

        $response->json($api);
    }

    public function activate(Request $request, Response $response): void
    {
        $ctx = $this->resolveCodespace($request, $response);
        if (!$ctx) return;
        [$projectID, $codespaceId] = $ctx;

        $subscriptionId = escape_string($request->input('subscription_id', ''));
        if (!$subscriptionId) {
            $response->error('subscription_id is required', 400);
            return;
        }

        $subscriptionResult = query("SELECT api_id FROM project_api_subscriptions WHERE id='$subscriptionId' AND projectID='$projectID' LIMIT 1");
        if (mysqli_num_rows($subscriptionResult) === 0) {
            $response->error('Invalid subscription', 400);
            return;
        }

        $existingResult = query("SELECT id FROM codespace_api_activations WHERE codespace_id='$codespaceId' AND subscription_id='$subscriptionId' LIMIT 1");
        if (mysqli_num_rows($existingResult) > 0) {
            $updateResult = query("UPDATE codespace_api_activations SET is_active=1 WHERE codespace_id='$codespaceId' AND subscription_id='$subscriptionId'");
        } else {
            $updateResult = query("INSERT INTO codespace_api_activations (codespace_id, subscription_id, is_active) VALUES ('$codespaceId', '$subscriptionId', 1)");
        }

        if (!$updateResult) {
            $response->error('Failed to activate API', 500);
            return;
        }

        $sub = fetch_assoc(query("
            SELECT pas.*, ca.slug
            FROM project_api_subscriptions pas
            JOIN cms_apis ca ON pas.api_id = ca.id
            WHERE pas.id='$subscriptionId'
        "));

        $envName = api_env_name($sub['slug']);
        $key = api_decrypt_key($sub);
        deploy_set_env_var($codespaceId, $envName, $key, 'both');
        deploy_set_env_var($codespaceId, 'FRINGELO_API_URL', 'https://gw.fringelo.com', 'both');

        $response->json(['success' => true, 'message' => 'API activated; key injected as ' . $envName . ' on next deploy', 'env_var' => $envName]);
    }

    public function deactivate(Request $request, Response $response): void
    {
        $ctx = $this->resolveCodespace($request, $response);
        if (!$ctx) return;
        [$projectID, $codespaceId] = $ctx;

        $subscriptionId = escape_string($request->input('subscription_id', ''));
        if (!$subscriptionId) {
            $response->error('subscription_id is required', 400);
            return;
        }

        $updateResult = query("UPDATE codespace_api_activations SET is_active=0 WHERE codespace_id='$codespaceId' AND subscription_id='$subscriptionId'");

        if (!$updateResult) {
            $response->error('Failed to deactivate API', 500);
            return;
        }

        $api = fetch_assoc(query("
            SELECT ca.slug
            FROM project_api_subscriptions pas
            JOIN cms_apis ca ON pas.api_id = ca.id
            WHERE pas.id='$subscriptionId'
        "));
        deploy_delete_env_var($codespaceId, api_env_name($api['slug']));

        $response->json(['success' => true, 'message' => 'API deactivated; key removed on next deploy']);
    }

    public function sync(Request $request, Response $response): void
    {
        $ctx = $this->resolveCodespace($request, $response);
        if (!$ctx) return;
        [$projectID, $codespaceId] = $ctx;

        $active = query("
            SELECT ca.slug, pas.*
            FROM codespace_api_activations caa
            JOIN project_api_subscriptions pas ON caa.subscription_id = pas.id
            JOIN cms_apis ca ON pas.api_id = ca.id
            WHERE caa.codespace_id='$codespaceId' AND caa.is_active=1
        ");
        $synced = [];
        foreach ($active as $sub) {
            $envName = api_env_name($sub['slug']);
            deploy_set_env_var($codespaceId, $envName, api_decrypt_key($sub), 'both');
            $synced[] = $envName;
        }
        deploy_set_env_var($codespaceId, 'FRINGELO_API_URL', 'https://gw.fringelo.com', 'both');

        $response->json(['success' => true, 'synced' => $synced, 'message' => count($synced) . ' API keys injected as env vars']);
    }

    public function publish(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        $codespaceSlug = escape_string($request->input('codespace', ''));

        try {
            $projectID = getProjectID($projectName);
        } catch (Exception $e) {
            $response->error('Project not found', 404);
            return;
        }

        if (!checkUserProjectPermission($request->userID, $projectID)) {
            $response->error('No permission for this project', 403);
            return;
        }

        $cs = fetch_assoc(query("SELECT id, name FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1"));
        if (!$cs) {
            $response->error('Codespace not found', 404);
            return;
        }
        $codespaceId = (int) $cs['id'];

        $name = escape_string($request->input('name', $cs['name']));
        $description = escape_string($request->input('description', ''));
        $rateLimit = (int) $request->input('rate_limit', 60);
        $slug = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $request->input('slug', $projectName . '-' . $codespaceSlug))), '-');

        $exists = fetch_assoc(query("SELECT id, codespace_id FROM cms_apis WHERE slug='$slug' LIMIT 1"));
        if ($exists && (int) $exists['codespace_id'] !== $codespaceId) {
            $response->error('API slug already taken, choose another', 409);
            return;
        }

        query("INSERT INTO cms_apis (name, slug, description, category, version, endpoint_base, auth_required, rate_limit_default, is_active, source_type, codespace_id)
               VALUES ('$name', '$slug', '$description', 'codespace', 'v1', '/', 1, '$rateLimit', 1, 'codespace', '$codespaceId')
               ON DUPLICATE KEY UPDATE name='$name', description='$description', rate_limit_default='$rateLimit', is_active=1, source_type='codespace', codespace_id='$codespaceId'");

        $response->json(['success' => true, 'slug' => $slug, 'gateway_url' => 'https://gw.fringelo.com/' . $slug]);
    }

    public function unpublish(Request $request, Response $response): void
    {
        $ctx = $this->resolveCodespace($request, $response);
        if (!$ctx) return;
        [$projectID, $codespaceId] = $ctx;

        query("UPDATE cms_apis SET is_active=0 WHERE source_type='codespace' AND codespace_id='$codespaceId'");
        $response->json(['success' => true, 'message' => 'Codespace API unpublished']);
    }

    private function resolveCodespace(Request $request, Response $response): ?array
    {
        $projectName = escape_string($request->input('project', ''));
        $codespaceSlug = escape_string($request->input('codespace', ''));

        if (!$projectName || !$codespaceSlug) {
            $response->error('project and codespace are required', 400);
            return null;
        }

        try {
            $projectID = getProjectID($projectName);
        } catch (Exception $e) {
            $response->error('Project not found', 404);
            return null;
        }

        if (!checkUserProjectPermission($request->userID, $projectID)) {
            $response->error('No permission for this project', 403);
            return null;
        }

        $codespaceResult = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1");
        if (mysqli_num_rows($codespaceResult) === 0) {
            $response->error('Codespace not found', 404);
            return null;
        }

        $codespaceId = fetch_assoc($codespaceResult)['id'];
        return [$projectID, $codespaceId];
    }
}
